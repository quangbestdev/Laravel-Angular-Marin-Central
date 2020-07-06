<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Auth;
use DB;
use App\Userdetail;
use Illuminate\Support\Facades\Hash;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Validator;
use CountryState;
use App\Http\Traits\LocationTrait;
use App\Claimed_business;
use App\dummy_registration;
use App\Http\Traits\NotificationTrait;
use App\Jobs\ImportUsers;
use App\Http\Traits\ImportTrait;
use App\Http\Traits\ZapierTrait;
class UserController extends Controller
{
    use LocationTrait;
    use NotificationTrait;
    use ImportTrait;
    use ZapierTrait;
    public $successStatus = 200;

    public function __construct(Request $request) {
        $value = $request->bearerToken();
	    if(!empty($value)) {
	     	$id= (new Parser())->parse($value)->getHeader('jti');
	     	$userid = DB::table('oauth_access_tokens')->where('id', '=', $id)->where('revoked', '=', false)->first()->user_id;
	     	if(!empty($userid) && $userid > 0) {
                $usertype = Auth::where('id', '=', $userid)->where('status' ,'=','active')->first()->usertype;
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
    // get all regular users //
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
            if($whereCompany == '') {
                $whereCompany = "LOWER(CONCAT(userdetails.firstname, ' ', userdetails.lastname)) LIKE '%".$searchString."%' OR LOWER(auths.email) LIKE '%".$searchString."%'";       
            }
        }
        
        if(!empty($statefilter) && $statefilter != 'All' && $statefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " userdetails.state = '".$statefilter."' ";
			} else {
				$whereCompany .= " AND userdetails.state = '".$statefilter."' ";
			}
		}
        if(!empty($cityfilter) && $cityfilter != 'All' && $cityfilter != '') {
			if($whereCompany == '') {
				$whereCompany = " userdetails.city = '".$cityfilter."' ";
			} else {
				$whereCompany .= " AND userdetails.city = '".$cityfilter."' ";
			}
		}
        if(!empty($zipcodefilter) && $zipcodefilter != 'All' && $zipcodefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " userdetails.zipcode = '".$zipcodefilter."' ";
			} else {
				$whereCompany .= " AND userdetails.zipcode = '".$zipcodefilter."' ";
			}
		}

        //Get total Records Count
        $query = DB::table('auths')
            ->Join('userdetails', 'auths.id', '=', 'userdetails.authid')
            ->where('auths.usertype', '=', 'regular')
            ->where('auths.status', '!=', 'deleted')
            ->select('auths.email','auths.is_social','auths.id as userauthid','auths.usertype','auths.status','auths.is_activated','userdetails.*')
            ->orderBy('auths.created_at', 'DESC');
         if($whereCompany != '') {
           $query =  $query->whereRaw($whereCompany);
        }           
        $totalrecords = $query->count();
        //Get total Records data
        $query2 = DB::table('auths')
            ->Join('userdetails', 'auths.id', '=', 'userdetails.authid')
            ->where('auths.usertype', '=', 'regular')
            ->where('auths.status', '!=', 'deleted')
            ->select('auths.email','auths.is_social','auths.id as userauthid','auths.usertype','auths.status','auths.is_activated','userdetails.*');
        if($orderBy == 'firstname') {
            $query2 = $query2->orderBy('userdetails.firstname', $order);
            $query2 = $query2->orderBy('userdetails.lastname', $order);
            
        } else if($orderBy == 'email') {
            $query2 = $query2->orderBy('auths.email', $order);    
        } else if($orderBy == 'mobile') {
            $query2 = $query2->orderBy('userdetails.mobile', $order);
        } else if($orderBy == 'created_at') {
            $query2 = $query2->orderBy('userdetails.created_at', $order);
        } else {
            $query2 = $query2->orderBy('auths.created_at', 'DESC');
        }
        if($whereCompany != '') {
           $query2 =  $query2->whereRaw($whereCompany);
        }           
        $usersdata = $query2->skip($offset)
                    ->take($limit)
                    ->get();


