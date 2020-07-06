<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Auth;
use DB;
use App\Yachtdetail;
use App\dummy_registration;
use Illuminate\Support\Facades\Hash;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Validator;
use Geocoder;
use Stripe\Error\Card;
use Cartalyst\Stripe\Stripe;
use Exception;
use App\User;
use App\Http\Traits\LocationTrait;
use App\Http\Traits\NotificationTrait;
use App\Jobs\ImportUsers;
use App\Http\Traits\ZapierTrait;
class YachtController extends Controller
{
    use LocationTrait;
    use NotificationTrait;
    use ZapierTrait;
    public $successStatus = 200;

    public function __construct(Request $request) {
        $value = $request->bearerToken();
	    if(!empty($value)) {
	     	$id= (new Parser())->parse($value)->getHeader('jti');
	     	$authid = DB::table('oauth_access_tokens')->where('id', '=', $id)->where('revoked', '=', false)->first()->user_id;
	     	if(!empty($authid) && $authid > 0) {
                $usertype = Auth::where('id', '=', $authid)->where('status' ,'=','active')->first()->usertype;
                if(empty($usertype) || $usertype != 'admin') {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
	     	} else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }
    public function addYacht(Request $request) {
        $validate = Validator::make($request->all(), [
            'firstname' => 'required',
            'email' => 'bail|required|E-mail',
            'password' => 'required',
            'confirm' => 'required|same:password',
            'yachtdetail' => 'required',
            'homeport' => 'required',
            'contact' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            // 'county' => 'required',
            'zipcode' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $auth   = new Auth; 
        $authid = 0;
        $newsletter = (!empty(request('newsletter')) && (request('newsletter') == 'true')) ? '1':'0';
        $auth->email = strtolower(request('email'));
        $auth->password = Hash::make(request('password'));
        $auth->usertype = 'yacht';
        $auth->ipaddress = $this->getIp();
        $auth->status = 'active';
        $auth->stepscompleted = '2';
        $auth->is_activated = 1;
        $auth->addedby =1;
        $auth->newsletter = $newsletter; 
        if($auth->save()) {
            $authid = $auth->id;
            if($authid) {
                $address = request('address');
                $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
            
                $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
                $output = $this->getGeoLocation($location); //Get Location from location Trait
                $longitude = $output['longitude'];
                $latitude = $output['latitude'];
                $yacht = new Yachtdetail;
                $yacht->authid = $authid;
                $yacht->firstname = request('firstname');
                $yacht->lastname = request('lastname');
                $yacht->contact = request('contact');
                $yacht->address = (!empty(request('address'))?request('address'):NULL);
                $yacht->longitude  = $longitude;
                $yacht->latitude   = $latitude;
                $yacht->city = request('city');
                $yacht->state = request('state');
                $yacht->country = request('country');  
                // $yacht->county = request('county');   
                $yacht->zipcode = request('zipcode');
                $yacht->yachtdetail = request('yachtdetail');
                $yacht->homeport = request('homeport');
                $yacht->status = 'active';
                $country_code = request('country_code');
				if($country_code != '') {
					$pos = strpos($country_code, '+');
					if(!$pos){
						$country_code ='+'.$country_code;
					}
				}   
				$yacht->country_code   = $country_code;
                if($yacht->save()) {
                    $yacht = $yacht->id;
                    $emailArr = [];
                    $emailArr['firstname'] = request('firstname');
                    $emailArr['lastname'] = request('lastname');
                    $emailArr['to_email'] = request('email');
                    $emailArr['password'] = request('password');
                    //Send account created email notification
                    $zaiperenv = env('ZAIPER_ENV','local');
					if($zaiperenv == 'live') {
						$zapierData = array();
						$zapierData['type'] 	= 'Yacht Owner';
						$zapierData['id'] 		= $authid;
						$zapierData['email'] 	= request('email');
						$zapierData['firstname']= request('firstname');
						$zapierData['lastname'] = request('lastname');
						$zapierData['contact'] 	= $country_code.request('contact');
						$zapierData['address'] 	= request('address');
						$zapierData['city'] 	= request('city');
						$zapierData['state'] 	= request('state');
						$zapierData['country'] 	= request('country');
						$zapierData['zipcode'] 	= request('zipcode');
						$zapierData['homeport'] = request('homeport');
						$this->sendAccountCreateZapier($zapierData);
					}
                    $status = $this->sendEmailNotification($emailArr,'user_added_by_admin');
                    if($status != 'sent') {
                        return array('status' =>'emailsentfail');
                    }
                    return response()->json(['success' => true], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401);     
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
                return response()->json(['error'=>'networkerror'], 401); 
        } 
    }

    public function getyachtdata() {
        $searchString = request('searchString');
        $page = request('page');
        $reverse = request('reverse');
        $orderBy = request('order');
        $order = ($reverse == 'false')?'ASC':'DESC';
        
        $statefilter = request('state');
        $cityfilter = request('city');
        $zipcodefilter = request('zipcode');

        if($page == 0 || $page == null || !(int)$page) {
            $page = 0;
        }
        $limit = 30;
        $offset = 0;
        if($page > 0) {
            $offset = ($page - 1)*$limit;
        }
        $whereCompany = '';
        if(!empty($searchString)) {
			$searchString = implode("''",explode("'",trim($searchString)));
            $searchString = strtolower($searchString);
            $whereCompany = "LOWER(CONCAT(yachtdetail.firstname, ' ', yachtdetail.lastname)) LIKE '%".$searchString."%'";         
        }
        
        if(!empty($statefilter) && $statefilter != 'All') {
			if($whereCompany == '') {
				$whereCompany = " yachtdetail.state = '".$statefilter."' ";
			} else {
				$whereCompany .= " AND yachtdetail.state = '".$statefilter."' ";
			}
		}
        if(!empty($cityfilter) && $cityfilter != 'All') {
			if($whereCompany == '') {
				$whereCompany = " yachtdetail.city = '".$cityfilter."' ";
			} else {
				$whereCompany .= " AND yachtdetail.city = '".$cityfilter."' ";
			}
		}
        if(!empty($zipcodefilter) && $zipcodefilter != 'All') {
			if($whereCompany == '') {
				$whereCompany = " yachtdetail.zipcode = '".$zipcodefilter."' ";
			} else {
				$whereCompany .= " AND yachtdetail.zipcode = '".$zipcodefilter."' ";
			}
		}

        $yachtlist = array();
        //Get count 
        $query = DB::table('auths')
                    ->Join('yachtdetail', 'auths.id', '=', 'yachtdetail.authid')
                    ->where('auths.usertype', '=', 'yacht')
                    ->where('auths.status', '!=', 'deleted')
                    ->select('auths.email','auths.is_social','auths.stepscompleted','auths.id as userauthid','auths.usertype','auths.status', 'yachtdetail.authid','yachtdetail.firstname', 'yachtdetail.lastname', 'yachtdetail.contact', 'yachtdetail.address', 'yachtdetail.city', 'yachtdetail.state', 'yachtdetail.zipcode', 'yachtdetail.country', 'yachtdetail.country', 'yachtdetail.country', 'yachtdetail.country', 'yachtdetail.country')
                    ->orderBy('auths.created_at', 'DESC');
        if($whereCompany != '') {
            $query = $query->whereRaw($whereCompany);
        }
        $totalRecords = $query->count();
        //Get All records
        $query = DB::table('auths')
                    ->Join('yachtdetail', 'auths.id', '=', 'yachtdetail.authid')
                    ->where('auths.usertype', '=', 'yacht')
                    ->where('auths.status', '!=', 'deleted')
                    ->select('auths.email','auths.is_social','auths.stepscompleted','auths.id as userauthid','auths.usertype','auths.status','yachtdetail.authid' ,'yachtdetail.firstname', 'yachtdetail.lastname', 'yachtdetail.contact', 'yachtdetail.address', 'yachtdetail.city', 'yachtdetail.state', 'yachtdetail.zipcode', 'yachtdetail.country', 'yachtdetail.country', 'yachtdetail.country', 'yachtdetail.country', 'yachtdetail.country');
        
        // Order By filter
        if($orderBy == 'firstname') {
            $query = $query->orderBy('yachtdetail.firstname', $order);
            $query = $query->orderBy('yachtdetail.lastname', $order);
        } else if($orderBy == 'email') {
            $query = $query->orderBy('auths.email', $order);    
        } else if($orderBy == 'contact') {
            $query = $query->orderBy('yachtdetail.contact', $order);
        } else if($orderBy == 'created_at') {
            $query = $query->orderBy('yachtdetail.created_at', $order);
        } else {
            $query = $query->orderBy('auths.created_at', 'DESC');
        }

        //Where condition 
        if($whereCompany != '') {
            $query = $query->whereRaw($whereCompany);
        }
        $yachtlist = $query->get();
         
        if(!empty($yachtlist)) { 
            return response()->json(['success' => true,'yachtlist' => $yachtlist,'totalrecords' => $totalRecords], $this->successStatus);
        } else {
            return response()->json(['success' => false,'yachtlist' => []], $this->successStatus);
        }
    }
    
    public function exportYachtData() {
        $searchString = request('searchString');
        $limit = request('limit');
        $offset = request('offset');
        $reverse = request('reverse');
        $orderBy = request('order');
        $order = ($reverse == 'false')?'ASC':'DESC';
		
        $statefilter = request('state');
        $cityfilter = request('city');
        $zipcodefilter = request('zipcode');
        $whereCompany = '';
        if(!empty($searchString) && $searchString != '') {
            $searchString = strtolower($searchString);
            if($whereCompany == '') {
                $whereCompany = "LOWER(CONCAT(yachtdetail.firstname, ' ', yachtdetail.lastname)) LIKE '%".$searchString."%'";       
            }
        }
        
        if(!empty($statefilter) && $statefilter != 'All' && $statefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " yachtdetail.state = '".$statefilter."' ";
			} else {
				$whereCompany .= " AND yachtdetail.state = '".$statefilter."' ";
			}
		}
        if(!empty($cityfilter) && $cityfilter != 'All'  && $cityfilter != '') {
			if($whereCompany == '') {
				$whereCompany = " yachtdetail.city = '".$cityfilter."' ";
			} else {
				$whereCompany .= " AND yachtdetail.city = '".$cityfilter."' ";
			}
		}
        if(!empty($zipcodefilter) && $zipcodefilter != 'All'  && $zipcodefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " yachtdetail.zipcode = '".$zipcodefilter."' ";
			} else {
				$whereCompany .= " AND yachtdetail.zipcode = '".$zipcodefilter."' ";
			}
		}
        $yachtlist = array();
        $query = DB::table('auths')
                    ->Join('yachtdetail', 'auths.id', '=', 'yachtdetail.authid')
                    ->where('auths.usertype', '=', 'yacht')
                    ->where('auths.status', '!=', 'deleted')
                    ->select('yachtdetail.firstname','yachtdetail.lastname','auths.email',DB::Raw("COALESCE(yachtdetail.address,'-') as address"),'yachtdetail.city','yachtdetail.state','yachtdetail.zipcode','yachtdetail.contact','yachtdetail.yachtdetail','yachtdetail.homeport');
        // Order By filter
        if($orderBy == 'firstname') {
            $query = $query->orderBy('yachtdetail.firstname', $order);
            $query = $query->orderBy('yachtdetail.lastname', $order);
        } else if($orderBy == 'email') {
            $query = $query->orderBy('auths.email', $order);    
        } else if($orderBy == 'contact') {
            $query = $query->orderBy('yachtdetail.contact', $order);
        } else {
            $query = $query->orderBy('auths.created_at', 'DESC');
        }

        //Where condition
        if($whereCompany != '') {
           $query =  $query->whereRaw($whereCompany);
        }
        $yachtlist = $query->skip($offset)
                    ->take($limit)
                    ->get();
        if(!empty($yachtlist)) { 
            return response()->json(['success' => true,'yachtlist' => $yachtlist], $this->successStatus);
        } else {
            return response()->json(['success' => false,'yachtlist' => []], $this->successStatus);
        }
    }


    public function editYacht(Request $request) {
        $validate = Validator::make($request->all(), [
            'authid' => 'required',
            'firstname' => 'required',
            // 'email' => 'bail|required|E-mail',
            'yachtdetail' => 'required',
            'homeport' => 'required',
            'contact' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            // 'county' => 'required',
            'zipcode' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $authid = request('authid');
        $auth = Auth::find($authid);
        // $auth->email = strtolower(request('email'));
        $auth->ipaddress = $this->getIp();
        $newsletter = (!empty(request('newsletter')) && (request('newsletter') == 'true')) ? '1':'0';
        $auth['newsletter'] =$newsletter;
        if($auth->save()) {
            $authid = $auth->id;
            if($authid) {
                $address = request('address');
                $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
                $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
                $output = $this->getGeoLocation($location); //Get Location from location Trait
                $longitude = $output['longitude'];
                $latitude = $output['latitude'];
                $yachtArr['firstname'] = request('firstname');
                $yachtArr['lastname'] = request('lastname');
                $yachtArr['contact'] = request('contact');
                $yachtArr['address'] = (!empty(request('address')) && request('address') != 'null'?request('address'):NULL);
                $yachtArr['longitude']  = $longitude;
                $yachtArr['latitude']   = $latitude;
                $yachtArr['city'] = request('city');
                $yachtArr['state'] = request('state');
                $yachtArr['country'] = request('country');  
                // $yachtArr['county'] = request('county');  
                $yachtArr['zipcode'] = request('zipcode');
                $yachtArr['yachtdetail'] = request('yachtdetail');
                $yachtArr['homeport'] = request('homeport');
                $country_code = request('country_code');
                if($country_code != '') {
                    $pos = strpos($country_code, '+');
                    if(!$pos){
                        $country_code ='+'.$country_code;
                    }
                }   
                $yachtArr['country_code']   = $country_code;
                $detailUpdate =  Yachtdetail::where('authid', '=', (int)$authid)->update($yachtArr);
                if($detailUpdate) {
					$zaiperenv = env('ZAIPER_ENV','local');
					if($zaiperenv == 'live') {
						$zapierData = array();
						$zapierData['type'] 	= 'Yacht Owner';
						$zapierData['id'] 		= $authid;
						$authEmailData = Auth::where('id',(int)$authid)->get();
						$zapierData['email'] 	= $authEmailData[0]->email;
						$zapierData['firstname']= request('firstname');
						$zapierData['lastname'] = request('lastname');
						$zapierData['contact'] 	= $country_code.request('contact');
						$zapierData['address'] 	= request('address');
						$zapierData['city'] 	= request('city');
						$zapierData['state'] 	= request('state');
						$zapierData['country'] 	= request('country');
						$zapierData['zipcode'] 	= request('zipcode');
						$zapierData['homeport'] = request('homeport');
						$this->sendAccountCreateZapier($zapierData);
					}
                    return response()->json(['success' => true], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401);     
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
                return response()->json(['error'=>'networkerror'], 401); 
        } 
    }

    public function getYachtDetailById(Request $request) {
         $validate = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $id = request('id');
        $yachtdetail =  DB::table('yachtdetail')
                    ->join('auths', 'auths.id', '=', 'yachtdetail.authid')
                    ->select('yachtdetail.*','auths.email','auths.is_social','auths.provider','auths.newsletter')
                    ->where('auths.id','=',$id)
                    ->get()->first();

        if(!empty($yachtdetail)) {
            return response()->json(['success' => true,'data' => $yachtdetail], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    // update password //
    public function updateaccount(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'email' => 'required'
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $password = request('password');
        $confirm = request('confirm');
        $auth   = array(); 
        $updated = 0;
        $authid = request('id');
        
        $userEmail = strtolower(request('email'));
        $emailUpdates = false;
        $userData = Auth::where('email',$userEmail)->where('id',(int)$authid)->count();
        if($userData > 0) {
            $emailUpdates = false;
        } else {
            $auth['email'] = $userEmail;
            $auth['requested_email'] = NULL;
            $auth['email_hash'] = NULL;
            $emailUpdates = true;
        }
        $userDatas = Auth::where('id',(int)$authid)->get();
        $passwordUpdate = false;
        if(!empty($password) && $password !='') {
            $auth['password'] = Hash::make($password);
            $passwordUpdate = true;
        }
        if(!empty($userDatas) && count($userDatas) > 0) {
			if($userDatas[0]->is_social == '1') {
				$passwordUpdate = false;
				$auth['password'] = NULL;
			}
		}
        $OldEmail = '';
        if(!empty($userDatas) && count($userDatas) > 0 ) {
            $OldEmail = $userDatas[0]->email;
        }
        
        $query = Auth::where(function ($query) use ($userEmail) {
                                        $query->where('email', '=', $userEmail)
                                        ->orWhere('requested_email', '=', $userEmail);
                                    })->where('id','!=',(int)$authid);
        $count = $query->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
        $query2 = dummy_registration::where('email', '=', $userEmail);
        $count2 = $query2->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
        $count = $count + $count2;
        if(!empty($count) && $count > 0) {
            return response()->json(['error'=>'networkerror'], 401);
        } 
        
        if(!empty($authid) && $authid > 0) {
            $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'yacht')->update($auth);
            if($updated) {
                if($emailUpdates && !$passwordUpdate) {
                    $emailArr = []; $emailArrnew = [];
                    $userDataDetail  =  Yachtdetail::where('authid',(int)$authid)->where('status','!=','deleted')->get();
                    if(!empty($userDataDetail) && count($userDataDetail) > 0 && $OldEmail != '' ) {
                        $emailArr['firstname'] = $userDataDetail[0]->firstname;
                        $emailArr['lastname'] = $userDataDetail[0]->lastname;
                        $emailArr['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                        $emailArr['to_email'] = $OldEmail;
                        $zaiperenv = env('ZAIPER_ENV','local');
						if($zaiperenv == 'live') {
							$this->sendAccountCreateZapierbyID($authid,'yacht',request('email'));
						}
                        $status1 = $this->sendEmailNotification($emailArr,'admin_emailchange_notification');
                        $emailArrnew['firstname'] = $userDataDetail[0]->firstname;
                        $emailArrnew['lastname'] = $userDataDetail[0]->lastname;
                        $emailArrnew['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                        $emailArrnew['to_email'] = request('email');
                        $status2 = $this->sendEmailNotification($emailArrnew,'admin_emailchange_notification_new');                        
                        return response()->json(['success' => true,'isSame'=>false], $this->successStatus);
                    }
                    return response()->json(['success' => true,'isSame'=>false], $this->successStatus);
                } else if($passwordUpdate && !$emailUpdates) {
                    $emailArr = []; 
                    $userDataDetail  =  Yachtdetail::where('authid',(int)$authid)->where('status','!=','deleted')->get();
                    if(!empty($userDataDetail) && count($userDataDetail) > 0 ) {
                        $emailArr['firstname'] = $userDataDetail[0]->firstname;
                        $emailArr['lastname'] = $userDataDetail[0]->lastname;
                        $emailArr['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                        $emailArr['to_email'] = $userDataDetail[0]->email;
                        $emailArr['password'] = $password;
                        $status1 = $this->sendEmailNotification($emailArr,'admin_passwordchange_notification');
                        return response()->json(['success' => true,'emailSent'=>true], $this->successStatus);
                    }
                } else if($passwordUpdate && $emailUpdates) {
                    $emailArr = []; $emailArrnew = [];
                    $userDataDetail  =  Yachtdetail::where('authid',(int)$authid)->where('status','!=','deleted')->get();
                    if(!empty($userDataDetail) && count($userDataDetail) > 0 && $OldEmail != '' ) {
                        $emailArr['firstname'] = $userDataDetail[0]->firstname;
                        $emailArr['lastname'] = $userDataDetail[0]->lastname;
                        $emailArr['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                        $emailArr['to_email'] = $OldEmail;
                        $emaiilArr['new_email'] = request('email');
                        $emailArr['password'] = $password;
                        $zaiperenv = env('ZAIPER_ENV','local');
						if($zaiperenv == 'live') {
							$this->sendAccountCreateZapierbyID($authid,'yacht',request('email'));
						}
                        $status1 = $this->sendEmailNotification($emailArr,'admin_emailPwdchange_notification');

                        $emailArrnew['firstname'] = $userDataDetail[0]->firstname;
                        $emailArrnew['lastname'] = $userDataDetail[0]->lastname;
                        $emailArrnew['password'] = $password;
                        $emailArrnew['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                        $emailArrnew['to_email'] = request('email');
                        $status2 = $this->sendEmailNotification($emailArrnew,'admin_emailPwdchange_notification_new');
                        return response()->json(['success' => true,'emailPwdSent'=>true], $this->successStatus);
                    }
                } else {
                    return response()->json(['success' => true,'emailPwdSent'=>false], $this->successStatus);
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }



    // delete company //
    public function deleteYacht(Request $request) {
        $authid = (int)request('id');
        $updated = 0;
        if(!empty($authid) && $authid > 0 ) {
            $updated = Auth::where('id', '=', $authid)->where('usertype', '=', 'yacht')->update(['status' => 'deleted']);
            if($updated) {
                $to_email = Auth::select('email')->where('id', '=', $authid)->where('usertype', '=', 'yacht')->get();
                $emailArr = [];
                $emailArr['to_email'] = $to_email[0]['email'];
                $status = $this->sendEmailNotification($emailArr,'user_deleted');
                if($status != 'sent') {
                    return array('status' =>'emailsentfail');
                }
                return response()->json(['success' => true,'authid' => $authid], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }
    }

    // change company status //
    public function changeStatus(Request $request) {
        $status = request('status');
        $authid = (int)request('id');
        $updated = 0;
        if(!empty($authid) && $authid > 0 && !empty($status)) {
            if($status == 'active') {
                $updated = Auth::where('id', '=', $authid)->where('usertype', '=', 'yacht')->update(['status' => 'suspended']);
                $updated_user = Yachtdetail::where('authid', '=', $authid)->update(['status' => 'suspended']);
                if($updated && $updated_user) {
                    return response()->json(['success' => true,'authid' => $authid], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            } else if ($status == 'suspended' || $status == 'pending') {
                $updated = Auth::where('id', '=', $authid)->where('usertype', '=', 'yacht')->update(['status' => 'active']);
                $updated_user = Yachtdetail::where('authid', '=', $authid)->update(['status' => 'active']);
                if($updated && $updated_user) {
                    return response()->json(['success' => true,'authid' => $authid], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            }
        }
    }
    
    // add s3 images //
    public function addS3Images(Request $request) {
        $authid = (int)request('userid');
        $s3images = json_decode(request('images'));
        $primaryImg = request('primary');
        $imageArr = [];
        for($i=0;$i< count($s3images);$i++) {
            $imageArr[$i]['image'] = $s3images[$i];
            if($s3images[$i] ===  $primaryImg) {
                $imageArr[$i]['primary'] = '1';
            } else {
                $imageArr[$i]['primary'] = '0'; 
            }
        }
        $jsonObj = json_encode($imageArr,JSON_UNESCAPED_SLASHES);
        $detailArr =[];
        $detailArr['images'] =  $jsonObj;
        $detailArr['primaryimage'] =  $primaryImg;
        $detailUpdate =  Yachtdetail::where('authid', '=', (int)$authid)->update($detailArr);
        if($detailUpdate) {
            $usersdata = DB::table('yachtdetail')
            ->where('authid', '=', (int)$authid)
            ->first();
            return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

     // set primary image //
    public function setPrimaryImages(Request $request) {
        $authid = request('userid');
        $s3images = json_decode(request('images'));
        $primaryImg = request('primary');
        $imageArr = [];
        for($i=0;$i< count($s3images);$i++) {
            $imageArr[$i]['image'] = $s3images[$i];
            if($s3images[$i] ===  $primaryImg) {
                $imageArr[$i]['primary'] = '1';
            } else {
                $imageArr[$i]['primary'] = '0'; 
            }
        }
        $jsonObj = json_encode($imageArr,JSON_UNESCAPED_SLASHES);
        $detailArr =[];
        $detailArr['images'] =  $jsonObj;
        $detailArr['primaryimage'] =  $primaryImg;
        $detailUpdate =  Yachtdetail::where('authid', '=', (int)$authid)->update($detailArr);
        if($detailUpdate) {
            return response()->json(['success' => true,'authid' => $authid], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    // delete company image //
    public function deleteCompanyImage(Request $request) {
        $authid = request('userid');
        $image = request('image');
        $s3images = json_decode(request('images'));
        $primaryImage = request('primaryImage');
        $imageArr = [];
        $j = 0;
        $primaryDelete = true;
        for($i=0;$i< count($s3images);$i++) {
            if($s3images[$i] !=  $image) {
                $imageArr[$j]['image'] = $s3images[$i];
                if($s3images[$i] ===  $primaryImage) {
                    $imageArr[$j]['primary'] = '1';
                    $primaryDelete = false;
                } else {
                    $imageArr[$j]['primary'] = '0'; 
                }
            $j++;
            }
        }
        $jsonObj = json_encode($imageArr,JSON_UNESCAPED_SLASHES);
        $detailArr =[];
        if($primaryDelete === true) {
            $detailArr['primaryimage'] = NULL;
        }
        $detailArr['images'] =  $jsonObj;
        $detailUpdate =  Yachtdetail::where('authid', '=', (int)$authid)->update($detailArr);
        if($detailUpdate) {
            return response()->json(['success' => true,'images' => $jsonObj], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    // get all company images //
    public function getImagesData(Request $request) {
        $authid = request('userid');
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('yachtdetail')
            ->where('authid', '=', (int)$authid)
            ->select('yachtdetail.images')
            ->first();
            if(!empty($usersdata)) {
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }

    public function importYachtData(Request $request) {

        $validate = Validator::make($request->all(), [
            'csvdata' => 'required',
            'userid' => 'required'
        ]);
        if ($validate->fails()) {
            // print_r($validate);
             return response()->json(['error'=>'validationError'], 401); 
        }
        $csvData = json_decode(request('csvdata'));

        $adminId = request('userid');
        $isError = false;
        $insertIds = [];
        $errorEmail = []; 
        if(!empty($csvData) && count($csvData) < 300 ) {
         // if(0){
            $insertIds = [];
            $errorEmail = []; 
            foreach($csvData as $csvDatas) {
                //Check Duplicate email
                $isError = false;
                $alredyExist =  Auth::where('email',strtolower($csvDatas->email))->count();
                    if($alredyExist) {
                        $errorEmail[] = $csvDatas->email;  
                    } else {      
                        DB::beginTransaction();                       //Insert records in auth
                        $auth   = new Auth; 
                        $authid = 0;
                        $auth->email = strtolower($csvDatas->email);
                        $auth->password = Hash::make($csvDatas->password);
                        $auth->usertype = 'yacht';
                        $auth->ipaddress = $this->getIp();
                        $auth->status = 'active';
                        $auth->stepscompleted = '2';
                        $auth->addedby =1;
                        $auth->is_activated = 1;
                        $newsletter = (!empty($csvDatas->newsletter) && (strtolower($csvDatas->newsletter) == 'yes')) ? '1':'0';
                        $auth->newsletter = $newsletter;
                        if($auth->save()) {
                            $authid = $auth->id;
                            if($authid) {
                                $address = $csvDatas->address;
                                $locAddress = ((isset($address) && $address !='') ? $csvDatas->address.' ': '');
                            
                                $location = $locAddress.$csvDatas->city.' '.$csvDatas->zipcode.' '.$csvDatas->state.' , United States';
                                $output = $this->getGeoLocation($location); //Get Location from location Trait
                                $longitude = $output['longitude'];
                                $latitude = $output['latitude'];
                                $yacht = new Yachtdetail;
                                $yacht->authid = $authid;
                                $yacht->firstname = $csvDatas->firstname;
                                $yacht->lastname = $csvDatas->lastname;
                                $yacht->contact = $csvDatas->contactmobile;
                                $yacht->address = (!empty($csvDatas->address)?$csvDatas->address:NULL);
                                $yacht->longitude  = $longitude;
                                $yacht->latitude   = $latitude;
                                $yacht->city = $csvDatas->city;
                                $yacht->state = $csvDatas->state;
                                $yacht->country = 'United States';
                                   
                                $yacht->zipcode = $csvDatas->zipcode;
                                $yacht->yachtdetail = json_encode($csvDatas->yachtdetail);
                                $yacht->homeport = $csvDatas->homeport;
                                $yacht->status = 'active';
                                
                                $yacht->country_code   = "+1";
                                if($yacht->save()) {
									$zaiperenv = env('ZAIPER_ENV','local');
									if($zaiperenv == 'live') {
										$zapierData = array();
										$zapierData['type'] 	= 'Yacht Owner';
										$zapierData['id'] 	= $authid;
										$zapierData['email'] 	= $csvDatas->email;
										$zapierData['firstname']= $csvDatas->firstname;
										$zapierData['lastname'] = $csvDatas->lastname;
										$zapierData['contact'] 	= "+1".$csvDatas->contactmobile;
										$zapierData['address'] 	= $csvDatas->address;
										$zapierData['city'] 	= $csvDatas->city;
										$zapierData['state'] 	= $csvDatas->state;
										$zapierData['country'] 	= 'United States';
										$zapierData['zipcode'] 	= $csvDatas->zipcode;
										$zapierData['homeport'] = $csvDatas->homeport;
										$this->sendAccountCreateZapier($zapierData);
									}
                                     $insertIds[] = $yacht->id;
                                } else {
                                    $isError = true; 
                                }
                            } else {
                                $isError = true;   
                            }
                        } else {
                                $isError = true;     
                        }
                        if(!$isError) {
                            DB::commit();
                        } else {
                            DB::rollBack();
                            $errorEmail[] = $csvDatas->email;
                        }
                    } 
            }
            if(count($insertIds)) { 
                $failedEmail = (count($errorEmail) >0 )? true:false; 
                return response()->json(['success' => true,'maxrecord' => false,'failed_records' => $failedEmail,'email_error' => $errorEmail], $this->successStatus);
            } else {
                $failedEmail = (count($errorEmail) >0 )? true:false; 
                return response()->json(['success' => false,'maxrecord' => false,'failed_records' => $failedEmail,'email_error' => $errorEmail], $this->successStatus);
            }
        } else {
            ImportUsers::dispatch($csvData,$adminId,'yacht');
            // $this->importDataDummyBusiness($csvData,$adminId);
            return response()->json(['success' => true,'maxrecord' => true], $this->successStatus);
        }
    }
    public function getIp(){
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            $clientIpAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $clientIpAddress = $_SERVER['REMOTE_ADDR'];
        }
        return $clientIpAddress;
    }
}
