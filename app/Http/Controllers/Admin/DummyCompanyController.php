<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Auth;
use App\Service;
use DB;
use App\Companydetail;
use App\dummy_registration;
use App\Dummy_geolocation;
use App\Dummy_paymenthistory;
use App\Dummy_registration_backup;
use App\Rejected_registration;
use App\Rejected_geolocation;
use App\Rejected_paymenthistory;
use App\Geolocation;
use App\Paymenthistory;
use App\Boat_Engine_Companies;
use App\Category;
use Illuminate\Support\Facades\Hash;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Validator;
use Stripe\Error\Card;
use Cartalyst\Stripe\Stripe;
use App\Emailverification;
use Exception;
use App\User;
use App\Dictionary;
use Carbon;
use App\Http\Traits\LocationTrait;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendNotification;
use App\Http\Traits\NotificationTrait;
use Braintree_ClientToken;
use Braintree_Transaction;
use Braintree_Customer;
use Braintree_Subscription;
use Braintree_PaymentMethod;
use App\Http\Traits\ImportTrait;
use App\Jobs\ImportUsers;
class DummyCompanyController extends Controller
{

    public $successStatus = 200;
    use LocationTrait;
    use NotificationTrait;
    use ImportTrait;
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
    // get all regular users //
    public function index(Request $request) {
        $searchString = request('searchString');
        $page = request('page');
        $orderBy = request('order');
        $reverse = request('reverse');
        $order = ($reverse == 'false')?'ASC':'DESC';
		
        $statefilter = request('state');
        $cityfilter = request('city');
        $zipcodefilter = request('zipcode');
        $adminassign = request('admin');
        $assign = request('assign');
        $adminid = request('adminid');
        $userid = 0;
        if(!empty($adminid)) {
			$userid =(int)$adminid;
		}

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
            $whereCompany = "LOWER(companydetails.name) LIKE '%".$searchString."%'";       
             
        }
        
