<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Auth;
use DB;
use App\Talentdetail;
use App\Jobtitles;
use App\dummy_registration;
use Illuminate\Support\Facades\Hash;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Validator;
use Geocoder;
use App\Http\Traits\NotificationTrait;
use App\Http\Traits\LocationTrait;
use App\Jobs\ImportUsers;
use App\Http\Traits\ZapierTrait;
class TalentController extends Controller
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
    
    // get all professional //
    public function index(Request $request) {
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
        if(!empty($searchString) && $searchString != '') {
			$searchString = implode("''",explode("'",trim($searchString)));
            $searchString = strtolower($searchString);
            $whereCompany = "LOWER(CONCAT(talentdetails.firstname, ' ', talentdetails.lastname)) LIKE '%".$searchString."%' OR auths.email LIKE '%".$searchString."%'";         
        }
        
        if(!empty($statefilter) && $statefilter != 'All' && $statefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " talentdetails.state = '".$statefilter."' ";
			} else {
				$whereCompany .= " AND talentdetails.state = '".$statefilter."' ";
			}
		}
        if(!empty($cityfilter) && $cityfilter != 'All' && $cityfilter != '') {
			if($whereCompany == '') {
				$whereCompany = " talentdetails.city = '".$cityfilter."' ";
			} else {
				$whereCompany .= " AND talentdetails.city = '".$cityfilter."' ";
			}
		}
        if(!empty($zipcodefilter) && $zipcodefilter != 'All' && $zipcodefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " talentdetails.zipcode = '".$zipcodefilter."' ";
			} else {
				$whereCompany .= " AND talentdetails.zipcode = '".$zipcodefilter."' ";
			}
		}

        $query1 = DB::table('auths')
            ->Join('talentdetails', 'auths.id', '=', 'talentdetails.authid')
            ->leftJoin('jobtitles','jobtitles.id','=','talentdetails.jobtitleid')
            ->where('auths.usertype', '=', 'professional')
            ->where('auths.status', '!=', 'deleted')
            ->select('auths.email','auths.is_social','auths.id as userauthid','auths.usertype','auths.status','jobtitles.title as jobtitle','talentdetails.authid','talentdetails.firstname','talentdetails.lastname','talentdetails.jobtitleid','talentdetails.mobile');
        if($whereCompany != '') {
            $query1 = $query1->whereRaw($whereCompany);
        }    
        $totalrecords = $query1->orderBy('created_at', 'DESC')
            ->count();

        $query2 = DB::table('auths')
            ->Join('talentdetails', 'auths.id', '=', 'talentdetails.authid')
            ->leftJoin('jobtitles','jobtitles.id','=','talentdetails.jobtitleid')
            ->where('auths.usertype', '=', 'professional')
            ->where('auths.status', '!=', 'deleted')
            ->select('auths.email','auths.is_social','auths.id as userauthid','auths.usertype','auths.status','jobtitles.title as jobtitle','talentdetails.authid','talentdetails.firstname','talentdetails.lastname','talentdetails.jobtitleid','talentdetails.mobile');
        // Order By filter
        if($orderBy == 'firstname') {
            $query2 = $query2->orderBy('talentdetails.firstname', $order);
            $query2 = $query2->orderBy('talentdetails.lastname', $order);
        } else if($orderBy == 'email') {
            $query2 = $query2->orderBy('auths.email', $order);    
        } else if($orderBy == 'mobile') {
            $query2 = $query2->orderBy('talentdetails.mobile', $order);
        } else if($orderBy == 'jobtitle') {
            $query2 = $query2->orderBy('talentdetails.mobile', $order);
        } else if($orderBy == 'created_at') {
            $query2 = $query2->orderBy('talentdetails.created_at', $order);
        } else {
            $query2 = $query2->orderBy('auths.created_at', 'DESC');
        }
        //Where condition 
        if($whereCompany != '') {
            $query2 = $query2->whereRaw($whereCompany);
        }    
        $usersdata = $query2->orderBy('auths.created_at', 'DESC')
            ->skip($offset)
            ->take($limit)
            ->get();
        if(!empty($usersdata)) {
            return response()->json(['success' => 'success','data' => $usersdata,'totalrecords' => $totalrecords], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    
    public function exportProfessionalData() {
        $searchString = request('searchString');
        $limit = request('limit');
        $offset = request('offset');
        $whereCompany = '';
        $reverse = request('reverse');
        $orderBy = request('order');
        $order = ($reverse == 'false')?'ASC':'DESC';
        
        $statefilter = request('state');
        $cityfilter = request('city');
        $zipcodefilter = request('zipcode');
        if(!empty($searchString) && $searchString != '') {
            $searchString = strtolower($searchString);
            $whereCompany = "LOWER(CONCAT(talentdetails.firstname, ' ', talentdetails.lastname)) LIKE '%".$searchString."%' OR auths.email LIKE '%".$searchString."%'";         
        }
        
        if(!empty($statefilter) && $statefilter != 'All' && $statefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " talentdetails.state = '".$statefilter."' ";
			} else {
				$whereCompany .= " AND talentdetails.state = '".$statefilter."' ";
			}
		}
        if(!empty($cityfilter) && $cityfilter != 'All' && $cityfilter != '') {
			if($whereCompany == '') {
				$whereCompany = " talentdetails.city = '".$cityfilter."' ";
			} else {
				$whereCompany .= " AND talentdetails.city = '".$cityfilter."' ";
			}
		}
        if(!empty($zipcodefilter) && $zipcodefilter != 'All' && $zipcodefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " talentdetails.zipcode = '".$zipcodefilter."' ";
			} else {
				$whereCompany .= " AND talentdetails.zipcode = '".$zipcodefilter."' ";
			}
		}
        $query = DB::table('auths')
            ->Join('talentdetails', 'auths.id', '=', 'talentdetails.authid')
            ->leftJoin('jobtitles','jobtitles.id','=','talentdetails.jobtitleid')
            ->where('auths.usertype', '=', 'professional')
            ->where('auths.status', '!=', 'deleted')
            ->select('talentdetails.firstname','talentdetails.lastname','auths.email','talentdetails.address','talentdetails.city','talentdetails.state','talentdetails.zipcode','talentdetails.mobile','jobtitles.title','talentdetails.totalexperience','talentdetails.willingtravel','talentdetails.workexperience','talentdetails.licences','talentdetails.certification','talentdetails.objective');
        // Order By filter
        if($orderBy == 'firstname') {
            $query = $query->orderBy('talentdetails.firstname', $order);
            $query = $query->orderBy('talentdetails.lastname', $order);
        } else if($orderBy == 'email') {
            $query = $query->orderBy('auths.email', $order);    
        } else if($orderBy == 'mobile') {
            $query = $query->orderBy('talentdetails.mobile', $order);
        } else if($orderBy == 'jobtitle') {
            $query = $query->orderBy('jobtitles.title', $order);
        } else {
            $query = $query->orderBy('auths.created_at', 'DESC');
        }
        if($whereCompany != '') {
            $query = $query->whereRaw($whereCompany);
        }  
        $usersdata = $query->orderBy('talentdetails.created_at', 'DESC')
            ->skip($offset)
            ->take($limit)
            ->get();
        if(!empty($usersdata)) {
            return response()->json(['success' => 'success','data' => $usersdata], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    
    // change professional status //
    public function changeStatus(Request $request) {
        $status = request('status');
        $authid = (int)request('id');
        $updated = 0;
        if(!empty($authid) && $authid > 0 && !empty($status)) {
            if($status == 'active') {
                $updated = Auth::where('id', '=', $authid)->where('usertype', '=', 'professional')->update(['status' => 'suspended']);
                $updated_pro = Talentdetail::where('authid', '=', $authid)->update(['status' => 'suspended']);
                if($updated && $updated_pro) {
                    return response()->json(['success' => 'success','authid' => $authid], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            } else if ($status == 'suspended' || $status == 'pending') {
                $updated = Auth::where('id', '=', $authid)->where('usertype', '=', 'professional')->update(['status' => 'active']);
                $updated_pro = Talentdetail::where('authid', '=', $authid)->update(['status' => 'active']);
                if($updated && $updated_pro) {
                    return response()->json(['success' => 'success','authid' => $authid], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            }
        }
    }
    
    // delete professional //
    public function deleteProfessional(Request $request) {
        $authid = (int)request('id');
        $updated = 0;
        if(!empty($authid) && $authid > 0 ) {
            $updated = Auth::where('id', '=', $authid)->where('usertype', '=', 'professional')->update(['status' => 'deleted']);
            $updated = Talentdetail::where('authid', '=', $authid)->update(['status' => 'deleted']);
            if($updated) {
                $to_email = Auth::select('email')->where('id', '=', $authid)->where('usertype', '=', 'professional')->get();
                $emailArr = [];
                $emailArr['to_email'] = $to_email[0]['email'];
                $status = $this->sendEmailNotification($emailArr,'user_deleted');
                if($status != 'sent') {
                    return array('status' =>'emailsentfail');
                }
                return response()->json(['success' => 'success','authid' => $authid], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }
    }
    
    // get professional details //
    public function getProfessionalDetail(Request $request) {
        $authid = request('id');
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('auths')
            ->leftJoin('talentdetails', 'auths.id', '=', 'talentdetails.authid')
            ->where('auths.id', '=', (int)$authid)
            ->where('auths.usertype', '=', 'professional')
            ->select('auths.email','auths.is_social','auths.provider','auths.newsletter','auths.id as userauthid','auths.usertype','auths.status', 'talentdetails.*')
            ->first();
            if(!empty($usersdata)) {
                return response()->json(['success' => 'success','data' => $usersdata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }
    
    // check company exist //
    public function checkEmail(Request $request) {
        $userEmail = strtolower(request('email'));
        $id = request('id');
        $success = false;
        if(!empty($userEmail) && $userEmail != '' ) {
            $query = Auth::where('email', '=', $userEmail);
            if(isset($id) && !empty($id) && $id != '') {
				$query->where('id','!=',$id);
			}
            $count = $query->where('status', '!=', 'deleted')->count();
            if(!empty($count) && $count > 0) {
                $success = true;
            } else {
                $success = false; 
            }
        }
        return response()->json(['success' => $success], $this->successStatus);
    }
    
    // update password //
    public function updateaccount(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'email' => 'required'
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'vaildationError'], 401); 
        }
        $password = request('password');
        $confirm = request('confirm');
        $auth   = array(); 
        $updated = 0;
        $authid = request('id');
        $passwordUpdate = false;
        if(!empty($password) && $password !='') {
            $auth['password'] = Hash::make($password);
            $passwordUpdate = true;
        }
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
            $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'professional')->update($auth);
            if($updated) {
                if($emailUpdates && !$passwordUpdate) {
                    $emailArr = [];
                    $emailArrnew = [];
                    $userDataDetail  =  Talentdetail::where('authid',(int)$authid)->where('status','!=','deleted')->get();
                    if(!empty($userDataDetail) && count($userDataDetail) > 0 && $OldEmail != '' ) {
                        $emailArr['firstname'] = $userDataDetail[0]->firstname;
                        $emailArr['lastname'] = $userDataDetail[0]->lastname;
                        $emailArr['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                        $emailArr['to_email'] = $OldEmail;
                        $zaiperenv = env('ZAIPER_ENV','local');
						if($zaiperenv == 'live') {
							$this->sendAccountCreateZapierbyID($authid,'professional',request('email'));
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
                    $userDataDetail  =  Talentdetail::where('authid',(int)$authid)->where('status','!=','deleted')->get();
                    if(!empty($userDataDetail) && count($userDataDetail) > 0 ) {
                        $emailArr['firstname'] = $userDataDetail[0]->firstname;
                        $emailArr['lastname'] = $userDataDetail[0]->lastname;
                        $emailArr['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                        $emailArr['to_email'] = $userDatas[0]->email;
                        $emailArr['password'] = $password;
                        $status1 = $this->sendEmailNotification($emailArr,'admin_passwordchange_notification');
                        return response()->json(['success' => true,'emailSent'=>true], $this->successStatus);
                    }
                } else if($passwordUpdate && $emailUpdates) {
                    $emailArr = []; $emailArrnew = [];
                    $userDataDetail  =  Talentdetail::where('authid',(int)$authid)->where('status','!=','deleted')->get();
                    if(!empty($userDataDetail) && count($userDataDetail) > 0 && $OldEmail != '' ) {
                        $emailArr['firstname'] = $userDataDetail[0]->firstname;
                        $emailArr['lastname'] = $userDataDetail[0]->lastname;
                        $emailArr['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                        $emailArr['to_email'] = $OldEmail;
                        $emaiilArr['new_email'] = request('email');
                        $emailArr['password'] = $password;
                        $zaiperenv = env('ZAIPER_ENV','local');
						if($zaiperenv == 'live') {
							$this->sendAccountCreateZapierbyID($authid,'professional',request('email'));
						}
                        $status1 = $this->sendEmailNotification($emailArr,'admin_emailPwdchange_notification');

                        $emailArrnew['firstname'] = $userDataDetail[0]->firstname;
                        $emailArrnew['lastname'] = $userDataDetail[0]->lastname;
                        $emailArrnew['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                        $emailArrnew['password'] = $password;
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



    
    // add professional //
    public function addProfessional(Request $request) {
        $validate = Validator::make($request->all(), [
            'firstname' => 'required',
            'jobtitle' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            // 'county' => 'required',
            'zipcode' => 'required',
            'mobile' => 'required',
            'resume' => 'required',
            'objective' => 'required',
            'willingtravel' => 'required',
            'workexperience' => 'required',
            'email' => 'bail|required|E-mail',
			'password' => 'required',
			'confirm' => 'required|same:password',
		]);
        if ($validate->fails()) {
           return response()->json(['error'=>'vaildationError'], 401); 
        }
        $auth	= new Auth; 
        $authid = 0;
        $newsletter = (!empty(request('newsletter')) && (request('newsletter') == 'true')) ? '1':'0';
		$auth->email = strtolower(request('email'));
        $auth->password = Hash::make(request('password'));
        $auth->usertype = 'professional';
        $auth->ipaddress = $this->getIp();
        $auth->status = 'active';
        $auth->stepscompleted ='3';	
        $auth->addedby =1;
        $auth->is_activated =1;
        $auth->newsletter = $newsletter;
        if($auth->save()) {
			$authid = $auth->id;
        } 
        if($authid) {
            $address = request('address');
            $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
            $output = app('geocoder')->geocode($locAddress.'city - '.request('city').' state - '.request('state').' country - '.request('country').' zipcode - '.request('zipcode'))->dump('geojson');
            if(empty($output->toArray())) {
                $longitude = 0;
                $latitude = 0;
            } else {
                $arrayCord = json_decode($output->toArray()[0]);
                if(!empty($arrayCord)) {
                    $longitude = $arrayCord->geometry->coordinates[0];
                    $latitude = $arrayCord->geometry->coordinates[1];
                } else {
                    $longitude = 0;
                    $latitude = 0;
                }
            }

            $licenceJson = request('licences');
            $emptyLicence = true;
            $licenceArray  = array();
            $i = 0;
            if(!empty($licenceJson)) {
                $licence = json_decode(request('licences'));
                foreach ($licence as $val) {
                    if($val->licencename == '' && $val->saveS3Licence == '') {

                    } else {
                        $licenceArray[$i]['licencename'] = $val->licencename;
                        $licenceArray[$i]['saveS3Licence'] = $val->saveS3Licence;
                        $emptyLicence = false;
                        $i++;
                    }
                }
            }
            $licenceObj = json_encode($licenceArray);


            $certificateJson = request('certification');
            $emptyCertificate = true;
            $certificateArray  = array();
            $i = 0;
            if(!empty($certificateJson)) {
                $certificate = json_decode(request('certification'));
                foreach ($certificate as $val) {
                    if($val->certificatename == '' && $val->saveS3Certificate == '') {

                    } else {
                        $certificateArray[$i]['certificatename'] = $val->certificatename;
                        $certificateArray[$i]['saveS3Certificate'] = $val->saveS3Certificate;
                        $emptyCertificate = false;
                        $i++;
                    }
                }
            }
            $certificateObj = json_encode($certificateArray);


            $address 			= request('address');
			$profile_image 		= request('profile_image');
			$lastName 			= request('lastname');
			// $certification  	= request('certification');
			// $licences 			= request('licences');
			$talentdetail		= new Talentdetail; 
            $talentdetail->authid  	= $authid;
            $talentdetail->firstname= request('firstname');
            $talentdetail->lastname = ((isset($lastName) && $lastName !='') ? request('lastname'): NULL);
            $talentdetail->jobtitleid = request('jobtitle');
            $talentdetail->otherjobtitle = request('otherJobTitle');
            $talentdetail->totalexperience = request('experience');
            $talentdetail->objective  = request('objective');
            $talentdetail->willingtravel  = request('willingtravel');
            $talentdetail->workexperience  = request('workexperience');
            $talentdetail->status = 'active';
            $talentdetail->certification= ($emptyCertificate)? NULL:$certificateObj;
            $talentdetail->licences    	= ($emptyLicence)?NULL:$licenceObj;
            $talentdetail->address      = ((isset($address) && $address !='') ? request('address'): NULL);
            $talentdetail->city       	= request('city');
            $talentdetail->state      	= request('state');
            $talentdetail->country    	= request('country');
            // $talentdetail->county      = request('county');
            $talentdetail->zipcode    	= request('zipcode');
            $talentdetail->mobile    	= request('mobile');
            $talentdetail->profile_image= ((isset($profile_image) && $profile_image !='') ? request('profile_image'): NULL);
            $talentdetail->resume    	= request('resume');
            $talentdetail->longitude  	= $longitude;
            $talentdetail->latitude   	= $latitude;
            $country_code = request('country_code');
            if($country_code != '') {
                $pos = strpos($country_code, '+');
                if(!$pos){
                    $country_code ='+'.$country_code;
                }
            }   
            $talentdetail->country_code   = $country_code;
            if($talentdetail->save()) {
                $usersdata = DB::table('auths')
                ->join('talentdetails', 'auths.id', '=', 'talentdetails.authid')
                ->where('auths.id', '=', (int)$authid)
                ->select('auths.email','auths.status', 'talentdetails.*')
                ->first();
                $emailArr = [];
                $emailArr['firstname'] = request('firstname');
                $emailArr['lastname'] = request('lastname');
                $emailArr['to_email'] = request('email');
                $emailArr['password'] = request('password');
                //Send account created email notification
                $zaiperenv = env('ZAIPER_ENV','local');
                if($zaiperenv == 'live') {
					$jobtitleArray = DB::table('jobtitles')->where('id',request('jobtitle'))->get();
					if(!empty($jobtitleArray) && count($jobtitleArray) > 0) {
						$jobtitle = $jobtitleArray[0]->title;
					} else {
						$jobtitle = '';
					}
					$zapierData = array();
					$zapierData['type'] 	= 'Professional';
					$zapierData['id'] 		= $authid;
					$zapierData['email'] 	= request('email');
					$zapierData['firstname']= request('lastname');
					$zapierData['lastname'] = request('lastname');
					$zapierData['contact'] 	= $country_code.request('mobile');
					$zapierData['address'] 	= request('address');
					$zapierData['city'] 	= request('city');
					$zapierData['state'] 	= request('state');
					$zapierData['country'] 	= request('country');
					$zapierData['zipcode'] 	= request('zipcode');
					$zapierData['jobtitle'] = $jobtitle;
					$zapierData['objective'] = request('objective');
					$zapierData['totalexperience'] = request('experience');
					$this->sendAccountCreateZapier($zapierData);
				}
                $status = $this->sendEmailNotification($emailArr,'user_added_by_admin');
                
                if($status != 'sent') {
                    return array('status' =>'emailsentfail');
                }
                return response()->json(['success' => 'success','data' => $usersdata], $this->successStatus);
			} else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    
    // edit professional //
    public function editProfessional(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'firstname' => 'required',
			'jobtitle' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            // 'county' => 'required',
            'zipcode' => 'required',
            'objective' => 'required',
            'willingtravel' => 'required',
            'workexperience' => 'required',
            'mobile' => 'required',
            'resume' => 'required',
            // 'email' => 'bail|required|E-mail',
		]);
        if ($validate->fails()) {
            return response()->json(['error'=>'vaildationError'], 401); 
        }
        $auth	= $detailArr = array(); 
        $updated = $detailUpdate = 0;
        $authid = request('id');
        // $auth['email'] = strtolower(request('email'));
        $auth['ipaddress'] =$request->ip();
        $newsletter = (!empty(request('newsletter')) && (request('newsletter') == 'true')) ? '1':'0';
        $auth['newsletter'] =$newsletter;
        if(!empty($authid) && $authid > 0) {
            $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'professional')->update($auth);
            if($updated) {
                $address = request('address');
                $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
            
                $output = app('geocoder')->geocode($locAddress.'city - '.request('city').' state - '.request('state').' country - '.request('country').' zipcode - '.request('zipcode'))->dump('geojson');
                if(empty($output->toArray())) {
                    $longitude = 0;
                    $latitude = 0;
                } else {
                    $arrayCord = json_decode($output->toArray()[0]);
                    if(!empty($arrayCord)) {
                        $longitude = $arrayCord->geometry->coordinates[0];
                        $latitude = $arrayCord->geometry->coordinates[1];
                    } else {
                        $longitude = 0;
                        $latitude = 0;
                    }
                }
                $licenceJson = request('licences');
                $emptyLicence = true;
                $licenceArray  = array();
                $i = 0;
                if(!empty($licenceJson)) {
                    $licence = json_decode(request('licences'));
                    foreach ($licence as $val) {
                        if($val->licencename == '' && $val->saveS3Licence == '') {

                        } else {
                            $licenceArray[$i]['licencename'] = $val->licencename;
                            $licenceArray[$i]['saveS3Licence'] = $val->saveS3Licence;
                            $emptyLicence = false;
                            $i++;
                        }
                    }
                }
                $licenceObj = json_encode($licenceArray);


                $certificateJson = request('certification');
                $emptyCertificate = true;
                $certificateArray  = array();
                $i = 0;
                if(!empty($certificateJson)) {
                    $certificate = json_decode(request('certification'));
                    foreach ($certificate as $val) {
                        if($val->certificatename == '' && $val->saveS3Certificate == '') {

                        } else {
                            $certificateArray[$i]['certificatename'] = $val->certificatename;
                            $certificateArray[$i]['saveS3Certificate'] = $val->saveS3Certificate;
                            $emptyCertificate = false;
                            $i++;
                        }
                    }
                }
                $certificateObj = json_encode($certificateArray);

                $address 			= request('address');
                $profile_image 		= request('profile_image');
				$lastName 			= request('lastname');
				// $certification  	= request('certification');
				// $licences 			= request('licences');
				$detailArr['firstname'] = request('firstname');
                $detailArr['lastname'] 	= ((isset($lastName) && $lastName !='') ? request('lastname'): NULL);
                $detailArr['jobtitleid'] 	= request('jobtitle');
                $detailArr['certification']	= ($emptyCertificate)? NULL:$certificateObj;
				$detailArr['licences']    	= ($emptyLicence)?NULL:$licenceObj;
				$detailArr['address'] 	 = ((isset($address) && $address !='') ? request('address'): NULL);
                $detailArr['city'] 		 = request('city');
                $detailArr['objective']  = request('objective');
                $detailArr['willingtravel'] = request('willingtravel');
                $detailArr['workexperience'] = request('workexperience');
                $detailArr['state'] 	 = request('state');
                $detailArr['country'] 	 = request('country');
                // $detailArr['county']   = request('county');
                $detailArr['zipcode'] 	 = request('zipcode');
                $detailArr['jobtitleid'] = request('jobtitle');
                $detailArr['otherjobtitle'] = request('otherJobTitle');
                $detailArr['totalexperience'] = request('experience');
                $detailArr['mobile'] 	 = request('mobile');
                $detailArr['profile_image'] = $profile_image;
                $detailArr['longitude']  = $longitude;
                $detailArr['latitude']   = $latitude;
                $country_code = request('country_code');
                if($country_code != '') {
                    $pos = strpos($country_code, '+');
                    if(!$pos){
                        $country_code ='+'.$country_code;
                    }
                }   
                $detailArr['country_code']   = $country_code;
                $detailUpdate =  Talentdetail::where('authid', '=', (int)$authid)->update($detailArr);
                if($detailUpdate) {
					$zaiperenv = env('ZAIPER_ENV','local');
					if($zaiperenv == 'live') {
						$jobtitleArray = DB::table('jobtitles')->where('id',request('jobtitle'))->get();
						if(!empty($jobtitleArray) && count($jobtitleArray) > 0) {
							$jobtitle = $jobtitleArray[0]->title;
						} else {
							$jobtitle = '';
						}
						$zapierData = array();
						$zapierData['type'] 	= 'Professional';
						$zapierData['id'] 		= $authid;
						$authEmailData = Auth::where('id',(int)$authid)->get();
						$zapierData['email'] 	= $authEmailData[0]->email;
						$zapierData['firstname']= request('lastname');
						$zapierData['lastname'] = request('lastname');
						$zapierData['contact'] 	= $country_code.request('mobile');
						$zapierData['address'] 	= request('address');
						$zapierData['city'] 	= request('city');
						$zapierData['state'] 	= request('state');
						$zapierData['country'] 	= request('country');
						$zapierData['zipcode'] 	= request('zipcode');
						$zapierData['jobtitle'] = $jobtitle;
						$zapierData['objective'] = request('objective');
						$zapierData['totalexperience'] = request('experience');
						$this->sendAccountCreateZapier($zapierData);
					}
                    return response()->json(['success' => 'success','authid' => $authid], $this->successStatus);
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

    public function importProfessionalData(Request $request){
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
        // if(!empty($csvData) && count($csvData) < 300 ) {
         if(0){
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
                        $newsletter = (!empty($csvDatas->newsletter) && (strtolower($csvDatas->newsletter) == 'yes')) ? '1':'0';
                        $auth->email = strtolower($csvDatas->email);
                        $auth->password = Hash::make($csvDatas->password);
                        $auth->usertype = 'professional';
                        $auth->ipaddress = $this->getIp();
                        $auth->status = 'active';
                        $auth->stepscompleted ='3'; 
                        $auth->is_activated = 1;
                        $auth->addedby =1;
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
                                

                                $talentdetail       = new Talentdetail; 
                                $talentdetail->authid   = $authid;
                                $talentdetail->firstname= $csvDatas->firstname;
                                $talentdetail->lastname = ((isset($csvDatas->lastname) && $csvDatas->lastname !='') ? $csvDatas->lastname: NULL);
                                $checkJobTitle = Jobtitles::whereRaw("LOWER(title) = '".strtolower($csvDatas->jobtitle)."'")->where('status','1')->first();
                                $jobtitleid = 0;
                                if(!empty($checkJobTitle)) {
                                    $jobtitleid = $checkJobTitle->id;
                                } else {
                                    $otherID = Jobtitles::where('title','Others')->first();
                                    $jobtitleid = $otherID->id;
                                    $talentdetail->otherjobtitle = $csvDatas->jobtitle;
                                }
                                $talentdetail->jobtitleid = $jobtitleid;
                               
                                $talentdetail->totalexperience = $csvDatas->totalexperience;
                                $talentdetail->objective  = $csvDatas->objective;
                                $talentdetail->willingtravel  = (strtolower($csvDatas->willingtravel) == 'yes')?'yes':'no';
                                $talentdetail->workexperience  = json_encode($csvDatas->workexperience);
                                $talentdetail->status = 'active';
                                $talentdetail->certification= NULL;
                                $talentdetail->licences     = NULL;
                                $talentdetail->address      = ((isset($address) && $address !='') ? $csvDatas->address: NULL);
                                $talentdetail->city         = $csvDatas->city;
                                $talentdetail->state        = $csvDatas->state;
                                $talentdetail->country      = 'United States';
                                $talentdetail->zipcode      = $csvDatas->zipcode;
                                $talentdetail->mobile       = $csvDatas->contactmobile;
                                $talentdetail->profile_image = NULL;
                                $talentdetail->resume       = NULL;
                                $talentdetail->longitude    = $longitude;
                                $talentdetail->latitude     = $latitude;
                                $country_code = $csvDatas->country_code;
                                if($country_code != '') {
                                    $pos = strpos($country_code, '+');
                                    if(!$pos){
                                        $country_code ='+'.$country_code;
                                    }
                                }   
                                $talentdetail->country_code   = $country_code;
                                if($talentdetail->save()) {
									$zaiperenv = env('ZAIPER_ENV','local');
									if($zaiperenv == 'live') {
										if($jobtitleid == '1') {
											$jobtitle = $csvDatas->jobtitle;
										} else {
											$jobtitleArray = DB::table('jobtitles')->where('id',$jobtitleid)->get();
											if(!empty($jobtitleArray) && count($jobtitleArray) > 0) {
												$jobtitle = $jobtitleArray[0]->title;
											} else {
												$jobtitle = '';
											}
										}
										$zapierData = array();
										$zapierData['type'] 	= 'Professional';
										$zapierData['id'] 		= $authid;
										$zapierData['email'] 	= $csvDatas->email;
										$zapierData['firstname']= $csvDatas->firstname;
										$zapierData['lastname'] = $csvDatas->lastname;
										$zapierData['contact'] 	= $country_code.$csvDatas->contactmobile;
										$zapierData['address'] 	= $csvDatas->address;
										$zapierData['city'] 	= $csvDatas->city;
										$zapierData['state'] 	= $csvDatas->state;
										$zapierData['country'] 	= 'United States';
										$zapierData['zipcode'] 	= $csvDatas->zipcode;
										$zapierData['jobtitle'] = $jobtitle;
										$zapierData['objective'] = $csvDatas->objective;
										$zapierData['totalexperience'] = $csvDatas->totalexperience;
										$this->sendAccountCreateZapier($zapierData);
									}
                                     $insertIds[] = $talentdetail->id;
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
            ImportUsers::dispatch($csvData,$adminId,'professional');
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
