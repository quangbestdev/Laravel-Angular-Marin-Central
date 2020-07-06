<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Auth;
use App\Service;
use DB;
use App\Companydetail;
use App\dummy_registration;
use App\Geolocation;
use App\Claimed_business;
use Illuminate\Support\Facades\Hash;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Validator;
use Stripe\Error\Card;
use Cartalyst\Stripe\Stripe;
use Exception;
use App\User;
use App\Webhook;
use App\Category;
use App\Dictionary;
use App\Quoterequests;
use App\Requestviewpage;
use App\Businessviewpage;
use App\jobsviewpage;
use App\RequestProposals;
use App\Jobs;
use App\Businesslistingcount;
use App\Businesstelephone;
use App\ServiceRequestReviews;
use App\Boat_Engine_Companies;
use App\Http\Traits\NotificationTrait;
use Carbon;
use App\Http\Traits\LocationTrait;
use Braintree_ClientToken;
use Braintree_Transaction;
use Braintree_Customer;
use Braintree_WebhookNotification;
use Braintree_WebhookTesting;
use Braintree_Subscription;
use Braintree_PaymentMethod;
use Braintree_PaymentMethodNonce;
use App\Jobs\ImportUsers;
use App\Http\Traits\ImportTrait;
use App\Http\Traits\ZapierTrait;
			
class CompanyController extends Controller
{   use ImportTrait;
    public $successStatus = 200;
    use LocationTrait;
    use NotificationTrait;
    use ZapierTrait;
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

        $statusFilter = request('statusFilter');
        $reg_type = request('socialFilter');
        $planfilter = request('plan');
        $statefilter = request('state');
        $cityfilter = request('city');
        $zipcodefilter = request('zipcode');
        $searchString = request('searchString');
        $adminassign = request('admin');
        $assign = request('assign');
        $adminid = request('adminid');
        $userid = 0;
        if(!empty($adminid)) {
			$userid =(int)$adminid;
		}
        
        $page = request('page');
        $orderBy = request('order');
        $reverse = request('reverse');
        $order = ($reverse == 'false')?'ASC':'DESC';
        if($page == 0 || $page == null || !(int)$page) {
            $page = 0;
        }
        $limit = 30;
        $offset = 0;
        if($page > 0) {
            $offset = ($page - 1)*$limit;
        }