        if(!empty($usersdata)) {
            return response()->json(['success' => 'success','data' => $usersdata,'totalrecords' => $totalrecords], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    
    public function exportUserData() {
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
        if(!empty($searchString) && $searchString !='') {
            $searchString = strtolower($searchString);
            if($whereCompany == '') {
                $whereCompany = "LOWER(CONCAT(userdetails.firstname, ' ', userdetails.lastname)) LIKE '%".$searchString."%' OR LOWER(auths.email) LIKE '%".$searchString."%'";       
            }
        }
		  if(!empty($statefilter) && $statefilter != 'All'  && $statefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " userdetails.state = '".$statefilter."' ";
			} else {
				$whereCompany .= " AND userdetails.state = '".$statefilter."' ";
			}
		}
        if(!empty($cityfilter) && $cityfilter != 'All'  && $cityfilter != '') {
			if($whereCompany == '') {
				$whereCompany = " userdetails.city = '".$cityfilter."' ";
			} else {
				$whereCompany .= " AND userdetails.city = '".$cityfilter."' ";
			}
		}
        if(!empty($zipcodefilter) && $zipcodefilter != 'All'  && $zipcodefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " userdetails.zipcode = '".$zipcodefilter."' ";
			} else {
				$whereCompany .= " AND userdetails.zipcode = '".$zipcodefilter."' ";
			}
		}

		$query = DB::table('auths')
            ->Join('userdetails', 'auths.id', '=', 'userdetails.authid')
            ->where('auths.usertype', '=', 'regular')
            ->where('auths.status', '!=', 'deleted')
            ->select('userdetails.firstname','userdetails.lastname','auths.email',DB::Raw("COALESCE(userdetails.address,'-') as address"),'userdetails.city','userdetails.zipcode','userdetails.state','userdetails.mobile');
        // Order By filter
        if($orderBy == 'firstname') {
            $query = $query->orderBy('userdetails.firstname', $order);
            $query = $query->orderBy('userdetails.lastname', $order);
        } else if($orderBy == 'email') {
            $query = $query->orderBy('auths.email', $order);    
        } else if($orderBy == 'mobile') {
            $query = $query->orderBy('userdetails.mobile', $order);
        } else {
            $query = $query->orderBy('auths.created_at', 'DESC');
        }
        //Where condition 
        if($whereCompany != '') {
           $query =  $query->whereRaw($whereCompany);
        }
        $usersdata = $query->orderBy('auths.created_at', 'DESC')
            ->skip($offset)
            ->take($limit)
            ->get();
        if(!empty($usersdata)) {
            return response()->json(['success' => 'success','data' => $usersdata], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
	}
	
    public function importUserData(Request $request) {

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
        $insertIds = [];
        $errorEmail = []; 
        if(!empty($csvData) && count($csvData) > 0 &&  count($csvData) < 300) {
        // if(0){
            foreach($csvData as $csvDatas) {
                $isError = false;
                //Check Duplicate email
                $alredyExist =  Auth::where('email',strtolower($csvDatas->email))->count();
                    if($alredyExist) {
                        $errorEmail[] = $csvDatas->email;  
                    } else {      
                        DB::beginTransaction();                       //Insert records in auth
                        $auth  = new Auth; 
                        $userid = 0;
                        $newsletter = (!empty($csvDatas->newsletter) && (strtolower($csvDatas->newsletter) == 'yes')) ? '1':'0';
        
                        $auth->email = strtolower($csvDatas->email);
                        $auth->password = Hash::make($csvDatas->password);
                        $auth->usertype = 'regular';
                        $auth->ipaddress = $this->getIp();
                        $auth->status = 'active';
                        $auth->stepscompleted ='2';    
                        $auth->addedby=1;
                        $auth->newsletter = $newsletter;
                        $auth->is_activated = '1';
                        if($auth->save()) {
                            $userid = $auth->id;
                        } else {
                            $isError = true;
                        }
                        if($userid) { 
                            $longitude = 0; 
                            $latitude = 0;
                            $address = $csvDatas->address;
                            $locAddress = ((isset($address) && $address !='') ? $csvDatas->address.' ': '');
                            $location = $locAddress.$csvDatas->city.' '.$csvDatas->zipcode.' '.$csvDatas->state.' , United States';
                            $output = $this->getGeoLocation($location); //Get User longitude and latitude
                            $longitude = $output['longitude'];
                            $latitude = $output['latitude'];
                            $userdetail    = new Userdetail; 
                            $userdetail->authid  = $userid;
                            $userdetail->firstname  = $csvDatas->firstname;
                            $userdetail->lastname   = $csvDatas->lastname;
                            //Find city state and zipcode 
                            // $checkAddressExist = DB::table('usareas')->whereRaw("zipcode = '".$csvDatas->zipcode."' AND LOWER(city) = '".strtolower($csvDatas->city)."' AND LOWER(statename) = '".strtolower($csvDatas->state)."' ")->count();
                            // if(!$checkAddressExist) {
                            //     $isError = true;  
                            // }
                            $userdetail->city       = $csvDatas->city;
                            $userdetail->state      = $csvDatas->state;
                            $userdetail->country    = 'United States';
                            $userdetail->address    = ((isset($address) && $address !='') ? $csvDatas->address: NULL);
                            $userdetail->zipcode    = $csvDatas->zipcode;
                            $userdetail->mobile     = $csvDatas->contactnumber;
                            $userdetail->status     = 'active';
                            $userdetail->profile_image     = NULL;
                            $userdetail->longitude  = $longitude;
                            $userdetail->latitude   = $latitude;
                            $userdetail->status   = 'active';
                            if(isset($csvDatas->country_code)) {
                                $country_code = $csvDatas->country_code;
                                if($country_code != '') {
                                    $pos = strpos($country_code, '+');
                                    if(!$pos){
                                        $country_code ='+'.$country_code;
                                    }
                                }   
                                $userdetail->country_code   = $country_code;
                            } else {
                                $userdetail->country_code   = '+1';
                            }
                            if($userdetail->save()) {
								$zaiperenv = env('ZAIPER_ENV','local');
								if($zaiperenv == 'live') {
									$zapierData = array();
									$zapierData['type'] 	= 'Boat Owner';
									$zapierData['id'] 		= $userid;
									$zapierData['email'] 	= $csvDatas->email;
									$zapierData['firstname']= $csvDatas->firstname;
									$zapierData['lastname'] = $csvDatas->lastname;
									$zapierData['contact'] 	= '+1'.$csvDatas->contactnumber;
									$zapierData['address'] 	= $csvDatas->address;
									$zapierData['city'] 	= $csvDatas->city;
									$zapierData['state'] 	= $csvDatas->state;
									$zapierData['country'] 	= 'United States';
									$zapierData['zipcode'] 	= $csvDatas->zipcode;
									$this->sendAccountCreateZapier($zapierData);
								}
                                $insertIds[] = $userid;
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
            ImportUsers::dispatch($csvData,$adminId,'regular');
            return response()->json(['success' => true,'maxrecord' => true], $this->successStatus);
        }
	}

    // search regular user //
    // public function searchUsers(Request $request) {
    //     $searchby = request('searchby');
    //     $usersdata = DB::table('auths')
    //         ->join('userdetails', 'auths.id', '=', 'userdetails.authid')
    //         ->where('auths.usertype', '=', 'regular')
    //         ->where(function($query) use ($searchby) {
    //             $query->where('auths.email', 'like', '%'.$searchby.'%')
    //             ->orWhere('userdetails.firstname', 'like', '%'.$searchby.'%')
    //             ->orWhere('userdetails.lastname', 'like', '%'.$searchby.'%');
    //         })
    //         ->select('auths.email','auths.usertype','auths.status', 'userdetails.*')
    //         ->get();
    //     if(!empty($usersdata)) {
    //         return response()->json(['success' => 'success','data' => $usersdata], $this->successStatus);
    //     } else {
    //         return response()->json(['error'=>'networkerror'], 401); 
    //     }
    // }
   
    // add new user //
    public function addUser(Request $request) {
        $validate = Validator::make($request->all(), [
			'firstname' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            // 'county' => 'required',
            'zipcode' => 'required',
            'mobile' => 'required',
            'email' => 'bail|required|E-mail',
			'password' => 'required',
			'confirm' => 'required|same:password',
		]);
        if ($validate->fails()) {
           // print_r($validate);
            return response()->json(['error'=>'validationError'], 401); 
        }
        $auth	= new Auth; 
        $userid = 0;
        $newsletter = (!empty(request('newsletter')) && (request('newsletter') == 'true')) ? '1':'0';
        $auth->email = strtolower(request('email'));
        $auth->password = Hash::make(request('password'));
        $auth->usertype = 'regular';
        $auth->ipaddress = $this->getIp();
        $auth->status = 'active';
        $auth->stepscompleted ='2'; 
        $auth->addedby=1;
        $auth->newsletter = $newsletter;
        $auth->is_activated = '1';
        
        if($auth->save()) {
			$userid = $auth->id;
        } 
        if($userid) {
            $address = request('address');
            $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
            $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];

            $userImage = request('profile_img');
            $userdetail	= new Userdetail; 
            $userdetail->authid  = $userid;
            $userdetail->firstname  = request('firstname');
            $userdetail->lastname   = request('lastname');
            $userdetail->city       = request('city');
            $userdetail->state      = request('state');
            $userdetail->country    = request('country');
            // $userdetail->county    = request('county');
            $userdetail->address    = ((isset($address) && $address !='') ? request('address'): NULL);
            $userdetail->zipcode    = request('zipcode');
            $userdetail->mobile     = request('mobile');
            $userdetail->status     = 'active';
            if(isset($userImage) && $userImage !='') {
                $userdetail->profile_image     = request('profile_img'); 
            } else {
                $userdetail->profile_image     = NULL; 
            }
            $userdetail->longitude  = $longitude;
            $userdetail->latitude   = $latitude;
            $country_code = request('country_code');
            if($country_code != '') {
                $pos = strpos($country_code, '+');
                if(!$pos){
                    $country_code ='+'.$country_code;
                }
            }   
            $userdetail->country_code   = $country_code;
            if($userdetail->save()) {
                $emailArr = [];
                $emailArr['firstname'] = request('firstname');
                $emailArr['lastname'] = request('lastname');
                $emailArr['to_email'] = request('email');
                $emailArr['password'] = request('password');
                //Send account created email notification
                $zaiperenv = env('ZAIPER_ENV','local');
				if($zaiperenv == 'live') {
					$zapierData = array();
					$zapierData['type'] 	= 'Boat Owner';
					$zapierData['id'] 		= $userid;
					$zapierData['email'] 	= request('email');
					$zapierData['firstname']= request('lastname');
					$zapierData['lastname'] = request('firstname');
					$zapierData['contact'] 	= $country_code.request('mobile');
					$zapierData['address'] 	= request('address');
					$zapierData['city'] 	= request('city');
					$zapierData['state'] 	= request('state');
					$zapierData['country'] 	= request('country');
					$zapierData['zipcode'] 	= request('zipcode');
					$this->sendAccountCreateZapier($zapierData);
				}
                $status = $this->sendEmailNotification($emailArr,'user_added_by_admin');
                if($status != 'sent') {
                    return array('status' =>'emailsentfail');
                }
                return response()->json(['success' => 'success','userid' => $userid], $this->successStatus);
			} else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    // change user status //
    public function changeStatus(Request $request) {
        $status = request('status');
        $userid = (int)request('id');
        $updated = 0;
        if(!empty($userid) && $userid > 0 && !empty($status)) {
            if($status == 'active') {
                $updated = Auth::where('id', '=', $userid)->where('usertype', '=', 'regular')->update(['status' => 'suspended']);
                if($updated) {
                    $updatedUser = Userdetail::where('authid', '=', $userid)->update(['status' => 'suspended']);
                     if($updatedUser) {
                        return response()->json(['success' => 'success','userid' => $userid], $this->successStatus);
                    } else {
                        return response()->json(['error'=>'networkerror'], 401); 
                    }
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            } else if ($status == 'suspended' || $status == 'pending') {
                $updated = Auth::where('id', '=', $userid)->where('usertype', '=', 'regular')->update(['status' => 'active']);
                if($updated) {
                    $updatedUser = Userdetail::where('authid', '=', $userid)->update(['status' => 'active']);
                    if($updatedUser) {
                        return response()->json(['success' => 'success','userid' => $userid], $this->successStatus);
                    } else {
                        return response()->json(['error'=>'networkerror'], 401);
                    }
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            }
        }
    }

    // delete regular user //
    public function deleteUser(Request $request) {
        $userid = (int)request('id');
        $updated = 0;
        if(!empty($userid) && $userid > 0 ) {
            $updated = Auth::where('id', '=', $userid)->where('usertype', '=', 'regular')->update(['status' => 'deleted']);
            if($updated) {
                $to_email = Auth::select('email')->where('id', '=', $userid)->where('usertype', '=', 'regular')->get();
                $emailArr = [];
                $emailArr['to_email'] = $to_email[0]['email'];
                $status = $this->sendEmailNotification($emailArr,'user_deleted');
                if($status != 'sent') {
                    return array('status' =>'emailsentfail');
                }
                return response()->json(['success' => 'success','userid' => $userid], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }
    }
    
    // edit new user //
    public function editUser(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
			'firstname' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            // 'county' => 'required',
            'zipcode' => 'required',
            'mobile' => 'required',
            // 'email' => 'bail|required|E-mail',
		]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $auth	= $detailArr = array(); 
        $updated = $detailUpdate = 0;
        $userid = request('id');
        // $auth['email'] = strtolower(request('email'));
        $auth['ipaddress'] =$this->getIp();
        $newsletter = (!empty(request('newsletter')) && (request('newsletter') == 'true')) ? '1':'0';
        $auth['newsletter'] =$newsletter;
        if(!empty($userid) && $userid > 0) {
            $updated =  Auth::where('id', '=', (int)$userid)->where('usertype', '=', 'regular')->update($auth);
            if($updated) {
                $address = request('address');
                $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
                $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
                $output = $this->getGeoLocation($location); //Get Location from location Trait
                $longitude = $output['longitude'];
                $latitude = $output['latitude'];

                $userImage =request('profile_img');
                $address =request('address');
                $detailArr['firstname'] = request('firstname');
                $detailArr['lastname'] = request('lastname');
                $detailArr['city'] = request('city');
                $detailArr['state'] = request('state');
                $detailArr['country'] = request('country');
                // $detailArr['county'] = request('county');
                $detailArr['address'] = ((isset($address) && $address !='') ? request('address'): NULL);
                $detailArr['zipcode'] = request('zipcode');
                $detailArr['mobile'] = request('mobile');
                if(isset($userImage) && $userImage !='') {
                    $detailArr['profile_image']     = request('profile_img'); 
                } else {
                    $detailArr['profile_image']     = NULL; 
                }
                $detailArr['longitude'] = $longitude;
                $detailArr['latitude']  = $latitude;
                $country_code = request('country_code');
                if($country_code != '') {
                    $pos = strpos($country_code, '+');
                    if(!$pos){
                        $country_code ='+'.$country_code;
                    }
                }   
                $detailArr['country_code']   = $country_code;
                $detailUpdate =  Userdetail::where('authid', '=', (int)$userid)->update($detailArr);
                if($detailUpdate) {
					$zaiperenv = env('ZAIPER_ENV','local');
					if($zaiperenv == 'live') {
						$zapierData = array();
						$zapierData['type'] 	= 'Boat Owner';
						$zapierData['id'] 		= $userid;
						$authEmailData = Auth::where('id',(int)$userid)->get();
						$zapierData['email'] 	= $authEmailData[0]->email;
						$zapierData['firstname']= request('lastname');
						$zapierData['lastname'] = request('firstname');
						$zapierData['contact'] 	= $country_code.request('mobile');
						$zapierData['address'] 	= request('address');
						$zapierData['city'] 	= request('city');
						$zapierData['state'] 	= request('state');
						$zapierData['country'] 	= request('country');
						$zapierData['zipcode'] 	= request('zipcode');
						$this->sendAccountCreateZapier($zapierData);
					}
                    return response()->json(['success' => 'success','userid' => $userid], $this->successStatus);
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

    // get user details //
    public function getUserDetail(Request $request) {
        $userid = request('id');
        if(!empty($userid) && $userid > 0) {
            $usersdata = DB::table('auths')
            ->leftJoin('userdetails', 'auths.id', '=', 'userdetails.authid')
            ->where('auths.id', '=', (int)$userid)
            ->where('auths.usertype', '=', 'regular')
            ->select('auths.email','auths.usertype','auths.is_social','auths.provider','auths.status','auths.newsletter', 'userdetails.*')
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

    // check user exist //
    public function checkEmail(Request $request) {
        $userEmail = strtolower(request('email'));
        $id = request('id');
        $success = false;
        if(!empty($userEmail) && $userEmail != '' ) {
            $query = Auth::where(function ($query) use ($userEmail) {
										$query->where('email', '=', $userEmail)
										->orWhere('requested_email', '=', $userEmail);
									});
            if(isset($id) && !empty($id)&& $id!= '') {
                $query->where('id','!=',(int)$id);
            }
            $count = $query->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
            $query2 = dummy_registration::where('email', '=', $userEmail);
            $count2 = $query2->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
            $count = $count + $count2;
            // $query2 = Claimed_business::where('email', '=', $userEmail);
            // $count2 = $query2->where('status', '=', 'pending')->count();
            // $count = $count + $count2;
            if(!empty($count) && $count > 0) {
                $success = true;
            } else {
                $success = false; 
            }
        }
        return response()->json(['success' => $success], $this->successStatus);
    }

    // Get all countries
    public function getAllCountries(Request $request) {
       // $countries = CountryState::getCountries();
        $countries = array('US'=>'United States');
        $formated = [];
        if(is_array($countries) && count($countries)) {
            foreach ($countries as $key => $value) {
                $formated[] = ['value' => $key, 'viewValue' => $value];
            }
            $success = true;
        } else {
            $success = false;    
        }
        return response()->json(['success' => $success, 'countries' => $formated], $this->successStatus);
    }

    // Get all States
    public function  getAllStates() {
        $countryCode = request('countrycode');
        $states = CountryState::getStates($countryCode);
        $formated = [];
        if(is_array($states) && count($states)) {
            foreach ($states as $key => $value) {
                $formated[] = ['value' => $key, 'viewValue' => $value];
            }
            $success = true;
        } else {
            $success = false;    
        }
        return response()->json(['success' => $success, 'states' => $formated ], $this->successStatus);
    }   

    // Get all countries
    public function getAllCountriesDetail(Request $request) {
        $countries = CountryState::getCountries();
        $formated = [];
        if(is_array($countries) && count($countries)) {
            $success = true;
        } else {
            $success = false;    
        }
        return response()->json(['success' => $success, 'countries' => $formated], $this->successStatus);
    }

    // Get all States
    public function  getAllStatesDetail() {
        $countryCode = request('countrycode');
        $states = CountryState::getStates($countryCode);
        $formated = [];
        if(is_array($states) && count($states)) {
            $success = true;
        } else {
            $success = false;    
        }
        return response()->json(['success' => $success, 'states' => $formated ], $this->successStatus);
    }  
    
    public function updateuseraccount(Request $request) {
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
            $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'regular')->update($auth);
            if($updated) {
                if($emailUpdates && !$passwordUpdate) {
                    $emailArr = []; 
                    $emailArrnew = [];
                    $userDataDetail  =  Userdetail::where('authid',(int)$authid)->where('status','!=','deleted')->get();
                    if(!empty($userDataDetail) && count($userDataDetail) > 0 && $OldEmail != '' ) {
                        $emailArr['firstname'] = $userDataDetail[0]->firstname;
                        $emailArr['lastname'] = $userDataDetail[0]->lastname;
                        $emailArr['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                        $emailArr['to_email'] = $OldEmail;
                        $zaiperenv = env('ZAIPER_ENV','local');
						if($zaiperenv == 'live') {
							$this->sendAccountCreateZapierbyID($authid,'regular',request('email'));
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
                } else if ($passwordUpdate && !$emailUpdates) {
                    $emailArr = []; 
                    $userDataDetail  =  Userdetail::where('authid',(int)$authid)->where('status','!=','deleted')->get();
                    if(!empty($userDataDetail) && count($userDataDetail) > 0 ) {
                        $emailArr['firstname'] = $userDataDetail[0]->firstname;
                        $emailArr['lastname'] = $userDataDetail[0]->lastname;
                        $emailArr['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                        $emailArr['to_email'] = $userDatas[0]->email;
                        $emailArr['password'] = $password;
                        $status1 = $this->sendEmailNotification($emailArr,'admin_passwordchange_notification');
                        return response()->json(['success' => true,'emailSent'=>true], $this->successStatus);
                    }
                } else if ($passwordUpdate && $emailUpdates) {
                    $emailArr = []; $emailArrnew = [];
                    $userDataDetail  =  Userdetail::where('authid',(int)$authid)->where('status','!=','deleted')->get();
                    if(!empty($userDataDetail) && count($userDataDetail) > 0 && $OldEmail != '' ) {
                        $emailArr['firstname'] = $userDataDetail[0]->firstname;
                        $emailArr['lastname'] = $userDataDetail[0]->lastname;
                        $emailArr['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                        $emailArr['to_email'] = $OldEmail;
                        $zaiperenv = env('ZAIPER_ENV','local');
						if($zaiperenv == 'live') {
							$this->sendAccountCreateZapierbyID($authid,'regular',request('email'));
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



     // update password //
    public function updateadminpassword(Request $request) {
        $validate = Validator::make($request->all(), [
            'authid' => 'required',
            'password' => 'required',
            'confirm' => 'required|same:password',
            'oldpassword' => 'required',
            'email' => 'required',
        ]);
   
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $auth   = array(); 
        $updated = 0;
        $authid = request('authid');
        $oldpassword =request('oldpassword');
        $email =request('email');
        $userDetail =  DB::table('auths')->where('id', '=', (int)$authid)->where('usertype', '=', 'admin')->where('status', '!=', 'deleted')->first();
        if(!empty($userDetail)) {
            if(!Hash::check($oldpassword,$userDetail->password)) {
                return response()->json(['error'=>'notmatch'], 401);
            } else {
                $auth['password'] =Hash::make(request('password'));
                if(!empty($authid) && $authid > 0) {
                    $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'admin')->update($auth);
                    if($updated) {
                        return response()->json(['success' => true], $this->successStatus);
                    } else {
                        return response()->json(['error'=>'networkerror'], 401);
                    }
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }
    
     // check email exist //
    public function checkemailexistemailadmin(Request $request) {
		$userEmail = strtolower(request('email'));
        $encryptId = request('id');
        $id = (int)$encryptId;
        $success = false;
        if(!empty($id) && (int)$id && !empty($userEmail) && $userEmail != '') {
			$query = Auth::where(function ($query) use ($userEmail) {
										$query->where('email', '=', $userEmail)
										->orWhere('requested_email', '=', $userEmail);
									})->where('id' ,'!=', (int)$id);
            $count = $query->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
            $query2 = dummy_registration::where('email', '=', $userEmail);
            $count2 = $query2->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
            $count = $count + $count2;
            if(!empty($count) && $count > 0) {
                $success = true;
            } else {
                $success = false; 
            }
		} 
		return response()->json(['success' => $success], $this->successStatus);
    } 
        // get admn profile det
    public function getAdminDetailProf(Request $request) {
        $validate = Validator::make($request->all(), [
            'authid' => 'required'
        ]);

        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $auth   = array(); 
        $updated = 0;
        $authid = request('authid');
        $userDetail =  DB::table('auths')->select('firstname_admin','lastname_admin','email','contact_email')
            ->where('id', '=', (int)$authid)
            ->where('usertype', '=', 'admin')
            ->where('status', '!=', 'deleted')->first();
        if(!empty($userDetail)) {
            return response()->json(['success' => true,'data' => $userDetail], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    } 

    // update admin profile
    public function updateadminprofile(Request $request) {
         $validate = Validator::make($request->all(), [
            'authid' => 'required',
            'firstname' => 'required',
            'lastname' => 'required',
        ]);

        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $auth   = array(); 
        $updated = 0;
        $authid = request('authid');
        $firstname =request('firstname');
        $lastname =request('lastname');
        $contactemail = request('contactemail');
        if(empty($contactemail)) {
            $contactemail = NULL;
        }

        $userDetail =  DB::table('auths')->where('id', '=', (int)$authid)->where('usertype', '=', 'admin')->where('status', '!=', 'deleted')->update(['firstname_admin'=> $firstname,'lastname_admin' => $lastname , 'contact_email' => $contactemail]);
        if(!empty($userDetail)) {
            return response()->json(['success' => true], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);
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