        if(!empty($statefilter) && $statefilter != 'All' && $statefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " companydetails.state = '".$statefilter."' ";
			} else {
				$whereCompany .= " AND companydetails.state = '".$statefilter."' ";
			}
		}
        if(!empty($cityfilter) && $cityfilter != 'All' && $cityfilter != '') {
			if($whereCompany == '') {
				$whereCompany = " companydetails.city = '".$cityfilter."' ";
			} else {
				$whereCompany .= " AND companydetails.city = '".$cityfilter."' ";
			}
		}
        if(!empty($zipcodefilter) && $zipcodefilter != 'All' && $zipcodefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " companydetails.zipcode = '".$zipcodefilter."' ";
			} else {
				$whereCompany .= " AND companydetails.zipcode = '".$zipcodefilter."' ";
			}
		}
		
		if(!empty($adminassign) && $adminassign != '0' && $adminassign != '-1' && $adminassign != '') {
			if($whereCompany == '') {
				$whereCompany = " companydetails.assign_admin = ".(int)$adminassign." ";
			} else {
				$whereCompany .= " AND companydetails.assign_admin = ".(int)$adminassign." ";
			}
		}
		
		if(!empty($assign) && $assign != 'all' && $assign != '') {
			if($whereCompany == '') {
				$whereCompany = " companydetails.assign_admin = '".$userid."' ";
			} else {
				$whereCompany .= " AND companydetails.assign_admin = '".$userid."' ";
			}
		}
		
        $query = DB::table('auths')
            ->Join('companydetails', 'auths.id', '=', 'companydetails.authid')
            ->where('auths.usertype', '=', 'company')
            ->where('auths.status', '!=', 'deleted')
            ->where('companydetails.accounttype','=','dummy');
        if($whereCompany != '') {
            $query = $query->whereRaw($whereCompany);
        }
        $totalrecords = $query->select('auths.email','auths.stepscompleted','auths.id as userauthid','auths.usertype','auths.status', 'companydetails.*')
            ->count();

        $query2 = DB::table('auths')
            ->Join('companydetails', 'auths.id', '=', 'companydetails.authid')
            ->where('auths.usertype', '=', 'company')
            ->where('auths.status', '!=', 'deleted')
            ->where('companydetails.accounttype','=','dummy');
        if($whereCompany != ''){
            $query2 = $query2->whereRaw($whereCompany);
        }
        $query2 = $query2->select('auths.email','auths.stepscompleted','auths.id as userauthid','auths.usertype','auths.status', 'companydetails.*');
            if($orderBy == 'data.created_at') {
                $query2 = $query2->orderBy('auths.created_at', 'DESC');
            } else if($orderBy == 'name') {
                $query2 = $query2->orderBy('companydetails.name', $order);
            } else if($orderBy == 'email') {
                $query2 = $query2->orderBy('auths.email', $order);    
            }  else if($orderBy == 'created_at') {
				$query2 = $query2->orderBy('companydetails.created_at', $order);
			}
        $usersdata = $query2->skip($offset)
            ->take($limit)
            ->get();
        if(!empty($usersdata)) {
            return response()->json(['success' => true,'data' => $usersdata,'totalrecords' => $totalrecords], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    
    // export dummy business data
    public function exportDummyCompanyData() {
        $limit = request('limit');
        $offset = request('offset');
        $searchString = request('searchString');
        $whereCompany = '';
        $order = request('order');
        $reverse = request('reverse');
        
        $statefilter = request('state');
        $cityfilter = request('city');
        $zipcodefilter = request('zipcode');
        $adminassign = request('admin');
        $issuperadmin = request('issuperadmin');
         $assign = request('assign');
        $adminid = request('adminid');
        $userid = 0;
        if(!empty($adminid)) {
			$userid =(int)$adminid;
		}
        $selectForAdmin = '';
        $adminJoin = '';
        //if(!empty($issuperadmin) && $issuperadmin == 'true') {
			$selectForAdmin = ", CONCAT(COALESCE(adminauth.firstname_admin,'-'), ' ', COALESCE(adminauth.lastname_admin,'')) as username ";
			$adminJoin = "left join auths as adminauth ON adminauth.id = companydetails.assign_admin";
		//}
        //get DESC OR ASC  
        
        $orderBy = '';
        if($order == 'data.created_at') {
            $orderBy = "ORDER BY auths.created_at DESC";
        } else if($order == 'name') {
            $reverse = ($reverse == 'false')?'ASC':'DESC';
            $orderBy = "ORDER BY companydetails.name ".$reverse;
        } 

        if(!empty($searchString) && $searchString !='') {
            $searchString = strtolower($searchString);
            if($whereCompany == '') {
                    $whereCompany = "AND LOWER(companydetails.name) LIKE '%".$searchString."%'";      
            } else {
                $whereCompany .= " AND LOWER(companydetails.name) LIKE '%".$searchString."%'";
            }
        }
        
        if(!empty($statefilter) && $statefilter != 'All' && $statefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " AND companydetails.state = '".$statefilter."' ";
			} else {
				$whereCompany .= " AND companydetails.state = '".$statefilter."' ";
			}
		}
        if(!empty($cityfilter) && $cityfilter != 'All' && $cityfilter != '') {
			if($whereCompany == '') {
				$whereCompany = " AND companydetails.city = '".$cityfilter."' ";
			} else {
				$whereCompany .= " AND companydetails.city = '".$cityfilter."' ";
			}
		}
        if(!empty($zipcodefilter) && $zipcodefilter != 'All' && $zipcodefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " AND companydetails.zipcode = '".$zipcodefilter."' ";
			} else {
				$whereCompany .= " AND companydetails.zipcode = '".$zipcodefilter."' ";
			}
		}
		if(!empty($assign) && $assign != 'all' && $assign != '') {
			if($whereCompany == '') {
				$whereCompany = "AND  companydetails.assign_admin = '".(int)$userid."' ";
			} else {
				$whereCompany .= " AND companydetails.assign_admin = '".(int)$userid."' ";
			}
		}
		
		if(!empty($adminassign) && $adminassign != '0' && $adminassign != '-1' && $adminassign != '') {
			if($whereCompany == '') {
				$whereCompany = " AND companydetails.assign_admin = ".(int)$adminassign." ";
			} else {
				$whereCompany .= " AND companydetails.assign_admin = ".(int)$adminassign." ";
			}
		}

        $data = DB::select("SELECT companydetails.name,auths.email,COALESCE(companydetails.address,'-') as address,companydetails.city,companydetails.state,companydetails.zipcode,companydetails.websiteurl,companydetails.contactname,companydetails.contactmobile,companydetails.contactemail,companydetails.services,companydetails.about,companydetails.businessemail,companydetails.contact ".$selectForAdmin."  ,companydetails.boats_yachts_worked,companydetails.engines_worked FROM auths JOIN companydetails ON  auths.id = companydetails.authid ".$adminJoin."  WHERE auths.status != 'deleted' AND companydetails.accounttype = 'dummy' ".$whereCompany." ".$orderBy." LIMIT ".$limit." OFFSET ".$offset."");
        
        if(!empty($data)) {
			$boartAndYachtData = Boat_Engine_Companies::where(function($query) {
								$query->where('category', '=', 'boats')
								->orWhere('category', '=', 'yachts');})->where('status','=','1')->select('id', 'name')->get()->toArray();
			$engineData = Boat_Engine_Companies::where('category', '=', 'engines')->where('status','=','1')->select('id', 'name')->get()->toArray();
            $allservices = Service::where('status','=','1')->orWhere('category','=','11')->select('id', 'service as itemName','subcategory')->get()->toArray();
            $newallservices = [];
            foreach ($allservices as $val) {
                $newallservices[$val['id']]['itemName'] = $val['itemName'];
                $newallservices[$val['id']]['id'] = $val['id'];
                $newallservices[$val['id']]['subcategory'] = $val['subcategory'];
            }
            $allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
            $newallCategory = [];
            foreach ($allCategory as $val) {
                $newallCategory[$val['id']] = $val['categoryname'];
            }
            $boats_yachts_workedArr = [];
            foreach ($boartAndYachtData as $val) {
                $boats_yachts_workedArr[$val['id']] = $val['name'];
            }
            $engines_workedArr = [];
            foreach ($engineData as $val) {
                $engines_workedArr[$val['id']] = $val['name'];
            }
            foreach ($data as $key => $value) {
				$service = json_decode($value->services);
                $newService = '';
                $temCateArr = [];
                foreach ($service as $catId => $SerIds) {
                    
                    foreach ($SerIds as $sid => $sval) {
                        if(isset($newallservices[$sval])) {
                            if($newService != '') {
                                $newService .=  ', '.$newallservices[$sval]['itemName'].'('.$newallCategory[$catId].')';
                            } else {
                                $newService =  $newallservices[$sval]['itemName'].'('.$newallCategory[$catId].')';
                            }
                        }
                    }
                }
                unset($data[$key]->services);
                $data[$key]->services =  $newService;
                
                $boats_yachts_worked =  json_decode($value->boats_yachts_worked);
                $engines_worked =  json_decode($value->engines_worked);
                $tempBoarAndYachtWorked = '';
                if(!empty($boats_yachts_worked) && $boats_yachts_worked != null) {
					if(!empty($boats_yachts_worked->saved) && count($boats_yachts_worked->saved) > 0 ) {
						foreach ($boats_yachts_worked->saved as $vals) {
							if (array_key_exists($vals,$boats_yachts_workedArr)) {
								if($tempBoarAndYachtWorked != '') {
									$tempBoarAndYachtWorked .= ", ".$boats_yachts_workedArr[$vals];
								} else {
									$tempBoarAndYachtWorked .= $boats_yachts_workedArr[$vals];
								}
							}
						}
					}
					if(!empty($boats_yachts_worked->other) && count($boats_yachts_worked->other) > 0 ) {
						foreach ($boats_yachts_worked->other as $vals) {
							if($tempBoarAndYachtWorked != '') {
								$tempBoarAndYachtWorked .= ", ".$vals;
							} else {
								$tempBoarAndYachtWorked .= $vals;
							}
						}
					}
				}
				unset($data[$key]->boats_yachts_worked);
				if($tempBoarAndYachtWorked == '') {
					$data[$key]->boats_yachts_worked =  "-";
				} else {
					$data[$key]->boats_yachts_worked =  $tempBoarAndYachtWorked;
				}
                $tempBoarAndYachtWorked = '';
                
                $tempEngineWorked = '';
                if(!empty($engines_worked) && $engines_worked != null) {
					if(!empty($engines_worked->saved) && count($engines_worked->saved) > 0 ) {
						foreach ($engines_worked->saved as $vals) {
							if (array_key_exists($vals,$engines_workedArr)) {
								if($tempEngineWorked != '') {
									$tempEngineWorked .= ", ".$engines_workedArr[$vals];
								} else {
									$tempEngineWorked .= $engines_workedArr[$vals];
								}
							}
						}
					}
					if(!empty($engines_worked->other) && count($engines_worked->other) > 0 ) {
						foreach ($engines_worked->other as $vals) {
							if($tempEngineWorked != '') {
								$tempEngineWorked .= ", ".$vals;
							} else {
								$tempEngineWorked .= $vals;
							}
						}
					}
				}
				unset($data[$key]->engines_worked);
				if($tempEngineWorked == '') {
					$data[$key]->engines_worked =  "-";
				} else {
					$data[$key]->engines_worked =  $tempEngineWorked;
				}
                $tempEngineWorked = '';
            }
            return response()->json(['success' => true,'data' => $data], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

     // add company //
    public function addCompany(Request $request) {
        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'services' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            // 'county' => 'required',
            'zipcode' => 'required',
            'contact' => 'required'
        ]);

            
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $auth   = new Auth; 

        $authid = 0;
        $requestEmail = strtolower(request('email'));
        if(empty($requestEmail)) {
            $countemail = Companydetail::where('accounttype','=','dummy')->count();
            if(empty($countemail)) {
                $countemail = 0;
            }
            $countemail = $countemail+1;
            $requestEmail = 'marine_business'.$countemail.'@marinecentral.com';
        }
        
        $boatYachtJson = request('boatYachtworked');
		$emptyboatYachtworked = true;
		$boatYachtworkedArray  = array();
		$i = 0;
		$j = 0;
		if(!empty($boatYachtJson)) {
			$boatYachtworked = json_decode(request('boatYachtworked'));
			$checkBoat = [];
            foreach ($boatYachtworked as $val) {
                if($val && !in_array($val,$checkBoat)) {
                    $boatYachtworkedData = [];
                    $checkBoat[] = $val;
                    $boatYachtworkedData = Boat_Engine_Companies::whereRaw("lower(name) = '".strtolower($val)."'")->where(function($query) {
                            $query->where('category', '=', 'boats')
                            ->orWhere('category', '=', 'yachts');
                        })->where('status','1')->get();
                    if(!empty($boatYachtworkedData) && count($boatYachtworkedData) > 0) {
                        $boatYachtworkedArray['saved'][$i] = $boatYachtworkedData[0]->id;
                        $i++;
                    } else {
                        $boatYachtworkedArray['other'][$j] = strtolower($val);
                        $j++;
                    }
                    $emptyboatYachtworked = false;
                }
            }
		}
		$boatYachtObj = json_encode($boatYachtworkedArray);
		
		$engineJson = request('engineworked');
		$emptyengineworked = true;
		$engineworkedArray  = array();
		$i = 0;
		$j = 0;
		if(!empty($engineJson)) {
			$engineworked = json_decode(request('engineworked'));
			$checkEngine = [];
            foreach ($engineworked as $val) {
                if($val && !in_array($val,$checkEngine)) {
                    $engineworkedData = [];
                    $checkEngine[] = $val;
                    $engineworkedData = Boat_Engine_Companies::whereRaw("lower(name) = '".strtolower($val)."'")->where('category','engines')->where('status','1')->get();
                    if(!empty($engineworkedData) && count($engineworkedData) > 0) {
                        $engineworkedArray['saved'][$i] = $engineworkedData[0]->id;
                        $i++;
                    } else {
                        $engineworkedArray['other'][$j] = $val;
                        $j++;
                    }
                    $emptyengineworked = false;
                }
            }
		}
		$engineObj = json_encode($engineworkedArray);
		
        $auth->email = $requestEmail;
        $auth->password = Hash::make('business');
        $auth->usertype = 'company';
        $auth->ipaddress = $this->getIp();
        $auth->status = 'active';
        $auth->is_activated = '1';
        $auth->stepscompleted ='3'; 
        $auth->addedby =1; 
        $auth->accounttype = 'dummy';
        if($auth->save()) {
            $authid = $auth->id;
        } 
        if($authid) {
            $address = request('address');
            $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
            $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];
            $allservices = json_decode(request('allservices'));
            $company_name = request('name');
            $company_name_new  = preg_replace('/[^a-zA-Z0-9_ -]/s','',$company_name);
            $array = explode(" ",$company_name_new);
            if(is_array($array)) {
                $slug = implode("-",$array);       
            }
            $slug1 = '';
            $array = explode(" ",request('city'));
            if(is_array($array)) {
                $slug1 = implode("-",$array);       
            }
            $slug = strtolower($slug.'-'.$slug1);
             
            $realSlug = $slug;
            $countSlugs = 0;
            $validSlug = false;
            for($i = 0 ; $validSlug != true ; $i++) {
                $checkSlug = Companydetail::where('actualslug','=',strtolower($slug))->count();
                $checkSlugEdit = Companydetail::where('slug','=',strtolower($slug))->count();
                if($checkSlug) {
                    $countSlugs = $countSlugs +$checkSlug;
                    $slug = $realSlug.'-'.($countSlugs);
                } else if($checkSlugEdit) {
                    $countSlugs = $countSlugs + 1;
                    $slug = $realSlug.'-'.($countSlugs);
                } else {
                    $validSlug = true;
                } 
            }
            
            $adminid = request('adminid');
			$admin_noteArr = [];
            $issuperadmin = request('issuperadmin');
            $adminassign = request('admin');
			$adminnote = request('adminnote');
			if(!empty($adminid) && $adminid > 0 && !empty($adminnote) && $adminnote !='' ) {
				$admin_noteArr[0]['id'] = (int)$adminid;
				$admin_noteArr[0]['note'] = $adminnote;
				$admin_noteArr[0]['date'] = date('Y-m-d H:i:s');
			}
            $requestEmailBus =  request('businessemail');
            $address = request('address');
            $websiteurl = request('websiteurl');
            $about = request('about');
            $contactname  = request('contactname');
            $contactmobile  = request('contactmobile');
            $contactemail  = request('contactemail');
            $companydetail  = new Companydetail; 
            $companydetail->authid  = $authid;
            $companydetail->name  = request('name');
            $companydetail->slug = strtolower($slug);
            $companydetail->services   = request('services');
            if(!empty($issuperadmin) && $issuperadmin == 'true') {
				$companydetail->assign_admin    = ((isset($adminassign) && $adminassign !='') ? (int)$adminassign: NULL);
			}
			$companydetail->admin_note    = (!empty($admin_noteArr) ?  json_encode($admin_noteArr): NULL);
            $companydetail->businessemail    = ((isset($requestEmailBus) && $requestEmailBus !='') ? request('businessemail'): NULL);
            $companydetail->address    = ((isset($address) && $address !='') ? request('address'): NULL);
            $companydetail->websiteurl    = ((isset($websiteurl) && $websiteurl !='') ? request('websiteurl'): NULL);
            $companydetail->allservices =  ((isset($allservices) && $allservices !='') ? json_encode($allservices,JSON_UNESCAPED_SLASHES): NULL);
            $companydetail->city       = request('city');
            $companydetail->state      = request('state');
            $companydetail->country    = request('country');
            // $companydetail->county    = request('county');
            $companydetail->about    = ((isset($about) && $about !='') ? request('about'): NULL);
            $companydetail->zipcode    = request('zipcode');
            $companydetail->contact    = request('contact');
            $companydetail->longitude  = $longitude;
            $companydetail->latitude   = $latitude;
            $companydetail->actualslug   = $realSlug;
            $companydetail->contactname    = ((isset($contactname) && $contactname !='') ? request('contactname'): NULL);
            $companydetail->contactmobile    = ((isset($contactmobile) && $contactmobile !='') ? request('contactmobile'): NULL);
            $companydetail->contactemail    = ((isset($contactemail) && $contactemail !='') ? request('contactemail'): NULL);
            $companydetail->accounttype   = 'dummy';
            // $companydetail->country_code   = request('country_code');
            $country_code = request('country_code');
                if($country_code != '') {
                    $pos = strpos($country_code, '+');
                    if(!$pos){
                        $country_code ='+'.$country_code;
                    }
                }   
            $companydetail->country_code = $country_code;
            $companydetail->boats_yachts_worked    = ($emptyboatYachtworked) ? NULL : $boatYachtObj;
            $companydetail->engines_worked    = ($emptyengineworked) ? NULL : $engineObj;
            if($companydetail->save()) {
				$DictionaryData = new Dictionary;
				$DictionaryData->authid = $authid;
				$DictionaryData->word = request('name');
				if($DictionaryData->save()) {
				}
                $usersdata['authid'] = $authid;
                // $geolocation = new Geolocation;
                // $city    = request('city');
                // $state   = request('state');
                // $zipcode = request('zipcode');
                // $country = request('country');
                // $addressGeo = ((isset($address) && $address !='') ? $address: '').' '.$city.' '.$zipcode.' '.$state.' ,'.$country;
                // $output = $this->getGeoLocation($addressGeo); //Get Location from location Trait
                // $longitude = $output['longitude'];
                // $latitude = $output['latitude'];
                // $geolocation->authid = $authid;
                // $geolocation->city = $city;
                // $geolocation->zipcode = $zipcode;
                // $geolocation->country = $country;
                // $geolocation->county = request('county');
                // $geolocation->state = $state;
                // $geolocation->address    = ((isset($address) && $address !='') ? request('address'): NULL);
                // $geolocation->longitude = $longitude;
                // $geolocation->latitude = $latitude;
                // $geolocation->status = '1';
                
                // if($geolocation->save()) {
                $plandata = DB::table('subscriptionplans')->where('isadminplan', '=', '1')->where('status', '=', 'active')->first();
                if(empty($plandata)) {
                    return response()->json(['error'=>'networkerror'], 401);
                }
                $subplan = $plandata->id;
                $nextDate = date('Y-m-d 00:00:00', strtotime("+20 years", strtotime(date('Y-m-d H:i:s'))));
                $statusCompany = Companydetail::where('authid', (int)$authid)->update(['subscriptiontype' => 'manual','nextpaymentdate' => $nextDate, 'paymentplan' => (int)($subplan),'next_paymentplan' => (int)($subplan),'plansubtype' => 'free','status' => 'active']);
                if($statusCompany) {
                    return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
                // } else {
                //     return response()->json(['error'=>'networkerror'], 401);
                // }  
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }


    // change company status //
    public function changeStatus(Request $request) {
        $status = request('status');
        $authid = (int)request('id');
        $updated = 0;
        if(!empty($authid) && $authid > 0 && !empty($status)) {
            if($status == 'active') {
                $updated = Auth::where('id', '=', $authid)->where('usertype', '=', 'company')->update(['status' => 'suspended']);
                if($updated) {
                    return response()->json(['success' => true,'authid' => $authid], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            } else if ($status == 'suspended' || $status == 'pending') {
                $updated = Auth::where('id', '=', $authid)->where('usertype', '=', 'company')->update(['status' => 'active']);
                if($updated) {
                    return response()->json(['success' => true,'authid' => $authid], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            }
        }
    }

    // delete company //
    public function deleteCompany(Request $request) {
        $authid = (int)request('id');
        $updated = 0;
        if(!empty($authid) && $authid > 0 ) {
            $updated = Auth::where('id', '=', $authid)->where('usertype', '=', 'company')->update(['status' => 'deleted']);
            $updatedCompany = Companydetail::where('authid', '=', $authid)->update(['status' => 'deleted']);
            $CompanyDetail = dummy_registration::where('authid', '=', $authid)->get();
            if(!empty($CompanyDetail)  && count($CompanyDetail) > 0) {
                foreach($CompanyDetail as $CompanyDetails) {
                    $subid = $CompanyDetails->subscription_id;
                    if($subid != null) {
                        try{
                            $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
                            $subscription = $stripe->subscriptions()->cancel($CompanyDetails->customer_id, $subid);
                        }   catch(\Cartalyst\Stripe\Exception\NotFoundException $e) {
                                 
                        }   catch (Exception $e) {
                            return response()->json(['error'=>'networkerror'], 401);   
                        }
                    }
                }
            }
            if($updated) {
                return response()->json(['success' => true,'authid' => $authid], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
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
    
     // edit new user //
    public function editCompany(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            'services' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            // 'county' => 'required',
            'zipcode' => 'required',
            'contact' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $auth   = $detailArr = array(); 
        $updated = $detailUpdate = 0;
        $authid = request('id');
        $requestEmail = strtolower(request('email'));
        if(empty($requestEmail)) {
            $countemail = Companydetail::where('accounttype','=','dummy')->count();
            if(empty($countemail)) {
                $countemail = 0;
            }
            $countemail = $countemail+1;
            $requestEmail = 'marine_business'.$countemail.'@marinecentral.com';
        }
        $auth['email'] = $requestEmail;
        $auth['ipaddress'] =$this->getIp();
        $auth['stepscompleted'] ='3';
        $boatYachtJson = request('boatYachtworked');
        $emptyboatYachtworked = true;
        $boatYachtworkedArray  = array();
        $i = 0;
        $j = 0;
        if(!empty($boatYachtJson)) {
            $boatYachtworked = json_decode(request('boatYachtworked'));
            $checkBoat = [];
            foreach ($boatYachtworked as $val) {
                if($val && !in_array($val,$checkBoat)) {
                    $boatYachtworkedData = [];
                    $checkBoat[] = $val;
                    $boatYachtworkedData = Boat_Engine_Companies::whereRaw("lower(name) = '".strtolower($val)."'")->where(function($query) {
                            $query->where('category', '=', 'boats')
                            ->orWhere('category', '=', 'yachts');
                        })->where('status','1')->get();
                    if(!empty($boatYachtworkedData) && count($boatYachtworkedData) > 0) {
                        $boatYachtworkedArray['saved'][$i] = $boatYachtworkedData[0]->id;
                        $i++;
                    } else {
                        $boatYachtworkedArray['other'][$j] = strtolower($val);
                        $j++;
                    }
                    $emptyboatYachtworked = false;
                }
            }
        }
        $boatYachtObj = json_encode($boatYachtworkedArray);
        
        $engineJson = request('engineworked');
        $emptyengineworked = true;
        $engineworkedArray  = array();
        $i = 0;
        $j = 0;
        $checkEngine = [];
        if(!empty($engineJson)) {
            $engineworked = json_decode(request('engineworked'));
            foreach ($engineworked as $val) {
                if($val && !in_array($val,$checkBoat)) {
                    $engineworkedData = [];
                    $checkEngine[] = $val;
                    $engineworkedData = Boat_Engine_Companies::whereRaw("lower(name) = '".strtolower($val)."'")->where('category','engines')->where('status','1')->get();
                    if(!empty($engineworkedData) && count($engineworkedData) > 0) {
                        $engineworkedArray['saved'][$i] = $engineworkedData[0]->id;
                        $i++;
                    } else {
                        $engineworkedArray['other'][$j] = $val;
                        $j++;
                    }
                    $emptyengineworked = false;
                }
            }
        }
        $engineObj = json_encode($engineworkedArray);  
        if(!empty($authid) && $authid > 0) {
            $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'company')->update($auth);
            if($updated) {
                $address = request('address');
                $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
            
                $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
                $output = $this->getGeoLocation($location); //Get Location from location Trait
                $longitude = $output['longitude'];
                $latitude = $output['latitude'];
                $company_name = request('name');
                $allservices = json_decode(request('allservices'));
                $company_name_new  = preg_replace('/[^a-zA-Z0-9_ -]/s','',$company_name);
                $array = explode(" ",$company_name_new);

                if(is_array($array)) {
                    $slug = implode("-",$array);       
                }

                $slug1 = '';
                $array = explode(" ",request('city'));
                if(is_array($array)) {
                    $slug1 = implode("-",$array);       
                }
                $slug = strtolower($slug.'-'.$slug1);
                $realSlug = strtolower($slug);
                $countSlugs = 0;
                $validSlug = false;
                for($i = 0 ; $validSlug != true ; $i++) {
                    $checkSlug = Companydetail::where('actualslug','=',strtolower($slug))->where('authid', '!=', (int)$authid)->count();
                    $checkSlugEdit = Companydetail::where('slug','=',strtolower($slug))->where('authid', '!=', (int)$authid)->count();
                    if($checkSlug) {
                        $countSlugs = $countSlugs +$checkSlug;
                        $slug = $realSlug.'-'.($countSlugs);
                    } else if($checkSlugEdit) {
                        $countSlugs = $countSlugs + 1;
                        $slug = $realSlug.'-'.($countSlugs);
                    } else {
                        $validSlug = true;
                    } 
                }
                $address = request('address');
                $websiteurl = request('websiteurl');
                $about = request('about');
                $contactname  = request('contactname');
                $contactmobile  = request('contactmobile');
                $contactemail  = request('contactemail');
                $contactemailBus = request('businessemail');
                $detailArr['name'] = request('name');
                $detailArr['slug'] = strtolower($slug);
                $detailArr['services'] = request('services');
                $detailArr['city'] = request('city');
                $detailArr['businessemail'] = ((isset($contactemailBus) && $contactemailBus !='') ? request('businessemail'): NULL);
                $detailArr['address'] = ((isset($address) && $address !='') ? request('address'): NULL);
                $detailArr['websiteurl'] = ((isset($websiteurl) && $websiteurl !='') ? request('websiteurl'): NULL);
                $detailArr['state'] = request('state');
                $detailArr['country'] = request('country');
                // $detailArr['county'] = request('county');
                $detailArr['zipcode'] = request('zipcode');
                $detailArr['contact'] = request('contact');
                $detailArr['about']    = ((isset($about) && $about !='') ? request('about'): NULL);
                $detailArr['contactname']    = ((isset($contactname) && $contactname !='') ? request('contactname'): NULL);
                $detailArr['contactmobile']    = ((isset($contactmobile) && $contactmobile !='') ? request('contactmobile'): NULL);
                $detailArr['contactemail']    = ((isset($contactemail) && $contactemail !='') ? request('contactemail'): NULL);
                $detailArr['accounttype']   = 'dummy';
                $detailArr['longitude']  = $longitude;
                $detailArr['allservices'] =  ((isset($allservices) && $allservices !='') ? json_encode($allservices,JSON_UNESCAPED_SLASHES): NULL);
                $detailArr['latitude']   = $latitude;
                $detailArr['actualslug']   = $realSlug;
                
                // $detailArr['country_code']   = request('country_code');
                $country_code = request('country_code');
                if($country_code != '') {
                    $pos = strpos($country_code, '+');
                    if(!$pos){
                        $country_code ='+'.$country_code;
                    }
                }   
                $detailArr['boats_yachts_worked']   = ($emptyboatYachtworked) ? NULL : $boatYachtObj;
                $detailArr['engines_worked']   = ($emptyengineworked) ? NULL : $engineObj;
                $detailArr['country_code']   = $country_code;
                $detailUpdate =  Companydetail::where('authid', '=', (int)$authid)->update($detailArr);
                if($detailUpdate) {
					$updatedDictionary = Dictionary::where('authid', '=', (int)$authid)->update(['word' => request('name')]);
                    // $geolocation = [];
                    // $city    = request('city');
                    // $state   = request('state');
                    // $zipcode = request('zipcode');
                    // $country = request('country');
                    // $addressGeo = ((isset($address) && $address !='') ? $address: '').' '.$city.' '.$zipcode.' '.$state.' ,'.$country;
                    // $output = $this->getGeoLocation($addressGeo); //Get Location from location Trait
                    // $longitude = $output['longitude'];
                    // $latitude = $output['latitude'];
                    // //$geolocationauthid = $authid;
                    // $geolocation['city'] = $city;
                    // $geolocation['zipcode'] = $zipcode;
                    // $geolocation['country'] = $country;
                    // $geolocation['county'] = request('county');
                    // $geolocation['state'] = $state;
                    // $geolocation['address']    = ((isset($address) && $address !='') ? request('address'): NULL);
                    // $geolocation['longitude'] = $longitude;
                    // $geolocation['latitude'] = $latitude;
                    // $geolocation['status'] = '1';
                    // //$usersdata['authid'] = $authid;
                    // $geoUpdate =  Geolocation::where('authid', '=', (int)$authid)->update($geolocation);
                    // if($geoUpdate) {
                    return response()->json(['success' => true,'authid' => $authid], $this->successStatus);
                    // } else {
                    //     return response()->json(['error'=>'networkerror'], 401);
                    // }
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


    // get company details //
    public function getCompanyDetail(Request $request) {
        $authid = request('id');
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('auths')
            ->leftJoin('companydetails', 'auths.id', '=', 'companydetails.authid')
            ->where('auths.id', '=', (int)$authid)
            ->where('auths.usertype', '=', 'company')
            ->where('companydetails.accounttype','=','dummy')
            ->select('auths.email','auths.id as userauthid','auths.usertype','auths.status', 'companydetails.*')
            ->first();
            if(!empty($usersdata)) {
				$engines_workedArr = [];
                if($usersdata->engines_worked != null) {
					$engines_worked = (array)json_decode($usersdata->engines_worked);
					if(!empty($engines_worked['saved']) && count($engines_worked['saved']) > 0) {
						$engines_workedData = Boat_Engine_Companies::select('name')->whereIn('id',$engines_worked['saved'])->where('category','engines')->where('status','1')->get();
						if(!empty($engines_workedData) && count($engines_workedData) > 0 ) {
							foreach($engines_workedData as $val) {
								$engines_workedArr[] = $val->name;
							}
						}
					}
					if(!empty($engines_worked['other']) && count($engines_worked['other']) > 0) {
						for($i = 0 ; $i < count($engines_worked['other']) ; $i++ ) {
							$engines_workedArr[] = $engines_worked['other'][$i];
						}
					}
				}
				$boats_yachts_workedArr = [];
				if($usersdata->boats_yachts_worked != null) {
					$boats_yachts_worked = (array)json_decode($usersdata->boats_yachts_worked);
					if(!empty($boats_yachts_worked['saved']) && count($boats_yachts_worked['saved']) > 0) {
						$boats_yachts_workedData = Boat_Engine_Companies::select('name')->whereIn('id',$boats_yachts_worked['saved'])->where(function($query) {
							$query->where('category', '=', 'boats')
							->orWhere('category', '=', 'yachts');
						})->where('status','1')->get();
						if(!empty($boats_yachts_workedData) && count($boats_yachts_workedData) > 0 ) {
							foreach($boats_yachts_workedData as $val) {
								$boats_yachts_workedArr[] = $val->name;
							}
						}
					}
					if(!empty($boats_yachts_worked['other']) && count($boats_yachts_worked['other']) > 0) {
						for($i = 0 ; $i < count($boats_yachts_worked['other']) ; $i++ ) {
							$boats_yachts_workedArr[] = $boats_yachts_worked['other'][$i];
						}
					}
				}
                
                return response()->json(['success' => true,'data' => $usersdata,'boatandyachtworked' => $boats_yachts_workedArr ,'enginesworked' => $engines_workedArr ], $this->successStatus);
                //return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }

    

    // update password //
    public function updatepassword(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'password' => 'required',
			'confirm' => 'required|same:password',
		]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $auth	= array(); 
        $updated = 0;
        $authid = request('id');
        $auth['password'] =Hash::make(request('password'));
        if(!empty($authid) && $authid > 0) {
            $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'company')->update($auth);
            if($updated) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    //Create Customer account in stripe
    public function createCustomerAccount(Request $request){
        $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
        /* Get user card Token and Plan*/
        $cardHolderName = request('nameoncard');
        $planPrice = request('ammount');
        $card_token = request('card_token');
        $userID = request('userID');
        $userDetail = User::where('id', '=', $userID)->get()->first()->toArray();
        $email = $userDetail['email'];
        $ex_message = '';
        /* Check if token is valid and exist (Remove this if not required)*/
        try {
            $tokenData = $stripe->tokens()->find($card_token);
            /* Check If user stripe account is already created*/
            if(empty($userDetail['stripeid'])){ 
                $customer = $stripe->customers()->create([      //Create a customer account 
                    'email' => $email,
                    'source' => $card_token
                ]);         
                $stripe_id = $customer['id'];
                $auth  = new Auth; 
                $status = $auth::where('id', $userID)->update(['stripeid' => $stripe_id]);
                if($status) {
                   $success = true;
                  
                } else {
                    $success = false;        
                } 
            } else {
                $card = $stripe->cards()->create($userDetail['stripeid'], $card_token); //Add card to customer account
                if(!empty($card)) {
                    try {
                            $charge = $stripe->charges()->create([
                                'customer' => $userDetail['stripeid'],
                                'currency' => 'USD',
                                'amount' => $planPrice
                            ]);
                            echo "<pre>";print_r($charge);
                            if($charge['status'] == 'succeeded') {
                                echo 'succeeded';
                            } else {
                                // generate exception
                                echo 'payment error';die;
                            }
                                die("done");
                    } catch (Exception $e) {
                        echo $e->getMessage();die;
                    } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
                        echo $e->getMessage();die;
                    } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
                        echo $e->getMessage();die;
                    }
                    $success = true;
                } else {
                    $success = false;
                }                
            }
        } catch(Exception $e) {
            $ex_message =  $e->getMessage();
            $success = false;
        } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
            $ex_message = $e->getMessage();
            $success = false;
        } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
            $ex_message = $e->getMessage();
            $success = false;
        }
   }

   // check company payment  //
    public function  checkcompanyPayment() {
        $id =request('id');
        if(!isset($id) || $id == '' ) {
            return response()->json(['error'=>'networkerror'], 401);
        } 
        $usersdata = DB::table('auths')
            ->leftJoin('companydetails', 'auths.id', '=', 'companydetails.authid')
            ->where('auths.usertype', '=', 'company')
            ->where('auths.status', '!=', 'deleted')
            ->where('auths.id', '=', (int)$id)
            ->where('auths.stepscompleted', '>=', 3)
            ->where('companydetails.nextpaymentdate', '>', date('Y-m-d H:i:s'))
            ->count();
        if($usersdata) {
            return response()->json(['success' => false ], $this->successStatus);
        } else {
            return response()->json(['success' => true], $this->successStatus);
        }
    }

    // //Save geolocations 
    // public function addGeolocation(Request $request) {
    //     $validate = Validator::make($request->all(), [
    //         'authid' => 'required',
    //         'locations' => 'required',
    //     ]);
    //     if ($validate->fails()) {
    //        return response()->json(['error'=>'validationError'], 401); 
    //     }
    //     $geolocationsArr = json_decode(request('locations'));
        
    //     $insertIds = [];
    //     //save all location 
    //     foreach ($geolocationsArr as $location) {
    //         $geolocation = new Geolocation;
    //         $city = $location->city;
    //         $state = $location->state;
    //         $zipcode = $location->zipcode;
    //         $country = $location->country;
    //         $address = $city.' '.$zipcode.' '.$state.' ,'.$country;
    //         $output = $this->getGeoLocation($address); //Get Location from location Trait
    //         $longitude = $output['longitude'];
    //         $latitude = $output['latitude'];
    //         $geolocation->authid = request('authid');
    //         $geolocation->city = $city;
    //         $geolocation->zipcode = $zipcode;
    //         $geolocation->country = $country;
    //         $geolocation->state = $state;
    //         $geolocation->longitude = $longitude;
    //         $geolocation->latitude = $latitude;
    //         $geolocation->status = '1';
    //         if($geolocation->save()) {
    //             $insertIds[] = $geolocation->id;
    //         } else {
    //             return response()->json(['error'=>'networkerror'], 401);
    //         }    
    //     }
    //     if(count($insertIds)) {
    //         return response()->json(['success' => true,'data' => $insertIds], $this->successStatus);
    //     } else {
    //         return response()->json(['success' => false,'data' => []], $this->successStatus);    
    //     }    
    // }
    
   // get  count user //
    public function getTotalUserwithamount() {
        $query = Auth::select('usertype', DB::raw('count(*) as user_count'))
                 ->where('status', '!=', 'deleted')->where('usertype','!=' , 'admin')->groupBy('usertype')->get();
        if($query) { 
            $amount = DB::table('paymenthistory')->select(DB::raw('SUM(amount) as payment_amount'))->get();
            if($amount) {
                return response()->json(['success' => true,'userdata' => $query,'amountdata' => $amount], $this->successStatus);
                print_r($amount);
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    //Create Customer account in stripe
    public function companyPayment(Request $request){
       // need to set plan month
        $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
        $validate = Validator::make($request->all(), [
            'nameoncard' => 'required',
            'subplan' => 'required',
            'card_token'  => 'required',
            'userID'  => 'required',
            'rtype'       => 'required'
        ]);
        if ($validate->fails()) {
            $success = false;
        }
        $renewPlan = request('renewal');

        if($renewPlan && $renewPlan != 'null' ) {
            $subType = 'automatic';
        } else {
            $subType = 'manual';
        }
        $rtype = request('rtype');
        if($rtype == 'admin') {
           $userID = request('userID');
        } else {
            $useridencrypt = request('userID');
            $userID = decrypt($useridencrypt);
        }
        if(empty($userID) || $userID == '') {
            return response()->json(['error'=>'networkerror'], 401); 
        }
        /* Get user card Token and Plan*/
        $cardHolderName = request('nameoncard');
        $subplan = request('subplan');
        $card_token = request('card_token');
        //$userID = request('userID');
        $userDetail = Auth::where('id', '=', (int)$userID)->where('status', '!=', 'deleted')->get()->first()->toArray();
        $email = $userDetail['email'];
        $ex_message = '';
        $plandata = DB::table('subscriptionplans')->where('id', '=', (int)$subplan)->where('status', '=', 'active')->first();
        if(!empty($plandata)) {
            $planPrice = $plandata->amount;
            $planType = $plandata->plantype;
            $planAccessType = $plandata->planaccesstype;
            $planAccessNumber = $plandata->planaccessnumber;
            if($planType =='paid') { 
                if($planAccessType == 'month') {
                    $nextDate = date('Y-m-d 00:00:00', strtotime("+".$planAccessNumber." months", strtotime(date('Y-m-d H:i:s'))));
                } else if($planAccessType == 'unlimited'){
                    $nextDate = '2099-01-01 00:00:00';
                }
            } else {
                //Add Free Plan
                $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                return response()->json(['success' => true], $this->successStatus);
            }            
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
        /* Check if token is valid and exist (Remove this if not required)*/
        try {
            $tokenData = $stripe->tokens()->find($card_token);
            /* Check If user stripe account is already created*/
            if(!isset($tokenData['id']) || $tokenData == '') {
                return response()->json(['error'=>'wrongToken'], 401);
            }
            if(empty($userDetail['stripeid'])){ 
                $customer = $stripe->customers()->create([      //Create a customer account 
                    'email' => $email,
                    'source' => $card_token
                ]);         
                $stripe_id = $customer['id'];
                try {
                    $charge = $stripe->charges()->create([
                        'customer' => $stripe_id,
                        'currency' => 'USD',
                        'amount' => $planPrice
                    ]);
                    if($charge['status'] == 'succeeded') {
                        //print_r($charge);die;
                        $statusStep = Auth::where('id', (int)$userID)->update(['stepscompleted' => '3','status' => 'active']);
                        $statusCompany = Companydetail::where('authid', (int)$userID)->update(['subscriptiontype' => $subType,'customer_id' => $stripe_id,'nextpaymentdate' => $nextDate, 'paymentplan' => (int)$subplan,'plansubtype' => 'paid','status' => 'active']);
                        if($statusStep && $statusCompany) {
                          $statusPayment =  DB::table('paymenthistory')->insert(
                                    ['companyid' => (int)$userID,
                                    'transactionid' => $charge['balance_transaction'],
                                    'tokenused' => $card_token,
                                    'transactionfor' => 'registrationfee',
                                    'amount' => $planPrice,
                                    'status' => 'approved' ,
                                    'payment_type' => $plandata->id,
                                    'fingerprintid' => $charge['source']['fingerprint'] ,
                                    'cardid' => $tokenData['card']['id'],
                                    'expiredate' => $nextDate,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                    ]);
                            if($statusPayment) {
                                return response()->json(['success' => true], $this->successStatus);
                            } else {
                                return response()->json(['error'=>'entryfail'], 401);
                            }
                        } else {
                            return response()->json(['error'=>'entryfail'], 401);
                        }
                    } else {
                        // generate exception
                        return response()->json(['error'=>'paymenterror'], 401);
                    }
                } catch (Exception $e) {
                    return response()->json(['error'=>$e->getMessage()], 401);
                } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
                    return response()->json(['error'=>$e->getMessage()], 401);
                } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
                    return response()->json(['error'=>$e->getMessage()], 401);
                }
            } else {
                $paymentHistroy = DB::table('paymenthistory')->where('companyid' ,'=',(int)$userID)->where('fingerprintid' ,'=',$tokenData['card']['fingerprint'])->first();
                if(!$paymentHistroy) {
                    $card = $stripe->cards()->create($userDetail['stripeid'], $card_token); //Add
                } else {
                    $card = 'already';
                    $cardID = $paymentHistroy->cardid;
                }
                if(!empty($card)) {
                    try {
                        if($card != 'already') {
                            $cardID = $tokenData['card']['id'];
                        }
                        $customer = $stripe->customers()->update( $userDetail['stripeid'], [
                            'default_source' => $cardID
                        ]);
                        $charge = $stripe->charges()->create([
                            'customer' => $userDetail['stripeid'],
                            'currency' => 'USD',
                            'amount' => $planPrice
                        ]);
                        if($charge['status'] == 'succeeded') {
                            $statusStep = Auth::where('id', (int)$userID)->update(['stepscompleted' => '3','status' => 'active']);
                            $statusCompany = Companydetail::where('authid', (int)$userID)->update(['subscriptiontype' => $subType,'customer_id' => $userDetail['stripeid'],'nextpaymentdate' => $nextDate,'paymentplan' => (int)$subplan, 'plansubtype' => 'paid','status' => 'active']);
                            if($statusStep && $statusCompany) {
                                $statusPayment =  DB::table('paymenthistory')->insert(
                                    ['companyid' => (int)$userID,
                                    'transactionid' => $charge['balance_transaction'],
                                    'tokenused' => $card_token,
                                    'transactionfor' => 'registrationfee',
                                    'amount' => $planPrice,
                                    'status' => 'approved' ,
                                    'payment_type' => $plandata->id,
                                    'fingerprintid' => $charge['source']['fingerprint'] ,
                                    'cardid' => $cardID,
                                    'expiredate' => $nextDate,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                    ]);
                                if($statusPayment) {
                                    return response()->json(['success' => true], $this->successStatus);
                                } else {
                                    return response()->json(['error'=>'entryfail'], 401);
                                }
                            } else {
                                return response()->json(['error'=>'entryfail'], 401);
                            }
                        } else {
                            // generate exception
                            return response()->json(['error'=>'paymenterror'], 401);
                        }
                    } catch (Exception $e) {
                        return response()->json(['error'=>$e->getMessage()], 401);
                    } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
                        return response()->json(['error'=>$e->getMessage()], 401);
                    } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
                        return response()->json(['error'=>$e->getMessage()], 401);
                    }
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }                
            }
        } catch(Exception $e) {
            $ex_message =  $e->getMessage();
            return response()->json(['error'=>$e->getMessage()], 401); 
        } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
            $ex_message = $e->getMessage();
            return response()->json(['error'=>$e->getMessage()], 401); 
        } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
            $ex_message = $e->getMessage();
            return response()->json(['error'=>$e->getMessage()], 401); 
        }
    }

    // trial plan payment //
    public function trialpaymentplan() {
        $id = request('id');
        $subplan = request('id');
        if(empty($id) || empty($subplan)) {
             return response()->json(['error'=>'networkerror'], 401);
        }
        $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
        $statusStep = Auth::where('id', (int)$id)->update(['stepscompleted' => '3','status' => 'active']);
        if($statusStep) {
            $statusCompany = Companydetail::where('authid', (int)$id)->update(['subscriptiontype' => 'manual','nextpaymentdate' => $nextDate, 'paymentplan' => (int)(request('subplan')),'plansubtype' => 'free','status' => 'active']);
            if($statusStep) {
                $statusPayment =  DB::table('paymenthistory')->insert(
                                ['companyid' => (int)$id,'transactionfor' => 'registrationfee',
                                'amount' => '0.00',
                                'status' => 'approved' ,
                                'payment_type' => (int)$subplan,
                                'expiredate' => $nextDate,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                                ]);
                if($statusPayment) {
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

    public function getCurrentPlan(Request $request) {
        $authid = request('id');
        $currentTime = Carbon\Carbon::now();
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('companydetails')
            ->Join('paymenthistory', 'paymenthistory.companyid', '=', 'companydetails.authid')
            ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'companydetails.paymentplan')
            ->select('paymenthistory.created_at','paymenthistory.expiredate', 'subscriptionplans.*')
            ->where('companydetails.authid','=',$authid)
            ->where('paymenthistory.expiredate','>',$currentTime)
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

     //Delet geolocations 
    // public function deleteGeolocation(Request $request) {
    //     $validate = Validator::make($request->all(), [
    //         'authid' => 'required',
    //         'id' => 'required'
    //     ]);
    //     if ($validate->fails()) {
    //        return response()->json(['error'=>'validationError'], 401); 
    //     }
    //     $updated = Geolocation::where('id', '=', ((int)request('id')))->update(['status' => '0']);
    //     if($updated) {
    //         return response()->json(['success' => true], $this->successStatus);
    //     }  else {
    //         return response()->json(['error'=>'networkerror'], 401);
    //     } 
    // }

    public function addAdsStatus(Request $request) {
        $status = request('status');
        $id = request('id');
        if(!empty($status) && !empty($id)) {
            $status = Companydetail::where('authid','=',$id)->update(['advertisebusiness' => (($status == 'checked')?'1':'0')]);
            if($status) {
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }
            return response()->json(['success' => true], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    // get all regular users //
    public function getclaimedCompany(Request $request) {
        $id = request('id');
        if(!empty($id)) {
            $getCompanyData = DB::table('companydetails')
            ->where('authid','=',(int)$id)
            ->where('accounttype','=','dummy')
            ->first();
            if(!empty($getCompanyData)) {
                $usersdata = DB::table('dummy_registration as dmy')
                    ->Join('subscriptionplans as sub', 'dmy.paymentplan', '=', 'sub.id')
                    ->where('dmy.authid', '=', (int)$id)
                    ->where('dmy.status', '=', 'active')
                    ->where('dmy.is_claim_user', '=', '1')
                    ->select('dmy.email','dmy.id','dmy.name','sub.planname','dmy.status')
                    ->orderBy('dmy.id', 'DESC')
                    ->get();
                if(!empty($usersdata)) {
                    return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {

        }
    }

    // reject company //
    public function rejectclaimedCompanies(Request $request) {
        $authid = (int)request('id');
        $rejectedBy = (int)request('authid');
        $updated = 0;
        if((!empty($rejectedBy)) && !empty($authid) && $authid > 0 ) {
            DB::beginTransaction();
            $rejectedRegistrations = dummy_registration::where('id', '=', $authid)->where('is_claim_user', '=', '1')->where('status', '=', 'active')->first();
            if(!empty($rejectedRegistrations)) {
                $rejectName = $rejectedRegistrations->name;
                $rejectEmail = $rejectedRegistrations->email ;
                $rejectedRegData = new Rejected_registration;
                $rejectedRegData->email = $rejectedRegistrations->email;
                $rejectedRegData->authid = $rejectedRegistrations->authid;
                $rejectedRegData->password = $rejectedRegistrations->password;
                $rejectedRegData->ipaddress = $rejectedRegistrations->ipaddress; 
                $rejectedRegData->is_social = $rejectedRegistrations->is_social;
                $rejectedRegData->social_id = $rejectedRegistrations->social_id;
                $rejectedRegData->provider  = $rejectedRegistrations->provider;            
                $rejectedRegData->name = $rejectedRegistrations->name;
                $rejectedRegData->slug = $rejectedRegistrations->slug;
                $rejectedRegData->actualslug =$rejectedRegistrations->actualslug;
                $rejectedRegData->services = $rejectedRegistrations->services;
                $rejectedRegData->address = $rejectedRegistrations->address;
                $rejectedRegData->city = $rejectedRegistrations->city;
                // $rejectedRegData->county = $rejectedRegistrations->county;
                $rejectedRegData->state = $rejectedRegistrations->state;
                $rejectedRegData->country = $rejectedRegistrations->country;
                $rejectedRegData->zipcode = $rejectedRegistrations->zipcode;
                $rejectedRegData->contact = $rejectedRegistrations->contact;
                $rejectedRegData->about = $rejectedRegistrations->about;
                $rejectedRegData->businessemail = $rejectedRegistrations->businessemail;
                $rejectedRegData->websiteurl = $rejectedRegistrations->websiteurl;
                $rejectedRegData->images = $rejectedRegistrations->images;
                $rejectedRegData->longitude = $rejectedRegistrations->longitude;
                $rejectedRegData->latitude = $rejectedRegistrations->latitude;
                $rejectedRegData->nextpaymentdate = $rejectedRegistrations->nextpaymentdate;
                $rejectedRegData->customer_id = $rejectedRegistrations->customer_id;
                $rejectedRegData->subscription_id = $rejectedRegistrations->subscription_id;
                $rejectedRegData->paymentplan = $rejectedRegistrations->paymentplan;
                $rejectedRegData->plansubtype = $rejectedRegistrations->plansubtype;
                $rejectedRegData->subscriptiontype = $rejectedRegistrations->subscriptiontype;
                $rejectedRegData->advertisebusiness = '0';
                $rejectedRegData->primaryimage = $rejectedRegistrations->primaryimage;
                $rejectedRegData->allservices = $rejectedRegistrations->allservices;
                $rejectedRegData->contactname = $rejectedRegistrations->contactname;
                $rejectedRegData->contactmobile = $rejectedRegistrations->contactmobile;
                $rejectedRegData->contactemail = $rejectedRegistrations->contactemail;
                $rejectedRegData->status = 'rejected';
                $rejectedRegData->coverphoto = $rejectedRegistrations->coverphoto;
                $rejectedRegData->accounttype = 'dummy';
                $rejectedRegData->rejected_id = $rejectedBy;
                $rejectedRegData->is_claim_user = '1';
                if($rejectedRegData->save()) {
                    $getIDRejected = $rejectedRegistrations->id;
                    $savedRejectedID = $rejectedRegData->id;
                    $rejectedGeo = Dummy_geolocation::where('authid', '=', $getIDRejected)->where('status', '=' ,'1')->get();
                    if(!empty($rejectedGeo)) {
                        if(count($rejectedGeo) > 0) {
                            foreach($rejectedGeo as $rejectedGeos) {
                                $rejectedGeoloc = new Rejected_geolocation;
                                $rejectedGeoloc->authid = $savedRejectedID;
                                $rejectedGeoloc->city = $rejectedGeos->city;
                                $rejectedGeoloc->zipcode = $rejectedGeos->zipcode;
                                $rejectedGeoloc->state = $rejectedGeos->state;
                                // $rejectedGeoloc->county = $rejectedGeos->county;
                                $rejectedGeoloc->status = $rejectedGeos->status;
                                if($rejectedGeoloc->save()) {
                                
                                } else {
                                    DB::rollBack();
                                    return response()->json(['error'=>'networkerror'], 401);
                                }
                            }
                            $deleteGeoRejected = Dummy_geolocation::where('authid', '=',$getIDRejected)->delete();
                            if(empty($deleteGeoRejected)) {
                                DB::rollBack();
                                return response()->json(['error'=>'networkerror'], 401);
                            }
                        }
                    }
                    $rejectedHis = Dummy_paymenthistory::where('companyid', '=', $getIDRejected)->get();
                    if(!empty($rejectedHis)) {
                        if(count($rejectedHis) > 0) {
                            foreach($rejectedHis as $rejectedHistory) {
                                $rejectedpayHis = new Rejected_paymenthistory;
                                $rejectedpayHis->companyid = $savedRejectedID;
                                $rejectedpayHis->requestid = $rejectedHistory->requestid;
                                $rejectedpayHis->talentid = $rejectedHistory->talentid;
                                $rejectedpayHis->transactionid = $rejectedHistory->transactionid;
                                $rejectedpayHis->transactionfor = $rejectedHistory->transactionfor;
                                $rejectedpayHis->amount = $rejectedHistory->amount;
                                $rejectedpayHis->status = $rejectedHistory->status;
                                $rejectedpayHis->customer_id = $rejectedHistory->customer_id;
                                $rejectedpayHis->subscription_id = $rejectedHistory->subscription_id;
                                $rejectedpayHis->expiredate = $rejectedHistory->expiredate;
                                $rejectedpayHis->created_at = $rejectedHistory->created_at;
                                $rejectedpayHis->updated_at = $rejectedHistory->updated_at;
                                $rejectedpayHis->payment_type = $rejectedHistory->payment_type;
                                if($rejectedpayHis->save()) {
                                } else {
                                    DB::rollBack();
                                    return response()->json(['error'=>'networkerror'], 401);
                                }
                            }
                            $deleteHisRejected = Dummy_paymenthistory::where('companyid', '=',$getIDRejected)->delete();
                            if(empty($deleteHisRejected)) {
                                DB::rollBack();
                                return response()->json(['error'=>'networkerror'], 401);
                            }
                        }
                    }
                    $deleteHisRejected = dummy_registration::where('id', '=',$getIDRejected)->delete();
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    $link = $website_url.'/registration';
                    $ACTIVATION_LINK = $link;
                    $emailArr = [];                                        
                    $emailArr['link'] = $ACTIVATION_LINK;
                    $emailArr['name'] = $rejectName;
                    $emailArr['to_email'] = $rejectEmail;
                    //Send activation email notification
                    $status = $this->sendEmailNotification($emailArr,'reject_claimbusiness');
                    if($rejectedRegistrations->subscription_id != null) {
                        try{
                            $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
                            $subscription = $stripe->subscriptions()->cancel($rejectedRegistrations->customer_id, $rejectedRegistrations->subscription_id);
                        }   catch(\Cartalyst\Stripe\Exception\NotFoundException $e) {
                                 
                        }   catch (Exception $e) {
                            return response()->json(['error'=>'networkerror'], 401);   
                        }
                    }
                    if($status != 'sent') {
                        DB::rollBack();
                        return response()->json(['error'=>'emailsentfail'], 401);
                    } else {
                        DB::commit();
                        return response()->json(['success' => true], $this->successStatus);
                    }
                } else {
                    DB::rollBack();
                    return response()->json(['error'=>'networkerror'], 401);
                }
            } else {
                DB::rollBack();
                return response()->json(['error'=>'networkerror'], 401);
            }
        } else {
            DB::rollBack();
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    // approve company //
    public function approvedclaimedCompanies(Request $request) {
        $isSocial = false;
        $authid = (int)request('id');
        $approvedBy = (int)request('authid');
        $updated = 0;
        $claimedEmail = '';
        $claimedName = '';
        if((!empty($approvedBy)) &&  !empty($authid) && $authid > 0 ) {
            $checkEmailExist =  dummy_registration::where('id', '=', (int)$authid)->where('usertype', '=', 'company')->first();
            if(!empty($checkEmailExist)) {
                $checkInauth = Auth::where('email', '=', $checkEmailExist->email)->where('accounttype','=','real')->first();
                if(!empty($checkInauth)) {
                    return response()->json(['error'=>'emailExist'], 401);
                }

            } else {
                 return response()->json(['error'=>'networkerror'], 401);
            }
            
            DB::beginTransaction();
             $usersdata = dummy_registration::where('id', '=', (int)$authid)
            ->where('is_claim_user', '=', '1')
            ->where('status','=','active')
            ->first();
            if(!empty($usersdata)) {
                $claimedEmail = $usersdata->email;
                $claimedName = $usersdata->name;
                $dummyCompanyID = (int)$usersdata->authid;
                $dummyCompanydata = DB::table('auths as at')
                        ->Join('companydetails as c', 'c.authid', '=', 'at.id')
                        ->where('at.id', '=', (int)$dummyCompanyID)
                        ->where('c.accounttype', '=', 'dummy')
                        ->where('c.status','!=','deleted')
                        ->select('at.email','at.password','at.ipaddress','c.*')
                        ->first();
                if(!empty($dummyCompanydata)) {
                    $backupData = new Dummy_registration_backup;
                    $backupData->authid = (int)$dummyCompanyID;
                    $backupData->email = $dummyCompanydata->email;
                    $backupData->password = $dummyCompanydata->password;
                    $backupData->ipaddress = $dummyCompanydata->ipaddress;
                    $backupData->name = $dummyCompanydata->name;
                    $backupData->slug = $dummyCompanydata->slug;
                    $backupData->services = $dummyCompanydata->services;
                    $backupData->address = $dummyCompanydata->address;
                    $backupData->city = $dummyCompanydata->city;
                    // $backupData->county = $dummyCompanydata->county;
                    $backupData->state = $dummyCompanydata->state;
                    $backupData->country = $dummyCompanydata->country;
                    $backupData->zipcode = $dummyCompanydata->zipcode;
                    $backupData->contact = $dummyCompanydata->contact;
                    $backupData->about = $dummyCompanydata->about;
                    $backupData->businessemail = $dummyCompanydata->businessemail;
                    $backupData->websiteurl = $dummyCompanydata->websiteurl;
                    $backupData->images = $dummyCompanydata->images;
                    $backupData->longitude = $dummyCompanydata->longitude;
                    $backupData->latitude = $dummyCompanydata->latitude;
                    $backupData->nextpaymentdate = $dummyCompanydata->nextpaymentdate;
                    $backupData->customer_id = $dummyCompanydata->customer_id;
                    $backupData->paymentplan = $dummyCompanydata->paymentplan;
                    $backupData->plansubtype = $dummyCompanydata->plansubtype;
                    $backupData->subscriptiontype = $dummyCompanydata->subscriptiontype;
                    $backupData->allservices = $dummyCompanydata->allservices;
                    $backupData->primaryimage = $dummyCompanydata->primaryimage;
                    $backupData->coverphoto = $dummyCompanydata->coverphoto;
                    $backupData->contactname = $dummyCompanydata->contactname;
                    $backupData->contactmobile = $dummyCompanydata->contactmobile;
                    $backupData->contactemail = $dummyCompanydata->contactemail;
                    $backupData->actualslug = $dummyCompanydata->actualslug;
                    $backupData->advertisebusiness = $dummyCompanydata->advertisebusiness;
                    $backupData->accounttype = $dummyCompanydata->accounttype;
                    if($backupData->save()) {
                        $authData = Auth::find($dummyCompanyID);
                        $authData->email = $usersdata->email;
                        $authData->password = $usersdata->password;
                        $authData->usertype = $usersdata->usertype;   
                        $authData->ipaddress = $usersdata->ipaddress;             
                        $authData->stepscompleted = $usersdata->stepscompleted;
                        $authData->addedby = $approvedBy;
                        if($usersdata->is_social == '1') {
                            $isSocial = true;
                            $authData->is_activated = '1';
                        } else {
                            $authData->is_activated = '0';
                        }
                        $authData->is_social = $usersdata->is_social;
                        $authData->social_id = $usersdata->social_id;
                        $authData->provider  = $usersdata->provider;
                        $authData->newsletter  = $usersdata->newsletter;
                        $authData->status = 'active';
                        $authData->accounttype = 'real';
                        if($authData->save()) {
                            $IsCompanyData = Companydetail::where('authid', '=',$dummyCompanyID)->first();
                            if(!empty($IsCompanyData)) {
                                $compId = $IsCompanyData->id;
                                $companyData  = Companydetail::find($compId);

                                $company_slug_new= preg_replace('/[^a-zA-Z0-9_ -]/s','',$usersdata->name); 
                                
                                $slug = implode("-",explode(" ",$company_slug_new));

                                $slug1 = '';
                                $array = explode(" ",$usersdata->city);
                                if(is_array($array)) {
                                    $slug1 = implode("-",$array);       
                                }
                                $slug = strtolower($slug).'-'.strtolower($slug1);
                                $realSlug = $slug;    
                                $companyData->authid = $dummyCompanyID;
                                $companyData->name = $usersdata->name;
                                $countSlugs = 0;
                                $validSlug = false;  
                                for($i = 0 ; $validSlug != true ; $i++) {
                                    $checkSlug = Companydetail::where('actualslug','=',strtolower($slug))->where('authid', '!=', (int)$dummyCompanyID)->count();
                                    $checkSlugEdit = Companydetail::where('slug','=',strtolower($slug))->where('authid', '!=', (int)$dummyCompanyID)->count();
                                    if($checkSlug) {
                                        $countSlugs = $countSlugs +$checkSlug;
                                        $slug = $realSlug.'-'.($countSlugs);
                                    } else if($checkSlugEdit) {
                                        $countSlugs = $countSlugs + 1;
                                        $slug = $realSlug.'-'.($countSlugs);
                                    } else {
                                        $validSlug = true;
                                    } 
                                } 
                                $companyData->slug = $slug;
                                $companyData->actualslug = $realSlug;
                                $companyData->services = $usersdata->services;
                                $companyData->address = $usersdata->address;
                                $companyData->city = $usersdata->city;
                                // $companyData->county = $usersdata->county;
                                $companyData->state = $usersdata->state;
                                $companyData->country = $usersdata->country;
                                $companyData->zipcode = $usersdata->zipcode;
                                $companyData->contact = $usersdata->contact;
                                $companyData->about = $usersdata->about;
                                $companyData->businessemail = $usersdata->businessemail;
                                $companyData->websiteurl = $usersdata->websiteurl;
                                $companyData->images = $usersdata->images;
                                $companyData->longitude = $usersdata->longitude;
                                $companyData->latitude = $usersdata->latitude;
                                $companyData->nextpaymentdate = $usersdata->nextpaymentdate;
                                $companyData->customer_id = $usersdata->customer_id;
                                $companyData->subscription_id = $usersdata->subscription_id;
                                $companyData->paymentplan = $usersdata->paymentplan;
                                $companyData->next_paymentplan   = $usersdata->paymentplan;
                                $companyData->plansubtype = $usersdata->plansubtype;
                                $companyData->subscriptiontype = $usersdata->subscriptiontype;
                                $companyData->advertisebusiness = '0';
                                $companyData->primaryimage = $usersdata->primaryimage;
                                $companyData->allservices = $usersdata->allservices;
                                $companyData->contactname = $usersdata->contactname;
                                $companyData->contactmobile = $usersdata->contactmobile;
                                $companyData->contactemail = $usersdata->contactemail;
                                $companyData->status = 'active';
                                $companyData->coverphoto = $usersdata->coverphoto;
                                $companyData->accounttype = 'real';
                                $companyData->is_admin_approve = '1';
                                $companyData->remaintrial = $usersdata->remaintrial;
                                $companyData->is_claimed = '1';
                                $companyData->approval_id = $approvedBy;
                                $companyData->boats_yachts_worked    = $usersdata->boats_yachts_worked;
                                $companyData->engines_worked    = $usersdata->engines_worked;
                                $dateDiscountCheck = date('2019-12-31 23:59:59');
                                $currentDiscountCheck = date('Y-m-d 00:00:00');
                                if($currentDiscountCheck < $dateDiscountCheck) {
                                    $companyData->is_discount = '1';
                                    $companyData->remaindiscount = 12;
                                    $companyData->discount = 50;
                                }
                                if($companyData->save()) {
                                    $updatedDictionary = Dictionary::where('authid', '=', (int)$dummyCompanyID)->update(['word' => $usersdata->name]);
                    
                                    $deleteGeoData = Geolocation::where('authid', '=',$dummyCompanyID)->delete();
                                    $getGeoData = Dummy_geolocation::where('authid','=',$authid)->where('status','=','1')->get();
                                    if(!empty($getGeoData)) {
                                        if(count($getGeoData) > 0) {
                                            foreach($getGeoData as $getGeodatas) {
                                                $geolocationInsert = new Geolocation;
                                                $geolocationInsert->authid = $dummyCompanyID;
                                                $geolocationInsert->city = $getGeodatas->city;
                                                $geolocationInsert->zipcode = $getGeodatas->zipcode;
                                                $geolocationInsert->state = $getGeodatas->state;
                                                // $geolocationInsert->county = $getGeodatas->county;
                                                $geolocationInsert->status = $getGeodatas->status;
                                                if($geolocationInsert->save()) {
                                                } else {
                                                    DB::rollBack();
                                                    return response()->json(['error'=>'networkerror'], 401);
                                                }
                                            }
                                            $deleteGeoApprove = Dummy_geolocation::where('authid', '=',$authid)->delete();
                                            if(empty($deleteGeoApprove)) {
                                                DB::rollBack();
                                                return response()->json(['error'=>'networkerror'], 401);
                                            }
                                        }
                                    }
                                    $getPaymentData = Dummy_paymenthistory::where('companyid','=',$authid)->get();
                                    if(!empty($getPaymentData)) {
                                        if(count($getPaymentData) > 0) {
                                            foreach($getPaymentData as $getPaymentDatas) {
                                                $paymenthistoryInsert = new Paymenthistory;
                                                $paymenthistoryInsert->companyid = $dummyCompanyID;
                                                $paymenthistoryInsert->requestid = $getPaymentDatas->requestid;
                                                $paymenthistoryInsert->talentid = $getPaymentDatas->talentid;
                                                $paymenthistoryInsert->transactionid = $getPaymentDatas->transactionid;
                                                $paymenthistoryInsert->transactionfor = $getPaymentDatas->transactionfor;
                                                $paymenthistoryInsert->amount = $getPaymentDatas->amount;
                                                $paymenthistoryInsert->status = $getPaymentDatas->status;
                                                $paymenthistoryInsert->customer_id = $getPaymentDatas->customer_id;
                                                $paymenthistoryInsert->subscription_id = $getPaymentDatas->subscription_id;
                                                $paymenthistoryInsert->expiredate = $getPaymentDatas->expiredate;
                                                $paymenthistoryInsert->created_at = $getPaymentDatas->created_at;
                                                $paymenthistoryInsert->updated_at = $getPaymentDatas->updated_at;
                                                $paymenthistoryInsert->payment_type = $getPaymentDatas->payment_type;
                                                if($paymenthistoryInsert->save()) {
                                                } else {
                                                    DB::rollBack();
                                                    return response()->json(['error'=>'networkerror'], 401);
                                                }
                                            }
                                            $deleteHisApprove = Dummy_paymenthistory::where('companyid', '=',$authid)->delete();
                                            if(empty($deleteHisApprove)) {
                                                DB::rollBack();
                                                return response()->json(['error'=>'networkerror'], 401);
                                            }
                                        }
                                    }

                                    //$rejectedRegistration = dummy_registration::where('id', '!=', $authid)->where('is_claim_user', '=', '1')->where('status', '=', 'active')->update(['status' => 'rejected','rejected_id' => (int)$approvedBy]);
                                    $rejectedRegistration = dummy_registration::where('authid', '=', $dummyCompanyID)->where('id', '!=', $authid)->where('is_claim_user', '=', '1')->where('status', '=', 'active')->get();
                                    if(!empty($rejectedRegistration)) {
                                        if(count($rejectedRegistration) > 0) {
                                            foreach($rejectedRegistration as $rejectedRegistrations) {
                                                $rejectName = $rejectedRegistrations->name;
                                                $rejectEmail = $rejectedRegistrations->email ;
                                                $rejectedRegData = new Rejected_registration;
                                                $rejectedRegData->email = $rejectedRegistrations->email;
                                                $rejectedRegData->authid = $rejectedRegistrations->authid;
                                                $rejectedRegData->password = $rejectedRegistrations->password;
                                                $rejectedRegData->ipaddress = $rejectedRegistrations->ipaddress;  
                                                $rejectedRegData->is_social = $rejectedRegistrations->is_social;
                                                $rejectedRegData->social_id = $rejectedRegistrations->social_id;
                                                $rejectedRegData->provider  = $rejectedRegistrations->provider;           
                                                $rejectedRegData->name = $rejectedRegistrations->name;
                                                $rejectedRegData->slug = $rejectedRegistrations->slug;
                                                $rejectedRegData->actualslug =$rejectedRegistrations->actualslug;
                                                $rejectedRegData->services = $rejectedRegistrations->services;
                                                $rejectedRegData->address = $rejectedRegistrations->address;
                                                $rejectedRegData->city = $rejectedRegistrations->city;
                                                // $rejectedRegData->county = $rejectedRegistrations->county;
                                                $rejectedRegData->state = $rejectedRegistrations->state;
                                                $rejectedRegData->country = $rejectedRegistrations->country;
                                                $rejectedRegData->zipcode = $rejectedRegistrations->zipcode;
                                                $rejectedRegData->contact = $rejectedRegistrations->contact;
                                                $rejectedRegData->about = $rejectedRegistrations->about;
                                                $rejectedRegData->businessemail = $rejectedRegistrations->businessemail;
                                                $rejectedRegData->websiteurl = $rejectedRegistrations->websiteurl;
                                                $rejectedRegData->images = $rejectedRegistrations->images;
                                                $rejectedRegData->longitude = $rejectedRegistrations->longitude;
                                                $rejectedRegData->latitude = $rejectedRegistrations->latitude;
                                                $rejectedRegData->nextpaymentdate = $rejectedRegistrations->nextpaymentdate;
                                                $rejectedRegData->customer_id = $rejectedRegistrations->customer_id;
                                                $rejectedRegData->subscription_id = $rejectedRegistrations->subscription_id;
                                                $rejectedRegData->paymentplan = $rejectedRegistrations->paymentplan;
                                                $rejectedRegData->plansubtype = $rejectedRegistrations->plansubtype;
                                                $rejectedRegData->subscriptiontype = $rejectedRegistrations->subscriptiontype;
                                                $rejectedRegData->advertisebusiness = '0';
                                                $rejectedRegData->primaryimage = $rejectedRegistrations->primaryimage;
                                                $rejectedRegData->allservices = $rejectedRegistrations->allservices;
                                                $rejectedRegData->contactname = $rejectedRegistrations->contactname;
                                                $rejectedRegData->contactmobile = $rejectedRegistrations->contactmobile;
                                                $rejectedRegData->contactemail = $rejectedRegistrations->contactemail;
                                                $rejectedRegData->status = 'rejected';
                                                $rejectedRegData->coverphoto = $rejectedRegistrations->coverphoto;
                                                $rejectedRegData->accounttype = 'dummy';
                                                $rejectedRegData->rejected_id = $approvedBy;
                                                $rejectedRegData->is_claim_user = '1';
                                                if($rejectedRegData->save()) {
                                                    $getIDRejected = $rejectedRegistrations->id;
                                                    $savedRejectedID = $rejectedRegData->id;
                                                    $rejectedGeo = Dummy_geolocation::where('authid', '=', $getIDRejected)->get();
                                                    if(!empty($rejectedGeo)) {
                                                        if(count($rejectedGeo) > 0) {
                                                            foreach($rejectedGeo as $rejectedGeos) {
                                                                $rejectedGeoloc = new Rejected_geolocation;
                                                                $rejectedGeoloc->authid = $savedRejectedID;
                                                                $rejectedGeoloc->city = $rejectedGeos->city;
                                                                $rejectedGeoloc->zipcode = $rejectedGeos->zipcode;
                                                                $rejectedGeoloc->state = $rejectedGeos->state;
                                                                // $rejectedGeoloc->county = $rejectedGeos->county;
                                                                $rejectedGeoloc->status = $rejectedGeos->status;
                                                                if($rejectedGeoloc->save()) {
                                                                } else {
                                                                    DB::rollBack();
                                                                    return response()->json(['error'=>'networkerror'], 401);
                                                                }
                                                            }
                                                            $deleteGeoRejected = Dummy_geolocation::where('authid', '=',$getIDRejected)->delete();
                                                            if(empty($deleteGeoRejected)) {
                                                                DB::rollBack();
                                                                return response()->json(['error'=>'networkerror'], 401);
                                                            }
                                                        }
                                                    }
                                                    $rejectedHis = Dummy_paymenthistory::where('companyid', '=', $getIDRejected)->get();
                                                    if(!empty($rejectedHis)) {
                                                        if(count($rejectedHis) > 0) {
                                                            foreach($rejectedHis as $rejectedHistory) {
                                                                $rejectedpayHis = new Rejected_paymenthistory;
                                                                $rejectedpayHis->companyid = $savedRejectedID;
                                                                $rejectedpayHis->requestid = $rejectedHistory->requestid;
                                                                $rejectedpayHis->talentid = $rejectedHistory->talentid;
                                                                $rejectedpayHis->transactionid = $rejectedHistory->transactionid;
                                                                $rejectedpayHis->transactionfor = $rejectedHistory->transactionfor;
                                                                $rejectedpayHis->amount = $rejectedHistory->amount;
                                                                $rejectedpayHis->status = $rejectedHistory->status;
                                                                $rejectedpayHis->customer_id = $rejectedHistory->customer_id;
                                                                $rejectedpayHis->subscription_id = $rejectedHistory->subscription_id;
                                                                $rejectedpayHis->expiredate = $rejectedHistory->expiredate;
                                                                $rejectedpayHis->created_at = $rejectedHistory->created_at;
                                                                $rejectedpayHis->updated_at = $rejectedHistory->updated_at;
                                                                $rejectedpayHis->payment_type = $rejectedHistory->payment_type;
                                                                if($rejectedpayHis->save()) {
                                                                } else {
                                                                    DB::rollBack();
                                                                    return response()->json(['error'=>'networkerror'], 401);
                                                                }
                                                            }
                                                            $deleteHisRejected = Dummy_paymenthistory::where('companyid', '=',$getIDRejected)->delete();
                                                            if(empty($deleteHisRejected)) {
                                                                DB::rollBack();
                                                                return response()->json(['error'=>'networkerror'], 401);
                                                            }
                                                        }
                                                    }
                                                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                                                    $link = $website_url.'/registration';
                                                    $ACTIVATION_LINK = $link;
                                                    $emailArr = [];                                        
                                                    $emailArr['link'] = $ACTIVATION_LINK;
                                                    $emailArr['name'] = $rejectName;
                                                    $emailArr['to_email'] = $rejectEmail;
                                                    //Send activation email notification
                                                    $status = $this->sendEmailNotification($emailArr,'reject_claimbusiness');
                                                    if($rejectedRegistrations->subscription_id != null) {
                                                        try{
                                                            $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
                                                            $subscription = $stripe->subscriptions()->cancel($rejectedRegistrations->customer_id, $rejectedRegistrations->subscription_id);
                                                        }   catch(\Cartalyst\Stripe\Exception\NotFoundException $e) {
                                                                 
                                                        }   catch (Exception $e) {
                                                            return response()->json(['error'=>'networkerror'], 401);   
                                                        }
                                                    }
                                                } else {
                                                    DB::rollBack();
                                                    return response()->json(['error'=>'networkerror'], 401);
                                                }

                                            }
                                        }

                                    }
                                    $rejectedRegistration = dummy_registration::where('authid', '=', $dummyCompanyID)->where('is_claim_user', '=', '1')->where('status', '=', 'active')->delete();
                                    $random_hashed = Hash::make(md5(uniqid($dummyCompanyID, true)));
                                    $updateHash = Auth::where('id','=',$dummyCompanyID)->update(['activation_hash' => $random_hashed]);
                                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                                    if($isSocial) {
                                        $link = $website_url.'/login';
                                    } else {
                                        $link = $website_url.'/activate?token='.urlencode($random_hashed);
                                    }
                                    $ACTIVATION_LINK = $link;
                                    $emailArr = [];                                        
                                    $emailArr['link'] = $ACTIVATION_LINK;
                                    $emailArr['name'] = $claimedName;
                                    $emailArr['to_email'] = $claimedEmail;
                                    //Send activation email notification 
                                    if($isSocial) {
                                        $status = $this->sendEmailNotification($emailArr,'approve_claimbusiness_social');
                                    } else {
                                        $status = $this->sendEmailNotification($emailArr,'approve_claimbusiness');
                                    }
                                    if($status != 'sent') {
                                        DB::rollBack();
                                        return response()->json(['error'=>'emailsentfail'], 401);
                                    } else {
                                        DB::commit();
                                        return response()->json(['success' => true], $this->successStatus);
                                    }
                                } else {
                                    DB::rollBack();
                                    return response()->json(['error'=>'networkerror'], 401);    
                                }
                            } else {
                                DB::rollBack();
                                return response()->json(['error'=>'networkerror'], 401);
                            }
                        } else {
                            DB::rollBack();
                            return response()->json(['error'=>'networkerror'], 401);
                        }
                    } else {
                        DB::rollBack();
                        return response()->json(['error'=>'networkerror'], 401);
                    }
                } else {
                    DB::rollBack();
                    return response()->json(['error'=>'networkerror'], 401);
                }
            } else {
                DB::rollBack();
                return response()->json(['error'=>'networkerror'], 401);
            } 
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }  
    }

     // get company details //
    public function getclaimedCompanyData(Request $request) {
        $authid = request('id');
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('dummy_registration')
            ->where('id', '=', (int)$authid)
            ->where('is_claim_user', '=', '1')
            ->where('status','=','active')
            ->first();
            if(!empty($usersdata)) {
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                return response()->json(['success' => false,'data' => []], $this->successStatus);
                // return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }
     // get company details //
    public function getclaimedcompaniesdata(Request $request) {
        $authid = request('id');
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('dummy_registration')
            ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'dummy_registration.paymentplan')
            ->where('dummy_registration.authid', '=', (int)$authid)
            ->where('dummy_registration.is_claim_user', '=', '1')
            ->where('dummy_registration.status','=','active')
            ->select('subscriptionplans.planname', 'dummy_registration.*','dummy_registration.id as userauthid')
            ->get();
            if(!empty($usersdata)) {
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                return response()->json(['success' => false,'data' => []], $this->successStatus);
                // return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }

    // get all company images //
    public function getImagesData(Request $request) {
        $authid = request('userid');
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('dummy_registration')
            ->where('id', '=', (int)$authid)
            ->select('dummy_registration.images','dummy_registration.authid')
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
        $detailUpdate =  dummy_registration::where('id', '=', (int)$authid)->update($detailArr);
        if($detailUpdate) {
            $usersdata = DB::table('dummy_registration')
            ->where('id', '=', (int)$authid)
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
        $detailUpdate =  dummy_registration::where('id', '=', (int)$authid)->update($detailArr);
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
                    $primaryDelete = false;
                    $imageArr[$j]['primary'] = '1';
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
        $detailUpdate =  dummy_registration::where('id', '=', (int)$authid)->update($detailArr);
        if($detailUpdate) {
            return response()->json(['success' => true,'images' => $jsonObj], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    //Get all geolocation by id 
    // public function getGeolocationsById(Request $request) {
    //     $validate = Validator::make($request->all(), [
    //         'authid' => 'required'
    //     ]);
    //     if ($validate->fails()) {
    //        return response()->json(['error'=>'validationError'], 401); 
    //     }
    //     $geolocations = Dummy_geolocation::select('id','authid','city','state','country','county','zipcode','address','created_at')
    //                     ->where('authid', '=',request('authid'))
    //                     ->where('status','=','1')
    //                     ->get();
    //     if($geolocations) {
    //         return response()->json(['success' => true,'data' => $geolocations], $this->successStatus);
    //     } else {
    //         return response()->json(['success' => false,'data' => []], $this->successStatus);
    //     }    
    // }    
    //Edit geolocations 
    // public function editGeolocation(Request $request) {
    //     $validate = Validator::make($request->all(), [
    //         'authid' => 'required',
    //         'locations' => 'required',
    //     ]);
    //     if ($validate->fails()) {
    //        return response()->json(['error'=>'validationError'], 401); 
    //     }
    //     $geolocationsArr = json_decode(request('locations'));
    //     $insertIds = [];
    //     $geoArr = [];
    //     $additionalGeo = (int)(request('additionalgeo'));
    //     $planLocations = count($geolocationsArr)-$additionalGeo;
    //     $countLoc = 0;

    //     $original = (int)(request('original'));

    //     $authid = request('authid');
    //     //save all location 
    //     foreach ($geolocationsArr as $location) {
    //         $totalGeoLoc = DB::table('dummy_registration as c')->select('geolocationaccess')
    //         ->join('subscriptionplans as s','s.id','=','c.paymentplan')
    //         ->where('c.id','=',$authid)->first();
    //         if(empty($totalGeoLoc)) {
    //             return response()->json(['error'=>'networkerror'], 401);
    //         }
    //         $totalrecords = DB::table('dummy_geolocation')->where('authid','=',$authid)->where('status','=','1')->count();
    //         // if($totalrecords <= $totalGeoLoc->geolocationaccess) {
    //             if(isset($location->hidlocationid) && !empty($location->hidlocationid)) {
    //                 $geolocation = Dummy_geolocation::find($location->hidlocationid);    
    //             } else {
    //                 $geolocation = new Dummy_geolocation;
    //             }
    //             $city = $location->city;
    //             $state = $location->state;
    //             $zipcode = $location->zipcode;
    //             $country = $location->country;
    //             $county = $location->county;
    //             $geoaddress = $location->address;

    //             $address = ((!empty($geoaddress))?$geoaddress:'').' '.$city.' '.$zipcode.' '.$county.' '.$state.' ,'.$country;
    //             $output = $this->getGeoLocation($address); //Get Location from location Trait
    //             $longitude = $output['longitude'];
    //             $latitude = $output['latitude'];
    //             $geolocation->authid = request('authid');
    //             $geolocation->city = $city;
    //             $geolocation->zipcode = $zipcode;
    //             $geolocation->country = $country;
    //             $geolocation->county = $county;
    //             $geolocation->state = $state;
    //             $geolocation->address = $geoaddress;
    //             $geolocation->longitude = $longitude;
    //             $geolocation->latitude = $latitude;
    //             if($countLoc >= $original) {
    //                 $geolocation->additional_location = '1';
    //             } else {
    //                 $geolocation->additional_location = '0';
    //             }
    //             $geolocation->status = '1';
    //             if($geolocation->save()) {
    //                 $insertIds[] = $geolocation->id;
    //                 $geoArr[] = $geolocation->id;
    //             } else {
    //                 return response()->json(['error'=>'networkerror'], 401);
    //             }
    //         // }    
    //         $countLoc++;   
    //     }
    //     $changeStatus = DB::table('dummy_geolocation')->whereNotIn('id',$geoArr)->where('authid',$authid)->update(['status' => '0']);
    //     if(count($insertIds)) {
    //         return response()->json(['success' => true], $this->successStatus);
    //     } else {
    //         return response()->json(['success' => false,'data' => []], $this->successStatus);    
    //     }    
    // }

    //Get Plan Geolocation details//
    // public function getUserPlanGeoLocation(Request $request) {
    //     $id = request('businessid');
    //     if(!empty($id) && (int)$id) {
    //         $planData = DB::table('dummy_registration')
    //         ->select('subscriptionplans.geolocationaccess','subscriptionplans.plantype','dummy_registration.authid')
    //         ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'dummy_registration.paymentplan')
    //         ->where('dummy_registration.id', '=', $id)
    //         ->where('dummy_registration.status','=','active')
    //         ->first();
    //         if($planData) {
    //             return response()->json(['success' => true,'data' => $planData], $this->successStatus);
    //         } else {
    //             return response()->json(['success' => false,'data' => []], $this->successStatus);    
    //         }
    //     } else {
    //         return response()->json(['error'=>'networkerror'], 401);
    //     }
    // }

    //Save geolocations 
    // public function addGeolocationpayment(Request $request) {
    //     $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
    //     $validate = Validator::make($request->all(), [
    //         'nameoncard' => 'required',
    //         'additionalgeo' => 'required',
    //         'card_token'  => 'required',
    //         'userID'  => 'required',
    //         'rtype'       => 'required'
    //     ]);
    //     if ($validate->fails()) {
    //         $success = false;
    //     }
    //     $rtype = request('rtype');
    //     if($rtype == 'admin') {
    //        $userID = request('userID');
    //     } else {
    //         $useridencrypt = request('userID');
    //         $userID = decrypt($useridencrypt);
    //     }
    //     $geoLocNumber = (int)request('additionalgeo');
    //     $geoLocAmount = $geoLocNumber*25;
    //     if(empty($userID) || $userID == '') {
    //         return response()->json(['error'=>'networkerror'], 401); 
    //     }
    //     /* Get user card Token and Plan*/
    //     $cardHolderName = request('nameoncard');
    //     //$subplan = request('subplan');
    //     $card_token = request('card_token');
    //     //$userID = request('userID');
    //     $userDetail = Auth::where('id', '=', (int)$userID)->where('status', '!=', 'deleted')->get()->first()->toArray();
    //     $email = $userDetail['email'];
    //     $ex_message = '';
        
    //     try {
    //         $tokenData = $stripe->tokens()->find($card_token);
    //         /* Check If user stripe account is already created*/
    //         if(!isset($tokenData['id']) || $tokenData == '') {
    //             return response()->json(['error'=>'wrongToken'], 401);
    //         }
    //         if(empty($userDetail['stripeid'])){ 
    //             $customer = $stripe->customers()->create([      //Create a customer account 
    //                 'email' => $email,
    //                 'source' => $card_token
    //             ]);         
    //             $stripe_id = $customer['id'];
    //             try {
    //                 $charge = $stripe->charges()->create([
    //                     'customer' => $stripe_id,
    //                     'currency' => 'USD',
    //                     'amount' => $geoLocAmount
    //                 ]);
    //                 if($charge['status'] == 'succeeded') {
    //                     $statusPayment =  DB::table('dummy_paymenthistory')->insert(
    //                             ['companyid' => (int)$userID,
    //                             'transactionid' => $charge['balance_transaction'],
    //                             'tokenused' => $card_token,
    //                             'transactionfor' => 'geolocationfee',
    //                             'amount' => $geoLocAmount,
    //                             'status' => 'approved' ,
    //                             'fingerprintid' => $charge['source']['fingerprint'] ,
    //                             'cardid' => $tokenData['card']['id'],
    //                             'expiredate' => date('Y-m-d H:i:s'),
    //                             'created_at' => date('Y-m-d H:i:s'),
    //                             'updated_at' => date('Y-m-d H:i:s')
    //                             ]);
    //                     if($statusPayment) {
    //                         return response()->json(['success' => true], $this->successStatus);
    //                     } else {
    //                         return response()->json(['error'=>'entryfail'], 401);
    //                     }
    //                 } else {
    //                     // generate exception
    //                     return response()->json(['error'=>'paymenterror'], 401);
    //                 }
    //             } catch (Exception $e) {
    //                 return response()->json(['error'=>$e->getMessage()], 401);
    //             } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
    //                 return response()->json(['error'=>$e->getMessage()], 401);
    //             } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
    //                 return response()->json(['error'=>$e->getMessage()], 401);
    //             }
    //         } else {
    //             $paymentHistroy = DB::table('dummy_paymenthistory')->where('companyid' ,'=',(int)$userID)->where('fingerprintid' ,'=',$tokenData['card']['fingerprint'])->first();
    //             if(!$paymentHistroy) {
    //                 $card = $stripe->cards()->create($userDetail['stripeid'], $card_token); //Add
    //             } else {
    //                 $card = 'already';
    //                 $cardID = $paymentHistroy->cardid;
    //             }
    //             if(!empty($card)) {
    //                 try {
    //                     if($card != 'already') {
    //                         $cardID = $tokenData['card']['id'];
    //                     }
    //                     $customer = $stripe->customers()->update( $userDetail['stripeid'], [
    //                         'default_source' => $cardID
    //                     ]);
    //                     $charge = $stripe->charges()->create([
    //                         'customer' => $userDetail['stripeid'],
    //                         'currency' => 'USD',
    //                         'amount' => $geoLocAmount
    //                     ]);
    //                     if($charge['status'] == 'succeeded') {
    //                         $statusPayment =  DB::table('dummy_paymenthistory')->insert(
    //                             ['companyid' => (int)$userID,
    //                             'transactionid' => $charge['balance_transaction'],
    //                             'tokenused' => $card_token,
    //                             'transactionfor' => 'registrationfee',
    //                             'amount' => $geoLocAmount,
    //                             'status' => 'approved' ,
    //                             'fingerprintid' => $charge['source']['fingerprint'] ,
    //                             'cardid' => $cardID,
    //                             'expiredate' => $nextDate,
    //                             'created_at' => date('Y-m-d H:i:s'),
    //                             'updated_at' => date('Y-m-d H:i:s')
    //                             ]);
    //                         if($statusPayment) {
    //                             return response()->json(['success' => true], $this->successStatus);
    //                         } else {
    //                             return response()->json(['error'=>'entryfail'], 401);
    //                         }
    //                     } else {
    //                         // generate exception
    //                         return response()->json(['error'=>'paymenterror'], 401);
    //                     }
    //                 } catch (Exception $e) {
    //                     return response()->json(['error'=>$e->getMessage()], 401);
    //                 } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
    //                     return response()->json(['error'=>$e->getMessage()], 401);
    //                 } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
    //                     return response()->json(['error'=>$e->getMessage()], 401);
    //                 }
    //             } else {
    //                 return response()->json(['error'=>'networkerror'], 401); 
    //             }                
    //         }
    //     } catch(Exception $e) {
    //         $ex_message =  $e->getMessage();
    //         return response()->json(['error'=>$e->getMessage()], 401); 
    //     } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
    //         $ex_message = $e->getMessage();
    //         return response()->json(['error'=>$e->getMessage()], 401); 
    //     } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
    //         $ex_message = $e->getMessage();
    //         return response()->json(['error'=>$e->getMessage()], 401); 
    //     }
    // }

    // get company details //
    public function getClaimedCompanyDetail(Request $request) {
        $authid = request('id');
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('dummy_registration')
            ->where('id', '=', (int)$authid)
            ->where('usertype', '=', 'company')
            ->where('is_claim_user','=','1')
            ->where('status','=','active')
            ->select('dummy_registration.*')
            ->first();
            if(!empty($usersdata)) {
				$engines_workedArr = [];
                if($usersdata->engines_worked != null) {
					$engines_worked = (array)json_decode($usersdata->engines_worked);
					if(!empty($engines_worked['saved']) && count($engines_worked['saved']) > 0) {
						$engines_workedData = Boat_Engine_Companies::select('name')->whereIn('id',$engines_worked['saved'])->where('category','engines')->where('status','1')->get();
						if(!empty($engines_workedData) && count($engines_workedData) > 0 ) {
							foreach($engines_workedData as $val) {
								$engines_workedArr[] = $val->name;
							}
						}
					}
					if(!empty($engines_worked['other']) && count($engines_worked['other']) > 0) {
						for($i = 0 ; $i < count($engines_worked['other']) ; $i++ ) {
							$engines_workedArr[] = $engines_worked['other'][$i];
						}
					}
				}
				$boats_yachts_workedArr = [];
				if($usersdata->boats_yachts_worked != null) {
					$boats_yachts_worked = (array)json_decode($usersdata->boats_yachts_worked);
					if(!empty($boats_yachts_worked['saved']) && count($boats_yachts_worked['saved']) > 0) {
						$boats_yachts_workedData = Boat_Engine_Companies::select('name')->whereIn('id',$boats_yachts_worked['saved'])->where(function($query) {
							$query->where('category', '=', 'boats')
							->orWhere('category', '=', 'yachts');
						})->where('status','1')->get();
						if(!empty($boats_yachts_workedData) && count($boats_yachts_workedData) > 0 ) {
							foreach($boats_yachts_workedData as $val) {
								$boats_yachts_workedArr[] = $val->name;
							}
						}
					}
					if(!empty($boats_yachts_worked['other']) && count($boats_yachts_worked['other']) > 0) {
						for($i = 0 ; $i < count($boats_yachts_worked['other']) ; $i++ ) {
							$boats_yachts_workedArr[] = $boats_yachts_worked['other'][$i];
						}
					}
				}
                
                return response()->json(['success' => true,'data' => $usersdata,'boatandyachtworked' => $boats_yachts_workedArr ,'enginesworked' => $engines_workedArr ], $this->successStatus);
                //return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                $usersdataError = DB::table('dummy_registration')
                ->where('id', '=', (int)$authid)
                ->select('dummy_registration.authid')
                ->first();
                if(!empty($usersdataError)) {
					$engines_workedArr = [];
					if($usersdataError->engines_worked != null) {
						$engines_worked = (array)json_decode($usersdataError->engines_worked);
						if(!empty($engines_worked['saved']) && count($engines_worked['saved']) > 0) {
							$engines_workedData = Boat_Engine_Companies::select('name')->whereIn('id',$engines_worked['saved'])->where('category','engines')->where('status','1')->get();
							if(!empty($engines_workedData) && count($engines_workedData) > 0 ) {
								foreach($engines_workedData as $val) {
									$engines_workedArr[] = $val->name;
								}
							}
						}
						if(!empty($engines_worked['other']) && count($engines_worked['other']) > 0) {
							for($i = 0 ; $i < count($engines_worked['other']) ; $i++ ) {
								$engines_workedArr[] = $engines_worked['other'][$i];
							}
						}
					}
					$boats_yachts_workedArr = [];
					if($usersdataError->boats_yachts_worked != null) {
						$boats_yachts_worked = (array)json_decode($usersdataError->boats_yachts_worked);
						if(!empty($boats_yachts_worked['saved']) && count($boats_yachts_worked['saved']) > 0) {
							$boats_yachts_workedData = Boat_Engine_Companies::select('name')->whereIn('id',$boats_yachts_worked['saved'])->where(function($query) {
								$query->where('category', '=', 'boats')
								->orWhere('category', '=', 'yachts');
							})->where('status','1')->get();
							if(!empty($boats_yachts_workedData) && count($boats_yachts_workedData) > 0 ) {
								foreach($boats_yachts_workedData as $val) {
									$boats_yachts_workedArr[] = $val->name;
								}
							}
						}
						if(!empty($boats_yachts_worked['other']) && count($boats_yachts_worked['other']) > 0) {
							for($i = 0 ; $i < count($boats_yachts_worked['other']) ; $i++ ) {
								$boats_yachts_workedArr[] = $boats_yachts_worked['other'][$i];
							}
						}
					}
					
					return response()->json(['success' => true,'data' => $usersdataError,'boatandyachtworked' => $boats_yachts_workedArr ,'enginesworked' => $engines_workedArr ], $this->successStatus);
                    //return response()->json(['success' => false,'data' => $usersdataError], $this->successStatus);
                } else {
                   return response()->json(['error'=>'networkerror'], 401); 
                }
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }

    // edit new user //
    public function editclaimedcompany(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            'services' => 'required',
            'city' => 'required',
            'state' => 'required',
            'about' => 'required',
            'country' => 'required',
            // 'county' => 'required',
            'zipcode' => 'required',
            'contact' => 'required',
            'email' => 'bail|required|E-mail',
            'businessemail' => 'bail|required|E-mail',
            'contactname' => 'required',
            'contactemail' => 'bail|required|E-mail',
            'contactmobile' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $auth   = $detailArr = array(); 
        $updated = $detailUpdate = 0;
        $authid = request('id');
        if(!empty($authid) && $authid > 0) {
            $address = request('address');
            $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
            $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];
            $company_name = request('name');
            $allservices = json_decode(request('allservices'));
            
            $company_name = request('name');
            $company_name_new  = preg_replace('/[^a-zA-Z0-9_ -]/s','',$company_name);
            $array = explode(" ",$company_name_new);
            if(is_array($array)) {
                $slug = implode("-",$array);       
            }

            $slug1 = '';
            $array = explode(" ",request('city'));
            if(is_array($array)) {
                $slug1 = implode("-",$array);       
            }
            $slug = strtolower($slug.'-'.$slug1);
            $realSlug = strtolower($slug);
            $countSlugs = 0;
            $validSlug = false;
            for($i = 0 ; $validSlug != true ; $i++) {
                $checkSlug = Companydetail::where('actualslug','=',strtolower($slug))->where('authid', '!=', (int)$authid)->count();
                $checkSlugEdit = Companydetail::where('slug','=',strtolower($slug))->where('authid', '!=', (int)$authid)->count();
                if($checkSlug) {
                    $countSlugs = $countSlugs +$checkSlug;
                    $slug = $realSlug.'-'.($countSlugs);
                } else if($checkSlugEdit) {
                    $countSlugs = $countSlugs + 1;
                    $slug = $realSlug.'-'.($countSlugs);
                } else {
                    $validSlug = true;
                } 
            }
            // $checkSlug = Claimed_business::where('slug','=',strtolower($slug))->get()->count();
            // $checkCompanySlug = Companydetail::where('slug','=',strtolower($slug))->get()->count();
            // $checkSlug = $checkSlug + $checkCompanySlug;
            // if($checkSlug) {
            //     $slug = $slug.'_'.($checkSlug+1);
            // }
            $address = request('address');
            $websiteurl = request('websiteurl');
			$boatYachtJson = request('boatYachtworked');
			$emptyboatYachtworked = true;
			$boatYachtworkedArray  = array();
			$i = 0;
			$j = 0;
			if(!empty($boatYachtJson)) {
				$boatYachtworked = json_decode(request('boatYachtworked'));
				$checkBoat = [];
                $boatYachtworked = json_decode(request('boatYachtworked'));
                foreach ($boatYachtworked as $val) {
                    if($val && !in_array($val,$checkBoat)) {
                        $checkBoat[] = $val;
                        $boatYachtworkedData = [];
                        $boatYachtworkedData = Boat_Engine_Companies::whereRaw("lower(name) = '".strtolower($val)."'")->where(function($query) {
                            $query->where('category', '=', 'boats')
                            ->orWhere('category', '=', 'yachts');
                        })->where('status','1')->get();
                        if(!empty($boatYachtworkedData) && count($boatYachtworkedData) > 0) {
                            $boatYachtworkedArray['saved'][$i] = $boatYachtworkedData[0]->id;
                            $i++;
                        } else {
                            $boatYachtworkedArray['other'][$j] = $val;
                            $j++;
                        }
                        $emptyboatYachtworked = false;
                    }
                }
			}
			$boatYachtObj = json_encode($boatYachtworkedArray);
			
			$engineJson = request('engineworked');
			$emptyengineworked = true;
			$engineworkedArray  = array();
			$i = 0;
			$j = 0;
			if(!empty($engineJson)) {
				$engineworked = json_decode(request('engineworked'));
				$checkEngine = [];
                foreach ($engineworked as $val) {
                    if($val && !in_array($val,$checkEngine)){
                        $checkEngine[] = $val;
                        $engineworkedData = [];
                        $engineworkedData = Boat_Engine_Companies::whereRaw("lower(name) = '".strtolower($val)."'")->where('category','engines')->where('status','1')->get();
                        if(!empty($engineworkedData) && count($engineworkedData) > 0) {
                            $engineworkedArray['saved'][$i] = $engineworkedData[0]->id;
                            $i++;
                        } else {
                            $engineworkedArray['other'][$j] = $val;
                            $j++;
                        }
                        $emptyengineworked = false;
                    }
                }
			}
			$engineObj = json_encode($engineworkedArray);
            $detailArr['email'] = strtolower(request('email'));
            $detailArr['businessemail']  = request('businessemail');
            $detailArr['name'] = request('name');
            $detailArr['actualslug'] = strtolower($realSlug);
            $detailArr['slug'] = strtolower($slug);
            $detailArr['services'] = request('services');
            $detailArr['city'] = request('city');
            $detailArr['address'] = ((isset($address) && $address !='') ? request('address'): NULL);
            $detailArr['websiteurl'] = ((isset($websiteurl) && $websiteurl !='') ? request('websiteurl'): NULL);
            $detailArr['state'] = request('state');
            $detailArr['country'] = request('country');
            // $detailArr['county'] = request('county');
            $detailArr['zipcode'] = request('zipcode');
            $detailArr['contact'] = request('contact');
            $detailArr['about'] = request('about');
            $detailArr['contactemail'] = request('contactemail');
            $detailArr['contactname'] = request('contactname');
            $detailArr['contactmobile'] = request('contactmobile');
            $detailArr['longitude']  = $longitude;
            $detailArr['allservices'] =  ((isset($allservices) && $allservices !='') ? json_encode($allservices,JSON_UNESCAPED_SLASHES): NULL);
            $detailArr['latitude']   = $latitude;
            $country_code = request('country_code');
                if($country_code != '') {
                    $pos = strpos($country_code, '+');
                    if(!$pos){
                        $country_code ='+'.$country_code;
                    }
                }   
            $detailArr['country_code']  = $country_code;
            $detailArr['boats_yachts_worked']    = ($emptyboatYachtworked) ? NULL : $boatYachtObj;
            $detailArr['engines_worked']    = ($emptyengineworked) ? NULL : $engineObj;
            $detailUpdate =  dummy_registration::where('id', '=', (int)$authid)->update($detailArr);
            
            if($detailUpdate) {
                return response()->json(['success' => true,'authid' => $authid], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    public function importDummyCompanyData(Request $request) {

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
        if(!empty($csvData) && count($csvData) < 100 ) {
         // if(0){
			$boartAndYachtData = Boat_Engine_Companies::where(function($query) {
								$query->where('category', '=', 'boats')
								->orWhere('category', '=', 'yachts');})->where('status','=','1')->select('id',  DB::raw('lower(name) as name'))->get()->toArray();
			$engineData = Boat_Engine_Companies::where('category', '=', 'engines')->where('status','=','1')->select('id', DB::raw('lower(name) as name'))->get()->toArray();
			$boats_yachts_workedArr = [];
            foreach ($boartAndYachtData as $val) {
                $boats_yachts_workedArr[$val['id']] = $val['name'];
            }
            $engines_workedArr = [];
            foreach ($engineData as $val) {
                $engines_workedArr[$val['id']] = $val['name'];
            }
            $allservices = Service::where('status','=','1')->select('id', 'service as itemName','category')->get()->toArray();
            $allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
            $newallCategory = [];
            foreach ($allCategory as $val) {
                $newallCategory[$val['id']] = $val['categoryname'];
            }
            $newallservices = [];
            foreach ($allservices as $val) {
                $newallservices[$val['id']]['itemName'] = $val['itemName'];
                $newallservices[$val['id']]['id'] = $val['id'];
                $newallservices[$val['id']]['category'] = $val['category'];
            }
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
                        $auth  = new Auth; 
                        $userid = 0;
                        $auth->email = strtolower($csvDatas->email);
                        $auth->password = Hash::make($csvDatas->password);
                        $auth->usertype = 'company';
                        $auth->ipaddress = $this->getIp();
                        $auth->status = 'active';
                        $auth->stepscompleted ='3';    
                        $auth->addedby=1;
                        $auth->accounttype = 'dummy';
                        $auth->is_activated = '1';
                        // $newsletter = (!empty($csvDatas->newsletter) && (strtolower($csvDatas->newsletter) == 'yes')) ? '1':'0';
                        // $auth->newsletter = $newsletter;
                        if($auth->save()) {
                            $companyid = $auth->id;
                        } else {
                            $isError = true;
                        }
                        if($companyid) {
                            $address = $csvDatas->address;
                            $locAddress = ((isset($address) && $address !='') ? $csvDatas->address.' ': '');
                            $location = $locAddress.((isset($csvDatas->city) && $csvDatas->city !='') ? $csvDatas->city.' ': '').((isset($csvDatas->zipcode) && $csvDatas->zipcode !='') ? $csvDatas->zipcode.' ': '').((isset($csvDatas->state) && $csvDatas->state !='') ? $csvDatas->state.' ': '').' , United States';
                            $output = $this->getGeoLocation($location); //Get Location from location Trait
                            $longitude = $output['longitude'];
                            $latitude = $output['latitude'];
                            //calculate services 
							if(!empty($csvDatas->service) && $csvDatas->service != '') {
								$serviceStr = json_decode($csvDatas->service);
								$service = explode(',',$serviceStr);
								$category = [];
								$finalService = [];
								$servicesIds = [];
								foreach ($service as $skey => $svalue) {  
									$start = strpos($svalue,"(")+1;
									$end = strpos($svalue,')');
									$tempcat  = substr($svalue,$start,$end - $start);
									$servicename = substr($svalue,0,$start-1);
									if(!in_array($tempcat, $category)) {
										foreach ($newallCategory as $c => $val) {
											if(strtolower($val) == strtolower($tempcat)) {
												$category[$val] = $c;
												$serviceId = 0;
												foreach ($newallservices as $ns => $nsval) {
													if($nsval['category'] == $c && trim(strtolower($nsval['itemName'])) == trim(strtolower($servicename))) {
														$finalService[$c][] = $nsval['id'];
														$servicesIds[] = $nsval['id'];
													}
												}
											}
										}
									}    
								}
								$allservices = $finalService;
							} else {
								$allservices = [];
							}
                            
                            
                            /// boats, Yachts and engines worked ///
                            if(!empty($csvDatas->boats_yachts_worked) && $csvDatas->boats_yachts_worked !='') {
								$boats_yachts_workedStr = json_decode($csvDatas->boats_yachts_worked);
								$boats_yachts_worked = explode(',',$boats_yachts_workedStr);
								$boatYachtworkedData = [];
								$emptyBoatAndYacht = true;
								$i = 0;
								$j = 0;
								foreach ($boats_yachts_worked as $val) {
									$boatandYachtexist = '';
									$boatandYachtexist = array_search(strtolower(trim($val)),$boats_yachts_workedArr);
									if($boatandYachtexist != null) {
										$boatYachtworkedData['saved'][$i] = $boatandYachtexist;
										$emptyBoatAndYacht = false;
										$i++;
									} else {
										$boatYachtworkedData['other'][$j] = trim($val);
										$emptyBoatAndYacht = false;
										$j++;
									}
								}
								$boatYachtObj = '';
								$boatYachtObj = json_encode($boatYachtworkedData);
							} else {
								$emptyBoatAndYacht = true;
							}
							if(!empty($csvDatas->engines_worked) && $csvDatas->engines_worked !='') {
								$engines_workedStr = json_decode($csvDatas->engines_worked);
								$engines_worked = explode(',',$engines_workedStr);
								
								$EnginesworkedData = [];
								$emptyEngines = true;
								$i = 0;
								$j = 0;
								foreach ($engines_worked as $val) {
									$engineexist = '';
									$engineexist = array_search(strtolower(trim($val)),$engines_workedArr);
									if($engineexist != null) {
										$EnginesworkedData['saved'][$i] = $engineexist;
										$emptyEngines = false;
										$i++;
									} else {
										$EnginesworkedData['other'][$j] = trim($val);
										$emptyEngines = false;
										$j++;
									}
								}
								$enginesObj = '';
								$enginesObj = json_encode($EnginesworkedData);
							} else {
								$emptyEngines = true;
							}
                            
                            ////////////////////////////////////////
                            $company_name = $csvDatas->businessname;
                            $company_name_new  = preg_replace('/[^a-zA-Z0-9_ -]/s','',$company_name);
                            $array = explode(" ",$company_name_new);
                            if(is_array($array)) {
                                $slug = implode("-",$array);       
                            }
                            $slug1 = '';
                            if(!empty($csvDatas->city) && $csvDatas->city != '') {
								$array = explode(" ",$csvDatas->city);
								if(is_array($array)) {
									$slug1 = implode("-",$array);       
								}
								$slug = strtolower($slug.'-'.$slug1);
							} else {
								 $slug = strtolower($slug);
							}
                            
                            $realSlug = $slug;
                            $countSlugs = 0;
                            $validSlug = false;
                            for($i = 0 ; $validSlug != true ; $i++) {
                                $checkSlug = Companydetail::where('actualslug','=',strtolower($slug))->count();
                                $checkSlugEdit = Companydetail::where('slug','=',strtolower($slug))->count();
                                if($checkSlug) {
                                    $countSlugs = $countSlugs +$checkSlug;
                                    $slug = $realSlug.'-'.($countSlugs);
                                } else if($checkSlugEdit) {
                                    $countSlugs = $countSlugs + 1;
                                    $slug = $realSlug.'-'.($countSlugs);
                                } else {
                                    $validSlug = true;
                                } 
                            }
                            
                            $address = $csvDatas->address;
                            $websiteurl = $csvDatas->websiteurl;
                            $companydetail  = new Companydetail; 
                            $companydetail->authid  = $companyid;
                            $companydetail->name  = $csvDatas->businessname;
                            $companydetail->actualslug = strtolower($realSlug);
                            $companydetail->slug = strtolower($slug);
                            $companydetail->allservices   = ((isset($servicesIds) && $servicesIds !='') ? json_encode($servicesIds,JSON_UNESCAPED_SLASHES): NULL);
                            $companydetail->businessemail   = $csvDatas->contactemail;
                            $companydetail->address    = ((isset($address) && trim($address) !='') ? $csvDatas->address: NULL);
                            $companydetail->websiteurl    = ((isset($websiteurl) && $websiteurl !='') ? $websiteurl: NULL);
                            $companydetail->services =  ((isset($allservices) && $allservices !='') ? json_encode($allservices,JSON_UNESCAPED_SLASHES): NULL);
                            $companydetail->city       = $csvDatas->city;
                            $companydetail->state      = $csvDatas->state;
                            $companydetail->country    = 'United States';
                            // $companydetail->county    = request('county');
                            $companydetail->about    = ((isset($csvDatas->about) && trim($csvDatas->about) !='') ? $csvDatas->about: NULL);
                            $companydetail->zipcode    = $csvDatas->zipcode;
                            $companydetail->contact    =$csvDatas->telephone;
                            $companydetail->longitude  = $longitude;
                            $companydetail->latitude   = $latitude;
                            $companydetail->contactname  = ((isset($csvDatas->contactname) && trim($csvDatas->contactname) !='') ? $csvDatas->contactname: NULL);
                            $companydetail->contactmobile  = ((isset($csvDatas->contactmobile) && trim($csvDatas->contactmobile) !='') ? $csvDatas->contactmobile: NULL);
                            $companydetail->contactemail  = ((isset($csvDatas->contactemail) && trim($csvDatas->contactemail) !='') ? $csvDatas->contactemail: NULL);
                            $companydetail->accounttype   = 'dummy';
                            $companydetail->status = 'active';
                            // $companydetail->country_code  = request('country_code');
                            
                            $companydetail->country_code   = "+1";
                            $companydetail->boats_yachts_worked    = ($emptyBoatAndYacht) ? NULL : $boatYachtObj;
							$companydetail->engines_worked    = ($emptyEngines) ? NULL : $enginesObj;
                            if($companydetail->save()) {
                                $insertIds[] = $companydetail->id;
                                $plandata = DB::table('subscriptionplans')->where('isadminplan', '=', '1')->where('status', '=', 'active')->first();
                                if(empty($plandata)) {
                                    $isError = ture;
                                }
                                $subplan = $plandata->id;
                                $nextDate = date('Y-m-d 00:00:00', strtotime("+18 years", strtotime(date('Y-m-d H:i:s'))));
                                
                                $statusCompany = Companydetail::where('authid', (int)$companyid)->update(['subscriptiontype' => 'manual','nextpaymentdate' => $nextDate, 'paymentplan' => (int)($subplan),'next_paymentplan' => (int)($subplan),'plansubtype' => 'free','status' => 'active']);
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
            ImportUsers::dispatch($csvData,$adminId,'dummy_company');
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
    
         // get company details //
    public function getassignadmindetail(Request $request) {
        $authid = request('id');
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('companydetails')
            ->where('companydetails.authid', '=', (int)$authid)
            ->where('companydetails.accounttype','dummy')
            ->select('companydetails.authid','companydetails.assign_admin','companydetails.admin_note')
            ->first();
            if(!empty($usersdata)) {
				$adminNoteArr = [];
				$adminData = $adminDataArray =  [];
				$adminNotesArray = [];
				$i = 0;
				if($usersdata->admin_note != null && $usersdata->admin_note != '') {
					$adminData = DB::table('auths')->where('usertype','admin')->where('status','!=' , 'deleted')->get();
					if(!empty($adminData) && count($adminData) > 0) {
						foreach($adminData as $adminDatas) {
							$adminDataArray[$adminDatas->id] = $adminDatas->firstname_admin.' '.$adminDatas->lastname_admin;
						}
						$adminNoteArr = (array)json_decode($usersdata->admin_note);
						foreach($adminNoteArr as $adminNoteArrs) {
							if (array_key_exists($adminNoteArrs->id,$adminDataArray)){
								$adminNotesArray[$i]['name'] =  $adminDataArray[$adminNoteArrs->id];
								$adminNotesArray[$i]['id'] =  (int)$adminNoteArrs->id;
								$adminNotesArray[$i]['note'] =  $adminNoteArrs->note;
								$adminNotesArray[$i]['date'] =  $adminNoteArrs->date;
								$i++;
							}
						}
						$usersdata->admin_note = $adminNotesArray;
						return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
					} else {
						 return response()->json(['error'=>'networkerror'], 401);
					}
				} else{
					$usersdata->admin_note = [];
					return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
				}
				
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }
    
      // edit assign info //
    public function editAssignInfo(Request $request) {
        $validate = Validator::make($request->all(), [
            'issuperadmin' => 'required',
            'id' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $auth   = $detailArr = array(); 
        $updated = $detailUpdate = 0;
        $authid = request('id');
        $adminId = request('adminId');
        $adminNoteArray = [];
        $issuperadmin = request('issuperadmin');
        $adminassign = request('admin');
		$adminnote = request('adminnote');
        if(!empty($authid) && $authid > 0) {
            $CompanyData =  Companydetail::where('authid', '=', (int)$authid)->where('accounttype','dummy')->get();
            if(!empty($CompanyData)) {
				
				if(!empty($CompanyData[0]->admin_note) && $CompanyData[0]->admin_note != null && $CompanyData[0]->admin_note != '') {
					$adminNotes = (array)json_decode($CompanyData[0]->admin_note);
					$isExist = false;
					$i = 0;
					foreach($adminNotes as $adminNotess) {
						if($adminNotess->id == $adminId ) {
							if(!empty($adminnote) && $adminnote != '' ) {
								$adminNoteArray[$i]['id'] = $adminNotess->id;
								$adminNoteArray[$i]['note'] = $adminnote;
								$adminNoteArray[$i]['date'] = date('Y-m-d H:i:s');
								$i++;
							}
							$isExist = true;
						} else {
							$adminNoteArray[$i]['id'] = $adminNotess->id;
							$adminNoteArray[$i]['note'] = $adminNotess->note;
							$adminNoteArray[$i]['date'] = $adminNotess->date;
							$i++;
						}
					}
					if($isExist == false) {
						if(!empty($adminnote) && $adminnote != '' ) {
							$adminNoteArray[$i]['id'] = $adminId;
							$adminNoteArray[$i]['note'] = $adminnote;
							$adminNoteArray[$i]['date'] = date('Y-m-d H:i:s');
						}
					}
				} else {
					if(!empty($adminnote) && $adminnote != '' ) {
						$adminNoteArray[0]['id'] = (int)$adminId;
						$adminNoteArray[0]['note'] = $adminnote;
						$adminNoteArray[0]['date'] = date('Y-m-d H:i:s');
					}
				}
				$detailArr = [];
				if(!empty($issuperadmin) && $issuperadmin == 'true') {
					$detailArr['assign_admin']    = ((isset($adminassign) && $adminassign !='') ? (int)$adminassign: NULL);
				}
				$detailArr['admin_note']    = json_encode($adminNoteArray) ;
                $detailUpdate =  Companydetail::where('authid', '=', (int)$authid)->update($detailArr);
                if($detailUpdate) {
				     return response()->json(['success' => true,'authid' => $authid], $this->successStatus);
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
    
    // delete note //
    public function deleteNote(Request $request) {
        $authid = (int)request('id');
        $compid = (int)request('compid');
        $updated = 0;
        if(!empty($authid) && $authid > 0 ) {
            $CompanyDetail = Companydetail::where('authid', '=', (int)$compid)->where('accounttype','dummy')->first();
                if(!empty($CompanyDetail)) {
                    if(!empty($CompanyDetail->admin_note) && $CompanyDetail->admin_note != null && $CompanyDetail->admin_note != '') {
					$adminNotes = (array)json_decode($CompanyDetail->admin_note);
					$i = 0;
					$adminNoteArray = [];
					foreach($adminNotes as $adminNotess) {
						if($adminNotess->id != $authid ) {
							$adminNoteArray[$i]['id'] = $adminNotess->id;
							$adminNoteArray[$i]['note'] = $adminNotess->note;
							$adminNoteArray[$i]['date'] = $adminNotess->date;
							$i++;
						}
					}
					$detailArr = [];
					$detailArr['admin_note']    = json_encode($adminNoteArray) ;
					$detailUpdate =  Companydetail::where('authid', '=', (int)$compid)->update($detailArr);
					if($detailUpdate) {
						 return response()->json(['success' => true,'authid' => $authid], $this->successStatus);
					} else {
						return response()->json(['error'=>'networkerror'], 401); 
					}
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

}