        $whereCompany = '';
        $currentTime = date('Y-m-d 00:00:00');
        if(!empty($statusFilter) && ($statusFilter != 'all' && $statusFilter != '' )) {
            if($statusFilter == 'pending') {
               $whereCompany  = "companydetails.paymentplan = 0 AND companydetails.account_type = 'paid'";
            } else if($statusFilter == 'expire') {
               $whereCompany  = "((companydetails.account_type = 'paid' AND companydetails.paymentplan > 0 AND companydetails.nextpaymentdate  < '".$currentTime."') OR (companydetails.account_type = 'free' AND companydetails.free_subscription_end  < '".$currentTime."'))";
            } else if($statusFilter == 'free') {
                $whereCompany = "companydetails.account_type = 'free'";
            } 
        }
        if(!empty($planfilter) && $planfilter != 'all' && $planfilter != '') {
			if($planfilter == 'marinepro' || $planfilter == 'advanced' || $planfilter == 'basic' || $planfilter == 'payperlead') {
				$plannameArr = array('marinepro' => 'Marine Pro','advanced' => 'Advanced' , 'basic' => 'Basic' , 'payperlead' => 'Free');
				$plandata = DB::table('subscriptionplans')->where('planname','ILIKE','%'.$plannameArr[$planfilter].'%')
                    ->where('status','=','active')
                    ->where('isadminplan','=','0')
                    ->first();
				if(!empty($plandata)) {
					$planid = $plandata->id;
					if($whereCompany == '') {
						$whereCompany = "companydetails.paymentplan = '".$planid."' AND companydetails.nextpaymentdate > '".date('Y-m-d H:i:s')."'";
					} else {
						$whereCompany .= " AND companydetails.paymentplan = '".$planid."' AND companydetails.nextpaymentdate > '".date('Y-m-d H:i:s')."'";
					}
				} else {
					return response()->json(['error'=>'networkerror'], 401); 
				}
			}
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
       
        if($reg_type != 'all' && $reg_type != '') {
            if($reg_type == 0 || $reg_type == 1) {
                if($whereCompany == '') {
                    $whereCompany = "auths.is_social = '".$reg_type."'";       
                } else {
                    $whereCompany .= " AND auths.is_social = '".$reg_type."'";
                }
            }
        }
        
        if(!empty($searchString) && $searchString !='') {
			$searchString = implode("''",explode("'",trim($searchString)));
            $searchString = strtolower($searchString);
            if($whereCompany == '') {
                    $whereCompany = "LOWER(companydetails.name) LIKE '%".$searchString."%'";       
                } else {
                    $whereCompany .= " AND LOWER(companydetails.name) LIKE '%".$searchString."%'";
                }
        }


        $query = DB::table('auths')
            ->Join('companydetails', 'auths.id', '=', 'companydetails.authid')
            ->leftJoin('subscriptionplans as sp','companydetails.paymentplan','=','sp.id')
            ->where('auths.usertype', '=', 'company')
            ->where('auths.status', '!=', 'deleted')
            ->where('companydetails.accounttype','!=','dummy')
            ->select('auths.email','auths.is_social','auths.stepscompleted','auths.id as userauthid','auths.usertype','auths.status', 'companydetails.*','sp.planname')
            ->orderBy('auths.created_at', 'DESC');
        if($whereCompany != '') {
           $query =  $query->whereRaw($whereCompany);
        }
        $totalrecords = $query->count();
        $query2 = DB::table('auths')
            ->Join('companydetails', 'auths.id', '=', 'companydetails.authid')
            ->leftJoin('subscriptionplans as sp','companydetails.paymentplan','=','sp.id')
            ->where('auths.usertype', '=', 'company')
            ->where('auths.status', '!=', 'deleted')
            ->where('companydetails.accounttype','!=','dummy');
        if($whereCompany != '') {
           $query2 =  $query2->whereRaw($whereCompany);
        }
        $query2 = $query2->select('auths.email','auths.is_social','auths.stepscompleted','auths.id as userauthid','auths.usertype','auths.status', 'companydetails.*','sp.planname');
        if($orderBy == 'data.created_at') {
            $query2 = $query2->orderBy('auths.created_at', 'DESC');
        } else if($orderBy == 'name') {
            $query2 = $query2->orderBy('companydetails.name', $order);
        } else if($orderBy == 'created_at') {
            $query2 = $query2->orderBy('companydetails.created_at', $order);
        }
        $usersdata = $query2
            ->skip($offset)
            ->take($limit)
            ->get();
        $currentDate = Date('Y-m-d H:i:s');
        if(!empty($usersdata)) {
            return response()->json(['success' => true,'data' => $usersdata,'totalrecords' => $totalrecords,'offset' => $offset,'currentdate' => $currentDate ], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    
    // export business
    public function exportCompanyData() {
        $data = DB::table('auths')
            ->Join('companydetails', 'auths.id', '=', 'companydetails.authid')
            ->where('auths.usertype', '=', 'company')
            ->where('auths.status', '!=', 'deleted')
            ->where('companydetails.accounttype','!=','dummy')
            ->select('companydetails.name','auths.email','companydetails.address','companydetails.city','companydetails.state','companydetails.zipcode','companydetails.websiteurl','companydetails.contactname','companydetails.contactmobile','companydetails.contactemail','companydetails.about','companydetails.businessemail','companydetails.contact','companydetails.services')
            ->orderBy('auths.created_at', 'DESC')
            ->get();
        if(!empty($data)) {
            $allservices = Service::where('status','=','1')->orWhere('category','=','11')->select('id', 'service as itemName','subcategory')->get()->toArray();
            $allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
            $newallCategory = [];
            foreach ($allCategory as $val) {
                $newallCategory[$val['id']] = $val['categoryname'];
            }
            $newallservices = [];
            foreach ($allservices as $val) {
                $newallservices[$val['id']]['itemName'] = $val['itemName'];
                $newallservices[$val['id']]['id'] = $val['id'];
                $newallservices[$val['id']]['subcategory'] = $val['subcategory'];
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
                $value->newservices =  $newService;
                unset($value->services);
            }
            return response()->json(['success' => true,'data' => $data], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    // export business
    public function exportCompanyDataFilter() {
        $statusFilter = request('statusFilter');
        $reg_type = request('socialFilter');
        $searchString = request('searchString');
        $limit = request('limit');
        $offset = request('offset');
        $order = request('order');
        $reverse = request('reverse');
        $planfilter = request('plan');
        $statefilter = request('state');
        $cityfilter = request('city');
        $zipcodefilter = request('zipcode');
        $adminassign = request('admin');
        $issuperadmin = request('issuperadmin');
        $selectForAdmin = '';
        $adminJoin = '';
        
         $assign = request('assign');
        $adminid = request('adminid');
        $userid = 0;
        if(!empty($adminid)) {
			$userid =(int)$adminid;
		}
		
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

        $whereCompany = '';
        $currentTime = date('Y-m-d 00:00:00');
        if(!empty($statusFilter) && $statusFilter != 'all' && $statusFilter != '') {
            if($statusFilter == 'pending') {
               $whereCompany  = "AND companydetails.paymentplan = 0 AND companydetails.account_type = 'paid'";
            } else if($statusFilter == 'expire') {
               $whereCompany  = "AND ((companydetails.account_type = 'paid' AND companydetails.paymentplan > 0 AND companydetails.nextpaymentdate  < '".$currentTime."') OR (companydetails.account_type = 'free' AND companydetails.free_subscription_end  < '".$currentTime."'))";
            } else if($statusFilter == 'free') {
                $whereCompany = "AND companydetails.account_type = 'free'";
            }
        }
        if( $reg_type != 'all' && $reg_type != '') {
            if($reg_type == 0 || $reg_type == 1) {
                if($whereCompany == '') {
                    $whereCompany = "AND auths.is_social = '".$reg_type."'";       
                } else {
                    $whereCompany .= " AND auths.is_social = '".$reg_type."'";
                }
            }
        }
        if(!empty($planfilter) && $planfilter != 'all'  && $planfilter != '') {
			if($planfilter == 'marinepro' || $planfilter == 'advanced' || $planfilter == 'basic' || $planfilter == 'payperlead') {
				$plannameArr = array('marinepro' => 'Marine Pro','advanced' => 'Advanced' , 'basic' => 'Basic' , 'payperlead' => 'Free');
				$plandata = DB::table('subscriptionplans')->where('planname','ILIKE','%'.$plannameArr[$planfilter].'%')
                    ->where('status','=','active')
                    ->where('isadminplan','=','0')
                    ->first();
				if(!empty($plandata)) {
					$planid = $plandata->id;
					if($whereCompany == '') {
						$whereCompany = " AND companydetails.paymentplan = '".$planid."' AND companydetails.nextpaymentdate > '".date('Y-m-d H:i:s')."'";
					} else {
						$whereCompany .= " AND companydetails.paymentplan = '".$planid."' AND companydetails.nextpaymentdate > '".date('Y-m-d H:i:s')."'";
					}
				} else {
					return response()->json(['error'=>'networkerror'], 401); 
				}
			}
		}
		
        if(!empty($statefilter) && $statefilter != 'All'  && $statefilter != '') {
			if($whereCompany == '') {
				$whereCompany = " AND companydetails.state = '".$statefilter."' ";
			} else {
				$whereCompany .= " AND companydetails.state = '".$statefilter."' ";
			}
		}
        if(!empty($cityfilter) && $cityfilter != 'All'  && $cityfilter != '') {
			if($whereCompany == '') {
				$whereCompany = " AND companydetails.city = '".$cityfilter."' ";
			} else {
				$whereCompany .= " AND companydetails.city = '".$cityfilter."' ";
			}
		}
        if(!empty($zipcodefilter) && $zipcodefilter != 'All'  && $zipcodefilter != '') {
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
       
        if(!empty($searchString) && $searchString != '') {
            $searchString = strtolower($searchString);
            if($whereCompany == '') {
                $whereCompany = "AND LOWER(companydetails.name) LIKE '%".$searchString."%'";       
            } else {
                $whereCompany .= " AND LOWER(companydetails.name) LIKE '%".$searchString."%'";
            }
        }
        
        if(!empty($adminassign) && $adminassign != '0' && $adminassign != '-1' && $adminassign != '') {
			if($whereCompany == '') {
				$whereCompany = " AND companydetails.assign_admin = ".(int)$adminassign." ";
			} else {
				$whereCompany .= " AND companydetails.assign_admin = ".(int)$adminassign." ";
			}
		}
		
        $data = DB::select("SELECT companydetails.name,auths.email,COALESCE(companydetails.address,'-') as address ,companydetails.city,companydetails.state,companydetails.zipcode,COALESCE(companydetails.websiteurl,'-') as websiteurl,companydetails.contactname,companydetails.contactmobile,companydetails.contactemail,companydetails.about,companydetails.businessemail,companydetails.contact ".$selectForAdmin."  ,companydetails.services,companydetails.boats_yachts_worked,companydetails.engines_worked FROM auths JOIN companydetails ON auths.id = companydetails.authid ".$adminJoin." WHERE auths.usertype='company' AND auths.status !='deleted' AND companydetails.accounttype!='dummy' ".$whereCompany." ".$orderBy." LIMIT ".$limit." OFFSET ".$offset."");
		if(!empty($data)) {
			$boartAndYachtData = Boat_Engine_Companies::where(function($query) {
								$query->where('category', '=', 'boats')
								->orWhere('category', '=', 'yachts');})->where('status','=','1')->select('id', 'name')->get()->toArray();
			$engineData = Boat_Engine_Companies::where('category', '=', 'engines')->where('status','=','1')->select('id', 'name')->get()->toArray();
            $allservices = Service::where('status','=','1')->orWhere('category','=','11')->select('id', 'service as itemName','subcategory')->get()->toArray();
            $allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
            $newallCategory = [];
            foreach ($allCategory as $val) {
                $newallCategory[$val['id']] = $val['categoryname'];
            }
            $newallservices = [];
            foreach ($allservices as $val) {
                $newallservices[$val['id']]['itemName'] = $val['itemName'];
                $newallservices[$val['id']]['id'] = $val['id'];
                $newallservices[$val['id']]['subcategory'] = $val['subcategory'];
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
            'about' => 'required',
            'contact' => 'required',
            'email' => 'bail|required|E-mail',
            'businessemail' => 'bail|required|E-mail',
            'password' => 'required',
            'confirm' => 'required|same:password',
            'contactname' => 'required',
            'contactemail' => 'bail|required|E-mail',
            'contactmobile' => 'required'
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $auth   = new Auth; 
        $authid = 0;
        $auth->email = strtolower(request('email'));
        $auth->password = Hash::make(request('password'));
        $newsletter = (!empty(request('newsletter')) && (request('newsletter') == 'true')) ? '1':'0';
        $auth->newsletter = $newsletter;
        $auth->usertype = 'company';
        $auth->ipaddress = $this->getIp();
        $auth->status = 'pending';
        $auth->stepscompleted ='2'; 
        $auth->addedby =1; 
        $auth->is_activated = 1;
        if($auth->save()) {
            $authid = $auth->id;
        } 
        if($authid) {
			$boatYachtJson = request('boatYachtworked');
            $emptyboatYachtworked = true;
            $boatYachtworkedArray  = array();
            $i = 0;
            $j = 0;
            if(!empty($boatYachtJson)) {
                $boatYachtworked = json_decode(request('boatYachtworked'));
                $checkBoat = [];
                foreach ($boatYachtworked as $val) {
                    if($val  && !in_array($val, $checkBoat)) {
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
                $checkEngine = [];
                $engineworked = json_decode(request('engineworked'));
                foreach ($engineworked as $val) {
                    if($val && !in_array($val, $checkEngine)) {
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
            $address = request('address');
            $websiteurl = request('websiteurl');
            $companydetail  = new Companydetail; 
            $companydetail->authid  = $authid;
            $companydetail->name  = request('name');
            $companydetail->actualslug = strtolower($realSlug);
            $companydetail->slug = strtolower($slug);
            $companydetail->services   = request('services');
            $companydetail->businessemail   = request('businessemail');
            if(!empty($issuperadmin) && $issuperadmin == 'true') {
				$companydetail->assign_admin    = ((isset($adminassign) && $adminassign !='') ? (int)$adminassign: NULL);
			}
            $companydetail->admin_note    = (!empty($admin_noteArr) ?  json_encode($admin_noteArr): NULL);
            $companydetail->address    = ((isset($address) && $address !='') ? request('address'): NULL);
            $companydetail->websiteurl    = ((isset($websiteurl) && $websiteurl !='') ? request('websiteurl'): NULL);
            $companydetail->allservices =  ((isset($allservices) && $allservices !='') ? json_encode($allservices,JSON_UNESCAPED_SLASHES): NULL);
            $companydetail->city       = request('city');
            $companydetail->state      = request('state');
            $companydetail->country    = request('country');
            // $companydetail->county    = request('county');
            $companydetail->about    = request('about');
            $companydetail->zipcode    = request('zipcode');
            $companydetail->contact    = request('contact');
            $companydetail->longitude  = $longitude;
            $companydetail->latitude   = $latitude;
            $companydetail->contactname  = request('contactname');
            $companydetail->contactmobile  = request('contactmobile');
            $companydetail->contactemail  = request('contactemail');
            // $companydetail->country_code  = request('country_code');
            $country_code = request('country_code');
            if($country_code != '') {
                $pos = strpos($country_code, '+');
                if(!$pos){
                    $country_code ='+'.$country_code;
                }
            }   
            $companydetail->country_code   = $country_code;
            $companydetail->boats_yachts_worked    = ($emptyboatYachtworked) ? NULL : $boatYachtObj;
            $companydetail->engines_worked    = ($emptyengineworked) ? NULL : $engineObj;
            if($companydetail->save()) {
				$DictionaryData = new Dictionary;
				$DictionaryData->authid = $authid;
				$DictionaryData->word = request('name');
				if($DictionaryData->save()) {
				}
                // $geolocation = new Geolocation;
                // $city    = request('city');
                // $state   = request('state');
                // $zipcode = request('zipcode');
                // $country = request('country');
                // // $addressGeo = ((isset($address) && $address !='') ? $address: '').' '.$city.' '.$zipcode.' '.$state.' ,'.$country;
                // // $output = $this->getGeoLocation($addressGeo); //Get Location from location Trait
                // // $longitude = $output['longitude'];
                // // $latitude = $output['latitude'];
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
                // $usersdata['authid'] = $authid;
                // if($geolocation->save()) {
                $usersdata = DB::table('auths')
                ->join('companydetails', 'auths.id', '=', 'companydetails.authid')
                ->where('auths.id', '=', (int)$authid)
                ->select('auths.email','auths.status', 'companydetails.*')
                ->first();
                $emailArr = [];
                $emailArr['name'] = request('name');
                $emailArr['to_email'] = request('email');
                $emailArr['password'] = request('password');
                //Send account created email notification
                //~ $status = $this->sendEmailNotification($emailArr,'business_added_by_admin');
                //~ if($status != 'sent') {
                    //~ return array('status' =>'emailsentfail');
                //~ }
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
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
                    $updatedCompany = Companydetail::where('authid', '=', $authid)->update(['status' => 'suspended']);
                    $CompanyDetail = Companydetail::where('authid', '=', $authid)->first();
                    if(!empty($CompanyDetail)) {
                        $subid = $CompanyDetail->subscription_id;
                        if ($subid != null && $CompanyDetail->customer_id != null) {
                            
                            try {
                                $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
                                $subscription = $stripe->subscriptions()->cancel($CompanyDetail->customer_id , $subid);
                            }   catch(\Cartalyst\Stripe\Exception\NotFoundException $e) {
                                
                            }   catch(Exception $e) {
                                return response()->json(['error'=>$e->getMessage()], 401);
                            }
                        }
                    }
                    if($updatedCompany) {
                        return response()->json(['success' => true,'authid' => $authid], $this->successStatus);    
                    } else {
                        return response()->json(['error'=>'networkerror'], 401);     
                    }
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            } else if ($status == 'suspended' || $status == 'pending') {
                $updated = Auth::where('id', '=', $authid)->where('usertype', '=', 'company')->update(['status' => 'active']);
                if($updated) {
                    $updatedCompany = Companydetail::where('authid', '=', $authid)->update(['status' => 'active']);
                    if ($updatedCompany) {
                        return response()->json(['success' => true,'authid' => $authid], $this->successStatus);
                    } else {
                        return response()->json(['error'=>'networkerror'], 401); 
                    }
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
            if($updated) {
                $updatedCompany = Companydetail::where('authid', '=', $authid)->update(['status' => 'deleted']);
                $CompanyDetail = Companydetail::where('authid', '=', $authid)->first();
                if(!empty($CompanyDetail)) {
                    $subid = $CompanyDetail->subscription_id;
                    if ($subid != null && $CompanyDetail->customer_id != null) {
                        
                        try {
                            $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
                            $subscription = $stripe->subscriptions()->cancel($CompanyDetail->customer_id , $subid);
                        }   catch(\Cartalyst\Stripe\Exception\NotFoundException $e) {
                            $subStatus = '';
                        }   catch(Exception $e) {
                            return response()->json(['error'=>$e->getMessage()], 401);
                        }
                    }
                }
                $to_email = Auth::select('email','is_social')->where('id', '=', $authid)->where('usertype', '=', 'company')->first();
                $emailArr = [];
                if(isset($to_email->is_social) && $to_email->is_social == '1') {
                    $emailArr['to_email'] = $CompanyDetail->contactemail; 
                } else {
                    $emailArr['to_email'] = $to_email->email;    
                }
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

    
     // edit new user //
    public function editCompany(Request $request) {
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
            // 'email' => 'bail|required|E-mail',
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
        // $auth['email'] = strtolower(request('email'));
        $auth['ipaddress'] =$this->getIp();
        $newsletter = (!empty(request('newsletter')) && (request('newsletter') == 'true')) ? '1':'0';
        $auth['newsletter'] =$newsletter;
        if(!empty($authid) && $authid > 0) {
            $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'company')->update($auth);
            if($updated) {
				$boatYachtJson = request('boatYachtworked');
				$emptyboatYachtworked = true;
				$boatYachtworkedArray  = array();
				$i = 0;
				$j = 0;
				if(!empty($boatYachtJson)) {
					$boatYachtworked = json_decode(request('boatYachtworked'));
                    $checkBoat = [];
					foreach ($boatYachtworked as $val) {
                        if($val && !in_array($val,$checkBoat)){
                            $checkBoat[] =$val;
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
                        if($val && !in_array($val, $checkEngine)){
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
                // $detailArr['country_code']   = request('country_code');
                $country_code = request('country_code');
                if($country_code != '') {
                    $pos = strpos($country_code, '+');
                    if(!$pos){
                        $country_code ='+'.$country_code;
                    }
                }
                $detailArr['country_code']   = $country_code;
                $detailArr['boats_yachts_worked']    = ($emptyboatYachtworked) ? NULL : $boatYachtObj;
				$detailArr['engines_worked']    = ($emptyengineworked) ? NULL : $engineObj;

                $detailUpdate =  Companydetail::where('authid', '=', (int)$authid)->update($detailArr);
                if($detailUpdate) {
                    $zaiperenv = env('ZAIPER_ENV','local');
                    if($zaiperenv == 'live') {
                        $this->companyCreateZapierbyID($authid);
                    }
					$updatedDictionary = Dictionary::where('authid', '=', (int)$authid)->update(['word' => request('name')]);
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


    // get company details //
    public function getCompanyDetail(Request $request) {
        $authid = request('id');
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('auths')
            ->leftJoin('companydetails', 'auths.id', '=', 'companydetails.authid')
            ->where('auths.id', '=', (int)$authid)
            ->where('auths.usertype', '=', 'company')
            ->where('companydetails.accounttype','!=','dummy')
            ->select('auths.email','auths.id as userauthid','auths.usertype','auths.status','auths.newsletter', 'companydetails.*')
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
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }

    // get all company images //
    public function getImagesData(Request $request) {
        $authid = request('userid');
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('companydetails')
            ->where('authid', '=', (int)$authid)
            ->select('companydetails.name','companydetails.images')
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
        $detailArr['primaryimage'] =  !empty($primaryImg)?$primaryImg:NULL;
        $detailUpdate =  Companydetail::where('authid', '=', (int)$authid)->update($detailArr);
        if($detailUpdate) {
            $usersdata = DB::table('companydetails')
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
        $detailUpdate =  Companydetail::where('authid', '=', (int)$authid)->update($detailArr);
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
        $detailUpdate =  Companydetail::where('authid', '=', (int)$authid)->update($detailArr);
        if($detailUpdate) {
            return response()->json(['success' => true,'images' => $jsonObj], $this->successStatus);
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
            $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'company')->update($auth);
            if($updated) {
                if($emailUpdates && !$passwordUpdate) {
                    $emailArr = [];
                    $emailArrnew = [];
                    $userDataDetail  =  Companydetail::where('authid',(int)$authid)->where('status','!=','deleted')->get();
                    if(!empty($userDataDetail) && count($userDataDetail) > 0 && $OldEmail != '' ) {
                        $emailArr['name'] = $userDataDetail[0]->name;
                        $emailArr['to_email'] = $OldEmail;
                        $emailArr['new_email'] = request('email');
                        $status1 = $this->sendEmailNotification($emailArr,'admin_emailchange_notification');
                        $emailArrnew['name'] = $userDataDetail[0]->name;
                        $emailArrnew['to_email'] = request('email');
                        $status2 = $this->sendEmailNotification($emailArrnew,'admin_emailchange_notification_new');
                        return response()->json(['success' => true,'isSame'=>false], $this->successStatus);
                    }
                    return response()->json(['success' => true,'isSame'=>false], $this->successStatus);
                } else if($passwordUpdate && !$emailUpdates) {
                    $emailArr = []; 
                    $userDataDetail  =  Companydetail::where('authid',(int)$authid)->where('status','!=','deleted')->get();
                    if(!empty($userDataDetail) && count($userDataDetail) > 0 ) {
                        $emailArr['name'] = $userDataDetail[0]->name;
                        $emailArr['to_email'] = $userDatas[0]->email;
                        $emailArr['password'] = $password;
                        $status1 = $this->sendEmailNotification($emailArr,'admin_passwordchange_notification');
                        return response()->json(['success' => true,'emailSent'=>true], $this->successStatus);
                    }
                } else if($passwordUpdate && $emailUpdates) {
                    $emailArr = [];  $emailArrnew = [];
                    $userDataDetail  =  Companydetail::where('authid',(int)$authid)->where('status','!=','deleted')->get();
                    if(!empty($userDataDetail) && count($userDataDetail) > 0 && $OldEmail != '' ) {
                        $emailArr['name'] = $userDataDetail[0]->name;
                        $emailArr['to_email'] = $OldEmail;
                        $emaiilArr['new_email'] = request('email');
                        $emailArr['password'] = $password;
                        $status1 = $this->sendEmailNotification($emailArr,'admin_emailPwdchange_notification');

                        $emailArrnew['name'] = $userDataDetail[0]->name;
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
            ->whereRaw("auths.status != 'deleted' AND auths.usertype = 'company' AND ((companydetails.account_type = 'paid' AND companydetails.accounttype != 'dummy' AND companydetails.nextpaymentdate > '".date('Y-m-d H:i:s')."') OR (companydetails.account_type = 'free' AND companydetails.free_subscription_end > '".date('Y-m-d H:i:s')."'))")
            ->where('auths.status', '!=', 'deleted')
            ->where('auths.id', '=', (int)$id)
            ->where('auths.stepscompleted', '>=', 3)
            ->count();
        $isUnlimit = false;
		$currentDate = date('Y-m-d 00:00:00');
		if(env('BASIC_UNLIMITED_GEO_LOC') == 'YES') {
			if ($currentDate < env('BASIC_UNLIMITED_ACCESS_END')) {
				$isUnlimit = true;
			}
		}
        $companyData = Companydetail::select('name')->where('authid',(int)$id)->first();
        $name = '';
        if(!empty($companyData)) {
			$name = $companyData->name;
		}
        if($usersdata) {
            return response()->json(['success' => false ,'name' => $name,'unlimited'=>$isUnlimit], $this->successStatus);
        } else {
            return response()->json(['success' => true,'name' => $name,'unlimited'=>$isUnlimit], $this->successStatus);
        }
    }

   // //Get Plan Geolocation details//
    public function getUserPlanGeoLocation(Request $request) {
        $id = request('businessid');
        if(!empty($id) && (int)$id) {
            $company_type = Companydetail::select('account_type','name')->where('authid',$id)->first();
            if(!empty($company_type) && isset($company_type->account_type)) {
                if($company_type->account_type == 'free') {
                    $planData['geolocationaccess'] = 9999;
                    $planData['plantype'] = 'free';
                    return response()->json(['success' => true,'data' => $planData], $this->successStatus);
                } else {
                    $planData = DB::table('companydetails')
                    ->select('subscriptionplans.planname','subscriptionplans.geolocationaccess','subscriptionplans.plantype','companydetails.is_discount','paymenthistory.created_at')
                    ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'companydetails.paymentplan')
                    ->Join('paymenthistory', 'paymenthistory.companyid', '=', 'companydetails.authid')
                    ->where('companydetails.authid', '=', $id)
                    ->where('paymenthistory.transactionfor','registrationfee')
					->orderBy('paymenthistory.id','DESC')
                    ->first();
                    if($planData) {
						$currentDate = date('Y-m-d 00:00:00');
						if(env('BASIC_UNLIMITED_GEO_LOC') == 'YES') {
							if ((strpos('Basic', $planData->planname) !== false) && (($planData->created_at < env('BASIC_UNLIMITED_ACCESS_END')) || ($planData->is_discount == '1'))) {
								$planData->geolocationaccess = 9999;
							} 
						}
                        return response()->json(['success' => true,'data' => $planData,'name'=> $company_type->name], $this->successStatus);
                    } else {
                        return response()->json(['success' => false,'data' => [],'name'=> $company_type->name], $this->successStatus);   
                    }
                }
            } else {
                return response()->json(['error'=>'networkerror3'], 401);                
            }
        } else {
            return response()->json(['error'=>'networkerror4'], 401);
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
    // //Get all geolocation by id 
    public function getGeolocationsById(Request $request) {
        $validate = Validator::make($request->all(), [
            'authid' => 'required'
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $geolocations = Geolocation::select('id','authid','city','state','zipcode','created_at')
                        ->where('authid', '=',request('authid'))
                        ->where('status','=','1')
                        ->get();
        if($geolocations) {
            return response()->json(['success' => true,'data' => $geolocations], $this->successStatus);
        } else {
            return response()->json(['success' => false,'data' => []], $this->successStatus);
        }    
    }  
    //Edit geolocations 
    public function editGeolocation(Request $request) {
        $validate = Validator::make($request->all(), [
            'authid' => 'required',
            'locations' => 'required',
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $geolocationsArr = json_decode(request('locations'));
        $insertIds = [];
        $geoArr = [];
        //~ $additionalGeo = (int)(request('additionalgeo'));
        //~ $planLocations = count($geolocationsArr)-$additionalGeo;
        $countLoc = 0;

        $original = (int)(request('original'));
        $authid = request('authid');
        //save all location 
        foreach ($geolocationsArr as $location) {
            if($location->state != '' && $location->city != '' && $location->zipcode != '') {
                if(isset($location->hidlocationid) && !empty($location->hidlocationid)) {
                    $geolocation = Geolocation::find($location->hidlocationid);    
                } else {
                    $geolocation = new Geolocation;
                }
                $city = $location->city;
                $state = $location->state;
                $zipcode = $location->zipcode;
                $geolocation->authid = request('authid');
                $geolocation->city = $city;
                $geolocation->zipcode = $zipcode;
                $geolocation->state = $state;
                $geolocation->status = '1';
                if($geolocation->save()) {
                    $insertIds[] = $geolocation->id;
                    $geoArr[] = $geolocation->id;
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
                // }    
                $countLoc++;   
            }
        }
        $changeStatus = DB::table('geolocation')->whereNotIn('id',$geoArr)->where('authid',$authid)->update(['status' => '0']);
        if(count($insertIds)) {
            return response()->json(['success' => true], $this->successStatus);
        } else {
            return response()->json(['success' => false,'data' => []], $this->successStatus);    
        }    
    }

   // get  count user //
    public function getTotalUserwithamount() {
        $usersCount = $companyCount = $talentCount = $yachtCount = 0;
        $usersCount = DB::table('auths')
            ->Join('userdetails', 'auths.id', '=', 'userdetails.authid')
            ->where('auths.usertype', '=', 'regular')
            ->where('auths.status', '!=', 'deleted')
            ->count();
        $companyCount = DB::table('auths')
            ->Join('companydetails', 'auths.id', '=', 'companydetails.authid')
            ->where('auths.usertype', '=', 'company')
            ->where('auths.status', '!=', 'deleted')
            ->count();
        $talentCount = DB::table('auths')
            ->Join('talentdetails', 'auths.id', '=', 'talentdetails.authid')
            ->leftJoin('jobtitles','jobtitles.id','=','talentdetails.jobtitleid')
            ->where('auths.usertype', '=', 'professional')
            ->where('auths.status', '!=', 'deleted')
            ->count();
        $yachtCount = DB::table('auths')
                    ->Join('yachtdetail', 'auths.id', '=', 'yachtdetail.authid')
                    ->where('auths.usertype', '=', 'yacht')
                    ->where('auths.status', '!=', 'deleted')
                    ->count();
        $countData = array(array('usertype' => 'professional','user_count'=>$talentCount),array('usertype' => 'company','user_count'=>$companyCount),array('usertype' => 'regular','user_count'=>$usersCount),array('usertype' => 'yacht','user_count'=>$yachtCount));
        $amount = DB::table('paymenthistory')->select(DB::raw('SUM(amount) as payment_amount'))->get();
        if($amount) {
            return response()->json(['success' => true,'userdata' => $countData,'amountdata' => $amount], $this->successStatus);
            print_r($amount);
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    //Create Customer account in stripe
    //Create Customer account in stripe
    public function companyPayment(Request $request){
       // need to set plan month
        //$stripe = Stripe::make(config()->get('services')['stripe']['secret']);
        $validate = Validator::make($request->all(), [
            'subplan' => 'required',
            'userID'  => 'required',
            'card_token' => 'required',
            'cardHolder' => 'required'
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
        $userID = request('userID'); 
        $subplan = request('subplan');
        $card_token = request('card_token');
        $cardHolder = request('cardHolder');
        $paymentStatus = $this->stripeTransaction($userID,$subplan,$card_token,$cardHolder);
        if(isset($paymentStatus['success'])) {
            $isPendingPayment = $paymentStatus['pending'];
            if($isPendingPayment) {
                $statuspayment = 'pending';
            } else {
                $statuspayment = 'approved';
            }
            if(empty($userID) || $userID == '') {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            /* Get user card Token and Plan*/
            //~ $cardHolderName = request('nameoncard');
            
            //~ $card_token = request('card_token');
            //$userID = request('userID');
            $userDetail = Auth::where('id', '=', (int)$userID)->where('status', '!=', 'deleted')->get()->first()->toArray();
            $email = $userDetail['email'];
            $ex_message = '';
            $plandata = DB::table('subscriptionplans')->leftJoin('discounts', 'discounts.paymentplan', '=', 'subscriptionplans.id')->select('subscriptionplans.*','discounts.current_discount')->where('subscriptionplans.id', '=', (int)$subplan)->where('subscriptionplans.status', '=', 'active')->first();
            $CompanyDetail = Companydetail::where('authid', '=', (int)$userID)->get()->first()->toArray();
            if($CompanyDetail['subscriptiontype'] == 'automatic' || $CompanyDetail['subscriptiontype'] == null ) {
                $subType = 'automatic';
            } else {
                $subType = 'manual';
            }
            if(!empty($plandata)) {
                $days = 0;
                $IsDayLeft = false;
                if($CompanyDetail['nextpaymentdate'] == null) {
                    $ammount = DB::table('subscriptionplans')->select('subscriptionplans.*')->where('subscriptionplans.id', '=', $CompanyDetail['paymentplan'])->where('subscriptionplans.status', '=', 'active')->first();
                    if($ammount->amount > $plandata->amount) {
                        $CreatedDate = strtotime($CompanyDetail['nextpaymentdate']);
                        $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                        $differStrTime = $CreatedDate - $CurrentDates;
                        if($differStrTime > 0) {
                            $day = floor($differStrTime/(24*60*60));
                            if($day > 0) {
                                $days = $day;
                                $IsDayLeft = true;
                            }
                        }
                    }
                }
                
                $planPrice = $plandata->amount;
                $planType = $plandata->plantype;
                $planAccessType = $plandata->planaccesstype;
                $planAccessNumber = $plandata->planaccessnumber;
                if($planType =='paid') { 
                    if($CompanyDetail['paymentplan'] > 0) {
                        if($planAccessType == 'month') {
                            if($subType == 'automatic') {
                                if($IsDayLeft) {
                                    $nextDate = date('Y-m-d 00:00:00', strtotime("+".$days." days"));
                                } else {
                                    $nextDate = date('Y-m-d 00:00:00', strtotime("+30 days"));
                                }
                            } else {
                                if($IsDayLeft) {
                                    $totalDay = $days+30;
                                    $nextDate = date('Y-m-d 00:00:00', strtotime("+".$totalDay." days"));
                                } else {
                                    $nextDate = date('Y-m-d 00:00:00', strtotime("+30 days"));
                                }
                            }
                        } else if($planAccessType == 'unlimited'){
                            $nextDate = '2099-01-01 00:00:00';
                        } else if($planAccessType == 'year') {
                            if($subType == 'automatic') {
                                if($IsDayLeft) {
                                    $nextDate = date('Y-m-d 00:00:00', strtotime("+".$days." days"));
                                } else {
                                    $nextDate = date('Y-m-d 00:00:00', strtotime("+365 days"));
                                }
                            } else {
                                if($IsDayLeft) {
                                    $totalDay = $days+365;
                                    $nextDate = date('Y-m-d 00:00:00', strtotime("+".$totalDay." days"));
                                } else {
                                    $nextDate = date('Y-m-d 00:00:00', strtotime("+365 days"));
                                }
                            }
                        }
                    } else {
                        if($planAccessType == 'month') {
							if($IsDayLeft && $days > 0 ) {
                                $nextDate = date('Y-m-d 00:00:00', strtotime("+".$days." days"));
                            } else {
								$nextDate = date('Y-m-d 00:00:00', strtotime("+30 days"));
							}
                        } else if($planAccessType == 'unlimited'){
                            $nextDate = '2099-01-01 00:00:00';
                        } else if($planAccessType == 'year') {
                            if($subType == 'automatic') {
                                if($IsDayLeft) {
                                    $nextDate = date('Y-m-d 00:00:00', strtotime("+".$days." days"));
                                } else {
                                    $nextDate = date('Y-m-d 00:00:00', strtotime("+365 days"));
                                }
                            } else {
                                if($IsDayLeft) {
                                    $totalDay = $days+365;
                                    $nextDate = date('Y-m-d 00:00:00', strtotime("+".$totalDay." days"));
                                } else {
                                    $nextDate = date('Y-m-d 00:00:00', strtotime("+365 days"));
                                }
                            }
                        }
                    }
                } else {
                    //Add Free Plan
                    if($planAccessType == 'unlimited'){
                        $nextDate = '2099-01-01 00:00:00';
                    } else if($planAccessType == 'year'){
                        $nextDate = date('Y-m-d 00:00:00', strtotime("+ 365 days", strtotime(date('Y-m-d H:i:s'))));
                    } else {
                        $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                    }
                    return response()->json(['success' => true], $this->successStatus);
                }            
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            $statusStep = Auth::where('id', (int)$userID)->update(['stepscompleted' => '3','status' => 'active']);
            $isNotWebHookEntery = true;
            $tranctionID = '';
            if($statuspayment == 'approved') {
                $tranctionID = $paymentStatus['chargeTrs'];       
                $dataUpdateComp = [];
                $isDiscount = false;
                $dataUpdateComp['remaindiscount'] = 0;
                $dataUpdateComp['discount'] = 0;
                $dataUpdateComp['is_discount'] = '0';
                $dataUpdateComp['lastpaymentdate'] = date('Y-m-d H:i:s');
                $dataUpdateComp['paymentplan'] = (int)$subplan;
                $dataUpdateComp['next_paymentplan'] = (int)$subplan;
                $dataUpdateComp['remaintrial'] = 0;
                $dataUpdateComp['nextpaymentdate'] = $nextDate;
                $dataUpdateComp['subscriptiontype'] = $subType;
                $dataUpdateComp['plansubtype'] = 'paid';
                $dataUpdateComp['status'] ='active';
                $dataUpdateComp['lead_payment'] = 0;
                $dataUpdateComp['account_type'] = 'paid';
                $dataUpdateComp['free_subscription_period'] = NULL;
                $dataUpdateComp['free_subscription_start'] = NULL;
                $dataUpdateComp['free_subscription_end'] = NULL;
                $statusCompany = Companydetail::where('authid', (int)$userID)->update($dataUpdateComp);
            } else {
                $statusCompany = Companydetail::where('authid', (int)$userID)->update(['subscriptiontype' => $subType,'nextpaymentdate' => $nextDate, 'next_paymentplan' => (int)$subplan, 'paymentplan' => (int)$subplan,'plansubtype' => 'paid','status' => 'active','lead_payment' => 0,'free_subscription_period' => null,'free_subscription_start' => null,'free_subscription_end' => null,'account_type' =>'paid','remaintrial'=>$days]);
            }
            if($statusStep && $statusCompany) {
                if($statuspayment == 'approved') {
                    $zaiperenv = env('ZAIPER_ENV','local');
                    if($zaiperenv == 'live') {
                        $this->companyCreateZapierbyID($userID);
                    }
                    $statusPayment =  DB::table('paymenthistory')->insert(
                            ['companyid' => (int)$userID,
                            'transactionid' => $tranctionID,
                            //'tokenused' => $card_token,
                            'transactionfor' => 'registrationfee',
                            'amount' => $planPrice,
                            'payment_type' => $plandata->id,
                            'status' => $statuspayment ,
                            'customer_id' => $CompanyDetail['customer_id'],
                            'subscription_id' => $CompanyDetail['subscription_id'],
                           // 'cardid' => $tokenData['card']['id'],
                            'expiredate' => $nextDate,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                            ]);
                    if($statusPayment) {
                        return response()->json(['success' => true,'nextdate'=> $nextDate], $this->successStatus);
                    } else {
                        return response()->json(['error'=>'entryfail'], 401);
                    }
                } else {
                    $paymentHistoryData = DB::table('paymenthistory')->where('subscription_id','=',$CompanyDetail['subscription_id'])->where('transactionfor','registrationfee')->orderBy('created_at','DESC')->get();
                    if(!empty($paymentHistoryData) && count($paymentHistoryData) > 0 && $paymentHistoryData[0]->status == 'approved') {
                        $statusPayment = true; 
                    } else {
                        $statusPayment =  DB::table('paymenthistory')->insert(
                            ['companyid' => (int)$userID,
                            'transactionid' => request('tranctionID'),
                            //'tokenused' => $card_token,
                            'transactionfor' => 'registrationfee',
                            'amount' => $planPrice,
                            'payment_type' => $plandata->id,
                            'status' => $statuspayment ,
                            'customer_id' => $CompanyDetail['customer_id'],
                            'subscription_id' => $CompanyDetail['subscription_id'],
                           // 'cardid' => $tokenData['card']['id'],
                            'expiredate' => $nextDate,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                            ]);
                       
                    }
                    if($statusPayment) {
                        $zaiperenv = env('ZAIPER_ENV','local');
                        if($zaiperenv == 'live') {
                            $this->companyCreateZapierbyID($userID);
                        }
                        return response()->json(['success' => true,'nextdate'=> $nextDate], $this->successStatus);
                    } else {
                        return response()->json(['error'=>'entryfail'], 401);
                    }
                }
            } else {
                return response()->json(['error'=>'entryfail'], 401);
            }
        } else {
                return response()->json(['error'=>$paymentStatus], 401);
        }
    }

    // trial plan payment //
    public function trialpaymentplan() {
        $id = request('id');
        $subplan = request('subplan');
        if(empty($id) || empty($subplan)) {
             return response()->json(['error'=>'networkerror'], 401);
        }
        $nextDate = date('Y-m-d 00:00:00', strtotime("+ 90 days", strtotime(date('Y-m-d H:i:s'))));
        $companyData = Companydetail::where('authid', (int)$id)->where('status','!=','deleted')->get();
		if(!empty($companyData) && count($companyData) > 0) {
			$currentDate = date('Y-m-d H:i:s');
            $trial = 0;
            $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
            if($companyData[0]->lastpaymentdate == null && $companyData[0]->paymentplan == 0 && $companyData[0]->next_paymentplan == 0 ) {
                    $trial = 60;
                    $nextDate = date('Y-m-d 00:00:00', strtotime("+ 90 days", strtotime(date('Y-m-d H:i:s'))));
            } else {
                $CreatedDate = strtotime($companyData[0]->nextpaymentdate);
                $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                $differStrTime = $CreatedDate - $CurrentDates;
                if($differStrTime > 0) {
                    $day = floor($differStrTime/(24*60*60));
                    if($day > 0 ) {
                        $trial = $day;
                        $day = $day+30;
                        $nextDate = date('Y-m-d 00:00:00', strtotime("+ ".$day." days", strtotime($currentDate)));
                    }
                }
            }
            /*
            $currentDate = date('Y-m-d H:i:s');
			if($companyData[0]->nextpaymentdate > $currentDate) {
				$lastPaymentDate = $companyData[0]->lastpaymentdate;
				if($lastPaymentDate == 'null') {
					$nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime($currentDate)));
				} else {
					if($companyData[0]->remaintrial > 0) {
						$nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime($currentDate)));
					} else {
						$nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime($currentDate)));
					}
				}
			} else {
				$nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime($currentDate)));
			}
            
			$trial = 0;
			if($companyData[0]->remaintrial > 0) {
				if($companyData[0]->lastpaymentdate == null && $companyData[0]->paymentplan == 0 && $companyData[0]->next_paymentplan == 0 ) {
					$trial = 30;
				} else {
					if($companyData[0]->lastpaymentdate == null ) {
						$CreatedDate = strtotime($companyData[0]->created_at);
						$CurrentDates = strtotime("- 30 days", strtotime(date('Y-m-d H:i:s')));
						$differStrTime = $CreatedDate - $CurrentDates;
						if($differStrTime > 0) {
							$day = ceil($differStrTime/(24*60*60));
							$dayremain = $companyData[0]->remaintrial - $day;
							if($dayremain > 0 && $dayremain < 31) {
								$trial = $dayremain;
							}
						}
					} else {
						$CreatedDate = strtotime($companyData[0]->nextpaymentdate);
						$CurrentDates = strtotime(date('Y-m-d H:i:s'));
						$differStrTime = $CreatedDate - $CurrentDates;
						if($differStrTime > 0) {
							$day = floor($differStrTime/(24*60*60));
							if($day < $companyData[0]->remaintrial) {
								$trial = $day;
							}
						}
					}
				}
			}
			*/
			$statusStep = Auth::where('id', (int)$id)->update(['stepscompleted' => '3','status' => 'active']);
			if($statusStep) {
				$statusCompany = Companydetail::where('authid', (int)$id)->update(['subscriptiontype' => 'manual','nextpaymentdate' => $nextDate, 'paymentplan' => (int)(request('subplan')),'plansubtype' => 'free','status' => 'active','lead_payment'=>0,'lastpaymentdate' =>$currentDate,'next_paymentplan' =>(int)(request('subplan')),'remaintrial' =>$trial,'account_type' =>'paid','free_subscription_period' => null,'free_subscription_start' => null,'free_subscription_end' => null ]);
				if($statusStep) {
					$statusPayment =  DB::table('paymenthistory')->insert(
									['companyid' => (int)$id,'transactionfor' => 'registrationfee',
									'amount' => '0.00',
									'status' => 'approved' ,
									'expiredate' => $nextDate,
									'payment_type' => (int)(request('subplan')),
									'created_at' => date('Y-m-d H:i:s'),
									'updated_at' => date('Y-m-d H:i:s')
									]);
					if($statusPayment) {
                        $zaiperenv = env('ZAIPER_ENV','local');
                        if($zaiperenv == 'live') {
                            $this->companyCreateZapierbyID($id);
                        }
						return response()->json(['success' => true,'nextdate'=> $nextDate], $this->successStatus);
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
            ->where('companydetails.account_type','paid')
            ->first();
            $companyData = Companydetail::select('name')->where('authid',(int)$authid)->first();
			$name = '';
			if(!empty($companyData)) {
				$name = $companyData->name;
			}

            if(!empty($usersdata)) {
                return response()->json(['success' => true,'data' => $usersdata,'account_type' => 'paid','name'=>$name], $this->successStatus);
            } else {
                $checkFreeAccount = DB::table('companydetails')->select('account_type','free_subscription_period','free_subscription_start','free_subscription_end')
                ->where('companydetails.authid','=',$authid)
                ->where('companydetails.account_type','=','free')
                ->first();
                if(!empty($checkFreeAccount) && isset($checkFreeAccount->free_subscription_period)) {
                    if($checkFreeAccount->free_subscription_period == 'unlimited') {
                        return response()->json(['success' => true,'data' => $checkFreeAccount,'account_type' => 'free','name' => $name], $this->successStatus);
                    } else {
                        if($checkFreeAccount->free_subscription_end > $currentTime) {
                            return response()->json(['success' => true,'data' => $checkFreeAccount,'account_type' => 'free','name' => $name], $this->successStatus);
                        } else {
                            return response()->json(['success' => true,'data'=>'planExpireError','account_type' => 'free','name' => $name], $this->successStatus); 
                        }
                        
                    }
                }
                // if(empty($usersdatas)) {
                return response()->json(['success' => true,'data'=>'planExpireError','account_type' => 'paid','name' => $name], $this->successStatus); 
                // } else {
                    // return response()->json(['error'=>'networkerror'], 401); 
                // }
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }

    //  //Delet geolocations 
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
    // public function index(Request $request) {
    //     $usersdata = DB::table('auths')
    //         ->Join('companydetails', 'auths.id', '=', 'companydetails.authid')
    //         ->where('auths.usertype', '=', 'company')
    //         ->where('auths.status', '!=', 'deleted')
    //         ->where('companydetails.accounttype','!=','dummy')
    //         ->select('auths.email','auths.stepscompleted','auths.id as userauthid','auths.usertype','auths.status', 'companydetails.*')
    //         ->orderBy('auths.created_at', 'DESC')
    //         ->get();
    //     if(!empty($usersdata)) {
    //         return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
    //     } else {
    //         return response()->json(['error'=>'networkerror'], 401); 
    //     }
    // }

    //Get Business details by Slug
	public function getPreviewBusinessDetail(Request $request) {
		$validate = Validator::make($request->all(), [
			'authid' => 'required'
		]);
		if ($validate->fails()) {
			print_r($validate->message());die;
			return response()->json(['error'=>'validationError'], 401); 
		}
		
		$authid = request('authid');
		$data = DB::table('companydetails as cd')->select('cd.authid','cd.name as businessname','cd.about as description','cd.contact','cd.city','cd.coverphoto','cd.primaryimage','cd.businessemail','cd.created_at','cd.images','cd.services','cd.allservices','cd.address','cd.state','cd.country','cd.zipcode','cd.businessemail','cd.websiteurl','cd.accounttype','cd.longitude','cd.latitude',DB::raw('coalesce( r.totalreviewed , 0 ) as totalreviewed,coalesce( r.totalrating , 0 ) as totalrated'))
			->leftJoin('reviewsview as r','cd.authid','=','r.toid')
			// ->where('cd.status','=','active')
			->whereRaw("cd.authid ='".$authid."'")
			->get();
		if(!empty($data)) {
			if(isset($data[0])) {
				$authid = $data[0]->authid;
				$latestReview = DB::table('service_request_reviews as sr')
					->select(DB::raw("CONCAT(ud.firstname, ' ', ud.lastname) as username"),'ud.profile_image','sr.rating','sr.subject','sr.comment','sr.created_at')
					->Join('userdetails as ud','ud.authid','=','sr.fromid')
					->where('isdeleted','!=','1')
					->where('toid','=',$authid)
					->orderBy('sr.created_at','DESC')->get();
				$service = json_decode($data[0]->services);
				$allservices = Service::where('status','=','1')->select('id', 'service as itemName')->get()->toArray();
				$newallservices = [];
				foreach ($allservices as $val) {
					$newallservices[$val['id']] = $val['itemName'];
				}
				$allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
				$newallCategory = [];
				foreach ($allCategory as $val) {
					$newallCategory[$val['id']] = $val['categoryname'];
				}
				$newService = [];
				$temCateArr = [];
				foreach ($service as $catId => $SerIds) {
					$newService[$newallCategory[$catId]] = [];
					foreach ($SerIds as $sid => $sval) {
						if(isset($newallservices[$sval])) {
							$newService[$newallCategory[$catId]][] =  $newallservices[$sval];
						}
					}
				}
				$data[0]->latestReview = $latestReview;
				$data[0]->newservices =  $newService;
				return response()->json(['success' => true,'data' => $data[0]], $this->successStatus);
			} else {
				return response()->json(['success' => false,'data' => []], $this->successStatus);
			}
		} else {
			return response()->json(['success' => false,'data' => []], $this->successStatus); 
		} 
	}
	
	 //Edit geolocations 
	public function changeServiceLocations(Request $request) {
		$validate = Validator::make($request->all(), [
			'id' => 'required',
		]);
		if ($validate->fails()) {
		   return response()->json(['error'=>'validationError'], 401); 
		}
		$geolocationsArr = json_decode(request('geoid'));
		$useridencrypt = request('id');
		$authid = (int)$useridencrypt;
		if(count($geolocationsArr) > 0) {
			$changeStatus = Geolocation::whereNotIn('id',$geolocationsArr)->where('authid',(int)$authid)->update(['status' => '0']);
		} else {
			$changeStatus = Geolocation::whereNotIn('id',$geolocationsArr)->where('authid',(int)$authid)->update(['status' => '0']);
		}
		$GeoData = Geolocation::where('authid','=',$authid)->where('status','=','1')->get();
		if(!empty($GeoData)) {
			return response()->json(['success' => true,'data'=>$GeoData], $this->successStatus);
		} else {
			return response()->json(['success' => false,'data' => []], $this->successStatus);    
		}    
	}
	
		 //Get Plan Geolocation details//
    public function getLocationInfo(Request $request) {
        $encryptId = request('id');
        $id = (int)$encryptId;
        if(!empty($id) && (int)$id) {
			$planDataData = Geolocation::where('authid',$id)->where('status','1')->get();
            return response()->json(['success' => true,'data' => $planDataData], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }    

    public function importCompanyData(Request $request) {

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
            
            $boartAndYachtData = Boat_Engine_Companies::where(function($query) {
								$query->where('category', '=', 'boats')
								->orWhere('category', '=', 'yachts');})->where('status','=','1')->select('id',DB::raw('lower(name) as name'))->get()->toArray();
			$engineData = Boat_Engine_Companies::where('category', '=', 'engines')->where('status','=','1')->select('id',DB::raw('lower(name) as name'))->get()->toArray();
			$boats_yachts_workedArr = [];
            foreach ($boartAndYachtData as $val) {
                $boats_yachts_workedArr[$val['id']] = $val['name'];
            }
            $engines_workedArr = [];
            foreach ($engineData as $val) {
                $engines_workedArr[$val['id']] = $val['name'];
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
                        $auth->status = 'pending';
                        $auth->stepscompleted ='2';    
                        $auth->addedby=1;
                        $auth->is_activated = 1;
                        $newsletter = (!empty($csvDatas->newsletter) && (strtolower($csvDatas->newsletter) == 'yes')) ? '1':'0';
                        $auth->newsletter = $newsletter;
                        if($auth->save()) {
                            $companyid = $auth->id;
                        } else {
                            $isError = true;
                        }
                        if($companyid) {
                            $address = $csvDatas->address;
                            $locAddress = ((isset($address) && $address !='') ? $csvDatas->address.' ': '');
                            $location = $locAddress.$csvDatas->city.' '.$csvDatas->zipcode.' '.$csvDatas->state.' , United States';
                            $output = $this->getGeoLocation($location); //Get Location from location Trait
                            $longitude = $output['longitude'];
                            $latitude = $output['latitude'];
                            //calculate services 

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
                            $array = explode(" ",$csvDatas->city);
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
                            $companydetail->about    = $csvDatas->about;
                            $companydetail->zipcode    = $csvDatas->zipcode;
                            $companydetail->contact    = $csvDatas->telephone;
                            $companydetail->longitude  = $longitude;
                            $companydetail->latitude   = $latitude;
                            $companydetail->contactname  = $csvDatas->contactname;
                            $companydetail->contactmobile  = $csvDatas->contactmobile;
                            $companydetail->contactemail  = $csvDatas->contactemail;
                            $companydetail->country_code   = "+1";
                            $companydetail->status = 'pending';
                            $companydetail->boats_yachts_worked    = ($emptyBoatAndYacht) ? NULL : $boatYachtObj;
							$companydetail->engines_worked    = ($emptyEngines) ? NULL : $enginesObj;
                            if($companydetail->save()) {
                                $insertIds[] = $companydetail->id;
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
            ImportUsers::dispatch($csvData,$adminId,'company');
            // $this->importDataBusiness($csvData,$adminId);
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
    
    public function getanalyticsBiz(Request $request) {
		$validate = Validator::make($request->all(), [
			'id' => 'required'
		]);
		$countwise = request('countwise');
		if ($validate->fails()) {
			return response()->json(['error'=>'validationError'], 401); 
		}
		$whereDate = '';
		$whereDateQuery = "";
		$whereDateQueryRev = "";
		if($countwise == 'week') {
			$whereDate = Carbon\Carbon::now()->subdays('7');
			$whereDateQuery .= " AND rq.created_at > '".$whereDate."' ";
			$whereDateQueryRev = "created_at > '".$whereDate."' ";
		} else if ($countwise == 'month') {
			$whereDate = Carbon\Carbon::now()->subMonths('1');
			$whereDateQuery .= " AND rq.created_at > '".$whereDate."' ";
			$whereDateQueryRev = "created_at > '".$whereDate."' ";
		} else if ($countwise == 'year') {
			$whereDate = Carbon\Carbon::now()->subMonths('12');
			$whereDateQuery .= " AND rq.created_at > '".$whereDate."' ";
			$whereDateQueryRev = "created_at > '".$whereDate."' ";
		}
		$id = request('id');
		if(!empty($id) && (int)$id) {
			$companyData = Companydetail::select('name','authid','latitude','longitude','free_subscription_period')
			->where('authid','=',(int)$id)
			->where('status','!=','deleted')
			->first();
			$latitude = 0;
			$longitude = 0;
			if(!empty($companyData)) {
				$latitude = $companyData->latitude;
				$longitude = $companyData->longitude;
			} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
			$ViewQuery = Businessviewpage::where('company_id','=',(int)$id);
			$ViewQueryMylead = RequestProposals::select(DB::raw('count(*) as requestcount'),'status')->where('companyid','=',(int)$id);
			$ViewQueryJobs = Jobs::where('authid','=',(int)$id);
			$ViewQueryRateReview = ServiceRequestReviews::select(DB::raw('count(*) as requestcount'),DB::raw('avg(rating) as avgRate'),DB::raw('sum(rating) as sumRate'))->where('toid','=',(int)$id);
			$ViewQueryQuoterequests = Quoterequests::where('businessid','=',(int)$id);
			$QuerybusinessContact = Businesstelephone::where('company_id','=',(int)$id);
			$Query_Bus_appeared_in_listing = Businesslistingcount::where('company_id','=',(int)$id);

			if($whereDate != '') {
				$ViewQuery = $ViewQuery->where('created_at','>',$whereDate);
				$ViewQueryMylead = $ViewQueryMylead->where('created_at','>',$whereDate);
				$ViewQueryJobs = $ViewQueryJobs->where('created_at','>',$whereDate);
				$ViewQueryRateReview = $ViewQueryRateReview->where('created_at','>',$whereDate);
				$ViewQueryQuoterequests = $ViewQueryQuoterequests->where('created_at','>',$whereDate);
				$QuerybusinessContact = $QuerybusinessContact->where('created_at','>',$whereDate);
				$Query_Bus_appeared_in_listing = $Query_Bus_appeared_in_listing->where('created_at','>',$whereDate);
			}
			$ViewCount = $ViewQuery->count();
			$ViewMyleadCount = $ViewQueryMylead->where('status' ,'!=', 'deleted')->groupBy('status')->get();
			$ViewMyJobsCount = $ViewQueryJobs->where('status' ,'!=', 'deleted')->count();
			$ViewMyReviewCount = $ViewQueryRateReview->where('isdeleted' ,'!=', '1')->get();
			$Myleads = array('total' => 0 , 'approved' => 0 , 'rejected' =>0 , 'pending' => 0);
			$Myleads50 = array('alltotal'=>0,'total' => 0 , 'approved' => 0 , 'rejected' =>0);
			$MyReviewArr = array('total'=>0,'avg' => 0 , 'sum' => 0);
			$totalQueryQuoterequests = $ViewQueryQuoterequests->count();
			$businessContact = $QuerybusinessContact->count();
			$Business_appeared_in_listing = $Query_Bus_appeared_in_listing->count();
			//////////////////////// get payment plan ///////////////////////
			$paymentPlanArr = array('currentplan'=>'','currentamount' => 0 ,'previousplan' => '' ,'previousamount' => 0);
			$i = 0;
			if($companyData->free_subscription_period == null) {
				$dataPayment = DB::table('companydetails as cmp')->select(DB::Raw('cast(his.amount as integer)'),DB::Raw('cast(sub.amount as integer) as companyamount'),'cmp.remaindiscount','sub.planname')
				->Join('subscriptionplans as sub','cmp.paymentplan','=','sub.id')
				->Join('paymenthistory as his','his.payment_type','=','cmp.paymentplan')
				->where('cmp.authid','=',(int)$id)
				->where('his.companyid','=',(int)$id)
				->where('cmp.nextpaymentdate','>',date('Y-m-d H:i:s'))
				->where('his.expiredate','>',date('Y-m-d H:i:s'))
				->orderBy('his.created_at','DESC')
				->get()->first();
				if(!empty($dataPayment)) {
					if($dataPayment->remaindiscount > 0) {
						$paymentPlanArr['currentplan'] = $dataPayment->planname;
						if($dataPayment->companyamount > 0) {
							$paymentPlanArr['currentamount'] = floor(($dataPayment->companyamount)/2);
						} else {
							$paymentPlanArr['currentamount'] = $dataPayment->companyamount;
						}
					} else {
						$paymentPlanArr['currentplan'] = $dataPayment->planname;
						$paymentPlanArr['currentamount'] = $dataPayment->amount;
					}
				}
			} else {
				$paymentPlanArr['currentplan'] = 'Free';
				$paymentPlanArr['currentamount'] = 0;
			}
			//~ $dataPayment = DB::table('paymenthistory as pay')->select('sub.amount','sub.planname')
			//~ ->Join('subscriptionplans as sub','pay.payment_type','=','sub.id')
			//~ ->where('pay.transactionfor','=','registrationfee')
			//~ ->where('pay.companyid','=',(int)$id)
			//~ ->orderBy('pay.created_at','DESC')
			//~ ->limit(2)
			//~ ->get();
			//~ if(!empty($dataPayment)) {
				//~ foreach($dataPayment as $dataPayments) {
					//~ if($i == 0) {
						//~ $paymentPlanArr['currentplan'] = $dataPayments->planname;
						//~ $paymentPlanArr['currentamount'] = $dataPayments->amount;
					//~ } else if($i == 1) {
						//~ $paymentPlanArr['previousplan'] = $dataPayments->planname;
						//~ $paymentPlanArr['previousamount'] = $dataPayments->amount;
					//~ }
					//~ $i++;
				//~ }
			//~ }
			///////////////////////////////////////////////////////////////
			
			//////////////// top 5 review /////////////////////////////////
			$getTop5Review = ServiceRequestReviews::select('comment','rating','created_at')->where('toid','=',(int)$id)
				->when($whereDateQueryRev, function($query) use ($whereDateQueryRev){
					return $query->whereRaw($whereDateQueryRev);
				})
				->orderBy('created_at','DESC')->limit(5)->get();
			///////////////////////////////////////////////////////////////
			
			/////////////////////////// review within 50 miles ////////////
			// $calDis = '(((acos(sin(('.$latitude.'*pi()/180)) * sin((urq.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((urq.latitude *pi()/180)) * cos((('.$longitude.'- urq.longitude)*pi()/180))))*180/pi())*60*1.1515)';
			$calDis = "2 * 3961 * asin(sqrt((sin(radians((urq.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(urq.latitude)) * (sin(radians((urq.longitude - ".$longitude.") / 2))) ^ 2))";
			$result50Miles = DB::select("SELECT count(rq.*) as requestcount,rq.status  FROM users_service_requests as urq JOIN request_proposals as rq ON rq.requestid = urq.id where rq.status != 'deleted' AND rq.companyid = ".(int)$id.$whereDateQuery."AND ".$calDis." <= 50 group by rq.status"); 
			
			$result50MilesAll = DB::select("SELECT count(rq.*) as requestcount FROM users_service_requests as urq JOIN request_proposals as rq ON rq.requestid = urq.id where rq.status != 'deleted' ".$whereDateQuery."AND ".$calDis." <= 50");
			if(!empty($result50MilesAll)) {
				$Myleads50['alltotal'] = $result50MilesAll[0]->requestcount;
			}
			if(!empty($result50Miles)) {
				foreach($result50Miles as $result50Mile) {
					if($result50Mile->status == 'active' || $result50Mile->status == 'completed') {
						$Myleads50['approved'] = $Myleads50['approved'] + $result50Mile->requestcount;
					} else if ($result50Mile->status == 'declined' || $result50Mile->status == 'rejected') {
						$Myleads50['rejected'] = $Myleads50['rejected'] + $result50Mile->requestcount;
					} 
					$Myleads50['total'] = $Myleads50['total']+$result50Mile->requestcount;
				}
			}
			//////////////////////////////////////////////////////////////
			
			if(!empty($ViewMyReviewCount) && count($ViewMyReviewCount) > 0) {
				$MyReviewArr['total'] = $ViewMyReviewCount[0]->requestcount;
				$MyReviewArr['avg'] = $ViewMyReviewCount[0]->avgrate;
				$MyReviewArr['sum'] = $ViewMyReviewCount[0]->sumrate;
				
			}
			
			$totalJobs = 0;
			if(!empty($ViewMyJobsCount)) {
				$totalJobs = $ViewMyJobsCount;
			}
			$totalQuterRequests = 0;
			if(!empty($totalQueryQuoterequests)) {
				$totalQuterRequests = $totalQueryQuoterequests;
			}
			$businessContactByUser = 0; 
			if(!empty($businessContact)) {
				$businessContactByUser = $businessContact;
			}
			$appeared_in_listing = 0; 
			if(!empty($Business_appeared_in_listing)) {
				$appeared_in_listing = $Business_appeared_in_listing;
			}
			if(!empty($ViewMyleadCount)) {
				foreach($ViewMyleadCount as $ViewMyleadCounts) {
					if($ViewMyleadCounts->status == 'active' || $ViewMyleadCounts->status == 'completed') {
						$Myleads['approved'] = $Myleads['approved'] + $ViewMyleadCounts->requestcount;
					} else if ($ViewMyleadCounts->status == 'declined' || $ViewMyleadCounts->status == 'rejected') {
						$Myleads['rejected'] = $Myleads['rejected'] + $ViewMyleadCounts->requestcount;
					} else if ($ViewMyleadCounts->status == 'pending') {
						$Myleads['pending'] = $Myleads['pending'] + $ViewMyleadCounts->requestcount;
					}
					$Myleads['total'] = $Myleads['total']+$ViewMyleadCounts->requestcount;
				}
			}
			$name = '';
			if(!empty($companyData->name)) {
				$name = $companyData->name;
			}
			$analyticsArray = array('viewCount' => $ViewCount,'payment' => $paymentPlanArr,'latestreview' => $getTop5Review , 'myleadlimited' => $Myleads50 ,'mylead' => $Myleads ,'jobs' => $totalJobs , 'myreviews' => $MyReviewArr,'quoterequests' => $totalQuterRequests,'appeared_listing' => $appeared_in_listing,'business_contact' => $businessContactByUser,'name' => $name);
			return response()->json(['success' => true,'data'=>$analyticsArray],$this->successStatus);
		} else {
			return response()->json(['error'=>'networkerror'], 401);
		}
	} 
	
	 // get business leads
	    public function getBusinessDashboardLead(Request $request) {
			$id = request('id');
			
			if(!empty($id) && (int)$id) {
				$companydetail =  Companydetail::where('authid', '=', (int)$id)->first();
				if(!empty($companydetail)) {
					$longitude =$companydetail->longitude;
					$latitude = $companydetail->latitude;
					// $calDis = '(((acos(sin(('.$latitude.'*pi()/180)) * sin((usr.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((usr.latitude *pi()/180)) * cos((('.$longitude.'- usr.longitude)*pi()/180))))*180/pi())*60*1.1515)';
                    $calDis = "2 * 3961 * asin(sqrt((sin(radians((usr.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(usr.latitude)) * (sin(radians((usr.longitude - ".$longitude.") / 2))) ^ 2))";
					$data = DB::select("SELECT  usr.id,usr.authid,usr.title,usr.description ,usr.services ,usr.status,usr.created_at FROM users_service_requests as usr WHERE usr.status = 'posted' AND ".$calDis." <= 50 order by usr.created_at desc");
					
					$dataUser = DB::select("SELECT  usr.id,usr.authid,usr.title,usr.description ,usr.services ,usr.status,rp.status as proposal_status,rp.created_at ,msg.message_id FROM users_service_requests as usr JOIN request_proposals as rp ON rp.requestid =usr.id LEFT JOIN messages as msg ON msg.request_id = usr.id WHERE msg.message_type = 'lead' AND msg.message_from = '".$id."' AND rp.companyid = '".$id."' AND (usr.status = 'received_leads' OR usr.status = 'completed' OR usr.status = 'posted' ) AND (rp.status='completed' OR rp.status = 'active' OR rp.status = 'pending' ) group by usr.id , msg.message_id , rp.id  order by rp.created_at desc");

				}
                if(!empty($data) || !empty($dataUser)) {
                    $allservices = Service::where('status','=','1')->select('id', 'service as itemName')->get()->toArray();
                    $newallservices = [];
                    foreach ($allservices as $val) {
                        $newallservices[$val['id']] = $val['itemName'];
                    }
                    $allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
                    $newallCategory = [];
                    foreach ($allCategory as $val) {
                        $newallCategory[$val['id']] = $val['categoryname'];
                    }
                    if(!empty($data)) {
						$newService = [];
						$temCateArr = [];
						$newService = [];
						foreach ($data as $dkey => $dval) {
							$service = json_decode($dval->services);
							foreach ($service as $catId => $SerIds) {
								foreach ($SerIds as $sid => $sval) {
									if(isset($newallservices[$sval]) && !in_array($newallservices[$sval], $newService)) {
											$newService[] =  $newallservices[$sval];
									}
								}
							} 
							$data[$dkey]->newservice = $newService; 
							unset($data[$dkey]->services);    
						}
					} else {
						$data = [];
					}
					if(!empty($dataUser)) {
						$newService = [];
						$temCateArr = [];
						$newService = [];
						foreach ($dataUser as $dkey => $dval) {
							$service = json_decode($dval->services);
							foreach ($service as $catId => $SerIds) {
								foreach ($SerIds as $sid => $sval) {
									if(isset($newallservices[$sval]) && !in_array($newallservices[$sval], $newService)) {
											$newService[] =  $newallservices[$sval];
									}
								}
							} 
							$dataUser[$dkey]->newservice = $newService; 
							unset($dataUser[$dkey]->services);    
						}
					} else {
						$dataUser = [];
					}
					return response()->json(['success' => true,'data' => $data,'userdata' => $dataUser], $this->successStatus);
				} else {
					return response()->json(['success' => false,'data' => [],'userdata' =>[]], $this->successStatus);	
				}
			} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
		}

    public function stripeTransaction($userID,$subplan,$card_token,$cardHolder) {
        $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
        $decryptUserid = $userID;
        $usersdata =$usersdata = DB::table('companydetails as cp')
            ->Join('auths as ats', 'ats.id', '=', 'cp.authid')
            ->select('cp.*','ats.email')
            ->where('cp.authid', '=', (int)$decryptUserid)
            ->first();
        $PlanData = DB::table('subscriptionplans')
                        ->select('subscriptionplans.*')
                        ->where('subscriptionplans.id',(int)$subplan)
                        ->where('subscriptionplans.status','=','active')
                        ->get();
        if(empty($PlanData)) {
			return 'network';
		}
        $renewPlan = $usersdata->subscriptiontype;
        $isRecur = false;
        if($renewPlan == 'automatic' || $renewPlan == null ) {
            $isRecur = true;
        } else {
            $isRecur = false;
        }
        $DateNext = $usersdata->nextpaymentdate;
        $days = 0;
        $IsDayLeft = false;                
        if($usersdata->nextpaymentdate == null) {
            $ammount = DB::table('subscriptionplans')->select('subscriptionplans.*')->where('subscriptionplans.id', '=', $CompanyDetail['paymentplan'])->where('subscriptionplans.status', '=', 'active')->first();
            if($ammount->amount > $PlanData->amount) { 
                $CreatedDate = strtotime($usersdata->nextpaymentdate);
                $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                $differStrTime = $CreatedDate - $CurrentDates;
                if($differStrTime > 0) {
                    $day = floor($differStrTime/(24*60*60));
                    if($day > 0) {
                        $days = $day;
                        $IsDayLeft = true;
                    }
                }
            }
        }
        
        $customer_id = $usersdata->customer_id;
        //if Customer Id exist
        if(empty($customer_id)) {
            try {
                $customer = $stripe->customers()->create([      //Create a customer account 
                    'email' => $usersdata->email,
                    'metadata' => ['name' => $usersdata->name,'email' => $usersdata->email]
                ]);
                $customer_id = $customer['id'];

            } catch(Exception $e) {
                return $e->getMessage();
            }
        } 
            if(!empty($customer_id)){
                $amount = $PlanData[0]->amount;
                $plan_id = $PlanData[0]->stripe_plan_id;
                $isPending = true;
                $subscription_id = $usersdata->subscription_id;
                

                if($usersdata->subscription_id != null) { //Cancel subscription if exist
                    try {
                        $subscription = $stripe->subscriptions()->cancel($customer_id, $subscription_id);
                    } catch(\Cartalyst\Stripe\Exception\NotFoundException $e) {
                        $subscription_id = NULL;
                    } catch(Exception $e) {
                        return $e->getMessage();
                    }
                }
                try {
                    $card = $stripe->cards()->create($customer_id, $card_token);
                }  catch(Exception $e) {
                    return $e->getMessage();
                }

                $chargeTrs=''; 
                try { 
                    if($isRecur){ //Create a subscription
                        if($IsDayLeft) {
                            $subscription = $stripe->subscriptions()->create($customer_id, [
                                'plan' => $plan_id,
                                'trial_end' => strtotime( '+'.$days.' day' ),
                                'metadata' => ['name' => $cardHolder,'added_by' => 'admin']
                            ]);
                        } else {
                            $subscription = $stripe->subscriptions()->create($customer_id, [
                                'plan' => $plan_id,
                                'metadata' => ['name' => $cardHolder,'added_by' => 'admin']
                            ]);
                        }
                    } else { // Create a charge 
                        $charge = $stripe->charges()->create([ 
                        'customer' => $customer_id,
                        'currency' => 'USD',
                        'amount' => $amount
                        ]);
                        if($charge['status'] == 'succeeded') {
                            $chargeTrs = $charge['balance_transaction'];
                            $isPending = false;
                        } else {
                            return response()->json(['error'=>'paymenterror'], 401);
                        }
                    }
                } catch(Exception $e) {
                    return $e->getMessage();
                }
                if (isset($subscription['id']) && $isRecur) {
                    $subID = $subscription['id'];
                    $updateArr['subscription_id'] = $subID;
                    $updateArr['customer_id'] = $customer_id;
                    $updated =  CompanyDetail::where('authid', '=', (int)$decryptUserid)->update($updateArr);
                    return ['success' => 'success','pending'=>$isPending];
                } else if(!$isRecur) {
                    $updateArr['subscription_id'] = null;
                    $updateArr['customer_id'] = $customer_id;
                    $updated =  CompanyDetail::where('authid', '=', (int)$decryptUserid)->update($updateArr);
                    return ['success' => 'success','pending'=>$isPending,'chargeTrs' => $chargeTrs];
                } else {
                    return 'network';
                }
            } else {
                return 'network';
            }
        }
        
        // get company details //
    public function getassignadmindetail(Request $request) {
        $authid = request('id');
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('companydetails')
            ->where('companydetails.authid', '=', (int)$authid)
            ->where('companydetails.accounttype','!=','dummy')
            ->select('companydetails.authid','companydetails.name','companydetails.assign_admin','companydetails.admin_note')
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
            $CompanyData =  Companydetail::where('authid', '=', (int)$authid)->get();
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
            $CompanyDetail = Companydetail::where('authid', '=', (int)$compid)->first();
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
