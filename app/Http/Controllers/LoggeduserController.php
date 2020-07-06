<?php 
	namespace App\Http\Controllers;
	use Illuminate\Http\Request;
	use App\Http\Traits\LocationTrait;
	use App\Auth;
	use DB;
	use App\Userdetail;
	use App\Service;
	use App\Companydetail;
	use App\Talentdetail;
	use App\dummy_registration;
	use App\Category;
	use App\Jobtitles;
	use App\Reviewsview;
	use App\User_request_services;
	use App\BusinessListingClicks;
	use App\Jobslistingclicks;
	use App\Professionallistingclicks;
	use App\Requestlistingclicks;
	use App\Requestviewpage;
	use App\Businessviewpage;
	use App\jobsviewpage;
    use App\ServiceRequestReviews;
    use App\Boat_Engine_Companies;
    use App\Jobs;
    use App\BookmarkJobs;
    use App\BookmarkRequests;
    use App\Apply_Jobs;
    use App\Messages;
	use App\Professionalviewpage;
    use App\RequestProposals;
    use App\Emailtemplates;
    use App\Contacted_Talent;
    use App\Yachtdetail;
    use App\notifications_error_logs;
    use App\Notifications;
    use App\Dictionary;
	use App\Webhook;
	use Carbon;
	use Illuminate\Support\Facades\Validator;
	use Illuminate\Support\Facades\Hash;
	use Lcobucci\JWT\Parser;
	use App\Usarea;
	use App\Subscriptionplans;
	use App\Geolocation;
	use CountryState;
	use Stripe\Error\Card;
	use Cartalyst\Stripe\Stripe;
	use Exception;
    use App\Http\Traits\NotificationTrait;
    use App\Jobs\SendNewLeadNotificationEmails;
    use App\Jobs\SendSmsToBusinesses;
    use App\Jobs\SaveNotifications;
    use App\Subcategory;
    use App\Quoterequests;
    use App\Businesslistingcount;
    use App\Businesstelephone;
    use App\WebsiteReviews;
    use Braintree_ClientToken;
	use Braintree_Transaction;
	use Braintree_Customer;
	use Braintree_WebhookNotification;
	use Braintree_WebhookTesting;
	use Braintree_Subscription;
	use Braintree_PaymentMethod;
	use Braintree_PaymentMethodNonce;
	use App\Http\Traits\ZapierTrait;

	class LoggeduserController extends Controller
	{	
		use LocationTrait;
        use NotificationTrait;
        use ZapierTrait;
		public $successStatus = 200;
	 	public function __construct(Request $request) {
            $value = $request->bearerToken();
            if(!empty($value) && $value != 'statics') {
                $id= (new Parser())->parse($value)->getHeader('jti');
                $authid = DB::table('oauth_access_tokens')->where('id', '=', $id)->where('revoked', '=', false)->first();
                if(!empty($authid->user_id) && $authid->user_id > 0) {
                    $usertype = Auth::where('id', '=', $authid->user_id)->where('status' ,'=','active')->first()->usertype;
                    if(empty($usertype) || $usertype != 'company') {
                        return response()->json(['error'=>'networkerror'], 401); 
                    }
                } else {
                    return response()->json(['error'=>'Unauthorised'], 401); 
                }
            } else {
                return response()->json(['error'=>'Unauthorised'], 401);  
            }
    	}
    	
    	//Get Business details by Slug
        public function getBusinessDetailBySlug(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required'
            ]);
            $userid = request('id');
            $decryptUserid = decrypt($userid);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            // $longitude = '-81.515754';
         //    $latitude = '27.664827';
         //    //}
         //    $calDis = '((((acos(sin(('.$latitude.'*pi()/180)) * sin((gl.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((gl.latitude *pi()/180)) * cos((('.$longitude.'- gl.longitude)*pi()/180))))*180/pi())*60*1.1515))';
            
            //$slug = strtolower(request('slug'));
            // $addressslug = strtolower(request('addressslug'));
            // $addressslug = str_replace('-',' ',$addressslug);
            $data = DB::table('companydetails as cd')->select('cd.authid','cd.name as businessname','cd.about as description','cd.contact','cd.city','cd.coverphoto','cd.primaryimage','cd.businessemail','cd.created_at','cd.images','cd.services','cd.allservices','cd.address','cd.state','cd.country','cd.longitude','cd.latitude','cd.zipcode','cd.businessemail','cd.websiteurl','cd.accounttype','cd.contactmobile','cd.country_code','cd.boats_yachts_worked','cd.engines_worked',DB::raw('coalesce( r.totalreviewed , 0 ) as totalreviewed,coalesce( r.totalrating , 0 ) as totalrated'),'cd.country_code')
                ->leftJoin('reviewsview as r','cd.authid','=','r.toid')
                ->where('cd.status','=','active')
                ->whereRaw("cd.authid ='".(int)$decryptUserid."'")
                ->get();
            if(!empty($data)) {
                // echo "<pre>";print_r($geoArr);die;
                if(isset($data[0])) {
                    $authid = $data[0]->authid;
                    
                     $geolocations = Geolocation::select('id','authid','city','state','zipcode','created_at')
                        ->where('authid', '=',(int)$decryptUserid)
                        ->where('status','=','1')
                        ->get();
                    // $latestReview = DB::table('service_request_reviews as sr')
                    //  ->select(DB::raw("CONCAT(ud.firstname, ' ', ud.lastname) as username"),'ud.profile_image','sr.rating','sr.subject','sr.comment','sr.created_at')
                    //  ->Join('userdetails as ud','ud.authid','=','sr.fromid')
                    //  ->where('isdeleted','!=','1')
                    //  ->where('toid','=',$authid)
                    //  ->orderBy('sr.created_at','DESC')->get();


                    // $latestReview = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
                    //     from service_request_reviews as msgmain
                    //     left join (
                    //         (select authid, firstname, lastname,profile_image from userdetails)
                    //         union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
                    //         union (select authid, firstname, lastname,profile_image from talentdetails)
                    //         union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
                    //     ) unionSub1 on unionSub1.authid = msgmain.fromid where  msgmain.toid = ".(int)$authid." AND msgmain.parent_id = 0 AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
                    //echo "<pre>";print_r($latestReview);die;
                    $latestReview = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype ,rplyCmp.name as replycompanyname,rplyCmp.primaryimage as replycompanyprimaryimage,rply.id as replyid,rply.created_at as replycreated_at,rply.fromid as replyfromid,rply.comment as replycomment 
                        from service_request_reviews as msgmain
                        left join (
                            (select authid, firstname, lastname,profile_image from userdetails)
                            union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
                            union (select authid, firstname, lastname,profile_image from talentdetails)
                            union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
                        ) unionSub1 on unionSub1.authid = msgmain.fromid LEFT JOIN service_request_reviews as rply ON rply.parent_id = msgmain.id AND rply.isdeleted ='0' LEFT JOIN companydetails as rplyCmp ON rply.fromid = rplyCmp.authid where  msgmain.toid = ".(int)$authid." AND msgmain.parent_id = 0 AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
                    $service = json_decode($data[0]->services);
                    $allservices = Service::where('status','=','1')->orWhere('category','=','11')->select('id', 'service as itemName','subcategory')->get()->toArray();
                    $newallservices = [];
                    foreach ($allservices as $val) {
                        $newallservices[$val['id']]['itemName'] = $val['itemName'];
                        $newallservices[$val['id']]['id'] = $val['id'];
                        $newallservices[$val['id']]['subcategory'] = $val['subcategory'];
                    }
                    $allSubCategory = Subcategory::select('id','category_id','subcategory_name')->where('status','=','1')->get()->toArray();
                    foreach ($allSubCategory as $val) {
                        $allSubCategory[$val['id']] = $val['subcategory_name'];
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
                                if($newallservices[$sval]['subcategory'] != '0') {
                                    $subname = $allSubCategory[$newallservices[$sval]['subcategory']];
                                    $newService[$newallCategory[$catId]][$subname][] =  $newallservices[$sval]['itemName'];
                                } else {
                                    $newService[$newallCategory[$catId]][] =  $newallservices[$sval]['itemName'];
                                }
                            }
                        }
                        if(!count($newService[$newallCategory[$catId]])){
                            unset($newService[$newallCategory[$catId]]);
                        }
                    }
                    $data[0]->latestReview = $latestReview;
                    $data[0]->newservices =  $newService;
                    $data[0]->geolocations = $geolocations;

                    $engines_workedArr = [];
					if($data[0]->engines_worked != null) {
						$engines_worked = (array)json_decode($data[0]->engines_worked);
						if(!empty($engines_worked['saved']) && count($engines_worked['saved']) > 0) {
							$engines_workedData = Boat_Engine_Companies::select('name')->whereIn('id',$engines_worked['saved'])->where('category','engines')->where('status','1')->get();
							if(!empty($engines_workedData) && count($engines_workedData) > 0 ) {
								foreach($engines_workedData as $val) {
									$engines_workedArr['saved'][] = $val->name;
								}
							}
						}
						if(!empty($engines_worked['other']) && count($engines_worked['other']) > 0) {
							for($i = 0 ; $i < count($engines_worked['other']) ; $i++ ) {
								$engines_workedArr['other'][] = $engines_worked['other'][$i];
							}
						}
					}
					$boats_yachts_workedArr = [];
					if($data[0]->boats_yachts_worked != null) {
						$boats_yachts_worked = (array)json_decode($data[0]->boats_yachts_worked);
						if(!empty($boats_yachts_worked['saved']) && count($boats_yachts_worked['saved']) > 0) {
							$boats_yachts_workedData = Boat_Engine_Companies::select('name')->whereIn('id',$boats_yachts_worked['saved'])->where(function($query) {
								$query->where('category', '=', 'boats')
								->orWhere('category', '=', 'yachts');
							})->where('status','1')->get();
							if(!empty($boats_yachts_workedData) && count($boats_yachts_workedData) > 0 ) {
								foreach($boats_yachts_workedData as $val) {
									$boats_yachts_workedArr['saved'][] = $val->name;
								}
							}
						}
						if(!empty($boats_yachts_worked['other']) && count($boats_yachts_worked['other']) > 0) {
							for($i = 0 ; $i < count($boats_yachts_worked['other']) ; $i++ ) {
								$boats_yachts_workedArr['other'][] = $boats_yachts_worked['other'][$i];
							}
						}
					}
					return response()->json(['success' => true,'data' => $data[0],'userid' => $authid,'boatandyachtworked' => $boats_yachts_workedArr ,'enginesworked' => $engines_workedArr ], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);
                }
            } else {
                return response()->json(['success' => false,'data' => []], $this->successStatus); 
            } 
        }


		// add s3 images //
	    public function addS3Imagesbusiness(Request $request) {
	    	$decrypt = decrypt(request('id'));
	        $authid = $decrypt;
	        $s3images = request('images');
	        $detailArr =[];
	        $detailArr['coverphoto'] =  $s3images;
	        $detailUpdate =  Companydetail::where('authid', '=', (int)$authid)->update($detailArr);
	        if($detailUpdate) {
	            $usersdata = DB::table('companydetails')
	            ->where('authid', '=', (int)$authid)
	            ->first();
	            return response()->json(['success' => true], $this->successStatus);
	        } else {
	            return response()->json(['error'=>'networkerror'], 401); 
	        }
	    }

	    public function getCompanyProfileById(Request $request) {
	    	$authid = request('id');
	    	$id = decrypt($authid);
	    	$usersdata = DB::table('companydetails as cmp')
				->Join('auths as au','au.id','=','cmp.authid')
	            ->where('cmp.authid', '=', (int)$id)
	            ->select('cmp.primaryimage','cmp.name','cmp.coverphoto','cmp.slug','au.is_social')
	            ->first();
	        if(!empty($usersdata)) {
            	return response()->json(['success' => 'success','data' => $usersdata], $this->successStatus);
	        } else {
	            return response()->json(['error'=>'networkerror'], 401); 
	        }
	    }
	    
	    public function updateCompanyProfile(Request $request) {
            $validate = Validator::make($request->all(), [
                'name' => 'required',
                'id' => 'required',
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
        
            $userid = request('id');
            $decryptUserid = decrypt($userid);
            if(empty($decryptUserid) || $decryptUserid == '') {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            $auth   = array(); 
            $address = request('address');
            $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
            
            $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];

            $CompanyImage = request('images');
            $usersdata = DB::table('companydetails')
            ->where('authid', '=', (int)$decryptUserid)
            ->first();
            if(!empty($usersdata->images)) {
                $companyAllImages = json_decode($usersdata->images,true);
            } else {
                $companyAllImages = [];
            }
            ///$CompanyImages = json_decode(request('companyimages'));
            $imagesArr = [];
            $imageCount = 0;
            if(!empty($companyAllImages) && count($companyAllImages) > 0) {
                for($i=0;$i< count($companyAllImages);$i++){
                    if(isset($companyAllImages[$i]['primary']) && $companyAllImages[$i]['primary'] == '0') {
                        $imagesArr[$imageCount]['image'] = $companyAllImages[$i]['image'];
                        $imagesArr[$imageCount]['primary'] = 0;
                        $imageCount++;
                    }
                }
                $imageArrSize = count($imagesArr);
                if(isset($CompanyImage) && $CompanyImage != '') {
                    $imagesArr[$imageArrSize]['image'] = $CompanyImage;
                    $imagesArr[$imageArrSize]['primary'] = 1;
                }
                $imagesObj =  json_encode($imagesArr,JSON_UNESCAPED_SLASHES);
                // echo "<pre>";print_r($imagesArr);
                // echo $imagesObj;die;
            } else {
                if(isset($CompanyImage) && $CompanyImage != '') {
                    $imagesArr[0]['image'] = $CompanyImage;
                    $imagesArr[0]['primary'] = 1;
                    $imagesObj = json_encode($imagesArr,JSON_UNESCAPED_SLASHES);
                } else {
                    $imagesObj = NULL;
                }
            }
            $otherserviceArr = [];
            $otherServices = request('otherservices');
            $services = request('services');
            //$service   = new Service; 
            //$service->status = '1';
            //$service->category = null;
           // $service->added_by = null;
            $allservices = json_decode(request('allservices'));
            if(empty($allservices)) {
                $allservices = [];
            }
            $countAllservice = count($allservices);
            $insertServiceArr = [];
            $insertServiceArr['status'] ='0';
            $insertServiceArr['category'] ='11';
            $insertServiceArr['added_by'] =NULL;
            $insertServiceArr['created_at'] = date('Y-m-d H:i:s');
            $insertServiceArr['updated_at'] = date('Y-m-d H:i:s');
            $otherservicesArr = json_decode($otherServices);
            for($ii =0 ; $ii < count($otherservicesArr);$ii++) {
                if(!empty($otherservicesArr[$ii]->othercatservice)) {
                    $insertServiceArr['service'] =$otherservicesArr[$ii]->othercatservice;
                    $id =0;
                    $id = Service::insertGetId($insertServiceArr);
                    if($id) {
                        $allservices[$countAllservice] = $id;
                        $countAllservice++;
                    //$otherservicesId = 0;
                   // $otherservicesId = $service->id;
                   // if($otherservicesId) {
                        $otherserviceArr[$ii] = $id;
                        //nothing
                    } else {
                        return response()->json(['error'=>'networkerror'], 401); 
                    }
                }
            }
            $services = json_decode($services);
            $services = (array)$services;
            foreach ($services as $key => $value) {
                if(empty($value)) {
                    unset($services[$key]);
                }
            }
            $services['11'] = $otherserviceArr;
            $services = json_encode($services);
            $existingCompany = Companydetail::where('authid', '=',$decryptUserid)->first();
            $boatYachtJson = request('boatYachtworked');
            $emptyboatYachtworked = true;
            $boatYachtworkedArray  = array();
            $i = 0;
            $j = 0;
            if(!empty($boatYachtJson)) {
                $boatYachtworked = json_decode(request('boatYachtworked'));
                $checkBOat = [];
                foreach ($boatYachtworked as $val) {
                    if($val && !in_array($val,$checkBOat)) {
                        $checkBOat[] = $val;
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
                    if($val && !in_array($val,$checkEngine)) {
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
            $company_name = request('name');
            $company_slug_new= preg_replace('/[^a-zA-Z0-9_ -]/s','',$company_name); 
            $slug = implode("-",explode(" ",$company_slug_new));
            $slug1 = '';
            $array = explode(" ",request('city'));
            if(is_array($array)) {
                $slug1 = implode("-",$array);       
            }
            $slug = strtolower($slug.'-'.$slug1);
            $realSlug = $slug;
            $countSlugs = 0;
            $validSlug = false;
            if($existingCompany) {
                $companyId = $existingCompany->id;
                $auth = $existingCompany->authid;
                $companydetail  = Companydetail::find($companyId);
                for($i = 0 ; $validSlug != true ; $i++) {
                    $checkSlug = Companydetail::where('actualslug','=',strtolower($slug))->where('authid', '!=', (int)$auth)->count();
                    $checkSlugEdit = Companydetail::where('slug','=',strtolower($slug))->where('authid', '!=', (int)$auth)->count();
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
            } else {
                $companydetail  = new Companydetail;   
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
            }
            $companydetail->actualslug   = strtolower($realSlug);
            $companydetail->slug   = strtolower($slug);
            //$address = request('address');
            $companydetail->authid  = $decryptUserid;
            $companydetail->name  = request('name');
            $companydetail->services   = $services;
            $companydetail->address    = ((isset($address) && $address !='') ? request('address'): NULL);
            $companydetail->city       = request('city');
            $companydetail->state      = request('state');
            $companydetail->primaryimage =   ((isset($CompanyImage) && $CompanyImage !='') ? $CompanyImage: NULL);
            $companydetail->allservices =  ((isset($allservices) && $allservices !='') ? json_encode($allservices,JSON_UNESCAPED_SLASHES): NULL);
            $companydetail->websiteurl = request('websiteurl');
            $companydetail->country    = request('country');
            //$companydetail->county    = NUMM;
            $companydetail->contactmobile    = request('contactmobile');
            $companydetail->contactemail    = strtolower(request('contactemail'));
            $companydetail->contactname    = request('contactname');
            $companydetail->zipcode    = request('zipcode');
            $companydetail->contact    = request('contact');
            $companydetail->boats_yachts_worked    = ($emptyboatYachtworked) ? NULL : $boatYachtObj;
            $companydetail->engines_worked    = ($emptyengineworked) ? NULL : $engineObj;
            $companydetail->images     = $imagesObj;
            $companydetail->longitude  = $longitude;
            $companydetail->latitude   = $latitude;
            // $companydetail->country_code   = request('country_code');
            $country_code = request('country_code');
            if($country_code != '') {
                $pos = strpos($country_code, '+');
                if(!$pos){
                    $country_code ='+'.$country_code;
                }
            }   
            $companydetail->country_code   = $country_code;
            if($companydetail->save()) {
                $zaiperenv = env('ZAIPER_ENV','local');
                if($zaiperenv == 'live') {
                    $this->companyCreateZapierbyID($decryptUserid);
                }
				$updatedDictionary = Dictionary::where('authid', '=', (int)$decryptUserid)->update(['word' => request('name')]);
                // $existingGeo = Geolocation::where('authid', '=',$decryptUserid)->first();
                // if($existingGeo) {
                //     $geoId = $existingGeo->id;
                //     $geolocation  = Geolocation::find($geoId);
                // } else {
                //     $geolocation  = new Geolocation;     
                // }
                // $city    = request('city');
                // $state   = request('state');
                // $zipcode = request('zipcode');
                // $country = request('country');
                // $county = request('county');
                // $geolocation->authid = $decryptUserid;
                // $geolocation->city = $city;
                // $geolocation->zipcode = $zipcode;
                // $geolocation->country = $country;
                // $geolocation->county = $county;
                // $geolocation->state = $state;
                // $geolocation->address    = ((isset($address) && $address !='') ? request('address'): NULL);
                // $geolocation->longitude = $longitude;
                // $geolocation->latitude = $latitude;
                // $geolocation->status = '1';
                // if($geolocation->save()) {
                    return response()->json(['success' => 'success'], $this->successStatus);
                // } else {
                //     return response()->json(['error'=>'networkerror'], 401);     
                // }
            } else {
                return response()->json(['error'=>'networkerror'], 401);  
            }    
        }


    	// update s3 images for company//
	    public function updateS3Images(Request $request) {
	    	$decrypt = decrypt(request('userid'));
	        $authid = $decrypt;
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
	            $usersdata = DB::table('companydetails')
	            ->select('images')
	            ->where('authid', '=', (int)$authid)
	            ->first();
	            return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
	        } else {
	            return response()->json(['error'=>'networkerror'], 401); 
	        }
	    }

	    // delete company image //
	    public function deleteCompanyImage(Request $request) {
	    	$decrypt = decrypt(request('userid'));
	        $authid = $decrypt;
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
	    public function changeBusinessPassword(Request $request) {
	        $validate = Validator::make($request->all(), [
	            'id' => 'required',
	            'password' => 'required',
	            'confirm' => 'required|same:password',
	            'oldpassword' => 'required',
	        ]);
	   
	        if ($validate->fails()) {
	           return response()->json(['error'=>'validationError'], 401); 
	        }
	        $userid = request('id');
        	$authid = decrypt($userid);
	        $auth   = array(); 
	        $updated = 0;
	        $oldpassword =request('oldpassword');
	        $userDetail =  DB::table('auths')->where('id', '=', (int)$authid)->where('usertype', '=', 'company')->where('status', '!=', 'deleted')->first();
	        if(!empty($userDetail)) {
	            if(!Hash::check($oldpassword,$userDetail->password)) {
	                return response()->json(['error'=>'notmatch'], 401);
	            } else {
	                $auth['password'] =Hash::make(request('password'));
	                if(!empty($authid) && $authid > 0) {
	                    $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'company')->where('status', '!=', 'deleted')->update($auth);
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

	    // get business leads
	    public function getBusinessLead(Request $request) {
			$id = decrypt(request('id'));

			if(!empty($id) && (int)$id) {

                $data = DB::select("SELECT  usr.id,usr.authid,usr.title,usr.description ,usr.services ,usr.created_at,srr.rating,rp.status as proposal_status,COALESCE(rv.requestid,0) as review,
                    COALESCE(NULLIF(ud.firstname,''), yd.firstname) as firstname,
                    COALESCE(NULLIF(ud.lastname,''), yd.lastname) as lastname,srr.comment,srr.updated_at FROM users_service_requests as usr JOIN request_proposals as rp ON rp.requestid =usr.id LEFT JOIN service_request_reviews as srr ON srr.fromid = usr.authid AND srr.toid = '".$id."' AND srr.requestid = usr.id LEFT JOIN userdetails as ud ON  usr.authid = ud.authid LEFT JOIN yachtdetail as yd ON yd.authid = usr.authid LEFT JOIN service_request_reviews as rv ON rv.requestid = usr.id
                    WHERE rp.companyid = '".$id."' order by usr.id desc");
                if(!empty($data)) {
                    
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
					return response()->json(['success' => true,'data' => $data], $this->successStatus);
				} else {
					return response()->json(['success' => false,'data' => []], $this->successStatus);	
				}
			} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
		}


        //Get All vacancies by Business Id
        public function getAllVacanciesByUserId(Request $request) {
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $jobs = Jobs::select('id as jobid','services','title','description','geolocation','salary','status','request_uniqueid','salarytype','created_at')->whereRaw('id IN(SELECT DISTINCT ON(request_uniqueid) id from jobs)')->where('authid',$id)->orderBy('id','DESC')->get();
                if(!empty($jobs) && count($jobs)) {
                    // $allservices = Service::where('status','=','1')->select('id', 'service as itemName')->get()->toArray();
                    // $newallservices = [];
                    // foreach ($allservices as $val) {
                    //     $newallservices[$val['id']] = $val['itemName'];
                    // }
                    // $allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
                    // $newallCategory = [];
                    // foreach ($allCategory as $val) {
                    //     $newallCategory[$val['id']] = $val['categoryname'];
                    // }
                    // foreach ($jobs as $jkey => $jval) {
                    //     $service = json_decode($jval->services);
                    //     $newService = [];
                    //     $temCateArr = [];
                    //     foreach ($service as $catId => $SerIds) {
                    //         // $newService[] = [];
                    //         foreach ($SerIds as $sid => $sval) {
                    //             if(isset($newallservices[$sval]) && !in_array($newallservices[$sval],$newService)) {
                    //                 $newService[] =  $newallservices[$sval];
                    //             }
                    //         }
                    //     }
                    //     $jobs[$jkey]->newservices = $newService;
                    //     unset($jobs[$jkey]->services);
                    // }
                    return response()->json(['success' => true,'data' => $jobs], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);    
                } 
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }                  
        }

        //Get all Inbox messages by Id
        public function getAllReceiveMessages(Request $request) {
            $id = '5';//decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                // $messages = DB::table('messages as m')->select('m.id as message_id','m.message_from','m.subject','m.message','m.attachment','is_read','ul.firstname as user_firstname','ul.lastname as user_lastname','ul.profile_image as user_profile')
                // ->leftJoin('userdetails as ul','ul.authid','=','m.message_from')
                // ->where('message_to',$id)
                // ->where('is_deleted','0')
                // ->orderBy('m.created_at','DESC')
                // ->get();
                $messages = DB::select('Select DISTINCT on (m.message_from) m.id,m.message_from,m.subject,m.message,m.created_at,total_message from (select count(id) as total_message,message_from from messages where message_to='.$id.' group by message_from) temp JOIN messages as m ON m.message_from = temp.message_from ORDER BY message_from ,created_at DESC');
                if(!empty($messages) && count($messages)) {
                    return response()->json(['success' => true,'data' => $messages], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);                    
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        //Get all sent messages 
        public function getAllSentMessages(Request $request) {
            $id = '5';//decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                // $messages = DB::table('messages as m')->select('m.id as message_id','m.message_from','m.subject','m.message','m.attachment','is_read','ul.firstname as user_firstname','ul.lastname as user_lastname','ul.profile_image as user_profile')
                // ->leftJoin('userdetails as ul','ul.authid','=','m.message_from')
                // ->where('message_from',$id)
                // ->where('is_deleted','0')
                // ->orderBy('m.created_at','DESC')
                // ->get();
                $messages = DB::select('Select DISTINCT on (m.message_from) m.id,m.message_from,m.subject,m.message,m.created_at,total_message from (select count(id) as total_message,message_from from messages where message_from='.$id.' group by message_from) temp JOIN messages as m ON m.message_from = temp.message_from ORDER BY message_from ,created_at DESC');
                if(!empty($messages) && count($messages)) {
                    return response()->json(['success' => true,'data' => $messages], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);                    
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        //Send message
        public function sendMessage(Request $request) {
            $validate = Validator::make($request->all(), [
                'to' => 'required',
                'id' => 'required',
                'message' => 'required',
                'subject' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $from_id = request('id');
            $message = new Messages;
            $to_email = strtolower(request('to'));
            $authData = Auth::select('authid')->where('email',$to_email)->where('status','active')->first(); 
            if(!empty($authData) && count($authData)) {
                $to =  $authData['authid'];
            } else {
                $to = 0;
            }
            $message->message_from = $from_id;
            $message->message_to = $to; 
            $message->message = request('message');
            $message->subject = request('subject');
            $message->message_type = 'lead';
            $message->attachment = request('attachment');
            if($message->save()) {
                //send email
                return response()->json(['success' => true], $this->successStatus);              
            } else {
                return response()->json(['error'=>'networkerror'], 401);               
            }
        }

        //Company payment history
        public function paymentHistory(Request $request) {
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $payment = DB::table('paymenthistory as ph')->select('ph.amount','ph.created_at','ph.transactionfor','s.planname')
                ->LeftJoin('subscriptionplans as s','s.id','=','ph.payment_type')->where('ph.companyid',$id)->where('ph.status','approved')->orderBy('ph.created_at','DESC')->get();
                if(count($payment)) {
                    return response()->json(['success' => true,'data' => $payment], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);
                }                  
            } else {
                return response()->json(['error'=>'networkerror'], 401);                
            }           
        }

        //get vacancies detail and user's applied for vacancies
        public function getCompanyVacancyDetails(Request $request) {
            $vacancy_id = '5';//request('id');
            if(!empty($vacancy_id) && $vacancy_id) {
                $vacancyData = DB::table('jobs as j')->select('j.services','j.title','j.description','j.geolocation','j.salary')->where('j.id',$vacancy_id)->get();            
                // $vacancyData->userapplied = json_encode(['firstname' => 'abcd','lastname' => 'kkkk','city','state','']);
                return response()->json(['success' => true,'data' => $vacancyData], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        //send me this lead
        public function sendMeLead(Request $request) {
            $validate = Validator::make($request->all(), [
                'businessid' => 'required',
               // 'authid' => 'required',
                'id' => 'required',
                'message' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $User_request_servicesData = User_request_services::where('id',(int)request('id'))->first(); 
            if(empty($User_request_servicesData)) {
               return response()->json(['error'=>'networkerror'], 401);         
            } else {
                $userSendLead = RequestProposals::where('requestid',(int)request('id'))->whereIn('status', array('pending','active','declined'))->count();
                if($userSendLead == $User_request_servicesData->numberofleads) {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            }
            $id = decrypt(request('businessid'));
            if(!empty($id) && (int)$id) {
                $insertRequestArr   = new RequestProposals; 
                $insertRequestArr->requestid =request('id');
                $insertRequestArr->companyid =(int)$id;
                $insertRequestArr->status = 'pending';
                $to_usertype = 0;
                $usertype = Auth::select('usertype')->where('id',$User_request_servicesData->authid)->first();
                if(!empty($usertype)) {
                    $to_usertype = $usertype->usertype;
                }
                if($insertRequestArr->save()) {
                    $reqid = $insertRequestArr->id;
                    $msgArr   = new Messages; 
                    $msgArr->message_to = (int)$User_request_servicesData->authid;
                    $msgArr->message_from = (int)$id;
                    $msgArr->message_type = 'lead';
                    $msgArr->to_usertype = $to_usertype;
                    $msgArr->from_usertype = 'company';
                    $msgArr->subject = 'Lead request';
                    $msgArr->message = request('message');
                    $msgArr->request_id= (int)request('id');
                    if($msgArr->save()) {
                        
                        $message_id = $msgArr->id;
                        $update_message_id = Messages::where('id',$message_id)->update(['message_id'=>$message_id]);
                        
                        $to_email = DB::table('auths')->select('companydetails.contactemail','companydetails.name')->Join('companydetails','auths.id','=','companydetails.authid')->where('auths.id',(int)$id)->first();
                        if(!empty($to_email) && isset($to_email->contactemail)) {
                                $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                            $link = $website_url.'/service-request/'.request('id').'?cf=marine';
                            $ACTIVATION_LINK = $link;
                            $emailArr = [];                                        
                            $emailArr['link'] = $ACTIVATION_LINK;
                            $emailArr['to_email'] = $to_email->contactemail;
                            //Send activation email notification
                            SendNewLeadNotificationEmails::dispatch($emailArr,'success_lead_sent');
                            
                            //Send Email notification to user about lead request
                            $authData = DB::table('users_service_requests as usr')->select('usr.authid','a.usertype','a.email')->Join('auths as a','usr.authid','=','a.id')->where('usr.id',request('id'))->first();
                            if(!empty($authData)) {
                                $data = [];
                                if($authData->usertype == 'yacht') {
                                    $data = Yachtdetail::select('firstname','lastname','contact','country_code')->where('authid',$authData->authid)->first(); 
                                } else if($authData->usertype == 'regular') {
                                    $data = Userdetail::select('firstname','lastname','country_code','mobile as contact')->where('authid',$authData->authid)->first();
                                }
                                if(!empty($data)) {
                                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                                    $link = $website_url.'/service-request/'.request('id').'?cf=marine';
                                    $ACTIVATION_LINK = $link;
                                    $emailArr = [];                                        
                                    $emailArr['link'] = $ACTIVATION_LINK;
                                    $emailArr['to_email'] = $authData->email;
                                    $emailArr['name'] = $data->firstname.' '.$data->lastname;
                                    $emailArr['business_name'] =  $to_email->name;
                                    SendNewLeadNotificationEmails::dispatch($emailArr,'new_lead_request');
                                    // print_r($emailArr);die;
                                    //Send sms

                                    $mobilenumber = $data->country_code.$data->contact;
                                    $sms = $emailArr['business_name'].' is interested in your service request and has sent you a message. Click '.$ACTIVATION_LINK.' to view.';
                                    // $sms ="A lead request has been sent to you for ".$ACTIVATION_LINK." request.";
                                    SendSmsToBusinesses::dispatch($sms,$mobilenumber,'job',$id,$authData->authid);
                                    
                                   
                                }
                            }  
                        }
                        
                        /*
                        $getTemplate = Emailtemplates::select('subject','body')->where('template_name','=','success_lead_sent')->where('status','1')->first();
                        if(!empty($getTemplate)) {
                            //Send notification email to business to lead sent successfully
                            $to_email = Auth::where('id',(int)$id)->first();
                            $emailArr = [];
                            if(!empty($to_email)) {
                                $emailArr['to_email'] = $to_email->email;
                                $email_body = $getTemplate->body;
                                $search  = array('%WEBSITE_LOGO%');
                                $replace = array(asset('public/img/logo.png'));
                                $emailArr['subject'] = $getTemplate->subject;
                                $emailArr['body'] = str_replace($search, $replace, $email_body);
                                $status = $this->sendEmailNotification($emailArr);
                            }
                            // if($status == 'sent') {
                            if(1) {
                                return response()->json(['success' => true,'data' => $id], $this->successStatus);
                            }  else {
                               return response()->json(['error'=>'networkerror'], 401);  
                            }   
                            
                         } else {
                             return response()->json(['error'=>'networkerror'], 401);    
                         }
                        */
                        return response()->json(['success' => true,'data' => $id], $this->successStatus);
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

        //send me this lead
        public function issendLead(Request $request) {
            $validate = Validator::make($request->all(), [
                'businessid' => 'required',
                'id' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $id = decrypt(request('businessid'));
            if(!empty($id) && (int)$id) {
                $User_request_servicesData = RequestProposals::where('requestid',(int)request('id'))->where('companyid',(int)$id)->first(); 
                if(!empty($User_request_servicesData)) {
                   return response()->json(['success' => true], $this->successStatus);        
                } else {
                    return response()->json(['error'=>'networkerror'], 401);                
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        //Get all Inbox messages by Id
        public function getinboxmessage2(Request $request) {
            $id = 41;//decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                // $messages = DB::table('messages as m')->select('m.id as message_id','m.message_from','m.subject','m.message','m.attachment','is_read','ul.firstname as user_firstname','ul.lastname as user_lastname','ul.profile_image as user_profile')
                // ->leftJoin('userdetails as ul','ul.authid','=','m.message_from')
                // ->where('message_to',$id)
                // ->where('is_deleted','0')
                // ->orderBy('m.created_at','DESC')
                // ->get();
                $currentDate = date("Y-m-d H:i:s");
                
               $messages = DB::select("SELECT * FROM (SELECT DISTINCT ON (message_id) *,count(*) over ( partition by message_id ) as totalmsg FROM (select msgmain.id,msgmain.parent_id, msgmain.message_to,msgmain.subject,msgmain.message,msgmain.message_from, unionSub1.firstname as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profle_image,unionSub2.firstname as to_firstname, unionSub2.lastname as to_lastname ,unionSub2.profile_image as to_profile_image,msgmain.created_at,msgmain.message_id,msgmain.is_read,msgmain.to_usertype,msgmain.from_usertype,COALESCE(0,NULL) as is_checked,DATE_PART('day',msgmain.created_at::timestamp - '".$currentDate."'::timestamp)
                from messages as msgmain
                left join (
                    (select authid, firstname, lastname,profile_image from userdetails)
                    union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
                    union (select authid, firstname, lastname,profile_image from talentdetails)
                    union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
                ) unionSub1 on unionSub1.authid = msgmain.message_from
                left join (
                    (select authid as rauthid, firstname, lastname,profile_image from userdetails)
                    union (select authid as rauthid, firstname, lastname,primaryimage as profile_image from yachtdetail)
                    union (select authid as rauthid, firstname, lastname,profile_image from talentdetails)
                    union (select authid as rauthid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
                ) unionSub2 on unionSub2.rauthid = msgmain.message_to where  msgmain.is_deleted != 'all' AND msgmain.is_deleted != '".(int)$id."' AND (msgmain.message_to = ".(int)$id." OR msgmain.message_from = ".(int)$id.") ORDER BY msgmain.created_at DESC) temp ORDER BY message_id) temp2 ORDER BY created_at DESC");
                if(!empty($messages) && count($messages)) {
                    return response()->json(['success' => true,'data' => $messages,'compID' => $id], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);                    
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        //Get all Inbox messages by Id
        public function getinboxmessage(Request $request) {
            $id = decrypt(request('id'));
            $message_type = request('message_type');
            $messArr = ['lead','request_quote','vacancy','contact_now','comment'];
            if(!in_array($message_type, $messArr)) {
                $message_type = 'lead';
            }
            if(!empty($id) && (int)$id) {
                $currentDate = date("Y-m-d H:i:s");
                 $change_status = DB::table('messages')->where('message_to',$id)->update(array('is_notified' => '1'));
                // $messages = DB::table('messages as m')->select('m.id as message_id','m.message_from','m.subject','m.message','m.attachment','is_read','ul.firstname as user_firstname','ul.lastname as user_lastname','ul.profile_image as user_profile')
                // ->leftJoin('userdetails as ul','ul.authid','=','m.message_from')
                // ->where('message_to',$id)
                // ->where('is_deleted','0')
                // ->orderBy('m.created_at','DESC')
                // ->get();
               $messages = DB::select("SELECT * FROM (SELECT DISTINCT ON (message_id) *,count(*) over ( partition by message_id ) as totalmsg FROM (select msgmain.id,msgmain.parent_id, msgmain.message_to,msgmain.quote_email,msgmain.quote_name,msgmain.subject,msgmain.message_type,msgmain.message,msgmain.message_from,COALESCE(unionSub1.firstname,msgmain.quote_name,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,unionSub2.firstname as to_firstname, unionSub2.lastname as to_lastname ,unionSub2.profile_image as to_profile_image,msgmain.created_at,msgmain.message_id,msgmain.is_read,msgmain.to_usertype,msgmain.from_usertype,COALESCE(0,NULL) as is_checked,DATE_PART('day',msgmain.created_at::timestamp - '".$currentDate."'::timestamp)
                from messages as msgmain
                left join (
                    (select authid, firstname, lastname,profile_image from userdetails)
                    union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
                    union (select authid, firstname, lastname,profile_image from talentdetails)
                    union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
                ) unionSub1 on unionSub1.authid = msgmain.message_from
                left join (
                    (select authid as rauthid, firstname, lastname,profile_image from userdetails)
                    union (select authid as rauthid, firstname, lastname,primaryimage as profile_image from yachtdetail)
                    union (select authid as rauthid, firstname, lastname,profile_image from talentdetails)
                    union (select authid as rauthid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
                ) unionSub2 on unionSub2.rauthid = msgmain.message_to where  msgmain.is_deleted != 'all' AND msgmain.message_type = '".$message_type."' AND msgmain.is_deleted != '".(int)$id."' AND (msgmain.message_to = ".(int)$id." OR msgmain.message_from = ".(int)$id.") ORDER BY msgmain.created_at DESC) temp ORDER BY message_id,created_at DESC) temp ORDER BY created_at DESC");
              
                if(!empty($messages) && count($messages)) {
                    return response()->json(['success' => true,'data' => $messages,'compID' => $id], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);                    
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }
        //Change Read and Unread status
        public function changeStatus(Request $request) {
            $ids = json_decode(request('id'));
            $status = request('status');
            $val = '';
            $field = '';
            if($status == 'read') {
                $field = 'is_read';
                $val = '1';
            } else if($status == 'unread') {
                $field = 'is_read';
                $val = '0';
            }
            if(is_array($ids) && count($ids) && !empty($field) && !empty($val)) { 
                $messages = DB::table('messages')->whereIn('message_id', $ids)->update(array($field => $val));
                if(!emptry($messages)) {
                    return response()->json(['success' => TRUE], $this->successStatus); 
                } else {
                    return response()->json(['error'=>'networkerror'], 401);    
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }
        }
        //delete messages
        public function deleteMessages(Request $request) {
            $authid = decrypt(request('authid'));
            $message_id = request('message_id');
            if(!empty($authid) && !empty($message_id)) {
                $getMsg = Messages::select('is_deleted','message_from','message_to')->where('message_id',$message_id)->first();
                // print_r($getMsg);die;
                if(!empty($getMsg)) { 
                    if($getMsg->message_from == $authid) {
                        $sender_id = $getMsg->message_to;
                    } else {
                        $sender_id = $getMsg->message_from;
                    }
                    if($getMsg->is_deleted == '0' || empty($getMsg->is_deleted)) {
                        $changeStatus = Messages::where('message_id',$message_id)->update(['is_deleted' => $authid]);
                    } else if($getMsg->is_deleted == $sender_id) {
                        $changeStatus = Messages::where('message_id',$message_id)->update(['is_deleted' => 'all']);
                    }
                    if(isset($changeStatus) && !empty($changeStatus)) {
                        return response()->json(['success' => true], $this->successStatus);
                    } else {
                        return response()->json(['error'=>'networkerror'], 401);    
                    }
                }  else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);                
            }
        } 
        
        //Get all Thread messages Details 
        public function getMessageDetail(Request $request) {
            $authid = decrypt(request('authid'));
            $message_id = request('message_id');
            $currentDate = date("Y-m-d H:i:s");
            $message_type = request('message_type');
            $messArr = ['lead','request_quote','vacancy','contact_now','comment'];
            if(!in_array($message_type, $messArr)) {
                $message_type = 'lead';
            }
            if(empty($message_id) || empty($authid)) {
                return response()->json(['error'=>'networkerror'], 401);
            }
            $change_status = DB::table('messages')->where('message_id', (int)$message_id)->where('message_to',$authid)->update(array('is_read' => '1','is_notified' =>'1'));
            if($message_type == 'lead'){
                $allMessages = DB::table('messages as m')->select('m.id','m.message_from','m.message_to','m.subject','m.message','m.attachment','m.is_read','m.to_usertype','m.from_usertype','m.message_id','m.created_at','m.quote_email','m.quote_name','m.request_id','m.message_type','rp.status as request_status',DB::Raw("DATE_PART('day',m.created_at::timestamp - '".$currentDate."'::timestamp)"))
                ->leftJoin('request_proposals as rp', function($join)
				 {
					 $join->on('rp.requestid','=','m.request_id');
					 $join->on('rp.companyid','=','m.message_from');
				 })
				->where('m.message_id',(int)$message_id)->where('m.message_type','=',$message_type)->where(function($q)  use ($authid) {
					$q->where('m.message_to', (int)$authid)
					->orWhere('m.message_from', (int)$authid);
				})->orderBy('m.created_at','ASC')->get();
            } else {
                $allMessages = Messages::select('id','message_from','message_to','subject','message','attachment','is_read','to_usertype','from_usertype','message_id','created_at','quote_email','quote_name','request_id','message_type',DB::Raw("DATE_PART('day',created_at::timestamp - '".$currentDate."'::timestamp)"))->where('message_id',(int)$message_id)->where('message_type','=',$message_type)->where(function($q)  use ($authid) {
					$q->where('message_to', (int)$authid)
					->orWhere('message_from', (int)$authid);
				})->orderBy('created_at','ASC')->get();    
            }

            if(!empty($allMessages)) {
                $sender_id = 0;
                if(isset($allMessages[0])) {
                    if($allMessages[0]->message_from == $authid) {
                        $sender_type = $allMessages[0]->to_usertype;
                        $sender_id = $allMessages[0]->message_to;
                        $users_type = $allMessages[0]->from_usertype;
                    } else {
                        $sender_type = $allMessages[0]->from_usertype;
                        $sender_id = $allMessages[0]->message_from;
                        $users_type = $allMessages[0]->to_usertype;
                    }
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
                $requestData =  $sender_detail = $user_detail = [];
                if(isset($allMessages[0]->message_type) && $allMessages[0]->message_type == 'lead' && isset($allMessages[0]->request_id)) {
                    $requestData = User_request_services::select('title','description')->where('id',$allMessages[0]->request_id)->first();
                    if(!empty($requestData)) {
                        $requestData->message_type = 'lead';
                        $requestData->request_id = $allMessages[0]->request_id;
                    }
                } else if(isset($allMessages[0]->message_type) && $allMessages[0]->message_type == 'vacancy' && isset($allMessages[0]->request_id)) {
                    $requestData = Jobs::select('title','description')->where('id',$allMessages[0]->request_id)->first();
                    if(!empty($requestData)) {
                        $requestData->request_id = $allMessages[0]->request_id;
                        $requestData->message_type = 'vacancy';
                    }
                }

                if(!empty($sender_type) || !empty($users_type)) {
                    if($sender_type == 'yacht') {
                        $select_sender = 'firstname,lastname,primaryimage as profile_image,concat(country_code,contact) as phoneno,city,state';
                        $table_sender = 'yachtdetail';
                    } else if($sender_type == 'company') {
                        $select_sender = 'name as firstname,COALESCE(null,null) as lastname,primaryimage as profile_image,slug,concat(country_code,contactmobile) AS phoneno,city,state';
                        $table_sender = 'companydetails';
                    } else if($sender_type == 'regular') {
                        $select_sender = 'firstname,lastname,profile_image,concat(country_code,mobile) as phoneno,city,state';
                        $table_sender = 'userdetails';
                    } else if($sender_type == 'professional') {
                        $select_sender = 'firstname,lastname,profile_image,concat(country_code,mobile) AS phoneno,city,state';
                        $table_sender = 'talentdetails';
                    } 
                    if($users_type == 'yacht') {
                        $select_user = 'firstname,lastname,primaryimage as profile_image';
                        $table_user = 'yachtdetail';
                    } else if($users_type == 'company') {
                        $select_user = 'name as firstname,COALESCE(null,null) as lastname,primaryimage as profile_image';
                        $table_user = 'companydetails';
                    } else if($users_type == 'regular') {
                        $select_user = 'firstname,lastname,profile_image';
                        $table_user = 'userdetails';
                    } else if($users_type == 'professional') {
                        $select_user = 'firstname,lastname,profile_image';
                        $table_user = 'talentdetails';
                    }
                   
                    //echo $select_sender.'   '.$table_sender.'  '.$sender_id;
                    if(!empty($sender_type)) {
                        $sender_detail = DB::table($table_sender)->select(DB::Raw($select_sender))->where('authid',$sender_id)->first();
                        if(!empty($sender_detail)) {
                            $sender_detail->user_type = $sender_type;
                            $sender_detail->authid = $sender_id;
                            $sender_detail->valid = TRUE;
                        } else {
                            $sender_detail = (object) ['valid' => FALSE];    
                        }  
                    } else {
                        $sender_detail = (object) ['valid' => FALSE];
                    }
                    if(!empty($users_type)) {
                        $user_detail = DB::table($table_user)->select(DB::Raw($select_user))->where('authid',$authid)->first();
                        if(!empty($user_detail)) {
                            $user_detail->user_type = $users_type;
                            $user_detail->authid = $authid;
                        }
                    } else {
                        $user_detail = (object) ['valid' => FALSE];
                    }
                    // if(!empty($user_detail) && !empty($sender_detail)) {
                        return response()->json(['success' => TRUE,'data' => $allMessages,'senderdetail' => $sender_detail , 'userdetail' => $user_detail,'requestdetail' => $requestData], $this->successStatus);
                    // } else {
                        // return response()->json(['error'=>'networkerror'], 401);
                    // }
                
                } else {
                    return response()->json(['error'=>'networkerror'], 401);   
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }
        } 

        //Get all Thread messages Details 
        public function getMessageUnread(Request $request) {
            $userID = decrypt(request('authid'));
            //$userID = 41;
            if(empty($userID)) {
                return response()->json(['error'=>'networkerror'], 401);
            } else {
                $authid = (int)$userID;
            }

           // $messageCount = Messages::select(' DISTINCT ON (message_id) *','message_type', DB::raw('count(*) as msgcount'))->where('message_to','=',$authid)->where('is_read', '=', '0')->where('is_deleted','!=','all')->where('is_deleted','!=',$authid)->distinct('message_id')->groupBy('message_type')->get();
           $messageCount = DB::select("SELECT message_type,count(*) as msgcount FROM (SELECT DISTINCT ON (message_id) * FROM messages where message_to = ".$authid." AND is_read ='0' AND is_deleted != 'all' AND is_deleted != '".$authid."') temp GROUP BY temp.message_type");
            $CountArray = array('total' => 0,'lead' => 0 , 'request_quote' => 0 , 'contact_now' => 0 , 'vacancy' => 0);
            if(!empty($messageCount) && count($messageCount) > 0 ) {
                foreach ($messageCount as $key => $msgval) {
                   $CountArray[$msgval->message_type] = (int)$msgval->msgcount;
                   $CountArray['total'] = $CountArray['total'] + $msgval->msgcount;
                }
            }
            return response()->json(['success' => TRUE,'data' => $CountArray], $this->successStatus);
        } 

        public function getNotificationData(Request $request) {
            $userID = decrypt(request('authid'));
            if(empty($userID)) {
                return response()->json(['error'=>'networkerror'], 401);
            } else {
                $authid = (int)$userID;
            }
            $date =  date("Y-m-d H:i:s", strtotime("-30 seconds"));
            $messages = DB::select("SELECT DISTINCT ON (message_id) * FROM (select msgmain.id,msgmain.message_id,msgmain.is_notified,msgmain.message_to,msgmain.message,msgmain.message_from,COALESCE(unionSub1.firstname,msgmain.quote_name,NULL) as from_firstname, unionSub1.lastname as from_lastname ,msgmain.created_at,msgmain.message_type,msgmain.from_usertype
                from messages as msgmain
                left join (
                    (select authid, firstname, lastname,profile_image from userdetails)
                    union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
                    union (select authid, firstname, lastname,profile_image from talentdetails)
                    union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)) unionSub1 on unionSub1.authid = msgmain.message_from
                where  msgmain.is_deleted != 'all'  AND msgmain.is_deleted != '".(int)$authid."' AND msgmain.message_to = ".(int)$authid." AND msgmain.created_at >= '".$date."' ORDER BY msgmain.created_at DESC) temp ORDER BY message_id,created_at DESC");
            if(!empty($messages) && count($messages)) {
                return response()->json(['success' => true,'data' => $messages], $this->successStatus);
            } else {
                return response()->json(['success' => false,'data' => []], $this->successStatus);                    
            }
        }
         
        //Get Business details by Slug
        public function getBusinessLocationById(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required'
            ]);
            $userid = request('id');
            $decryptUserid = decrypt($userid);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $data = DB::table('companydetails as cd')->select('cd.authid','cd.address','cd.city','cd.state','cd.country','cd.longitude','cd.latitude','cd.zipcode')
                ->where('cd.status','=','active')
                ->whereRaw("cd.authid ='".(int)$decryptUserid."'")
                ->get();    
            if(!empty($data)) {
                return response()->json(['success' => true,'data' => $data[0]], $this->successStatus);
            } else {
                return response()->json(['success' => false,'data' => []], $this->successStatus); 
            } 
        }

        //Add vacancies 
        public function addVacancies(Request $request) {
            $validate = Validator::make($request->all(), [
                'authid' => 'required',
                'title' => 'required',
                'description' => 'required',
                'experience' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $authid = decrypt(request('authid'));
            $request_uniqueid = 0;
            $insert_id = [];
            $jobs = new Jobs;
            $skillSet = (!empty(request('skillset')) ? request('skillset') : NULL);
            $jobs->title = request('title');
            $jobs->authid = $authid;
            if(!empty(request('salary'))) {
                $jobs->salarytype = request('salarytype');
            } else {
                $jobs->salarytype = null;
            }
            $jobs->jobtitleid = request('jobtitle');
            $jobs->description = request('description');
            $jobs->salary = request('salary');
            $jobs->salarytype = request('salarytype');
            $jobs->experience = request('experience');
            $jobs->skillset = request('skillset');
            $jobs->addedby = 'company';
            $jobs->status = 'active';
            $jobs->request_uniqueid = $request_uniqueid;
            if($jobs->save()) {
                if($request_uniqueid == 0) {
                    $request_uniqueid = $jobs->id;
                    $add_request_uniqueid = Jobs::where('id',$request_uniqueid)->update(['request_uniqueid' => $request_uniqueid]);
                }
                $jobsId = $jobs->id;
                $companydetails =  DB::table('companydetails')
                    ->where('authid', '=', (int)$authid)
                    ->select('name')
                    ->first();
                $miles = 50;
                $location = Companydetail::select('longitude','latitude')->where('authid',$authid)->first();
                if(!empty($location)) {
                    $latitude = $location->latitude;
                    $longitude = $location->longitude;
                    // $county = $location->county;
                    //Get Business under 50 miles
                    // $calDis = '(((acos(sin(('.$latitude.'*pi()/180)) * sin((td.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((td.latitude *pi()/180)) * cos((('.$longitude.'- td.longitude)*pi()/180))))*180/pi())*60*1.1515) <= 50';
                    $calDis = "2 * 3961 * asin(sqrt((sin(radians((td.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(td.latitude)) * (sin(radians((td.longitude - ".$longitude.") / 2))) ^ 2)) <= 50 AND text_notification = '1' AND (jobtitleid = ".request('jobtitle')." OR text_notification_other = '1')";
                    $listOfProfessionalInMiles = DB::table('talentdetails as td')->select('td.authid','td.firstname','td.lastname','a.email','td.mobile as contactmobile','td.country_code')
                        ->leftJoin('auths as a','a.id','=','td.authid')
                        ->whereRaw($calDis)
                         ->where('td.status','!=','deleted')
                        ->get();
                        $notificationDate = date('Y-m-d H:i:s');
                    if (!empty($listOfProfessionalInMiles) && count($listOfProfessionalInMiles) > 0) {
                        $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                        $link = $website_url.'/job-detail/'.$jobsId.'?cf=marine';
                        $emailArr = [];
                        $emailArr['name'] = $companydetails->name;
                        $emailArr['link'] = $link;
                        for ($i=0; $i < count($listOfProfessionalInMiles); $i++) {
                            $mobilenumber = $listOfProfessionalInMiles[$i]->country_code.$listOfProfessionalInMiles[$i]->contactmobile;
                            $emailArr['to_email'] = $listOfProfessionalInMiles[$i]->email;
                            //Dispatch Email Job
                            SendNewLeadNotificationEmails::dispatch($emailArr,'job_notification');
                            //Dispatch SMS job
                            $sms = $emailArr['name'].' has added a new job. Click '.$link.' to view job details.';
                            SendSmsToBusinesses::dispatch($sms,$mobilenumber,'job',$authid,$listOfProfessionalInMiles[$i]->authid);
                            //Dispatch Add Notification Job

                            // SaveNotifications::dispatch($listOfProfessionalInMiles[$i]->authid,'professional',NULL,NULL,'New vacancy is available in your county.',$jobsId,$notificationDate,null,0,'job');
                            $this->addNotification($listOfProfessionalInMiles[$i]->authid,'professional',NULL,NULL,'New vacancy is available in your area.',$jobsId,$notificationDate,null,0,'job');
                        }
                    }
                }
                return response()->json(['success' => true,'data' => $insert_id], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        //reply to message 
        public function replytoMessage(Request $request) {
            $validate = Validator::make($request->all(), [
                'reply' => 'required',
                'messageid' => 'required',
                'receiver' => 'required',
                'sender' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $message_type = request('message_type');
            $messArr = ['lead','request_quote','vacancy','contact_now'];
            if(!in_array($message_type, $messArr)) {
                $message_type = 'lead';
            }
            $sender_detail = DB::table('auths')->where('id',(int)request('sender'))->first();
            if(empty($sender_detail)) {
                return response()->json(['error'=>'networkerror'], 401);
            }
            $receiver_detail = DB::table('auths')->where('id',(int)request('receiver'))->first();
            if(empty($receiver_detail)) {
                return response()->json(['error'=>'networkerror'], 401);
            }
            
            /// send message ///
            $sendNotification = false;
            $NotActiveUser = false;
            $toMessageArray = Messages::where('message_id',(int)request('messageid'))->where('message_from',(int)request('sender'))->where('message_to',(int)request('receiver'))->get();
            if(!empty($toMessageArray) && count($toMessageArray) > 0) {
				$sendNotification = false;
			} else {
				$sendNotification = true;
				$UserInfo = [];
				if(!empty($sender_detail) && !empty($sender_detail->usertype)) {
					if($sender_detail->usertype == 'company') {
						$userData = Companydetail::select('contactemail','name')->where('authid', '=', (int)request('sender'))->where('status','active')->first();
						if(!empty($userData)) {
							$UserInfo['from_name'] = $userData->name;
						} else {
							$NotActiveUser = true;
						}
					} else if($sender_detail->usertype == 'regular') {
						$userData = Userdetail::select('firstname','lastname')->where('authid', '=', (int)request('sender'))->where('status','active')->first();
						if(!empty($userData)) {
							$UserInfo['from_name'] = $userData->firstname.' '.$userData->lastname;
						} else {
							$NotActiveUser = true;
						}
					} else if($sender_detail->usertype == 'yacht') {
						$userData = Yachtdetail::select('firstname','lastname')->where('authid', '=', (int)request('sender'))->where('status','active')->first();
						if(!empty($userData)) {
							$UserInfo['from_name'] = $userData->firstname.' '.$userData->lastname;
						} else {
							$NotActiveUser = true;
						}
					}  else if($sender_detail->usertype == 'professional') {
						$userData = Talentdetail::select('firstname','lastname')->where('authid', '=', (int)request('sender'))->where('status','active')->first();
						if(!empty($userData)) {
							$UserInfo['from_name'] = $userData->firstname.' '.$userData->lastname;
						} else {
							$NotActiveUser = true;
						}
					} 
				}
				
				if(!empty($receiver_detail) && !empty($receiver_detail->usertype)) {
					if($receiver_detail->usertype == 'company') {
						$userData = Companydetail::select('contactemail','name')->where('authid', '=', (int)request('receiver'))->where('status','active')->first();
						if(!empty($userData)) {
							$UserInfo['to_name'] = $userData->name;
							$UserInfo['to_email'] = $userData->contactemail;
						} else {
							$NotActiveUser = true;
						}
					} else if($receiver_detail->usertype == 'regular') {
						$userData = Userdetail::select('firstname','lastname')->where('authid', '=', (int)request('receiver'))->where('status','active')->first();
						if(!empty($userData)) {
							$UserInfo['to_name'] = $userData->firstname.' '.$userData->lastname;
							$UserInfo['to_email'] = $receiver_detail->email;
						} else {
							$NotActiveUser = true;
						}
					} else if($receiver_detail->usertype == 'yacht') {
						$userData = Yachtdetail::select('firstname','lastname')->where('authid', '=', (int)request('receiver'))->where('status','active')->first();
						if(!empty($userData)) {
							$UserInfo['to_name'] = $userData->firstname.' '.$userData->lastname;
							$UserInfo['to_email'] = $receiver_detail->email;
						} else {
							$NotActiveUser = true;
						}
					}  else if($receiver_detail->usertype == 'professional') {
						$userData = Talentdetail::select('firstname','lastname')->where('authid', '=', (int)request('receiver'))->where('status','active')->first();
						if(!empty($userData)) {
							$UserInfo['to_name'] = $userData->firstname.' '.$userData->lastname;
							$UserInfo['to_email'] = $receiver_detail->email;
						} else {
							$NotActiveUser = true;
						}
					} 
				}
			}
			
			///////////
            $messageData = Messages::where('message_id',(int)request('messageid'))->get();
            $messageRequestid = $messageData[0]->request_id;
            $senderusertype= $sender_detail->usertype;
            $receiverusertype= $receiver_detail->usertype;
            $attachment = request('attachment');
            $msgArr   = new Messages; 
            $msgArr->message_to = (int)request('receiver');
            $msgArr->message_from = (int)request('sender');
            $msgArr->message_type = $message_type; 
            $msgArr->to_usertype = $receiverusertype;
            $msgArr->from_usertype = $senderusertype;
            $msgArr->subject = '';
            $msgArr->request_id = $messageRequestid != null ? (int)$messageRequestid : NULL;
            $msgArr->message = request('reply');
            $msgArr->parent_id = (int)request('messageid');
            $msgArr->message_id = (int)request('messageid');
            $msgArr->attachment = ($attachment !='' && $attachment != null && $attachment != 'null') ? $attachment : NULL;
            if($msgArr->save()) {
				if($NotActiveUser == false && $sendNotification) {
					$website_url = env('NG_APP_URL','https://www.marinecentral.com');
					$link = '';
					if($receiver_detail->usertype == 'company') {
						$link = $website_url.'/business/messages?id='.request('messageid').'&type='.$messageData[0]->message_type.'&cf=marine';
					} else if($receiver_detail->usertype == 'yacht') {
						$link = $website_url.'/yacht/messages?id='.request('messageid').'&type='.$messageData[0]->message_type.'&cf=marine';
					} else if($receiver_detail->usertype == 'regular') {
						$link = $website_url.'/boat-owner/messages?id='.request('messageid').'&type='.$messageData[0]->message_type.'&cf=marine';
					} else if($receiver_detail->usertype == 'professional') {
						$link = $website_url.'/job-seeker/messages?id='.request('messageid').'&type='.$messageData[0]->message_type.'&cf=marine';
					}
					if($link != '' && !empty($UserInfo['to_email'])) {
						$UserInfo['link'] = $link;
						$status = $this->sendEmailNotification($UserInfo,'unreadMessage_reminder');
					}
				}
                return response()->json(['success' => true], $this->successStatus); 
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }
        
        //Get Yacht Owner Details
        public function getYachtOwnerDetail() {
            $id = request("id");
            if(!empty($id)) {
                $id = decrypt($id);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            if(!empty($id) && (int)$id) {
                $data = DB::table('yachtdetail as yd')
                    ->select('yd.authid','yd.firstname','yd.lastname','yd.contact','yd.address','yd.city','yd.state','yd.zipcode','yd.country','yd.longitude','yd.latitude','yd.yachtdetail','yd.homeport','yd.images','yd.coverphoto','yd.created_at','yd.primaryimage','a.email',DB::Raw('coalesce( r.totalrating , 0 ) as totalrating,coalesce( r.totalreviewed , 0 ) as totalreviewed'))
                        ->Join('auths as a','a.id','=','yd.authid')
                        ->leftJoin('reviewsview as r','r.toid','=','yd.authid')
                        ->where('yd.authid' ,'=', (int)$id)
                        ->where('yd.status' ,'=', 'active')
                        ->get();
                    if(!empty($data)) {
                        $authid = $data[0]->authid;
                        $jobs = DB::table('jobs')->select(DB::Raw('id,title,services,salarytype,salary,description,created_at'))
                        ->where('authid',$authid)
                        ->orderBy('created_at','DESC')->get();
                        
                        //~ if(!empty($jobs)) {
                            //~ $allservices = Service::where('status','=','1')->select('id', 'service as itemName')->get()->toArray();
                            //~ $newallservices = [];
                            //~ foreach ($allservices as $val) {
                                //~ $newallservices[$val['id']] = $val['itemName'];
                            //~ }
                            //~ $allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
                            //~ $newallCategory = [];
                            //~ foreach ($allCategory as $val) {
                                //~ $newallCategory[$val['id']] = $val['categoryname'];
                            //~ }
                            //~ $newService = [];
                            //~ $temCateArr = [];
                            //~ $newService = [];
                            //~ foreach ($jobs as $dkey => $dval) {
                                //~ $service = json_decode($dval->services);
                                //~ foreach ($service as $catId => $SerIds) {
                                    //~ foreach ($SerIds as $sid => $sval) {
                                        //~ if(isset($newallservices[$sval]) && !in_array($newallservices[$sval], $newService)){
                                            //~ $newService[] =  $newallservices[$sval];
                                        //~ }
                                    //~ }
                                //~ } 
                                //~ $jobs[$dkey]->newservice = $newService; 
                                //~ unset($jobs[$dkey]->services);    
                            //~ }
                        //~ }

                        $data[0]->jobs = $jobs;
                        $requested_service = User_request_services::select('id',    'title','description','numberofleads','services','addspecialrequirement','created_at')->where('authid','=',$id)->where('status','!=','deleted')->orderBy('created_at','DESC')->get();

                        if(!empty($requested_service)) {
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
                            $newService = [];
                            foreach ($requested_service as $dkey => $dval) {
                                $service = json_decode($dval->services);
                                foreach ($service as $catId => $SerIds) {
                                    foreach ($SerIds as $sid => $sval) {
                                        if(isset($newallservices[$sval]) && !in_array($newallservices[$sval], $newService)){
                                            $newService[] =  $newallservices[$sval];
                                        }
                                    }
                                } 
                                $requested_service[$dkey]->newservice = $newService; 
                                unset($requested_service[$dkey]->services);    
                            }
                        }
                        

                        $data[0]->user_service_request = $requested_service;
                        $allservices = Service::where(['status'=>'1','category' => '6'])->select('id', 'service as itemName')->where('category','6')->get()->toArray();
                        $newallservices = [];
                        foreach ($allservices as $val) {
                            $newallservices[$val['id']] = $val['itemName'];
                        }
                        return response()->json(['success' => true,'data' => $data,'allservices' =>$newallservices], $this->successStatus); 
                    } else {
                        return response()->json(['success' => false,'data' => []], $this->successStatus);
                    }
            } else {
                return response()->json(['error'=>'networkerror'], 401);        
            }      
        }


        //Is contact now
        public function isContactNow(Request $request) {
            $validate = Validator::make($request->all(), [
                'businessid' => 'required',
                'id' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $id = decrypt(request('businessid'));
            if(!empty($id) && (int)$id) {
                $Contacted_Talent = Contacted_Talent::where('talentid',(int)request('id'))->where('companyid',(int)$id)->first(); 
                if(empty($Contacted_Talent)) {
                   return response()->json(['success' => true], $this->successStatus);      
                } else {
                   return response()->json(['success' => false], $this->successStatus);
                }
            } else {
                return response()->json(['error'=>'validationError'], 401); 
            }
        }
        //contact now
        public function sendContactNow(Request $request) {
            $validate = Validator::make($request->all(), [
                'businessid' => 'required',
                'id' => 'required',
                'message' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $id = decrypt(request('businessid'));
            if(!empty($id) && (int)$id) {
                $userdataContact = Auth::where('id', '=', (int)$id)->where('status' ,'=','active')->first();
                if(!empty($userdataContact)) {
                    if($userdataContact->usertype == 'company'){
                        $userdataType = 'company';
                    } else if($userdataContact->usertype == 'yacht') {
                        $userdataType = 'yacht';
                    }
                    $insertRequestArr   = new Contacted_Talent; 
                    $insertRequestArr->talentid =request('id');
                    $insertRequestArr->companyid =(int)$id;
                    $insertRequestArr->message =request('message');
                    $insertRequestArr->status = '1';
                    if($insertRequestArr->save()) {
                        $reqid = $insertRequestArr->id;
                        $msgArr   = new Messages; 
                        $msgArr->message_to = (int)request('id');
                        $msgArr->message_from = (int)$id;
                        $msgArr->message_type = 'contact_now';
                        $msgArr->to_usertype = 'professional';
                        $msgArr->from_usertype = $userdataType;
                        $msgArr->subject = 'Contact Now';
                        $msgArr->message = request('message');
                        if($msgArr->save()) {
                            
                            $message_id = $msgArr->id;
                            $update_message_id = Messages::where('id',$message_id)->update(['message_id'=>$message_id]);
                            
                             /// send message ///
							$NotActiveUser = false;
							$UserInfo = [];
							if(!empty($userdataContact) && !empty($userdataContact->usertype)) {
								if($userdataContact->usertype == 'company') {
									$userData = Companydetail::select('contactemail','name')->where('authid', '=', (int)$id)->where('status','active')->first();
									if(!empty($userData)) {
										$UserInfo['from_name'] = $userData->name;
									} else {
										$NotActiveUser = true;
									}
								} else if($userdataContact->usertype == 'yacht') {
									$userData = Yachtdetail::select('firstname','lastname')->where('authid', '=', (int)$id)->where('status','active')->first();
									if(!empty($userData)) {
										$UserInfo['from_name'] = $userData->firstname.' '.$userData->lastname;
									} else {
										$NotActiveUser = true;
									}
								}  
							}
							$userData = DB::table('talentdetails as ts')->select('ts.firstname','ts.lastname','au.email')->join('auths as au','au.id','=','ts.authid')->where('ts.authid', '=', (int)request('id'))->where('ts.status','active')->first();
							if(!empty($userData)) {
								$UserInfo['to_name'] = $userData->firstname.' '.$userData->lastname;
								$UserInfo['to_email'] = $userData->email;
							} else {
								$NotActiveUser = true;
							}
				
							if($NotActiveUser == false) {
								$website_url = env('NG_APP_URL','https://www.marinecentral.com');
								$link = '';
								$link = $website_url.'/job-seeker/messages?id='.$message_id.'&type=contact_now&cf=marine';
								if($link != '' && !empty($UserInfo['to_email'])) {
									$UserInfo['link'] = $link;
									$status = $this->sendEmailNotification($UserInfo,'unreadMessage_reminder');
								}
							}
                            /*
                            $getTemplate = Emailtemplates::select('subject','body')->where('template_name','=','success_lead_sent')->where('status','1')->first();
                            if(!empty($getTemplate)) {
                                //Send notification email to business to lead sent successfully
                                $to_email = Auth::where('id',(int)$id)->first();
                                $emailArr = [];
                                if(!empty($to_email)) {
                                    $emailArr['to_email'] = $to_email->email;
                                    $email_body = $getTemplate->body;
                                    $search  = array('%WEBSITE_LOGO%');
                                    $replace = array(asset('public/img/logo.png'));
                                    $emailArr['subject'] = $getTemplate->subject;
                                    $emailArr['body'] = str_replace($search, $replace, $email_body);
                                    $status = $this->sendEmailNotification($emailArr);
                                }
                                // if($status == 'sent') {
                                if(1) {
                                    return response()->json(['success' => true,'data' => $id], $this->successStatus);
                                }  else {
                                   return response()->json(['error'=>'networkerror'], 401);  
                                }   
                                
                            } else {
                                return response()->json(['error'=>'networkerror'], 401);    
                            }
                            */
                            return response()->json(['success' => true,'data' => $id], $this->successStatus);
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

        // updte yacht coverphoto
        public function addS3ImagesYacht(Request $request) {
            $decrypt = decrypt(request('id'));
            $authid = $decrypt;
            $s3images = request('images');
            $detailArr =[];
            $detailArr['coverphoto'] =  $s3images;
            $detailUpdate =  Yachtdetail::where('authid', '=', (int)$authid)->update($detailArr);
            if($detailUpdate) {
                $usersdata = DB::table('yachtdetail')
                ->where('authid', '=', (int)$authid)
                ->first();
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }

        // to show name in header
        public function getYachtProfileById(Request $request) {
            $authid = request('id');
            $id = decrypt($authid);
            $yachtdata = DB::table('yachtdetail as yt')
				->Join('auths as au','au.id','=','yt.authid')
                ->where('yt.authid', '=', (int)$id)
                ->select('yt.primaryimage','yt.coverphoto','au.is_social',DB::raw("CONCAT(yt.firstname,' ',yt.lastname) AS name"))
                ->first();
            if(!empty($yachtdata)) {
                return response()->json(['success' => 'success','data' => $yachtdata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }

        // to get yacht personal details
        public function getYachtOwnerDetailwithoutRating() {
            $id = request("id");
            if(!empty($id)) {
                $id = decrypt($id);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            if(!empty($id) && (int)$id) {
                $data = DB::table('yachtdetail')
                    ->select('firstname','lastname','contact','address','city','state','zipcode','country','homeport','images','coverphoto','primaryimage','country_code')
                        ->where('authid' ,'=', (int)$id)
                        ->where('status' ,'=', 'active')
                        ->get();
                    if(!empty($data)) {
                        return response()->json(['success' => true,'data' => $data], $this->successStatus);
                    } else {
                        return response()->json(['success' => false,'data' => []], $this->successStatus);
                    }
            } else {
                return response()->json(['error'=>'networkerror'], 401);        
            }      
        }

        // updae yacht personal detals
        public function updateYachtPersonal(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'firstname' => 'required',
                'lastname' => 'required',
                'contact' => 'required',
                'city' => 'required',
                'state' => 'required',
                'country' => 'required',
                // 'county' => 'required',
                'zipcode' => 'required',
                // 'images' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $authid = decrypt(request('id'));
            $auth = Auth::find($authid);
            $auth->ipaddress = $this->getIp();
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
                    $yachtArr['address'] = (!empty(request('address'))?request('address'):NULL);
                    $yachtArr['longitude']  = $longitude;
                    $yachtArr['latitude']   = $latitude;
                    $yachtArr['city'] = request('city');
                    $yachtArr['state'] = request('state');
                    $yachtArr['country'] = request('country');  
                    // $yachtArr['county'] = request('county');  
                    $yachtArr['zipcode'] = request('zipcode');
                    $country_code = request('country_code');
					if($country_code != '') {
						$pos = strpos($country_code, '+');
						if(!$pos){
							$country_code ='+'.$country_code;
						}
					} 
					$yachtArr['country_code'] = $country_code;
             
                    //$imagesObj =  '';
                    if (request('images') != null && request('images') != '' && request('images') != 'null') {
                        $yachtArr['primaryimage'] = request('images');
                        $YachtImage = request('images');
                        $usersdata = DB::table('yachtdetail')
                        ->where('authid', '=', (int)$authid)
                        ->first();
                        if(!empty($usersdata->images)) {
                            $yachtAllImages = json_decode($usersdata->images,true);
                        } else {
                            $yachtAllImages = [];
                        }
                        ///$CompanyImages = json_decode(request('companyimages'));
                        $imagesArr = [];
                        $imageCount = 0;
                        if(!empty($yachtAllImages) && count($yachtAllImages) > 0) {
                            for($i=0;$i< count($yachtAllImages);$i++){
                                if(isset($yachtAllImages[$i]['primary']) && $yachtAllImages[$i]['primary'] == '0') {
                                    $imagesArr[$imageCount]['image'] = $yachtAllImages[$i]['image'];
                                    $imagesArr[$imageCount]['primary'] = 0;
                                    $imageCount++;
                                }
                            }
                            $imageArrSize = count($imagesArr);
                            if(isset($YachtImage) && $YachtImage != '') {
                                $imagesArr[$imageArrSize]['image'] = $YachtImage;
                                $yachtArr['primaryimage'] = $YachtImage;
                                $imagesArr[$imageArrSize]['primary'] = 1;
                            } else {
                                $yachtArr['primaryimage'] = NULL;
                            }
                            $imagesObj =  json_encode($imagesArr,JSON_UNESCAPED_SLASHES);
                        } else {
                            $imagesArr = [];
                            if(isset($YachtImage) && $YachtImage != '') {
                                $imagesArr[0]['image'] = $YachtImage;
                                $imagesArr[0]['primary'] = 1;
                                $yachtArr['primaryimage'] = $YachtImage;
                                $imagesObj = json_encode($imagesArr,JSON_UNESCAPED_SLASHES);
                            } else {
                                $imagesObj = NULL;
                                $yachtArr['primaryimage'] = NULL;
                            }
                        }
                        $yachtArr['images'] = $imagesObj;
                    } else {
                        $yachtArr['primaryimage'] = NULL;
                        $usersdata = DB::table('yachtdetail')
                        ->where('authid', '=', (int)$authid)
                        ->first();
                        if(!empty($usersdata->images)) {
                            $yachtAllImages = json_decode($usersdata->images,true);
                        } else {
                            $yachtAllImages = [];
                        }
                        $imagesArr = [];
                        $imageCount = 0;
                        if(!empty($yachtAllImages) && count($yachtAllImages) > 0) {
                            for($i=0;$i< count($yachtAllImages);$i++){
                                if(isset($yachtAllImages[$i]['primary']) && $yachtAllImages[$i]['primary'] == '0') {
                                    $imagesArr[$imageCount]['image'] = $yachtAllImages[$i]['image'];
                                    $imagesArr[$imageCount]['primary'] = 0;
                                    $imageCount++;
                                }
                            }
                            $imageArrSize = count($imagesArr);
                            $imagesObj =  json_encode($imagesArr,JSON_UNESCAPED_SLASHES);
                        } else {
                            $imagesObj = NULL;
                        }
                        $yachtArr['images'] = $imagesObj;
                    }
                    $detailUpdate =  Yachtdetail::where('authid', '=', (int)$authid)->update($yachtArr);
                    if($detailUpdate) {
						$zaiperenv = env('ZAIPER_ENV','local');
						if($zaiperenv == 'live') {
							$this->sendAccountCreateZapierbyID($authid,'yacht');
						}
                        return response()->json(['success' => true,'data' => $imagesObj], $this->successStatus);
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

        // get yacht owner yacht detals
        public function getYachtOwnerYachtdetail(Request $request){
            $id = request("id");
            if(!empty($id)) {
                $id = decrypt($id);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            if(!empty($id) && (int)$id) {
                $data = DB::table('yachtdetail')
                    ->select('yachtdetail','homeport')
                        ->where('authid' ,'=', (int)$id)
                        ->where('status' ,'=', 'active')
                        ->get();
                    if(!empty($data)) {
                        return response()->json(['success' => true,'data' => $data], $this->successStatus);
                    } else {
                        return response()->json(['success' => false,'data' => []], $this->successStatus);
                    }
            } else {
                return response()->json(['error'=>'networkerror'], 401);        
            } 
        }

        // update yacht owner yacht detals
        public function updateYachtOwnerYachtDetail(Request $request){
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'yachtdetail' => 'required',
                'homeport' => 'required',
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $authid = decrypt(request('id'));
            $auth = Auth::find($authid);
            $auth->ipaddress = $this->getIp();
            if($auth->save()) {
                $authid = $auth->id;
                if($authid) {
                    $yachtArr['homeport'] = request('homeport');
                    $yachtArr['yachtdetail'] = request('yachtdetail');
                    $detailUpdate =  Yachtdetail::where('authid', '=', (int)$authid)->update($yachtArr);
                    if($detailUpdate) {
						$zaiperenv = env('ZAIPER_ENV','local');
						if($zaiperenv == 'live') {
							$this->sendAccountCreateZapierbyID($authid,'yacht');
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

        // update S3 images for yacht
        public function updateYachtS3Images(Request $request) {
            $decrypt = decrypt(request('id'));
            $authid = $decrypt;
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
            $detailArr['primaryimage'] =  (!empty($primaryImg)?$primaryImg:NULL);
            $detailUpdate =  Yachtdetail::where('authid', '=', (int)$authid)->update($detailArr);
            if($detailUpdate) {
                $usersdata = DB::table('yachtdetail')
                ->select('images','primaryimage')
                ->where('authid', '=', (int)$authid)
                ->first();
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }

        // delete yacht portfolio images
        public function deleteYachtImage(Request $request) {
            $decrypt = decrypt(request('id'));
            $authid = $decrypt;
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
            $detailUpdate =  Yachtdetail::where('authid', '=', (int)$authid)->update($detailArr);
            $primaryImage =  Yachtdetail::select('primaryimage')->where('authid', '=', (int)$authid)->first();
            if($detailUpdate) {
                return response()->json(['success' => true,'images' => $jsonObj,'primaryimage' => $primaryImage], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }

        public function changeYachtPassword(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'password' => 'required',
                'confirm' => 'required|same:password',
                'oldpassword' => 'required',
            ]);
       
            if ($validate->fails()) {
               return response()->json(['error'=>'validationError'], 401); 
            }
            $userid = request('id');
            $authid = decrypt($userid);
            $auth   = array(); 
            $updated = 0;
            $oldpassword =request('oldpassword');
            $userDetail =  DB::table('auths')->where('id', '=', (int)$authid)->where('usertype', '=', 'yacht')->where('status', '!=', 'deleted')->first();
            if(!empty($userDetail)) {
                if(!Hash::check($oldpassword,$userDetail->password)) {
                    return response()->json(['error'=>'notmatch'], 401);
                } else {
                    $auth['password'] =Hash::make(request('password'));
                    if(!empty($authid) && $authid > 0) {
                        $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'yacht')->where('status', '!=', 'deleted')->update($auth);
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

        public function companyCurrentPlan(Request $request) {
            $useridencrypt = request('id');
            $authid = decrypt($useridencrypt);
            $currentTime = Carbon\Carbon::now();
            if(!empty($authid) && $authid > 0) {
                $checkFreeAccount = DB::table('companydetails')
                ->select('account_type','is_discount','free_subscription_period','free_subscription_start','free_subscription_end')
                ->whereRaw("account_type ='free'")
                ->where('authid',$authid)
                ->first();
                $isUnlimit = false;
                $currentDate = date('Y-m-d 00:00:00');
                if(env('BASIC_UNLIMITED_GEO_LOC') == 'YES') {
					if ($currentDate < env('BASIC_UNLIMITED_ACCESS_END')) {
						$isUnlimit = true;
					} 
				}

                if(!empty($checkFreeAccount) && isset($checkFreeAccount->account_type) && $checkFreeAccount->account_type == 'free') {
                    if($checkFreeAccount->free_subscription_period == 'unlimited' || $checkFreeAccount->free_subscription_end > $currentTime) {
                        
                        return response()->json(['success' => true,'data'=>$checkFreeAccount,'account_type' => 'free','unlimited' => $isUnlimit], $this->successStatus);                    
                    } else {
                        return response()->json(['success' => true,'data'=>'planExpireError','account_type' => 'free','unlimited' => $isUnlimit], $this->successStatus);
                    }
                } else {
                    $usersdata = DB::table('companydetails')
                    ->Join('paymenthistory', 'paymenthistory.companyid', '=', 'companydetails.authid')
                    ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'companydetails.next_paymentplan')
                    ->leftJoin('degrade','companydetails.authid','=','degrade.authid')
                    ->select('degrade.paymentplan as degradedplan','paymenthistory.created_at','paymenthistory.expiredate', 'subscriptionplans.*','companydetails.subscriptiontype','companydetails.is_discount','subscriptionplans.id as subid','discounts.current_discount')
                    ->leftJoin('discounts', 'discounts.paymentplan', '=', 'subscriptionplans.id')
                    ->where('companydetails.authid','=',(int)$authid)
                    ->where('paymenthistory.expiredate','>',$currentTime)
                    ->where('paymenthistory.transactionfor','registrationfee')
                    ->orderBy('paymenthistory.id','DESC')
                    ->first();
                    if(isset($usersdata->amount) && isset($usersdata->current_discount) && $usersdata->amount > 0) {
                        $discountapply = $usersdata->current_discount;

                        if($discountapply > 0) {
                            $discountAmount = ceil(($usersdata->amount * $discountapply)/100);
                        }
                        $usersdata->discountedAmmount = $usersdata->amount - $discountAmount; 
                    } else {
                        // $usersdata->discountedAmmount = 0;                            
                    }
                    //echo "<pre>";print_r($usersdata);die;
                    $isUnlimit = false;
                    if(!empty($usersdata)) {
						$currentDate = date('Y-m-d 00:00:00');
						if (($currentDate < env('BASIC_UNLIMITED_ACCESS_END'))) {
							$isUnlimit = true;
						} 
                        return response()->json(['success' => true,'data' => $usersdata,'account_type' => 'paid','unlimited' => $isUnlimit], $this->successStatus);
                    } else {
                        $usersdata = DB::table('companydetails')
                        ->Join('paymenthistory', 'paymenthistory.companyid', '=', 'companydetails.authid')
                        ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'companydetails.next_paymentplan')
                        ->leftJoin('degrade','companydetails.authid','=','degrade.authid')
                        ->leftJoin('discounts', 'discounts.paymentplan', '=', 'subscriptionplans.id')
                        ->select('degrade.paymentplan as degradedplan','paymenthistory.created_at','paymenthistory.expiredate', 'subscriptionplans.*','companydetails.subscriptiontype','discounts.current_discount')
                        ->where('companydetails.authid','=',(int)$authid)
                        ->where('paymenthistory.transactionfor','registrationfee')
                        ->orderBy('paymenthistory.id','DESC')
                        ->first();
                        if(isset($usersdata->amount) && isset($usersdata->current_discount) && $usersdata->amount > 0) {
                            $discountapply = $usersdata->current_discount;

                            if($discountapply > 0) {
                                $discountAmount = ceil(($usersdata->amount * $discountapply)/100);
                            }
                            $usersdata->discountedAmmount = $usersdata->amount - $discountAmount; 
                        } else {
                            $usersdata->discountedAmmount = 0;                            
                        }
                        $currentDate = date('Y-m-d 00:00:00');
						if (($currentDate < env('BASIC_UNLIMITED_ACCESS_END'))) {
							$isUnlimit = true;
						} 
                        return response()->json(['success' => true,'data'=>'planExpireError','plandetail' => $usersdata,'account_type' => 'paid','unlimited' => $isUnlimit], $this->successStatus);
                    }
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);  
            }
        }
         // trial plan payment //
        public function trialbusinesspaymentplan(Request $request) {
            $encryptId = request('id');
            $isdegrade = request('isdegrade');
            if($isdegrade == 'true') {
				$degrade = true;
			} else {
				$degrade = false;
			}
            $id = decrypt($encryptId);
            $companyData = Companydetail::where('authid', (int)$id)->where('status','active')->get();
            if(!empty($companyData) && count($companyData) > 0) {
				$currentDate = date('Y-m-d H:i:s');
                /*
				if($companyData[0]->nextpaymentdate > $currentDate) {
					$lastPaymentDate = $companyData[0]->lastpaymentdate;
					if($lastPaymentDate == 'null') {
						$nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime($currentDate)));
					} else {
						if($companyData[0]->remaintrial > 0) {
							$nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime($currentDate)));
						} else {
							$currStr =  strtotime($currentDate);
							$NxtStr =  strtotime($companyData[0]->nextpaymentdate);
							$diffcheck = $NxtStr - $currStr;
							$dayTrial = 0;
							if($diffcheck > 0) {
								$dayTrial = (int)($diffcheck/(24*60*60));
							}
							$trialDaysNxt = $dayTrial + 30;
							$nextDate = date('Y-m-d 00:00:00', strtotime("+ ".$trialDaysNxt." days", strtotime($currentDate)));
						}
					}
				} else {
					$nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime($currentDate)));
				}
                */
                $IsDayLeft = false;
                $days = 0;
                $trailDays = 0;
                if($companyData[0]->nextpaymentdate > $currentDate) {
                    $CreatedDate = strtotime($companyData[0]->nextpaymentdate);
                    $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                    $differStrTime = $CreatedDate - $CurrentDates;
                    if($differStrTime > 0) {
                        $day = round($differStrTime/(24*60*60));
                        if($day > 0) {
                          $trailDays =  $days = $day;
                            $IsDayLeft = true;
                        }
                    }
                }
                if($IsDayLeft) {
                    $days = $days+30;
                    $nextDate = date('Y-m-d 00:00:00', strtotime("+ ".$days." days", strtotime($currentDate)));
                } else {
                    $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime($currentDate)));
                }
				$paymentPlan = $companyData[0]->paymentplan;
				$paymentHistory = DB::table('paymenthistory')->where('companyid',((int)$id))->orderBy('id','DESC')->first();
				if(!empty($paymentHistory)) {
					$statusHistory = DB::table('paymenthistory')->where('id', (int)$paymentHistory->id)->update(['expiredate' => date('Y-m-d H:i:s')]);
					if($statusHistory) {
					} else {
						return response()->json(['error'=>'networkerror'], 401);
					}
				}
				$trial = 0;
                /*
				if($companyData[0]->remaintrial > 0) {
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
                */
				$statusCompany = Companydetail::where('authid', (int)$id)->update(['subscriptiontype' => 'manual','nextpaymentdate' => $nextDate, 'paymentplan' => (int)(request('subplan')),'plansubtype' => 'free','status' => 'active','lead_payment'=>0,'lastpaymentdate' =>$currentDate,'next_paymentplan' =>(int)(request('subplan')),'remaintrial' =>$trial,'account_type' =>'paid','free_subscription_period' => null,'free_subscription_start' => null,'free_subscription_end' => null,'remaintrial'=> $trailDays ]);
				if($statusCompany) {
                $statusPayment =  DB::table('paymenthistory')->insert(
                                ['companyid' => (int)$id,'transactionfor' => 'registrationfee',
                                'amount' => '0.00',
                                'status' => 'approved' ,
                                'payment_type' => (int)(request('subplan')),
                                'expiredate' => $nextDate,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                                ]);
					if($statusPayment) {
						if($degrade) {
							DB::table('degrade')->insert(
                                ['authid' => (int)$id,
                                'paymentplan' => (int)(request('subplan')),
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                                ]);
						}
						if(!empty($companyData[0]->subscription_id) && $companyData[0]->subscription_id != null) {
    						try {
                                $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
                                $subscription = $stripe->subscriptions()->cancel($companyData[0]->customer_id , $companyData[0]->subscription_id);
                                $deletSub =  Companydetail::where('authid', (int)$id)->update(['subscription_id' => null]);
                            }	catch(\Cartalyst\Stripe\Exception\NotFoundException $e) {

                            }   catch(Exception $e) {
                                return response()->json(['error'=>$e->getMessage()], 401);
                            }
						}
                        $zaiperenv = env('ZAIPER_ENV','local');
                        if($zaiperenv == 'live') {
                            $this->companyCreateZapierbyID($id);
                        }
						return response()->json(['success' => true,'nextdate' =>$nextDate], $this->successStatus);
                        
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
        
         //stripe payment for logged user
        public function companyloggedpayment(Request $request){
            $validate = Validator::make($request->all(), [
                'subplan' => 'required',
                'userID'  => 'required',
                'card_token' => 'required'
            ]);  
            if ($validate->fails()) {
                return response()->json(['error'=>'Validation Failed.'], 401);
            }
            $upgrade = request('isupgrade');
            $isdegrade = request('isdegrade');
            if(!empty($isdegrade) && $isdegrade == 'true') {
				$degrade = true;
			} else {
				$degrade = false;
			}
            $subType = 'manual';
            $useridencrypt = request('userID');
            $userID = decrypt($useridencrypt);
            if(empty($userID) || $userID == '') {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            $card_token = request('card_token');
            $cardHolder = request('name');
            $type = request('cardtype');
            if($type == 'old') {
                $card_token = decrypt($card_token);
            }else{
                $cardHolder = request('name');
                if(empty($cardHolder)){
                    return response()->json(['error'=>'Validation Failed.'], 401);
                }
            }
            $paymentStatus = $this->braintreeTransaction($userID,request('subplan'),$card_token,$cardHolder,$type,$degrade);
            if(isset($paymentStatus['success'])) {
                $isPendingPayment = $paymentStatus['pending'];
                if($isPendingPayment) {
                    $statuspayment = 'pending';
                } else {
                    $statuspayment = 'approved';
                }

                /* Get user card Token and Plan*/
                //$cardHolderName = request('nameoncard');
                $subplan = request('subplan');
                //$card_token = request('card_token');
                //$userID = request('userID');
                $userDetail = Auth::where('id', '=', (int)$userID)->where('status', '!=', 'deleted')->get()->first()->toArray();
                $email = $userDetail['email'];
                $ex_message = '';
                $companyDetail = Companydetail::where('authid', '=', (int)$userID)->get()->first()->toArray();
                $subType = $companyDetail['subscriptiontype'];
                $plandata = DB::table('subscriptionplans')->where('id', '=', (int)$subplan)->where('status', '=', 'active')->first();
                $basicTrialDays = 0;
                //Check if user day left for previous plan
                $IsDayLeft = false;
                $days = 0;
                if($companyDetail['plansubtype'] != 'free' && $degrade){
                    
                    $CreatedDate = strtotime($companyDetail['nextpaymentdate']);
                    $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                    $differStrTime = $CreatedDate - $CurrentDates;
                    if($differStrTime > 0) {
                        $day = round($differStrTime/(24*60*60));
                        if($day > 0) {
                            $days = $day;
                            $IsDayLeft = true;
                        }
                    }
                }
                if(!empty($plandata)) {
                    $planPrice = $plandata->amount;
                    $planType = $plandata->plantype;
                    $planAccessType = $plandata->planaccesstype;
                    $planAccessNumber = $plandata->planaccessnumber;
                    if($planType =='paid') { 
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
                        }  else if($planAccessType == 'year'){
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
                        //Add Free Plan
                        if($planAccessType == 'unlimited'){
                            $nextDate = '2099-01-01 00:00:00';
                        } else {
							if($IsDayLeft && $days > 0 ) {
								$nextDate = date('Y-m-d 00:00:00', strtotime("+".$days." days"));
							} else {
								$nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
							}
                        }
                        return response()->json(['success' => true,'nextdate' =>$nextDate], $this->successStatus);
                    }            
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
                $isNotWebHookEntery = true;
                $tranctionID = '';
                if($statuspayment == 'approved') {
                    $successMsg = 'invoice_payment_succeeded';
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
                    $statusCompany = Companydetail::where('authid', (int)$userID)->update(['subscriptiontype' => $subType,'nextpaymentdate' => $nextDate, 'next_paymentplan' => (int)$subplan, 'paymentplan' => (int)$subplan,'plansubtype' => 'paid','status' => 'active','lead_payment' => 0,'account_type' => 'paid','free_subscription_period' => null,'free_subscription_start' => null,'free_subscription_end' => null,'remaintrial'=>$days]);
                }
                if($statusCompany) {
                    $zaiperenv = env('ZAIPER_ENV','local');
                    if($zaiperenv == 'live') {
                        $this->companyCreateZapierbyID($userID);
                    }
                    if($degrade) {
                        DB::table('degrade')->insert(
                            ['authid' => (int)$userID,
                            'paymentplan' => (int)($subplan),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                            ]);
                    }
                    if($statuspayment == 'approved') {
                        $statusPayment =  DB::table('paymenthistory')->insert(
                                ['companyid' => (int)$userID,
                                'transactionid' => $tranctionID,
                                //'tokenused' => $card_token,
                                'transactionfor' => 'registrationfee',
                                'amount' => $planPrice,
                                'status' => $statuspayment ,
                                'customer_id' => $companyDetail['customer_id'],
                                'subscription_id' => $companyDetail['subscription_id'],
                                'payment_type' => (int)$subplan,
                                //'cardid' => $tokenData['card']['id'],
                                'expiredate' => $nextDate,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                                ]);
                        if($statusPayment) {
                            return response()->json(['success' => true,'nextdate' =>$nextDate], $this->successStatus);
                        } else {
                            return response()->json(['error'=>'entryfail'], 401);
                        }
                    } else {
                        $paymentHistoryData = DB::table('paymenthistory')->where('subscription_id','=',$companyDetail['subscription_id'])->where('transactionfor','registrationfee')->orderBy('created_at','DESC')->get();
                        if(!empty($paymentHistoryData) && count($paymentHistoryData) > 0 && $paymentHistoryData[0]->status == 'approved') {
                                $statusPayment = true;
                            } else {
                                $statusPayment =  DB::table('paymenthistory')->insert(
                                    ['companyid' => (int)$userID,
                                    'transactionid' => request('tranctionID'),
                                    //'tokenused' => $card_token,
                                    'transactionfor' => 'registrationfee',
                                    'amount' => $planPrice,
                                    'status' => $statuspayment ,
                                    'customer_id' => $companyDetail['customer_id'],
                                    'subscription_id' => $companyDetail['subscription_id'],
                                    'payment_type' =>(int)$subplan,
                                    //'cardid' => $tokenData['card']['id'],
                                    'expiredate' => $nextDate,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                    ]);
                            }
                        if($statusPayment) {
                            return response()->json(['success' => true,'nextdate' =>$nextDate], $this->successStatus);
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

        //Change subscription type
       			//Change subscription type
        public function changeCompanyPaymentStatus(Request $request) {
            $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
            $id = request('id');
            if(!empty($id)) {
                $userid = decrypt($id);
                $status = request('subType');
                if($status == 'manual') {
                    $status = 'automatic';
                } else if($status == 'automatic') {
                    $status = 'manual';
                } else {
                    $status = 'manual';
                }
                $datauser = DB::table('companydetails as cmp')
				->Join('subscriptionplans as sub','sub.id','=','cmp.next_paymentplan')
                ->where('cmp.authid', '=', (int)$userid)
                ->select('cmp.*','sub.amount as planamount','sub.stripe_plan_id')
                ->first();
				if(!empty($datauser)) {
					if ($status == 'automatic') {
                        if(empty($datauser->subscription_id)) {
                            try {
                                $subscription = $stripe->subscriptions()->cancel($datauser->customer_id, $datauser->subscription_id);
                            } catch(\Cartalyst\Stripe\Exception\NotFoundException $e){

                            } catch(Exception $e){
                                 return response()->json(['error'=>$e->getMessage()], 401); 
                            }
                        }
                        
						$amount = (int)$datauser->planamount;
                        $DateNext = $datauser->nextpaymentdate;
                        $plan_id =$datauser->stripe_plan_id;
                        //Add create subscription for to a create_subscription_table
                        $day = 0;
                        $CreatedDate = strtotime($datauser->nextpaymentdate);
                        $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                        $differStrTime = $CreatedDate - $CurrentDates;
                        if($differStrTime > 0) {
                            $day = ceil($differStrTime/(24*60*60));
                        }
                        if($day > 0) { //Create Subscription with trail
                            $subscription = $stripe->subscriptions()->create($datauser->customer_id, [
                                    'plan' => $plan_id,
                                    'trial_end' => strtotime( '+'.$day.' day' )
                                ]);
                        } else { //Create Subscription without trail
                            $subscription = $stripe->subscriptions()->create($datauser->customer_id, [
                                    'plan' => $plan_id
                            ]);
                        }
                        $subID = $subscription['id'];
                        $changeStatus = Companydetail::where('authid','=',$userid)->update(['subscriptiontype'=>$status,'subscription_id'=>$subID]);
                        if($changeStatus) {
                            return response()->json(['success' => true], $this->successStatus);
                        } else {
                            return response()->json(['error'=>'networkerror'], 401);    
                        }
                        /*
						$plan_id ='';
						if($amount == 199) {
							$plan_id ='plan_basic_monthly';
						} else if($amount == 299) {
							$plan_id ='plan_advance_monthly';
						} else if ($amount == 399) {
							$plan_id ='plan_pro_monthly';
						}
						if(empty($datauser->subscription_id)) {
                            return response()->json(['error'=>'Subscription ID not found.'], 401); 
                        }

                        try {
                            $subscription = $stripe->subscriptions()->cancel($datauser->customer_id, $datauser->subscription_id);
                        } catch(\Cartalyst\Stripe\Exception\NotFoundException $e){

                        } catch(Exception $e){
                             return response()->json(['error'=>$e->getMessage()], 401); 
                        } 
                        try {
                            
                            $DateNext = $datauser->nextpaymentdate;
                            $days = 0;
                            $IsDayLeft = false;
                            if($datauser->remaintrial > 0) {
                                if($datauser->lastpaymentdate == null ) {
                                    if($datauser->nextpaymentdate == null) {
                                        $days = 30;
                                        $IsDayLeft = true;
                                    } else {
                                        $CreatedDate = strtotime($datauser->nextpaymentdate);
                                        $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                                        $differStrTime = $CreatedDate - $CurrentDates;
                                        if($differStrTime > 0) {
                                            $day = ceil($differStrTime/(24*60*60));
                                            //if($day < 30) {
                                            $days = $day;
                                            $IsDayLeft = true;
                                            //}
                                        }
                                    }
                                } else {
                                    if($datauser->plansubtype == 'free') {
                                        $days = $datauser->remaintrial;
                                        $IsDayLeft = true;
                                    } else {
                                        $CreatedDate = strtotime($datauser->nextpaymentdate);
                                        $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                                        $differStrTime = $CreatedDate - $CurrentDates;
                                        if($differStrTime > 0) {
                                            $day = ceil($differStrTime/(24*60*60));
                                            if($day <= 30) {
                                                $days = $day;
                                                $IsDayLeft = true;
                                            }
                                        }
                                    }
                                }
                            } else {
                                $CreatedDate = strtotime($datauser->nextpaymentdate);
                                $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                                $differStrTime = $CreatedDate - $CurrentDates;
                                if($differStrTime > 0) {
                                    $day = ceil($differStrTime/(24*60*60));
                                    if($day <= 30) {
                                        $days = $day;
                                        $IsDayLeft = true;
                                    }
                                }
                            }
                            $amountPaidD = 0;
                            $remaindiscount = 1;
                            //echo $trial;
                            $dateD = date('2019-12-31 23:59:59');
                            $currentD = date('Y-m-d 00:00:00');
                            if($currentD < $dateD) {
                                $remaindiscount = $datauser->remaindiscount;
                                $discountapply = 50;
                                
                                //$amount = $amount;
                                $amountPaidD = ceil(($amount * $discountapply)/100);
                                if($remaindiscount > 0) {
                                    //$amountPaidD = true;
                                } else {
                                    $amountPaidD = 0;
                                }
                            }
                            if($IsDayLeft) {
                                //create Coupon 
                                $coupon = $stripe->coupons()->create([
                                    'duration'    => 'repeating',
                                    'amount_off' => $amountPaidD * 100,
                                    'currency'  => 'USD',
                                    'duration_in_months' => $remaindiscount
                                ]);
                                
                                $subscription = $stripe->subscriptions()->create($datauser->customer_id, [
                                    'plan' => $plan_id,
                                    'coupon' => $coupon['id'],
                                    'trial_end' => strtotime( '+'.$days.' day' )
                                ]);
                            } else {
                                $subscription = $stripe->subscriptions()->create($datauser->customer_id, [
                                    'plan' => $plan_id
                                ]);
                            }
                            $subID = $subscription['id'];
                            $changeStatus = Companydetail::where('authid','=',$userid)->update(['subscriptiontype'=>$status,'subscription_id'=>$subID]);
                            if($changeStatus) {
                                return response()->json(['success' => true], $this->successStatus);
                            } else {
                                return response()->json(['error'=>'networkerror'], 401);    
                            }
                        } catch(\Cartalyst\Stripe\Exception\NotFoundException $e){
                            return response()->json(['error'=>$e->getMessage()], 401); 
                        } catch(\Cartalyst\Stripe\Exception\InvalidRequestException $e) {
                            return response()->json(['error'=>$e->getMessage()], 401);                                
                        } catch(Exception $e){
                            return response()->json(['error'=>$e->getMessage()], 401); 
                        } */
					} else {
                        $subId = $datauser->subscription_id;
                        $customerId = $datauser->customer_id;
                        if(!empty($subId)) {
                            $updateArr = ['subscriptiontype'=>$status];
                            try {
                                $updateArr = ['subscriptiontype'=>$status,'subscription_id'=>NULL];
                                $subscription = $stripe->subscriptions()->cancel($customerId, $subId);
                            } catch(\Cartalyst\Stripe\Exception\NotFoundException $e){
                                $updateArr = ['subscriptiontype'=>$status,'subscription_id'=>NULL]; 
                            } catch(Exception $e){
                                return response()->json(['error'=>$e->getMessage()], 401); 
                            }
                            $changeStatus = Companydetail::where('authid','=',$userid)->update($updateArr);
                            if($changeStatus) {
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
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }


       // get list of yacht service requests
        public function getYachtServiceRequest(Request $Request) {
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $data = DB::select("SELECT  usr.id,usr.title,usr.description ,usr.services ,usr.created_at,usr.status,srr.rating,
                bn.name as businessname,srr.comment,srr.updated_at FROM users_service_requests as usr LEFT JOIN service_request_reviews as srr ON srr.toid = usr.authid AND srr.requestid = usr.id LEFT JOIN companydetails as bn ON  srr.fromid = bn.authid where usr.authid = $id AND usr.status != 'deleted' order by usr.id DESC");
                if(!empty($data)) {
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
                    $newService = [];
                    foreach ($data as $dkey => $dval) {
                        $service = json_decode($dval->services);
                        $newService = array();
                        foreach ($service as $catId => $SerIds) {
							if($catId == '11') {
								$newService[] = $SerIds;
							} else {
								foreach ($SerIds as $sid => $sval) {
									if(isset($newallservices[$sval]) && !in_array($newallservices[$sval], $newService)){
										$newService[] =  $newallservices[$sval];
									}
								}
							}
                        } 
                        $data[$dkey]->newservice = $newService; 
                        unset($data[$dkey]->services);    
                    }
                    return response()->json(['success' => true,'data' => $data], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);   
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

               // add yacht service request
        public function addYachtServiceRequest(Request $request) {
            $validate = Validator::make($request->all(), [
                'authid' => 'required',
                'title' => 'required',
                'description' => 'required',
                'country' => 'required',
                'state' => 'required',
                // 'county' => 'required',
                'city' => 'required',
                'zipcode' => 'required',
                'numberofleads' => 'required',
                'services' => 'required'
            ]);

            
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $authid = decrypt(request('authid'));
            $address = request('address');
            $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
            
            $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];
            $addspecialrequirement = request('addspecialrequirement');
            
            $serviceInsert = (array)json_decode(request('services'));
            $otherService = request('otherservice');
            if(!empty($otherService) && $otherService != '' && $otherService != null) {
				$serviceInsert[11][0] = $otherService;
			}
			$getNameServicearr = array();
			foreach ($serviceInsert as $skeyAll => $svalAll) {
				foreach ($svalAll as $skey => $sval) {
					if($skeyAll != '11') {
						$getNameServicearr[] = $sval;
					}
				}
			}
			$serviceNameGet = Service::whereIn('id', $getNameServicearr)->where('status','=','1')->get();
			$like = '';
			if(!empty($serviceNameGet) && count($serviceNameGet) > 0) {
				for($i = 0 ; $i < count($serviceNameGet);$i++){
					if($like == '') {
						$like .= "and service ILIKE '%".$serviceNameGet[$i]->service."%'";
					} else {
						$like.= " or service ILIKE '%".$serviceNameGet[$i]->service."%'";
					}
				}
			}
			$whereService = '';
			//$like .= ")'";
			if($like != '') {
				$getAllService = DB::select("SELECT s.id,s.service,s.category from services as s  where s.status = '1' ".$like."");
				if(!empty($getAllService) && count($getAllService) > 0) {
					foreach ($getAllService as $skey => $sval) {
						$jsonArr = [];
						$jsonArr[$sval->category] = [$sval->id];
						if($whereService != '' ) {
							$whereService = $whereService." OR services::jsonb @> '".json_encode($jsonArr)."'";
						} else {
							$whereService = "( services::jsonb @> '".json_encode($jsonArr)."'";
						}
					}
					if($whereService != '') {
						$whereService = $whereService ." )";
					}
				}
			}
            $servicesObj = json_encode($serviceInsert);
			if(!empty($authid) && (int)($authid)) {
                $servreq = new User_request_services;
                $servreq->title = request('title');
                $servreq->authid = $authid;
                $servreq->description = request('description');
                $servreq->addspecialrequirement = (!empty($addspecialrequirement)?$addspecialrequirement:NULL);
                $servreq->address = request('address');
                $servreq->longitude = $longitude;
                $servreq->latitude = $latitude;
                $servreq->country = request('country');
                $servreq->state = request('state');
                // $servreq->county = request('county');
                $servreq->city = request('city');
                $servreq->zipcode = request('zipcode');
                $servreq->numberofleads = (int)(request('numberofleads'));
                $servreq->services = $servicesObj;
                $servreq->status = 'posted';
                $servreq->optionalinfo = (!empty(request('optionalinfo'))?request('optionalinfo'):NULL);
                if($servreq->save()) {
                    $serviceRequestId = $servreq->id;
                    $yachtOwnerDetail =  DB::table('yachtdetail')
                        ->where('authid', '=', (int)$authid)
                        ->select('firstname','lastname')
                        ->first();
                    $notificationDate = date('Y-m-d H:i:s');
                    
                    //Get Business under 50 miles
                    // $calDis = '(((acos(sin(('.$latitude.'*pi()/180)) * sin((cd.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((cd.latitude *pi()/180)) * cos((('.$longitude.'- cd.longitude)*pi()/180))))*180/pi())*60*1.1515) <= 50';
                    $calDis = "2 * 3961 * asin(sqrt((sin(radians((cd.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(cd.latitude)) * (sin(radians((cd.longitude - ".$longitude.") / 2))) ^ 2)) <= 50";
                    $listOfBusinessInMilesQury = DB::table('companydetails as cd')->select('authid','name','email','contactmobile','contactemail','country_code')
                        ->leftJoin('auths as a','a.id','=','cd.authid')
                        ->where('cd.status','!=','deleted')
                        ->whereRaw($calDis);
                    if($whereService != '') {
						$listOfBusinessInMilesQury = $listOfBusinessInMilesQury->whereRaw($whereService);
					}
					$listOfBusinessInMiles = $listOfBusinessInMilesQury->where('cd.accounttype','!=','dummy')->get();
					if (!empty($listOfBusinessInMiles) && count($listOfBusinessInMiles) > 0) {
                        $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                        $link = $website_url.'/service-request/'.$serviceRequestId.'?cf=marine';
                        for ($i=0; $i < count($listOfBusinessInMiles); $i++) {
                            $emailArr = [];
                            $emailArr['firstname'] = $yachtOwnerDetail->firstname;
                            $emailArr['lastname'] = $yachtOwnerDetail->lastname;
                            $emailArr['link'] = $link;
                            $emailArr['to_email'] = $listOfBusinessInMiles[$i]->contactemail;
                            $mobilenumber = $listOfBusinessInMiles[$i]->country_code.$listOfBusinessInMiles[$i]->contactmobile;
                            //Send lead email notification to business
                            // $emailstatus = $this->sendEmailNotification();
                            SendNewLeadNotificationEmails::dispatch($emailArr,'lead_notification');
                            $adminEmailArr['userEmail'] = $emailArr['to_email'];
                            $adminEmailArr['link'] = $link;
                            $adminEmailArr['userType'] = 'Yacht Owner';
                            $adminEmailArr['userFirstname'] =  $emailArr['firstname'].' '. $emailArr['lastname'];
                            $adminEmailArr['to_email'] = env("Admin_Email");

                            SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_service_notification');
                            $adminEmailArr['to_email'] = env("Info_Email");
                            SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_service_notification');
                            $sms = 'A user has added a new Service Request on Marine Central. Please click '.$link.' to view this request.';
                            SendSmsToBusinesses::dispatch($sms,$mobilenumber,'service_request',$authid,$listOfBusinessInMiles[$i]->authid);
                            //Update notification tables
                            SaveNotifications::dispatch($listOfBusinessInMiles[$i]->authid,'company','You have a new lead request in your area.',NULL,NULL,$serviceRequestId,$notificationDate,null,0,'lead');
                            // $this->addNotification($listOfBusinessInMiles[$i]->authid,'company','You have a new lead request in your area.',NULL,NULL,$serviceRequestId,$notificationDate);
                        }
                        return response()->json(['success' => true], $this->successStatus);
                    } else {
                        return response()->json(['success' => true], $this->successStatus);    
                    }
                } else {
                    return response()->json(['error'=>'networkerror'], 401);    
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        // get vacancty posted by yacht
        public function getAllYachtVacanciesById(Request $request) {
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $jobs = Jobs::select('id as jobid','services','title','description','geolocation','salary','status','request_uniqueid','salarytype','created_at')->whereRaw('id IN(SELECT DISTINCT ON(request_uniqueid) id from jobs)')->where('authid',$id)->get();
                if(!empty($jobs) && count($jobs)) {
                    // $allservices = Service::where('status','=','1')->select('id', 'service as itemName')->get()->toArray();
                    // $newallservices = [];
                    // foreach ($allservices as $val) {
                    //     $newallservices[$val['id']] = $val['itemName'];
                    // }
                    // $allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
                    // $newallCategory = [];
                    // foreach ($allCategory as $val) {
                    //     $newallCategory[$val['id']] = $val['categoryname'];
                    // }
                    // foreach ($jobs as $jkey => $jval) {
                    //     $service = json_decode($jval->services);
                    //     $newService = [];
                    //     $temCateArr = [];
                    //     foreach ($service as $catId => $SerIds) {
                    //         foreach ($SerIds as $sid => $sval) {
                    //             if(isset($newallservices[$sval]) && !in_array($newallservices[$sval],$newService)) {
                    //                 $newService[] =  $newallservices[$sval];
                    //             }
                    //         }
                    //     }
                    //     $jobs[$jkey]->newservices = $newService;
                    //     unset($jobs[$jkey]->services);
                    // }
                    return response()->json(['success' => true,'data' => $jobs], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);    
                } 
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        public function addYachtVacancies(Request $request) {
            $validate = Validator::make($request->all(), [
                'authid' => 'required',
                'title' => 'required',
                'description' => 'required',
                'experience' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $skillSet = (!empty(request('skillset')) ? request('skillset') : NULL);
            $authid = decrypt(request('authid'));
            $request_uniqueid = 0;
            $insert_id = [];
            $jobs = new Jobs;
            $jobs->title = request('title');
            $jobs->authid = $authid;
            $jobs->description = request('description');
            $jobs->salary = request('salary');
            $jobs->skillset = $skillSet;
            if(!empty(request('salary'))) {
                $jobs->salarytype = request('salarytype');
            } else {
                $jobs->salarytype = null;
            }
            $jobs->jobtitleid = request('jobtitle');
            $jobs->salarytype = request('salarytype');
            $jobs->experience = request('experience');
            $jobs->addedby = 'yacht';
            $jobs->status = 'active';
            $jobs->request_uniqueid = $request_uniqueid;
            if($jobs->save()) {
                if($request_uniqueid == 0) {
                    $request_uniqueid = $jobs->id;
                    $add_request_uniqueid = Jobs::where('id',$request_uniqueid)->update(['request_uniqueid' => $request_uniqueid]);
                }
                $jobsId = $jobs->id;
                $companydetails =  DB::table('yachtdetail')
                    ->where('authid', '=', (int)$authid)
                    ->select('firstname','lastname','longitude','latitude')
                    ->first();
                $miles = 50;
                if(!empty($companydetails)) {
                    $latitude = $companydetails->latitude;
                    $longitude = $companydetails->longitude;
                    // $county = $companydetails->county;
                    //Get Business under 50 miles
                    // $calDis = '(((acos(sin(('.$latitude.'*pi()/180)) * sin((td.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((td.latitude *pi()/180)) * cos((('.$longitude.'- td.longitude)*pi()/180))))*180/pi())*60*1.1515) <= 50';
                    $calDis = "2 * 3961 * asin(sqrt((sin(radians((td.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(td.latitude)) * (sin(radians((td.longitude - ".$longitude.") / 2))) ^ 2)) <= 50 AND text_notification = '1' AND (jobtitleid = ".request('jobtitle')." OR text_notification_other = '1')";
                    $listOfProfessionalInMiles = DB::table('talentdetails as td')->select('authid','firstname','lastname','email','mobile as contactmobile','country_code','jobtitleid')
                        ->leftJoin('auths as a','a.id','=','td.authid')
                        ->whereRaw($calDis)
                        ->get();
                        $notificationDate = date('Y-m-d H:i:s');
                    if (!empty($listOfProfessionalInMiles) && count($listOfProfessionalInMiles) > 0) {
                        $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                        $link = $website_url.'/job-detail/'.$jobsId.'?cf=marine';
                        $emailArr = [];
                        $emailArr['name'] = $companydetails->firstname.' '.$companydetails->lastname;
                        $emailArr['link'] = $link;
                        for ($i=0; $i < count($listOfProfessionalInMiles); $i++) {
                            $mobilenumber = $listOfProfessionalInMiles[$i]->country_code.$listOfProfessionalInMiles[$i]->contactmobile;
                            $emailArr['to_email'] = $listOfProfessionalInMiles[$i]->email;
                            //Dispatch Email Job
                            SendNewLeadNotificationEmails::dispatch($emailArr,'job_notification');
                            //Dispatch SMS job
                            $sms = $emailArr['name'].' has added a new job. Click '.$link.' to view job details.';
                            SendSmsToBusinesses::dispatch($sms,$mobilenumber,'job_notification',$authid,$listOfProfessionalInMiles[$i]->authid);
                            //Dispatch Add Notification Job

                            // SaveNotifications::dispatch($listOfProfessionalInMiles[$i]->authid,'professional',NULL,NULL,'New vacancy is available in your county.',$jobsId,$notificationDate,null,0,'job');
                            $this->addNotification($listOfProfessionalInMiles[$i]->authid,'professional',NULL,NULL,'New vacancy is available in your area.',$jobsId,$notificationDate,null,0,'job');
                        }
                    }
                }
                return response()->json(['success' => true,'data' => $insert_id], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }

        // for boat owner
        //Boat ower detail page (userdetails table)
        public function getBoatOwnerDetailsById(Request $request) {
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $data = DB::table('userdetails as ud')
                        ->select('ud.authid','ud.firstname','ud.lastname','ud.country_code','ud.profile_image','ud.created_at','ud.latitude','ud.longitude','ud.address','ud.state','ud.city','ud.zipcode','ud.mobile','ud.country','ud.coverphoto','a.email','a.provider',DB::Raw('coalesce( r.totalrating , 0 ) as totalrating,coalesce( r.totalreviewed , 0 ) as totalreviewed'))
                        ->Join('auths as a','a.id','=','ud.authid')
                        ->leftJoin('reviewsview as r','r.toid','=','ud.authid')
                        ->where('ud.status','active')
                        ->where('ud.authid',$id)
                        ->get();
                if(!empty($data)) {
                    if(isset($data[0])) {
                        $authid = $data[0]->authid;
                        $jobs = DB::table('users_service_requests')->select(DB::Raw('id,title,services,description,created_at'))
                        ->where('authid',$authid)
                        ->orderBy('created_at','DESC')->get();
                        $data[0]->service_requests = $jobs;
                        $latestReview = DB::table('service_request_reviews as sr')
                        ->select('cd.name','cd.primaryimage','sr.rating','sr.subject','sr.comment','sr.created_at')
                        ->Join('companydetails as cd','cd.authid','=','sr.fromid')
                        ->where('isdeleted','!=','1')
                        ->where('toid','=',$authid)
                        ->orderBy('sr.created_at','DESC')->get();
                        $data[0]->latestReview = $latestReview;

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
                        foreach ($data[0]->service_requests as $skey => $val) {
                            $service = json_decode($val->services);
                            $newService = [];
                            $temCateArr = [];
                            foreach ($service as $catId => $SerIds) {
                                foreach ($SerIds as $sid => $sval) {
                                    if(isset($newallservices[$sval]) && !in_array($newallservices[$sval], $newService)) {
                                        $newService[] =  $newallservices[$sval];
                                    }
                                }
                            }
                            $data[0]->service_requests[$skey]->newservice = $newService;
                        }
                        return response()->json(['success' => true,'data' => $data], $this->successStatus);
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
        // to show name in header
        public function getBoatOwnerProfileById(Request $request) {
            $id = decrypt(request('id'));
            $data = DB::table('userdetails as ur')
				->Join('auths as au','au.id','=','ur.authid')
                ->where('ur.authid', '=', (int)$id)
                ->select('ur.profile_image','ur.coverphoto','au.is_social',DB::raw("CONCAT(ur.firstname,' ',ur.lastname) AS name"))
                ->first();
            if(!empty($data)) {
                return response()->json(['success' => 'success','data' => $data], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }

        public function updateBoatOwnerProfile(Request $request) {
            $validate = Validator::make($request->all(), [
                'firstname' => 'required',
                'lastname' => 'required',
                'id' => 'required',
                'city' => 'required',
                'state' => 'required',
                'country' => 'required',
                // 'county' => 'required',
                'zipcode' => 'required',
                'mobile' => 'required',
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $userid = request('id');
            $decryptUserid = decrypt($userid);
            if(empty($decryptUserid) || $decryptUserid == '') {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            $auth   = array(); 
            $address = request('address');
            $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
            
            $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];

            if(!empty(request('images'))) {
                $BoatOwnerImage = request('images');
            }
            if(!empty(request('mobile'))) {
                $mobile = request('mobile');
            }
            // $userdetail = new Userdetail;
            $userdetail['authid']  = $decryptUserid;
            $userdetail['firstname']  = request('firstname');
            $userdetail['lastname']   = request('lastname');
            $userdetail['address']    = ((isset($address) && $address !='') ? request('address'): NULL);
            $userdetail['city']       = request('city');
            $userdetail['state']      = request('state');
            $userdetail['profile_image'] =   ((isset($BoatOwnerImage) && $BoatOwnerImage !='') ? $BoatOwnerImage: NULL);
            $userdetail['country']    = request('country');
            // $userdetail['county']    = request('county');
            $userdetail['zipcode']    = request('zipcode');
            $userdetail['mobile']    = $mobile;
            $userdetail['longitude']  = $longitude;
            $userdetail['latitude']   = $latitude;
            $country_code = request('country_code');
			if($country_code != '') {
				$pos = strpos($country_code, '+');
				if(!$pos){
					$country_code ='+'.$country_code;
				}
			} 
			$userdetail['country_code'] = $country_code;
                   
            $update = DB::table('userdetails')->where('authid','=',$decryptUserid)->update($userdetail);
            if($update) {
				$zaiperenv = env('ZAIPER_ENV','local');
				if($zaiperenv == 'live') {
					$this->sendAccountCreateZapierbyID($decryptUserid,'regular');
				}
                return response()->json(['success' => 'success'], $this->successStatus);
            } else {
                //echo "string33";
                return response()->json(['error'=>'networkerror'], 401);  
            }   
        }

        public function changeBoatOwnerPassword(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'password' => 'required',
                'confirm' => 'required|same:password',
                'oldpassword' => 'required',
            ]);
       
            if ($validate->fails()) {
               return response()->json(['error'=>'validationError'], 401); 
            }
            $userid = request('id');
            $authid = decrypt($userid);
            $auth   = array(); 
            $updated = 0;
            $oldpassword =request('oldpassword');
            $userDetail =  DB::table('auths')->where('id', '=', (int)$authid)->where('usertype', '=', 'regular')->where('status', '!=', 'deleted')->first();
            if(!empty($userDetail)) {
                if(!Hash::check($oldpassword,$userDetail->password)) {
                    return response()->json(['error'=>'notmatch'], 401);
                } else {
                    $auth['password'] =Hash::make(request('password'));
                    if(!empty($authid) && $authid > 0) {
                        $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'regular')->where('status', '!=', 'deleted')->update($auth);
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

       // get list of boat owner service requests
        public function getBoatOwnerServiceRequest(Request $Request) {
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $data = DB::select("SELECT  usr.id,usr.title,usr.description ,usr.services ,usr.created_at,usr.status,srr.rating,
                bn.name as businessname,srr.comment,srr.updated_at FROM users_service_requests as usr LEFT JOIN service_request_reviews as srr ON srr.toid = usr.authid AND srr.requestid = usr.id LEFT JOIN companydetails as bn ON  srr.fromid = bn.authid where usr.authid = $id AND usr.status != 'deleted' order by usr.id DESC");
                if(!empty($data)) {
                    $allservices = Service::where('status','=','1')->select('id', 'service as itemName')->where('category','6')->get()->toArray();
                    $newallservices = [];
                    foreach ($allservices as $val) {
                        $newallservices[$val['id']] = $val['itemName'];
                    }
                     $allCategory = Category::select('id','categoryname')->where('status','=','1')->where('categoryname','Service & Repair')->get()->toArray();
                    $newallCategory = [];
                    foreach ($allCategory as $val) {
                        $newallCategory[$val['id']] = $val['categoryname'];
                    }
                    $newService = [];
                    $temCateArr = [];
                    $newService = [];
                    foreach ($data as $dkey => $dval) {
                        $service = json_decode($dval->services);
                        $newService = array();
                        foreach ($service as $catId => $SerIds) {
							if($catId == '11') {
								$newService[] = $SerIds;
							} else {
								foreach ($SerIds as $sid => $sval) {
									if(isset($newallservices[$sval]) && !in_array($newallservices[$sval], $newService)){
										$newService[] =  $newallservices[$sval];
									}
								}
							}
                        } 
                        $data[$dkey]->newservice = $newService; 
                        unset($data[$dkey]->services);    
                    }
                    return response()->json(['success' => true,'data' => $data], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);   
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

         // add boat owner service request
        public function addBoatOwnerServiceRequest(Request $request) {
            $validate = Validator::make($request->all(), [
                'authid' => 'required',
                'title' => 'required',
                'description' => 'required',
                'country' => 'required',
                'state' => 'required',
                // 'county' => 'required',
                'city' => 'required',
                'zipcode' => 'required',
                'numberofleads' => 'required',
                'services' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $authid = decrypt(request('authid'));
            $address = request('address');
            $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
            
            $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];
            $addspecialrequirement = request('addspecialrequirement');
            
            $serviceInsert = (array)json_decode(request('services'));
            $otherService = request('otherservice');
            if(!empty($otherService) && $otherService != '' && $otherService != null) {
				$serviceInsert[11][0] = $otherService;
			}
			$getNameServicearr = array();
			foreach ($serviceInsert as $skeyAll => $svalAll) {
				foreach ($svalAll as $skey => $sval) {
					if($skeyAll != '11') {
						$getNameServicearr[] = $sval;
					}
				}
			}
			$serviceNameGet = Service::whereIn('id', $getNameServicearr)->where('status','=','1')->get();
			$like = '';
			if(!empty($serviceNameGet) && count($serviceNameGet) > 0) {
				for($i = 0 ; $i < count($serviceNameGet);$i++){
					if($like == '') {
						$like .= "and service ILIKE '%".$serviceNameGet[$i]->service."%'";
					} else {
						$like.= " or service ILIKE '%".$serviceNameGet[$i]->service."%'";
					}
				}
			}
			$whereService = '';
			//$like .= ")'";
			if($like != '') {
				$getAllService = DB::select("SELECT s.id,s.service,s.category from services as s  where s.status = '1' ".$like."");
				if(!empty($getAllService) && count($getAllService) > 0) {
					foreach ($getAllService as $skey => $sval) {
						$jsonArr = [];
						$jsonArr[$sval->category] = [$sval->id];
						if($whereService != '' ) {
							$whereService = $whereService." OR services::jsonb @> '".json_encode($jsonArr)."'";
						} else {
							$whereService = "( services::jsonb @> '".json_encode($jsonArr)."'";
						}
					}
					if($whereService != '') {
						$whereService = $whereService ." )";
					}
				}
			}
            $servicesObj = json_encode($serviceInsert);
			if(!empty($authid) && (int)($authid)) {
                $servreq = new User_request_services;
                $servreq->title = request('title');
                $servreq->authid = $authid;
                //otherservice
                $servreq->description = request('description');
                $servreq->addspecialrequirement = (!empty($addspecialrequirement)?$addspecialrequirement:NULL);
                $servreq->address = request('address');
                $servreq->longitude = $longitude;
                $servreq->latitude = $latitude;
                $servreq->country = request('country');
                $servreq->state = request('state');
                // $servreq->county = request('county');
                $servreq->city = request('city');
                $servreq->zipcode = request('zipcode');
                $servreq->numberofleads = (int)(request('numberofleads'));
                $servreq->services = $servicesObj;
                $servreq->status = 'posted';
                $servreq->optionalinfo = (!empty(request('optionalinfo'))?request('optionalinfo'):NULL);
                if($servreq->save()) {
                    $serviceRequestId = $servreq->id;
                    $boatOwnerDetail =  DB::table('userdetails')
                        ->leftJoin('auths', 'auths.id','userdetails.authid')
                        ->where('authid', '=', (int)$authid)
                        ->select('firstname','lastname', 'email')
                        ->first();
                    $miles = 50;
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    $link = $website_url.'/service-request/'.$serviceRequestId.'?cf=marine';
                    $adminEmailArr['userEmail'] = $boatOwnerDetail->email;
                    $adminEmailArr['link'] = $link;
                    $adminEmailArr['userType'] = 'Boat Owner';
                    $adminEmailArr['userFirstname'] = $boatOwnerDetail->firstname.' '. $boatOwnerDetail->lastname;
                    $adminEmailArr['to_email'] = env("Admin_Email");
                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_service_notification');
                    $adminEmailArr['to_email'] = env("Info_Email");
                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_service_notification');

                    //Get Business under 50 miles
                    // $calDis = '(((acos(sin(('.$latitude.'*pi()/180)) * sin((cd.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((cd.latitude *pi()/180)) * cos((('.$longitude.'- cd.longitude)*pi()/180))))*180/pi())*60*1.1515) <= '.$miles.'';
                    $calDis = "2 * 3961 * asin(sqrt((sin(radians((cd.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(cd.latitude)) * (sin(radians((cd.longitude - ".$longitude.") / 2))) ^ 2)) <= ".$miles."";
                    $listOfBusinessInMilesQury = DB::table('companydetails as cd')->select('authid','name','email','contactmobile','contactemail','country_code')
                        ->leftJoin('auths as a','a.id','=','cd.authid')
                        ->where('cd.status','!=','deleted')
                        ->whereRaw($calDis);
                    if($whereService != '') {
						$listOfBusinessInMilesQury = $listOfBusinessInMilesQury->whereRaw($whereService);
					}
					$listOfBusinessInMiles = $listOfBusinessInMilesQury->where('cd.accounttype','!=','dummy')->get();
                    $notificationDate = date('Y-m-d H:i:s');
                   // echo "<pre>";print_r($listOfBusinessInMiles);die;
                    if (!empty($listOfBusinessInMiles) && count($listOfBusinessInMiles) > 0) {
                        $emailArr = [];
                        $emailArr['firstname'] = $boatOwnerDetail->firstname;
                        $emailArr['lastname'] = $boatOwnerDetail->lastname;
                        $emailArr['link'] = $link;
                        for ($i=0; $i < count($listOfBusinessInMiles); $i++) {
							$mobilenumber = $listOfBusinessInMiles[$i]->country_code.$listOfBusinessInMiles[$i]->contactmobile;
                            $emailArr['to_email'] = $listOfBusinessInMiles[$i]->contactemail;
                            //Dispatch Email Job
                            SendNewLeadNotificationEmails::dispatch($emailArr,'lead_notification');
                            //Dispatch SMS job
                            $sms = 'A user has added a new Service Request on Marine Central. Please click '.$link.' to view this request.';
                            SendSmsToBusinesses::dispatch($sms,$mobilenumber,'service_request',$authid,$listOfBusinessInMiles[$i]->authid);
                            //Dispatch Add Notification Job
                            SaveNotifications::dispatch($listOfBusinessInMiles[$i]->authid,'company','You have a new lead request in your area.',NULL,NULL,$serviceRequestId,$notificationDate,null,0,'lead');
                        }
                        return response()->json(['success' => true], $this->successStatus);
                    } else {
                        return response()->json(['success' => true], $this->successStatus);     
                    }
                } else {
                    return response()->json(['error'=>'networkerror'], 401);    
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

    public function addBoatSlipReq(Request $request){
       $validate = Validator::make($request->all(), [
                'authid'=>'required',
              'authType'=>'required',
              'title'=> 'required',
              'description'=> 'required',
              'boatCountry'=> 'required',
              'boatState'=> 'required',
              'boatCity'=> 'required',
              'boatZipcode'=>'required',
              'startDate' => 'required',
               'endDate'=> 'required',
              'boatName'=> 'required',
              'lengthbeam'=>'required',
              'lengthboat' => 'required',
               'lengthdraft' => 'required',
               'metricboatgroup'=> 'required',
              'metricbeamgroup'=> 'required',
              'metricdraftgroup'=>'required',
              'powerType' => 'required',
        ]);
       $authid = decrypt(request('authid'));
       $authType = request('authType');
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        if($authid){
            $locAddress = ((isset($address) && $address !='') ? request('boatAddress').' ': '');
            
             $location = $locAddress.request('boatCity').' '.request('boatZipcode').' '.request('boatState').' ,'.request('boatCountry');
             $output = $this->getGeoLocation($location); //Get Location from location Trait
             $longitude = $output['longitude'];
             $latitude = $output['latitude'];
             // $category_Id = env("Boat_Slip_Category_id");
             // $service_Id = env("Boat_Slip_Service_id");
             $serviceInsert = ["6" => [325]];
            
            $getNameServicearr = array();
            foreach ($serviceInsert as $skeyAll => $svalAll) {
                foreach ($svalAll as $skey => $sval) {
                    if($skeyAll != '11') {
                        $getNameServicearr[] = $sval;
                    }
                }
            }
            $serviceNameGet = Service::whereIn('id', $getNameServicearr)->where('status','=','1')->get();
            $like = '';
            if(!empty($serviceNameGet) && count($serviceNameGet) > 0) {
                for($i = 0 ; $i < count($serviceNameGet);$i++){
                    if($like == '') {
                        $like .= "and service ILIKE '%".$serviceNameGet[$i]->service."%'";
                    } else {
                        $like.= " or service ILIKE '%".$serviceNameGet[$i]->service."%'";
                    }
                }
            }
            $whereService = '';
            //$like .= ")'";
            if($like != '') {
                $getAllService = DB::select("SELECT s.id,s.service,s.category from services as s  where s.status = '1' ".$like."");
                if(!empty($getAllService) && count($getAllService) > 0) {
                    foreach ($getAllService as $skey => $sval) {
                        $jsonArr = [];
                        $jsonArr[$sval->category] = [$sval->id];
                        if($whereService != '' ) {
                            $whereService = $whereService." OR services::jsonb @> '".json_encode($jsonArr)."'";
                        } else {
                            $whereService = "( services::jsonb @> '".json_encode($jsonArr)."'";
                        }
                    }
                    if($whereService != '') {
                        $whereService = $whereService ." )";
                    }
                }
            }
            $servicesObj = json_encode($serviceInsert);
            if(!empty($authid) && (int)($authid)) {
                $servreq = new User_request_services;
                $servreq->authid = $authid;
                $servreq->services = $servicesObj;
                $servreq->request_type = 'boat_slip_request';
                 $servreq->title = request('title');
                $servreq->description = request('description');
                $servreq->addspecialrequirement = request('optionalinfo');
                $servreq->country = request('boatCountry');
                $servreq->city = request('boatCity');
                $servreq->state = request('boatState');
                $servreq->zipcode = request('boatZipcode');
                 $servreq->address = request('boatAddress');
                $servreq->startDate = request('startDate');
                $servreq->endDate = request('endDate');
                $servreq->boatName = request('boatName');
                $servreq->lengthbeam = request('lengthbeam');
                $servreq->lengthboat = request('lengthboat');
                $servreq->lengthdraft = request('lengthdraft');
                $servreq->metricboatgroup = request('metricboatgroup');
                $servreq->metricbeamgroup = request('metricbeamgroup');
                $servreq->metricdraftgroup = request('metricdraftgroup');
                $servreq->powerType = request('powerType');
                $servreq->longitude = $longitude;
                $servreq->latitude = $latitude;
                $servreq->status = 'posted';
                if($servreq->save()) {
                    if($authType == 'regular') $tablename = 'userdetails';
                    else $tablename = 'yachtdetail';
                    $serviceRequestId = $servreq->id;
                    if($tablename == 'userdetails'){
                        $boatOwnerDetail =  DB::table('userdetails')
                        ->leftJoin('auths', 'auths.id','userdetails.authid')
                        ->where('authid', '=', (int)$authid)
                        ->select('firstname','lastname', 'email')
                        ->first();    
                    } else {
                         $boatOwnerDetail =  DB::table('yachtdetail')
                        ->leftJoin('auths', 'auths.id','yachtdetail.authid')
                        ->where('authid', '=', (int)$authid)
                        ->select('firstname','lastname', 'email')
                        ->first(); 
                    }
                        
                    $miles = 50;
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    $link = $website_url.'/service-request/'.$serviceRequestId.'?cf=marine';
                    $adminEmailArr['userEmail'] = $boatOwnerDetail->email;
                    $adminEmailArr['link'] = $link;
                    $adminEmailArr['userType'] = 'Boat Owner';
                    $adminEmailArr['userFirstname'] = $boatOwnerDetail->firstname.' '. $boatOwnerDetail->lastname;
                    $adminEmailArr['to_email'] = env("Admin_Email");
                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_service_notification');
                    $adminEmailArr['to_email'] = env("Info_Email");
                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_service_notification');
                    //Get Business under 50 miles
                    // $calDis = '(((acos(sin(('.$latitude.'*pi()/180)) * sin((cd.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((cd.latitude *pi()/180)) * cos((('.$longitude.'- cd.longitude)*pi()/180))))*180/pi())*60*1.1515) <= '.$miles.'';
                    $calDis = "2 * 3961 * asin(sqrt((sin(radians((cd.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(cd.latitude)) * (sin(radians((cd.longitude - ".$longitude.") / 2))) ^ 2)) <= ".$miles."";
                    $listOfBusinessInMilesQury = DB::table('companydetails as cd')->select('authid','name','email','contactmobile','contactemail','country_code')
                        ->leftJoin('auths as a','a.id','=','cd.authid')
                        ->where('cd.status','!=','deleted')
                        ->whereRaw($calDis);
                    if($whereService != '') {
                        $listOfBusinessInMilesQury = $listOfBusinessInMilesQury->whereRaw($whereService);
                    }
                    $listOfBusinessInMiles = $listOfBusinessInMilesQury->where('cd.accounttype','!=','dummy')->get();
                    $notificationDate = date('Y-m-d H:i:s');
                   // echo "<pre>";print_r($listOfBusinessInMiles);die;
                    if (!empty($listOfBusinessInMiles) && count($listOfBusinessInMiles) > 0) {
                        $emailArr = [];
                        $emailArr['firstname'] = $boatOwnerDetail->firstname;
                        $emailArr['lastname'] = $boatOwnerDetail->lastname;
                        $emailArr['link'] = $link;
                        for ($i=0; $i < count($listOfBusinessInMiles); $i++) {
                            $mobilenumber = $listOfBusinessInMiles[$i]->country_code.$listOfBusinessInMiles[$i]->contactmobile;
                            $emailArr['to_email'] = $listOfBusinessInMiles[$i]->contactemail;
                            //Dispatch Email Job
                            SendNewLeadNotificationEmails::dispatch($emailArr,'lead_notification');
                            //Dispatch SMS job
                            $sms = 'A user has added a new Service Request on Marine Central. Please click '.$link.' to view this request.';
                            SendSmsToBusinesses::dispatch($sms,$mobilenumber,'service_request',$authid,$listOfBusinessInMiles[$i]->authid);
                            //Dispatch Add Notification Job
                            SaveNotifications::dispatch($listOfBusinessInMiles[$i]->authid,'company','You have a new lead request in your area.',NULL,NULL,$serviceRequestId,$notificationDate,null,0,'lead');
                            return response()->json(['success' => true,'userid' => encrypt($authid)], $this->successStatus);
                        }
                    } else {
                        return response()->json(['success' => true,'userid' => encrypt($authid)], $this->successStatus);
                    }    
                    return response()->json(['success' => true,'userid' => encrypt($authid)], $this->successStatus);
                } else {
                    return response()->json(['success' => true, 'userid' => encrypt($authid)], $this->successStatus);     
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }



    public function addFishCharterReq(Request $request){
       $validate = Validator::make($request->all(), [
              'authid'=>'required',
              'authType'=>'required',
              'title'=> 'required',
              'country'=> 'required',
              'state'=> 'required',
              'city'=> 'required',
              'zipcode'=>'required',
              'description'=> 'required',
              'charterDays'=> 'required',
              'totalPeople'=> 'required',
              'charterType'=>'required',

        ]);
        $authid = decrypt(request('authid'));
        $authType = request('authType');
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        if($authid){
            $serviceInsert = ["10" => [437]];
            $getNameServicearr = array();
            foreach ($serviceInsert as $skeyAll => $svalAll) {
                foreach ($svalAll as $skey => $sval) {
                    if($skeyAll != '11') {
                        $getNameServicearr[] = $sval;
                    }
                }
            }
            $serviceNameGet = Service::whereIn('id', $getNameServicearr)->where('status','=','1')->get();
            $like = '';
            if(!empty($serviceNameGet) && count($serviceNameGet) > 0) {
                for($i = 0 ; $i < count($serviceNameGet);$i++){
                    if($like == '') {
                        $like .= "and service ILIKE '%".$serviceNameGet[$i]->service."%'";
                    } else {
                        $like.= " or service ILIKE '%".$serviceNameGet[$i]->service."%'";
                    }
                }
            }
            $whereService = '';
            //$like .= ")'";
            if($like != '') {
                $getAllService = DB::select("SELECT s.id,s.service,s.category from services as s  where s.status = '1' ".$like."");
                if(!empty($getAllService) && count($getAllService) > 0) {
                    foreach ($getAllService as $skey => $sval) {
                        $jsonArr = [];
                        $jsonArr[$sval->category] = [$sval->id];
                        if($whereService != '' ) {
                            $whereService = $whereService." OR services::jsonb @> '".json_encode($jsonArr)."'";
                        } else {
                            $whereService = "( services::jsonb @> '".json_encode($jsonArr)."'";
                        }
                    }
                    if($whereService != '') {
                        $whereService = $whereService ." )";
                    }
                }
            }
            $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
            $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];
            $servicesObj = json_encode($serviceInsert);
            $servreq = new User_request_services;
            $servreq->authid = $authid;
            $servreq->services = $servicesObj;
            $servreq->request_type = 'fish_charter_request';
            $servreq->country = request('country');
            $servreq->city = request('city');
            $servreq->state = request('state');
            $servreq->zipcode = request('zipcode');
            $servreq->address = request('address');
            $servreq->charterDays = request('charterDays');
            $servreq->title = request('title');
            $servreq->description = request('description');
            $servreq->addspecialrequirement = request('optionalinfo');
            $servreq->charterType = ucfirst(request('charterType'));
            $servreq->totalPeople = request('totalPeople');
            $servreq->status = 'posted';
            if($servreq->save()) {
                if($authType == 'regular') $tablename = 'userdetails';
                else $tablename = 'yachtdetail';
                $serviceRequestId = $servreq->id;
                if($tablename == 'userdetails'){
                    $boatOwnerDetail =  DB::table('userdetails')
                    ->leftJoin('auths', 'auths.id','userdetails.authid')
                    ->where('authid', '=', (int)$authid)
                    ->select('firstname','lastname', 'email')
                    ->first();    
                } else {
                     $boatOwnerDetail =  DB::table('yachtdetail')
                    ->leftJoin('auths', 'auths.id','yachtdetail.authid')
                    ->where('authid', '=', (int)$authid)
                    ->select('firstname','lastname', 'email')
                    ->first(); 
                }
                    
                $miles = 50;
                $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                $link = $website_url.'/service-request/'.$serviceRequestId.'?cf=marine';
                $adminEmailArr['userEmail'] = $boatOwnerDetail->email;
                $adminEmailArr['link'] = $link;
                $adminEmailArr['userType'] = 'Boat Owner';
                $adminEmailArr['userFirstname'] = $boatOwnerDetail->firstname.' '. $boatOwnerDetail->lastname;
                $adminEmailArr['to_email'] = env("Admin_Email");
                SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_service_notification');
                $adminEmailArr['to_email'] = env("Info_Email");
                SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_service_notification');
                //Get Business under 50 miles
                // $calDis = '(((acos(sin(('.$latitude.'*pi()/180)) * sin((cd.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((cd.latitude *pi()/180)) * cos((('.$longitude.'- cd.longitude)*pi()/180))))*180/pi())*60*1.1515) <= '.$miles.'';
                $calDis = "2 * 3961 * asin(sqrt((sin(radians((cd.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(cd.latitude)) * (sin(radians((cd.longitude - ".$longitude.") / 2))) ^ 2)) <= ".$miles."";
                $listOfBusinessInMilesQury = DB::table('companydetails as cd')->select('authid','name','email','contactmobile','contactemail','country_code')
                    ->leftJoin('auths as a','a.id','=','cd.authid')
                    ->where('cd.status','!=','deleted')
                    ->whereRaw($calDis);
                if($whereService != '') {
                    $listOfBusinessInMilesQury = $listOfBusinessInMilesQury->whereRaw($whereService);
                }
                $listOfBusinessInMiles = $listOfBusinessInMilesQury->where('cd.accounttype','!=','dummy')->get();
                $notificationDate = date('Y-m-d H:i:s');
               // echo "<pre>";print_r($listOfBusinessInMiles);die;
                if (!empty($listOfBusinessInMiles) && count($listOfBusinessInMiles) > 0) {
                    $emailArr = [];
                    $emailArr['firstname'] = $boatOwnerDetail->firstname;
                    $emailArr['lastname'] = $boatOwnerDetail->lastname;
                    $emailArr['link'] = $link;
                    for ($i=0; $i < count($listOfBusinessInMiles); $i++) {
                        $mobilenumber = $listOfBusinessInMiles[$i]->country_code.$listOfBusinessInMiles[$i]->contactmobile;
                        $emailArr['to_email'] = $listOfBusinessInMiles[$i]->contactemail;
                        //Dispatch Email Job
                        SendNewLeadNotificationEmails::dispatch($emailArr,'lead_notification');
                        //Dispatch SMS job
                        $sms = 'A user has added a new Service Request on Marine Central. Please click '.$link.' to view this request.';
                        SendSmsToBusinesses::dispatch($sms,$mobilenumber,'service_request',$authid,$listOfBusinessInMiles[$i]->authid);
                        //Dispatch Add Notification Job
                        SaveNotifications::dispatch($listOfBusinessInMiles[$i]->authid,'company','You have a new lead request in your area.',NULL,NULL,$serviceRequestId,$notificationDate,null,0,'lead');
                        return response()->json(['success' => true,'userid' => encrypt($authid)], $this->successStatus);
                    }
                } else {
                    return response()->json(['success' => true,'userid' => encrypt($authid)], $this->successStatus);
                }    
                return response()->json(['success' => true,'userid' => encrypt($authid)], $this->successStatus);
            } else {
                return response()->json(['success' => true, 'userid' => encrypt($authid)], $this->successStatus);     
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }


        // add s3 images for boat owner to change cover photo //
        public function addS3Imagesboatowner(Request $request) {
            $decrypt = decrypt(request('id'));
            $authid = $decrypt;
            $s3images = request('images');
            $detailArr =[];
            $detailArr['coverphoto'] =  $s3images;
            $detailUpdate =  Userdetail::where('authid', '=', (int)$authid)->update($detailArr);
            if($detailUpdate) {
                $usersdata = DB::table('userdetails')
                ->where('authid', '=', (int)$authid)
                ->first();
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }

        // change profile image for boat owner
        public function addS3ImagesboatownerProfile(Request $request) {
            $decrypt = decrypt(request('id'));
            $authid = $decrypt;
            $s3images = request('images');
            $detailArr =[];
            $detailArr['profile_image'] =  $s3images;
            $detailUpdate =  Userdetail::where('authid', '=', (int)$authid)->update($detailArr);
            if($detailUpdate) {
                $usersdata = DB::table('userdetails')
                ->where('authid', '=', (int)$authid)
                ->first();
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }   
        }

        public function addS3ProfileImageYacht(Request $request) {
            $authid = request('id');
            if(!empty($authid)) {
                $authid  = decrypt($authid);
                if(!(int)$authid) {
                  return response()->json(['error'=>'networkerror'], 401);  
                }
                $CompanyImage = request('images');
                $usersdata = DB::table('yachtdetail')
                ->where('authid', '=', (int)$authid)
                ->first();
                if(!empty($usersdata->images)) {
                    $companyAllImages = json_decode($usersdata->images,true);
                } else {
                    $companyAllImages = [];
                }

                $imagesArr = [];
                $imageCount = 0;
                if(!empty($companyAllImages) && count($companyAllImages) > 0) {
                    for($i=0;$i< count($companyAllImages);$i++){
                        if(isset($companyAllImages[$i]['primary']) && $companyAllImages[$i]['primary'] == '0') {
                            $imagesArr[$imageCount]['image'] = $companyAllImages[$i]['image'];
                            $imagesArr[$imageCount]['primary'] = 0;
                            $imageCount++;
                        }
                    }
                    $imageArrSize = count($imagesArr);
                    if(isset($CompanyImage) && $CompanyImage != '') {
                        $imagesArr[$imageArrSize]['image'] = $CompanyImage;
                        $imagesArr[$imageArrSize]['primary'] = 1;
                    }
                    $imagesObj =  json_encode($imagesArr,JSON_UNESCAPED_SLASHES);
                    // echo "<pre>";print_r($imagesArr);
                    // echo $imagesObj;die;
                } else {
                    if(isset($CompanyImage) && $CompanyImage != '') {
                        $imagesArr[0]['image'] = $CompanyImage;
                        $imagesArr[0]['primary'] = 1;
                        $imagesObj = json_encode($imagesArr,JSON_UNESCAPED_SLASHES);
                    } else {
                        $imagesObj = NULL;
                    }
                }

                $detailArr =[];
                $detailArr['images'] =  $imagesObj;
                $detailArr['primaryimage'] =  $CompanyImage;
                $detailUpdate =  Yachtdetail::where('authid', '=', (int)$authid)->update($detailArr);
                if($detailUpdate) {
                    return response()->json(['success' => true], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);                 
            }
        }

        // add s3 images //
        public function changeProfileImagebusiness(Request $request) {
            $authid = decrypt(request('id'));
            $CompanyImage = request('images');
            $usersdata = DB::table('companydetails')
            ->where('authid', '=', (int)$authid)
            ->first();
            if(!empty($usersdata->images)) {
                $companyAllImages = json_decode($usersdata->images,true);
            } else {
                $companyAllImages = [];
            }

            $imagesArr = [];
            $imageCount = 0;
            if(!empty($companyAllImages) && count($companyAllImages) > 0) {
                for($i=0;$i< count($companyAllImages);$i++){
                    if(isset($companyAllImages[$i]['primary']) && $companyAllImages[$i]['primary'] == '0') {
                        $imagesArr[$imageCount]['image'] = $companyAllImages[$i]['image'];
                        $imagesArr[$imageCount]['primary'] = 0;
                        $imageCount++;
                    }
                }
                $imageArrSize = count($imagesArr);
                if(isset($CompanyImage) && $CompanyImage != '') {
                    $imagesArr[$imageArrSize]['image'] = $CompanyImage;
                    $imagesArr[$imageArrSize]['primary'] = 1;
                }
                $imagesObj =  json_encode($imagesArr,JSON_UNESCAPED_SLASHES);
                // echo "<pre>";print_r($imagesArr);
                // echo $imagesObj;die;
            } else {
                if(isset($CompanyImage) && $CompanyImage != '') {
                    $imagesArr[0]['image'] = $CompanyImage;
                    $imagesArr[0]['primary'] = 1;
                    $imagesObj = json_encode($imagesArr,JSON_UNESCAPED_SLASHES);
                } else {
                    $imagesObj = NULL;
                }
            }

            $detailArr =[];
            $detailArr['images'] =  $imagesObj;
            $detailArr['primaryimage'] =  $CompanyImage;
            $detailUpdate =  Companydetail::where('authid', '=', (int)$authid)->update($detailArr);
            if($detailUpdate) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }

        //Get user notification 
        public function getUserNotification(Request $request) {
            $id = request('authid');
            if(!empty($id)) {
                $authid = decrypt($id);
                if((int)$authid) {
                    $total_new_msg = Messages::select('message_id')->where('message_to',(int)$authid)->where('is_notified','=','0')->groupBy('message_id')->get();
                    if(!empty($total_new_msg)) {
                        $total_notification  = count($total_new_msg);
                        return response()->json(['success' => true,'notification' => $total_notification], $this->successStatus);
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

        // get professional profile imgage
        public function getProfessionalProfileById(Request $request) {
            $authid = request('id');
            $id = decrypt($authid);
            $yachtdata = DB::table('talentdetails as td')
				->Join('auths as au','au.id','=','td.authid')
                ->where('td.authid', '=', (int)$id)
                ->select('td.profile_image','au.is_social','td.coverphoto',DB::raw("CONCAT(td.firstname,' ',td.lastname) AS name"))
                ->first();
            if(!empty($yachtdata)) {
                return response()->json(['success' => 'success','data' => $yachtdata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } 

        // to get professional details
        public function getProfessionalDetailsById(Request $request) {
            $id = request("id");
            if(!empty($id)) {
                $id = decrypt($id);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            if(!empty($id) && (int)$id) {
                $data = DB::table('talentdetails')
                    ->select('firstname','lastname','licences','certification','objective','workexperience','willingtravel','address','city','state','zipcode','country','mobile','resume','totalexperience','profile_image','longitude','latitude','created_at','jobtitleid','otherjobtitle','coverphoto','objective','country_code','text_notification','text_notification_other')
                    ->where('status' ,'=', 'active')
                    ->where('authid' ,'=', (int)$id)
                    ->first();
                return response()->json(['success' => true,'data' => $data], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);        
            }
        }

        // change professional password]
        public function changeProfessionalPassword(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'password' => 'required',
                'confirm' => 'required|same:password',
                'oldpassword' => 'required',
            ]);
       
            if ($validate->fails()) {
               return response()->json(['error'=>'validationError'], 401); 
            }
            $userid = request('id');
            $authid = decrypt($userid);
            $auth   = array(); 
            $updated = 0;
            $oldpassword =request('oldpassword');
            $userDetail =  DB::table('auths')->where('id', '=', (int)$authid)->where('usertype', '=', 'professional')->where('status', '!=', 'deleted')->first();
            if(!empty($userDetail)) {
                if(!Hash::check($oldpassword,$userDetail->password)) {
                    return response()->json(['error'=>'notmatch'], 401);
                } else {
                    $auth['password'] =Hash::make(request('password'));
                    if(!empty($authid) && $authid > 0) {
                        $updated =  Auth::where('id', '=', (int)$authid)->where('usertype', '=', 'professional')->where('status', '!=', 'deleted')->update($auth);
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

        // updte professional coverphoto
        public function addS3ImagesProfessional(Request $request) {
            $decrypt = decrypt(request('id'));
            $authid = $decrypt;
            $s3images = request('images');
            $detailArr =[];
            $detailArr['coverphoto'] =  $s3images;
            $detailUpdate =  Talentdetail::where('authid', '=', (int)$authid)->update($detailArr);
            if($detailUpdate) {
                $usersdata = DB::table('talentdetails')
                ->where('authid', '=', (int)$authid)
                ->first();
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }

        // change profile image for boat owner
        public function addS3ProfileImageProfessional(Request $request) {
            $decrypt = decrypt(request('id'));
            $authid = $decrypt;
            $s3images = request('images');
            $detailArr =[];
            $detailArr['profile_image'] =  $s3images;
            $detailUpdate =  Talentdetail::where('authid', '=', (int)$authid)->update($detailArr);
            if($detailUpdate) {
                $usersdata = DB::table('talentdetails')
                ->where('authid', '=', (int)$authid)
                ->first();
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }   
        }

        // updae professional personal detals
        public function updateProfessionalPersonal(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'firstname' => 'required',
                'lastname' => 'required',
                'mobile' => 'required',
                'city' => 'required',
                'state' => 'required',
                'country' => 'required',
                // 'county' => 'required',
                'zipcode' => 'required',
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $authid = decrypt(request('id'));
            $auth = Auth::find($authid);
            $auth->ipaddress = $this->getIp();
            if($auth->save()) {
                $authid = $auth->id;
                if($authid) {
                    $address = request('address');
                    $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
                    $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
                    $output = $this->getGeoLocation($location); //Get Location from location Trait
                    $longitude = $output['longitude'];
                    $latitude = $output['latitude'];
                    $proffArr['firstname'] = request('firstname');
                    $proffArr['lastname'] = request('lastname');
                    $proffArr['mobile'] = request('mobile');
                    $proffArr['address'] = (!empty(request('address'))?request('address'):NULL);
                    $proffArr['longitude']  = $longitude;
                    $proffArr['latitude']   = $latitude;
                    $proffArr['city'] = request('city');
                    $proffArr['state'] = request('state');
                    $proffArr['country'] = request('country');  
                    // $proffArr['county'] = request('county');  
                    $proffArr['zipcode'] = request('zipcode');
                    $country_code = request('country_code');
                    if($country_code != '') {
                        $pos = strpos($country_code, '+');
                        if(!$pos){
                            $country_code ='+'.$country_code;
                        }
                    } 
                    $proffArr['country_code'] = $country_code;
                    $proffArr['profile_image'] = (!empty(request('images'))?request('images'):NULL);
                    
                    $detailUpdate =  Talentdetail::where('authid', '=', (int)$authid)->update($proffArr);
                    if($detailUpdate) {
						$zaiperenv = env('ZAIPER_ENV','local');
						if($zaiperenv == 'live') {
							$this->sendAccountCreateZapierbyID($authid,'professional');
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

        // for professional detail //
        public function updateProfessionalDetail(Request $request ) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'jobtitle' => 'required',
                'willingtravel' => 'required',
                'resume' => 'required'
            ]);
            
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401);
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

            //echo "<pre>";echo $licenceObj;die;
            $userid = request('id');
            $decryptUserid = decrypt($userid);
            if(empty($decryptUserid) || $decryptUserid == '') {
                return response()->json(['error'=>'networkerror'], 401);
            }
            $auth = Auth::find($decryptUserid);
            if ($auth) {
                $talentdetail['jobtitleid']  = request('jobtitle');
                $talentdetail['licences']  = ($emptyLicence)?NULL:$licenceObj;
                $talentdetail['certification']  = ($emptyCertificate)? NULL:$certificateObj;
                $talentdetail['objective']  = (!empty(request('objective')))?request('objective'):NULL;
                $talentdetail['workexperience']  = request('workexperience');
                $talentdetail['willingtravel']  = request('willingtravel');
                $talentdetail['resume']  = request('resume');
                $talentdetail['otherjobtitle']  = request('otherJobTitle');
                $talentdetail['totalexperience']  = request('experience');
                $detailUpdate =  Talentdetail::where('authid', '=', (int)$decryptUserid)->update($talentdetail);
                if ($detailUpdate) {
					$zaiperenv = env('ZAIPER_ENV','local');
					if($zaiperenv == 'live') {
						$this->sendAccountCreateZapierbyID($decryptUserid,'professional');
					}
                    return response()->json(['success' => true], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
                
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }

        public function changeBookmarkStatus(Request $request ) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'jobid' => 'required'
            ]);
            
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401);
            }
            $userid = request('id');
            $decryptUserid = decrypt($userid);
            if(empty($decryptUserid) || $decryptUserid == '') {
                return response()->json(['error'=>'networkerror'], 401);
            }
            $getbookmark =  BookmarkJobs::where('authid', '=', (int)$decryptUserid)->where('jobid','=',(int)request('jobid'))->first();
            if(!empty($getbookmark)) {
                $deleteBookmark =  BookmarkJobs::where('authid', '=', (int)$decryptUserid)->where('jobid','=',(int)request('jobid'))->delete();
                if(!empty($deleteBookmark)) {
                    return response()->json(['success' => true,'deleted' => true], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            } else {
                $BookmarkJobs = new BookmarkJobs;
                $BookmarkJobs->jobid = (int)request('jobid');
                $BookmarkJobs->authid = (int)$decryptUserid;
                if($BookmarkJobs->save()) {
                    $savedbookmarkid = $BookmarkJobs->id;
                    return response()->json(['success' => true,'deleted' => false,'id' =>$savedbookmarkid], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            }
        }

        //Get All job application by Professional Id
        public function getAllJobByProffId(Request $request) {
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $jobs = DB::table('jobs as jb')
                    ->select('jb.id as jobid','jb.services','jb.title','jb.description','jb.geolocation','jb.salary','jb.status','jb.request_uniqueid','jb.salarytype','jb.created_at')
                    // ->whereRaw('id IN(SELECT DISTINCT ON(request_uniqueid) id from jobs)')
                    ->join('apply_jobs as appjb','appjb.jobid','=','jb.id')
                    ->where('appjb.authid',$id)
                    // ->where('authid',$id)
                    ->get();
                if(!empty($jobs) && count($jobs)) {
                    // $allservices = Service::where('status','=','1')->select('id', 'service as itemName')->get()->toArray();
                    // $newallservices = [];
                    // foreach ($allservices as $val) {
                    //     $newallservices[$val['id']] = $val['itemName'];
                    // }
                    // $allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
                    // $newallCategory = [];
                    // foreach ($allCategory as $val) {
                    //     $newallCategory[$val['id']] = $val['categoryname'];
                    // }
                    // foreach ($jobs as $jkey => $jval) {
                    //     $service = json_decode($jval->services);
                    //     $newService = [];
                    //     $temCateArr = [];
                    //     foreach ($service as $catId => $SerIds) {
                    //         // $newService[] = [];
                    //         foreach ($SerIds as $sid => $sval) {
                    //             if(isset($newallservices[$sval]) && !in_array($newallservices[$sval],$newService)) {
                    //                 $newService[] =  $newallservices[$sval];
                    //             }
                    //         }
                    //     }
                    //     $jobs[$jkey]->newservices = $newService;
                    //     unset($jobs[$jkey]->services);
                    // }
                    return response()->json(['success' => true,'data' => $jobs], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);    
                } 
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }                  
        }

        //apply for job
        public function applyForJob(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'jobid' => 'required',
                'message' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $User_request_servicesData = Jobs::where('id',(int)request('jobid'))->first(); 
            if(empty($User_request_servicesData)) {
               return response()->json(['error'=>'networkerror'], 401);         
            }
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $insertRequestArr   = new Apply_Jobs; 
                $insertRequestArr->jobid =(int)request('jobid');
                $insertRequestArr->authid =(int)$id;
                $to_usertype = 0;
                $usertype = Auth::select('usertype')->where('id',$User_request_servicesData->authid)->first();
                if(!empty($usertype)) {
                    $to_usertype = $usertype->usertype;
                }
                if($insertRequestArr->save()) {
                    $reqid = $insertRequestArr->id;
                    $msgArr   = new Messages; 
                    $msgArr->message_to = (int)$User_request_servicesData->authid;
                    $msgArr->message_from = (int)$id;
                    $msgArr->message_type = 'vacancy';
                    $msgArr->to_usertype = $to_usertype;
                    $msgArr->from_usertype = 'professional';
                    $msgArr->subject = 'Apply Job';
                    $msgArr->message = request('message');
                    $msgArr->request_id= (int)request('jobid');
                    if($msgArr->save()) {
                        
                        $message_id = $msgArr->id;
                        $update_message_id = Messages::where('id',$message_id)->update(['message_id'=>$message_id]);
                        $deleteBookmark =  BookmarkJobs::where('authid', '=', (int)$id)->where('jobid','=',(int)request('jobid'))->delete();
                        $talentdetail = Talentdetail::where('authid','=',$id)->first();
                        //Check usertype of employer
                        $checkUsertype = DB::table('jobs')->select('usertype','email','authid')->Join('auths','auths.id','=','jobs.authid')->where('jobs.id',(int)request('jobid'))->first();
                        if(!empty($checkUsertype) && isset($checkUsertype->usertype)) {
                            $employerType = '';
                            if($checkUsertype->usertype == 'yacht') {
                                $data = Yachtdetail::select('firstname','lastname','contact','country_code','authid')->where('authid','=',$checkUsertype->authid)->first();
                                $employerType = 'yacht';
                            } 
                            if($checkUsertype->usertype == 'company') {
                                $data = Companydetail::select('name','contactemail','contactmobile as contact','country_code','authid')->where('authid','=',$checkUsertype->authid)->first();
                                $employerType = 'company';
                            }
                            // print_r($data);die;
                            if(!empty($data)) {
                                $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                                $link = $website_url.'/job-detail/'.request('jobid').'?cf=marine';
                                $ACTIVATION_LINK = $link;
                                $emailArr = [];                                        
                                $emailArr['link'] = $ACTIVATION_LINK;
                                if($employerType == 'company') {
                                    $emailArr['to_email'] = $data->contactemail;    
                                    $emailArr['name'] = $data->name;
                                } else {
                                    $emailArr['to_email'] = $checkUsertype->email;
                                    $emailArr['name'] = $data->firstname.' '.$data->lastname;
                                }
                                $emailArr['professional_name'] =  $talentdetail->firstname.' '.$talentdetail->lastname;
                                // echo '<pre>';print_r($emailArr);die;
                                SendNewLeadNotificationEmails::dispatch($emailArr,'job_applied');
                                //Send sms

                                $mobilenumber = $data->country_code.$data->contact;
                                $sms = $emailArr['professional_name'].' applied for a job. Click '.$ACTIVATION_LINK.' to view job details.';
                                // $sms ="A lead request has been sent to you for ".$ACTIVATION_LINK." request.";
                                SendSmsToBusinesses::dispatch($sms,$mobilenumber,'job',$id,$data->authid);
                                
                                /// send message notification ///
								//~ $UserInfo = [];
								//~ $UserInfo['from_name'] = $emailArr['professional_name'];
								//~ $UserInfo['to_name'] =  $emailArr['name'];
								//~ $UserInfo['to_email'] = $emailArr['to_email'];
								//~ $website_url = env('NG_APP_URL','https://www.marinecentral.com');
								//~ $link = '';
								 //~ if($employerType == 'company') {
									//~ $link = $website_url.'/business/messages?id='.$message_id.'&type=vacancy';
								//~ } else if($employerType == 'yacht') {
									//~ $link = $website_url.'/yacht/messages?id='.$message_id.'&type=vacancy';
								//~ } 
								//~ if($link != '' && !empty($UserInfo['to_email'])) {
									//~ $UserInfo['link'] = $link;
									//~ $status = $this->sendEmailNotification($UserInfo,'unreadMessage_reminder');
								//~ }
								/////////////////////////////////
                            }
                        }

                        
                        /*
                        $getTemplate = Emailtemplates::select('subject','body')->where('template_name','=','success_lead_sent')->where('status','1')->first();
                        if(!empty($getTemplate)) {
                            //Send notification email to business to lead sent successfully
                            $to_email = Auth::where('id',(int)$id)->first();
                            $emailArr = [];
                            if(!empty($to_email)) {
                                $emailArr['to_email'] = $to_email->email;
                                $email_body = $getTemplate->body;
                                $search  = array('%WEBSITE_LOGO%');
                                $replace = array(asset('public/img/logo.png'));
                                $emailArr['subject'] = $getTemplate->subject;
                                $emailArr['body'] = str_replace($search, $replace, $email_body);
                                $status = $this->sendEmailNotification($emailArr);
                            }
                            // if($status == 'sent') {
                            if(1) {
                                return response()->json(['success' => true,'data' => $id], $this->successStatus);
                            }  else {
                               return response()->json(['error'=>'networkerror'], 401);  
                            }   
                            
                         } else {
                             return response()->json(['error'=>'networkerror'], 401);    
                         }
                        */
                        return response()->json(['success' => true,'id' => request('jobid')], $this->successStatus);
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

        //apply for job
        public function changeJobStatus(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'jobid' => 'required',
                'status' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $jobUser = Jobs::where('id',(int)request('jobid'))->where('authid',(int)$id)->first(); 
                if(empty($jobUser)) {
                   return response()->json(['error'=>'networkerror'], 401);         
                }
                $status = request('status');
                if($status != 'expired' && $status != 'deleted') {
                    return response()->json(['error'=>'validationError'], 401); 
                }

                $changeMessageStatus = Jobs::where('id',(int)request('jobid'))->update(['status'=>$status]);
                if(!empty($changeMessageStatus)) {
                    //$deleteBookmark =  BookmarkJobs::where('jobid','=',(int)request('jobid'))->delete();
                    return response()->json(['success' => true], $this->successStatus);
                } else {
                    return response()->json(['error'=>'validationError'], 401);
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);             
            }           
        }

        public function addRemoveBookmarkProff(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'jobid' => 'required',
                'status' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $deleteBookmarkJob = BookmarkJobs::where('jobid','=',(int)request('jobid'))
                ->where('authid','=',$id)
                ->delete();
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        public function getBookmarkJobsProff(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $bookmark_jobs = DB::table('jobs as jb')
                    ->select('jb.id as jobid','jb.services','jb.title','jb.description','jb.geolocation','jb.salary','jb.status','jb.request_uniqueid','jb.salarytype','jb.created_at')
                    // ->whereRaw('jb.id IN(SELECT DISTINCT ON(jb.request_uniqueid) jb.id from jobs)')
                    // ->where('jb.authid',$id)
                    ->Join('bookmark_jobs as bkj','bkj.jobid','=','jb.id')
                    ->where('bkj.authid','=', (int)$id)
                    ->where('bkj.status','=', '1')
                    ->get();
                if(!empty($bookmark_jobs)  && count($bookmark_jobs)) {
                    //  $allservices = Service::where('status','=','1')->select('id', 'service as itemName')->get()->toArray();
                    // $newallservices = [];
                    // foreach ($allservices as $val) {
                    //     $newallservices[$val['id']] = $val['itemName'];
                    // }
                    // $allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
                    // $newallCategory = [];
                    // foreach ($allCategory as $val) {
                    //     $newallCategory[$val['id']] = $val['categoryname'];
                    // }
                    // foreach ($bookmark_jobs as $jkey => $jval) {
                    //     $service = json_decode($jval->services);
                    //     $newService = [];
                    //     $temCateArr = [];
                    //     foreach ($service as $catId => $SerIds) {
                    //         foreach ($SerIds as $sid => $sval) {
                    //             if(isset($newallservices[$sval]) && !in_array($newallservices[$sval],$newService)) {
                    //                 $newService[] =  $newallservices[$sval];
                    //             }
                    //         }
                    //     }
                    //     $bookmark_jobs[$jkey]->newservices = $newService;
                    //     unset($bookmark_jobs[$jkey]->services);
                    // }
                    return response()->json(['success' => true,'data' => $bookmark_jobs], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);    
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        //apply for job
        public function changeserviceRequestStatus(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'jobid' => 'required',
                'appliedid' => 'required',
                'status' => 'required'
            ]);

            if ($validate->fails()) {
				return response()->json(['error'=>'validationError'], 401); 
            }
            
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $jobUser = User_request_services::where('id',(int)request('jobid'))->where('authid',(int)$id)->first(); 
                if(empty($jobUser)) {
                   return response()->json(['error'=>'networkerror'], 401);         
                }
                $status = request('status');
                if($status != 'active' && $status != 'deleted') {
                    return response()->json(['error'=>'networkerror'], 401); 
                } else if($status == 'deleted') {
                    $status = 'rejected';
                }

                $changeMessageStatus = RequestProposals::where('requestid',(int)request('jobid'))->where('companyid',(int)request('appliedid'))->update(['status'=>$status]);
                if(!empty($changeMessageStatus)) {
                    if($status == 'active') {
                        $deletedUserreq = RequestProposals::where('requestid',(int)request('jobid'))->where('companyid','!=',(int)request('appliedid'))->where('status','!=','rejected')->update(['status'=>'declined']);
                        $jobUserData = User_request_services::where('id',(int)request('jobid'))->update(['status'=>'received_leads']);
                        $getMessageData = Messages::where('message_from',(int)request('appliedid'))->where('message_to',(int)$id)->where('request_id',(int)request('jobid'))->first();
                        $deleteBookmark =  BookmarkRequests::where('requestid','=',(int)request('jobid'))->delete();
                        $from_usertype = 0;
                        $usertype = Auth::select('usertype')->where('id',(int)$id)->first();
                        if(!empty($usertype)) {
                            $from_usertype = $usertype->usertype;
                        }
                        if(!empty($getMessageData)) {
                            $decliendUserreq = DB::table('request_proposals as rp')->select('cd.authid as companyid','cd.name','cd.contactemail','cd.country_code','cd.contactmobile')->Join('companydetails as cd','cd.authid','=','rp.companyid')->where('rp.requestid',(int)request('jobid'))->where('rp.status','=','declined')->get();
                             
                            if(!empty($decliendUserreq) && count($decliendUserreq) > 0) {
                                foreach ($decliendUserreq as $decliendreq) {
                                    // $getMessageDatareq = Messages::where('message_from',(int)$decliendreq->companyid)->where('message_to',(int)$id)->where('request_id',(int)request('jobid'))->first();
                                    //  $msgArrReq   = new Messages; 
                                    //  $msgArrReq->message_to = (int)$decliendreq->companyid;
                                    //  $msgArrReq->message_from = (int)$id;
                                    //  $msgArrReq->message_type = 'lead';
                                    //  $msgArrReq->to_usertype = 'company';
                                    //  $msgArrReq->from_usertype = $from_usertype;
                                    //  $msgArrReq->subject = 'Lead request';
                                    //  $msgArrReq->message_id = $getMessageDatareq->message_id;
                                    //  $msgArrReq->parent_id = $getMessageDatareq->message_id;
                                    //  $msgArrReq->message = 'This service request is closed';
                                    //  $msgArrReq->request_id= (int)request('jobid');
                                     // if($msgArrReq->save()) {
                                        $notificationDate = date('Y-m-d H:i:s');
                                        $emailArr = [];
                                        $emailArr['name'] = $decliendreq->name;
										$emailArr['to_email'] = $decliendreq->contactemail;
										$emailArr['title'] = $jobUser->title;
										SendNewLeadNotificationEmails::dispatch($emailArr,'reject_lead_notification');
                                        SaveNotifications::dispatch((int)$decliendreq->companyid,'company','Your lead was rejected.',NULL,NULL,(int)request('jobid'),$notificationDate,null,0,'decline_lead'); 
                                        $mobilenumber = $decliendreq->country_code.$decliendreq->contactmobile;
                                       $sms = 'The Service Request for '.$jobUser->title.' has been cancelled or closed by the user.';
                                       SendSmsToBusinesses::dispatch($sms,$mobilenumber,'service_request',$id,$decliendreq->companyid);
                                     // }
                                }
                            }
                            //$reqid = $insertRequestArr->id;
                            // $msgArr   = new Messages; 
                            // $msgArr->message_to = (int)request('appliedid');
                            // $msgArr->message_from = (int)$id;
                            // $msgArr->message_type = 'lead';
                            // $msgArr->to_usertype = 'company';
                            // $msgArr->from_usertype = $from_usertype;
                            // $msgArr->subject = 'Lead request';
                            // $msgArr->message_id = $getMessageData->message_id;
                            // $msgArr->parent_id = $getMessageData->message_id;
                            // $msgArr->message = 'Your lead has been approved';
                            // $msgArr->request_id= (int)request('jobid');
                            // if($msgArr->save()) {
                                $notificationDate = date('Y-m-d H:i:s');
                                $approvedUserreq = DB::table('request_proposals as rp')->select('cd.authid as companyid','cd.name','cd.contactemail','cd.country_code','cd.contactmobile')->Join('companydetails as cd','cd.authid','=','rp.companyid')->where('rp.requestid',(int)request('jobid'))->where('rp.status','=','active')->get();
                                $CompanyData = DB::table('request_proposals as rq')->select('rq.status','cmp.name','cmp.primaryimage','cmp.authid','cmp.slug','cmp.address','cmp.city','cmp.country','cmp.state','cmp.zipcode')->LeftJoin('companydetails as cmp','cmp.authid','=','rq.companyid')->where('rq.requestid','=',(int)request('jobid'))->where('rq.status','!=','deleted')->orderBy('rq.status','ASC')->get();
                                //echo "<pre>";print_r($CompanyData);
                                if(!empty($CompanyData)) {
									 $emailArr = [];
									$emailArr['name'] = $approvedUserreq[0]->name;
									$emailArr['to_email'] = $approvedUserreq[0]->contactemail;
									$emailArr['title'] = $jobUser->title;
									SendNewLeadNotificationEmails::dispatch($emailArr,'approved_lead_notification');
									SaveNotifications::dispatch((int)request('appliedid'),'company','Your lead is approved.',NULL,NULL,(int)request('jobid'),$notificationDate,null,0,'approved_lead');
                                    $mobilenumber = $approvedUserreq[0]->country_code.$approvedUserreq[0]->contactmobile;
                                   $sms = 'The Service Request for '.$jobUser->title.' has been approved by the user.';
                                   SendSmsToBusinesses::dispatch($sms,$mobilenumber,'service_request',$id,$approvedUserreq[0]->companyid);
                                    return response()->json(['success' => true,'data' => $CompanyData], $this->successStatus);
                                } else {
                                    return response()->json(['error'=>'networkerror'], 401);
                                }
                            // } else {
                            //      return response()->json(['error'=>'networkerror'], 401);
                            // }
                        } else {
                            return response()->json(['error'=>'networkerror'], 401);
                        }

                    } else {
                        $getMessageData = Messages::where('message_from',(int)request('appliedid'))->where('message_to',(int)$id)->where('request_id',(int)request('jobid'))->first();
                        $from_usertype = 0;
                        $usertype = Auth::select('usertype')->where('id',(int)$id)->first();
                        if(!empty($usertype)) {
                            $from_usertype = $usertype->usertype;
                        }
                        if(!empty($getMessageData)) {
                            $reqid = (int)request('jobid');
                            // $msgArr   = new Messages; 
                            // $msgArr->message_to = (int)request('appliedid');
                            // $msgArr->message_from = (int)$id;
                            // $msgArr->message_type = 'lead';
                            // $msgArr->to_usertype = 'company';
                            // $msgArr->from_usertype = $from_usertype;
                            // $msgArr->subject = 'Lead request';
                            // $msgArr->message_id = $getMessageData->message_id;
                            // $msgArr->parent_id = $getMessageData->message_id;
                            // $msgArr->message = 'Your lead is rejected';
                            // $msgArr->request_id= (int)request('jobid');
                            // if($msgArr->save()) {
                                $CompanyData = DB::table('request_proposals as rq')->select('rq.status','cmp.name','cmp.primaryimage','cmp.authid','cmp.slug','cmp.address','cmp.city','cmp.country','cmp.state','cmp.zipcode')->LeftJoin('companydetails as cmp','cmp.authid','=','rq.companyid')->where('rq.requestid','=',(int)request('jobid'))->where('rq.status','!=','deleted')->orderBy('rq.status','ASC')->get();
                                 $decliendUserreq = DB::table('request_proposals as rp')->select('cd.authid as companyid','cd.name','cd.contactemail')->Join('companydetails as cd','cd.authid','=','rp.companyid')->where('rp.requestid',(int)request('jobid'))->where('rp.companyid',(int)request('appliedid'))->get();
                                //echo "<pre>";print_r($CompanyData);
                                if(!empty($CompanyData)) {
                                    $notificationDate = date('Y-m-d H:i:s');
                                     $emailArr = [];
									$emailArr['name'] = $decliendUserreq[0]->name;
									$emailArr['to_email'] = $decliendUserreq[0]->contactemail;
									$emailArr['title'] = $jobUser->title;
									SendNewLeadNotificationEmails::dispatch($emailArr,'reject_lead_notification');
                                    SaveNotifications::dispatch((int)request('appliedid'),'company','Your lead was rejected.',NULL,NULL,(int)request('jobid'),$notificationDate,null,0,'rejected_lead');
                                    return response()->json(['success' => true,'data' => $CompanyData], $this->successStatus);
                                } else {
									return response()->json(['error'=>'networkerror'], 401);
                                }
                            // } else {
                            //     return response()->json(['error'=>'networkerror'], 401);
                            // }
                        } else {
                            return response()->json(['error'=>'networkerror'], 401);
                        }
                       
                    }
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);             
            }           
        }

        //change request status
        public function changeRequestStatus(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'jobid' => 'required',
                'status' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $jobUser = User_request_services::where('id',(int)request('jobid'))->where('authid',(int)$id)->first(); 
                if(empty($jobUser)) {
                   return response()->json(['error'=>'networkerror'], 401);         
                }
                $status = request('status');
                if($status != 'completed' && $status != 'deleted') {
                    return response()->json(['error'=>'validationError'], 401); 
                }

                $changeMessageStatus = User_request_services::where('id',(int)request('jobid'))->update(['status'=>$status]);
                if(!empty($changeMessageStatus)) {
                    if($status == 'deleted') {
                        $deletedUserreq = RequestProposals::where('requestid',(int)request('jobid'))->update(['status'=>'deleted']);
                        $deleteBookmark =  BookmarkRequests::where('requestid','=',(int)request('jobid'))->delete();
                        $decliendUserreq = DB::table('request_proposals as rp')->select('cd.authid as companyid','cd.name','cd.contactemail')->Join('companydetails as cd','cd.authid','=','rp.companyid')->where('rp.requestid',(int)request('jobid'))->where('rp.status','=','deleted')->get();
                        if(!empty($decliendUserreq) && count($decliendUserreq) > 0) {
							$from_usertype = 0;
							$usertype = Auth::select('usertype')->where('id',(int)$id)->first();
							if(!empty($usertype)) {
								$from_usertype = $usertype->usertype;
							}
                            foreach ($decliendUserreq as $decliendreq) {
                               // print($decliendUserreq);
                                $emailArr = [];
                                $emailArr['name'] = $decliendreq->name;
                                $emailArr['to_email'] = $decliendreq->contactemail;
                                $emailArr['title'] = $jobUser->title;
								$notificationDate = date('Y-m-d H:i:s');
								SaveNotifications::dispatch((int)$decliendreq->companyid,'company','Your lead was rejected.',NULL,NULL,(int)request('jobid'),$notificationDate,null,0,'declined_lead');
								//SendNewLeadNotificationEmails::dispatch($emailArr,'lead_notification');
								SendNewLeadNotificationEmails::dispatch($emailArr,'reject_lead_notification');
								// $getMessageData = Messages::where('message_from',(int)$decliendreq->companyid)->where('message_to',(int)$id)->where('request_id',(int)request('jobid'))->first();
								// $message = 'Your lead was rejected.';
        //                             if(!empty($getMessageData)) {
								// 	 //$reqid = $insertRequestArr->id;
								// 	 $msgArr   = new Messages; 
								// 	 $msgArr->message_to = (int)$decliendreq->companyid;
								// 	 $msgArr->message_from = (int)$id;
								// 	 $msgArr->message_type = 'lead';
								// 	 $msgArr->to_usertype = 'company';
								// 	 $msgArr->from_usertype = $from_usertype;
								// 	 $msgArr->subject = 'Review';
								// 	 $msgArr->message_id = $getMessageData->message_id;
								// 	 $msgArr->parent_id = $getMessageData->message_id;
								// 	 $msgArr->message = $message;
								// 	 $msgArr->request_id= (int)request('jobid');
								// 	 if($msgArr->save()) {
								// 	 } else {
								// 		 return response()->json(['error'=>'networkerror'], 401);
								// 	 }
								//  } else {
								// 	 return response()->json(['error'=>'networkerror'], 401);
								//  }
                            }
                        }
                    } else {
                        $isskip = request('isSkip'); 
                        if($isskip == 0) {
                            $companyID = request('companyid');
                            $message = request('message');
                            $rating = request('rating');
                            if(empty($companyID) || empty($message) ||  empty($rating)) {
                                return response()->json(['error'=>'validationError'], 401);
                            } else {
								$sender_detail = DB::table('auths')->where('id',(int)$id)->first();
								$senderusertype= $sender_detail->usertype;
								$reviewsData = new ServiceRequestReviews;
                                $reviewsData->fromid = (int)$id;
                                $reviewsData->toid = (int)$companyID;
                                $reviewsData->rating = (int)$rating +1;
                                $reviewsData->comment = $message;
                                $reviewsData->subject = 'Lead complete review';
                                $reviewsData->isdeleted = '0';
                                $reviewsData->requestid = (int)request('jobid');
                                $reviewsData->from_usertype = $senderusertype;
                                $reviewsData->to_usertype = 'company';
                                if($reviewsData->save()) {
									if($rating < 2) {
										$detailBiz =  Companydetail::where('authid', '=', (int)$companyID)->first();
										$website_url = env('NG_APP_URL','https://www.marinecentral.com');
										$link = $website_url.'/biz/'.$detailBiz[0]->slug.'?cf=marine';
										$ACTIVATION_LINK = $link;
										$emailArr = [];                                        
										$emailArr['link'] = $ACTIVATION_LINK;
										$emailArr['name'] = $detailBiz[0]->name;
										$emailArr['to_email'] = $detailBiz[0]->contactemail;
										//Send activation email notification
										$status = $this->sendEmailNotification($emailArr,'bad_rating_notification');
									}
                                    // $getMessageData = Messages::where('message_from',(int)$companyID)->where('message_to',(int)$id)->where('request_id',(int)request('jobid'))->first();
                                    // $from_usertype = 0;
                                    // $usertype = Auth::select('usertype')->where('id',(int)$id)->first();
                                    // if(!empty($usertype)) {
                                    //     $from_usertype = $usertype->usertype;
                                    // }
                                    // if(!empty($getMessageData)) {
                                    //     //$reqid = $insertRequestArr->id;
                                    //     $msgArr   = new Messages; 
                                    //     $msgArr->message_to = (int)$companyID;
                                    //     $msgArr->message_from = (int)$id;
                                    //     $msgArr->message_type = 'lead';
                                    //     $msgArr->to_usertype = 'company';
                                    //     $msgArr->from_usertype = $from_usertype;
                                    //     $msgArr->subject = 'Review';
                                    //     $msgArr->message_id = $getMessageData->message_id;
                                    //     $msgArr->parent_id = $getMessageData->message_id;
                                    //     $msgArr->message = $message;
                                    //     $msgArr->request_id= (int)request('jobid');
                                    //     if($msgArr->save()) {
                                    //     } else {
                                    //         return response()->json(['error'=>'validationError'], 401);
                                    //     }
                                    // } else {
                                    //     return response()->json(['error'=>'validationError'], 401);
                                    // }

                                } else {
                                    return response()->json(['error'=>'networkerror'], 401);
                                }
                            }
                        } 
                        $isall = request('isall');
                        if($isall == 'all') {
                            $deletedUserreq = RequestProposals::where('requestid',(int)request('jobid'))->where('status','!=','rejected')->update(['status'=>'declined']);
                            
                            $decliendUserreq = DB::table('request_proposals as rp')->select('cd.authid as companyid','cd.name','cd.contactemail')->Join('companydetails as cd','cd.authid','=','rp.companyid')->where('rp.requestid',(int)request('jobid'))->get();
							if(!empty($decliendUserreq) && count($decliendUserreq) > 0) {
								$from_usertype = 0;
								$usertype = Auth::select('usertype')->where('id',(int)$id)->first();
								if(!empty($usertype)) {
									$from_usertype = $usertype->usertype;
								}
								foreach ($decliendUserreq as $decliendreq) {
									$emailArr = [];
									$emailArr['name'] = $decliendreq->name;
									$emailArr['to_email'] = $decliendreq->contactemail;
									$emailArr['title'] = $jobUser->title;
									$notificationDate = date('Y-m-d H:i:s');
									SaveNotifications::dispatch((int)$decliendreq->companyid,'company','Your lead was rejected.',NULL,NULL,(int)request('jobid'),$notificationDate,null,0,'rejected_lead');
									//SendNewLeadNotificationEmails::dispatch($emailArr,'lead_notification');
									SendNewLeadNotificationEmails::dispatch($emailArr,'reject_lead_notification');
									// $getMessageData = Messages::where('message_from',(int)$decliendreq->companyid)->where('message_to',(int)$id)->where('request_id',(int)request('jobid'))->first();
									// $message = 'Your lead was rejected.';
									// 	if(!empty($getMessageData)) {
									// 	 //$reqid = $insertRequestArr->id;
									// 	 $msgArr   = new Messages; 
									// 	 $msgArr->message_to = (int)$decliendreq->companyid;
									// 	 $msgArr->message_from = (int)$id;
									// 	 $msgArr->message_type = 'lead';
									// 	 $msgArr->to_usertype = 'company';
									// 	 $msgArr->from_usertype = $from_usertype;
									// 	 $msgArr->subject = 'Review';
									// 	 $msgArr->message_id = $getMessageData->message_id;
									// 	 $msgArr->parent_id = $getMessageData->message_id;
									// 	 $msgArr->message = $message;
									// 	 $msgArr->request_id= (int)request('jobid');
									// 	 if($msgArr->save()) {
									// 	 } else {
									// 		 return response()->json(['error'=>'networkerror'], 401);
									// 	 }
									//  } else {
									// 	 return response()->json(['error'=>'networkerror'], 401);
									//  }
								}
							}
                            
                        } else {
                            $deletedUserreq = RequestProposals::where('requestid',(int)request('jobid'))->where('status','=','active')->update(['status'=>'completed']);
                        }
                        
                    }
                    $CompanyData = DB::table('request_proposals as rq')->select('rq.status','cmp.name','cmp.primaryimage','cmp.authid','cmp.slug','cmp.address','cmp.city','cmp.country','cmp.state','cmp.zipcode')->LeftJoin('companydetails as cmp','cmp.authid','=','rq.companyid')->where('rq.requestid','=',(int)request('jobid'))->where('rq.status','!=','deleted')->orderBy('rq.status','ASC')->get();
                    //echo "<pre>";print_r($CompanyData);
                    if(!empty($CompanyData)) {
                        return response()->json(['success' => true,'data' => $CompanyData], $this->successStatus);
                    } else {
                        return response()->json(['error'=>'validationError'], 401);
                    }
                    
                } else {
                    return response()->json(['error'=>'validationError'], 401);
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);             
            }           
        }

        public function changeBookmarkRequestStatus(Request $request ) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'reqid' => 'required'
            ]);
            
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401);
            }
            $userid = request('id');
            $decryptUserid = decrypt($userid);
            if(empty($decryptUserid) || $decryptUserid == '') {
                return response()->json(['error'=>'networkerror'], 401);
            }
            $getbookmark =  BookmarkRequests::where('authid', '=', (int)$decryptUserid)->where('requestid','=',(int)request('reqid'))->first();
            if(!empty($getbookmark)) {
                $deleteBookmark =  BookmarkRequests::where('authid', '=', (int)$decryptUserid)->where('requestid','=',(int)request('reqid'))->delete();
                if(!empty($deleteBookmark)) {
                    return response()->json(['success' => true,'deleted' => true], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            } else {
                $BookmarkJobs = new BookmarkRequests;
                $BookmarkJobs->requestid = (int)request('reqid');
                $BookmarkJobs->authid = (int)$decryptUserid;
                if($BookmarkJobs->save()) {
                    $savedbookmarkid = $BookmarkJobs->id;
                    return response()->json(['success' => true,'deleted' => false,'id' =>$savedbookmarkid], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            }
        }

        public function getBookmarkLeadsCompany(Request $requuest){
            $id = decrypt(request('id'));
            if (!empty($id) && (int)$id) {
                $data = DB::table('users_service_requests as usr')
                    ->select('usr.services','usr.created_at','usr.description','usr.numberofleads','usr.title','usr.status','bkreq.status as bookmark_status','usr.id')
                    ->join('bookmark_requests as bkreq','bkreq.requestid','=','usr.id')
                    ->where('bkreq.authid',$id)
                    ->get();
                if(!empty($data)) {
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
                    return response()->json(['success' => true,'data' => $data], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);   
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        public function addRemoveBookmarkLead(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'leadreqid' => 'required',
                'status' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $deleteBookmarkLead = BookmarkRequests::where('requestid','=',(int)request('leadreqid'))
                ->where('authid','=',$id)
                ->delete();
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

         public function sendReviewandRating(Request $request) {
            $validate = Validator::make($request->all(), [
                'rating' => 'required',
                'message' => 'required',
                'userid' => 'required',
                'companySlug' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $reqID = request('reqid');
            $requestID = 0;
            if(!empty($reqID) && $reqID != '') {
				$requestID = (int)$reqID;
			}
			$reviewid = (int)request('reviewid');
			$CompanyData = Companydetail::where('slug','=',request('companySlug'))->where('status', '!=', 'deleted')->first();

            if(!empty($CompanyData)) {
                $from_usertype = 0;
                $from_usertypedata = Auth::select('usertype')->where('id',(int)request('userid'))->first();
                if(!empty($from_usertypedata)) {
                    $from_usertype = $from_usertypedata->usertype;
                }
                $rating = request('rating');
                $message = request('message');
                $companyID = $CompanyData->authid;
                $firstNotificaiton = true; 
                if(!empty($reviewid) || $reviewid != '0') {
                    $firstNotificaiton = false;
					$reviewsData = ServiceRequestReviews::find($reviewid);
					$reviewsData->rating = (int)$rating +1;
					$reviewsData->comment = $message;
				} else {
					$reviewsData = new ServiceRequestReviews;
					$reviewsData->fromid = (int)request('userid');
					$reviewsData->toid = (int)$companyID;
					$reviewsData->rating = (int)$rating +1;
					$reviewsData->comment = $message;
					$reviewsData->subject = 'Review';
					$reviewsData->isdeleted = '0';
					$reviewsData->from_usertype = $from_usertype;
					$reviewsData->to_usertype = 'company';
					$reviewsData->parent_id = 0;
					$reviewsData->requestid = $requestID;
				} 
                if($reviewsData->save()) {
					if($rating < 2) {
						$detailBiz =  Companydetail::where('authid', '=', (int)$companyID)->first();
						$website_url = env('NG_APP_URL','https://www.marinecentral.com');
						$link = $website_url.'/biz/'.$detailBiz->slug.'?cf=marine';
						$ACTIVATION_LINK = $link;
						$emailArr = [];                                        
						$emailArr['link'] = $ACTIVATION_LINK;
						$emailArr['name'] = $detailBiz->name;
						$emailArr['to_email'] = $detailBiz->contactemail;
						//Send activation email notification
						$status = $this->sendEmailNotification($emailArr,'bad_rating_notification');
					}
                $reviewsID = $reviewsData->id;
                $notificationDate = date('Y-m-d H:i:s');
                if($firstNotificaiton) {
                    $this->addNotification($companyID,'company',NULL,$message,NULL,$reviewsID,$notificationDate,'review');
                } else {
                    $this->addNotification($companyID,'company',NULL,$message,NULL,$reviewsID,$notificationDate,'review-update');      
                }
			     $latestReview = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,DATE_PART('day','".$notificationDate."'::timestamp - msgmain.created_at::timestamp) as datediff,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
					from service_request_reviews as msgmain
					left join (
						(select authid, firstname, lastname,profile_image from userdetails)
						union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
						union (select authid, firstname, lastname,profile_image from talentdetails)
						union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
					) unionSub1 on unionSub1.authid = msgmain.fromid where  msgmain.toid = ".(int)$companyID." AND msgmain.parent_id = 0 AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
					$rate = 0;
					$review = 0;
					$dataView = DB::table('reviewsview as r')->select(DB::raw('coalesce( r.totalreviewed , 0 ) as totalreviewed,coalesce( r.totalrating , 0 ) as totalrated'))
					->where('r.toid','=',(int)$companyID)
					->get();
					if(!empty($dataView) && count($dataView) > 0) {
						$rate = $dataView[0]->totalrated;
						$review = $dataView[0]->totalreviewed;
					}
                    // $getMessageData = Messages::where('message_from',(int)$companyID)->where('message_to',(int)$id)->where('request_id',(int)request('jobid'))->first();
                    // $from_usertype = 0;
                    // $usertype = Auth::select('usertype')->where('id',(int)$id)->first();
                    // if(!empty($usertype)) {
                    //     $from_usertype = $usertype->usertype;
                    // }
                    // if(!empty($getMessageData)) {
                    //     //$reqid = $insertRequestArr->id;
                    //     $msgArr   = new Messages; 
                    //     $msgArr->message_to = (int)$companyID;
                    //     $msgArr->message_from = (int)$id;
                    //     $msgArr->message_type = 'lead';
                    //     $msgArr->to_usertype = 'company';
                    //     $msgArr->from_usertype = $from_usertype;
                    //     $msgArr->subject = 'Review';
                    //     $msgArr->message_id = $getMessageData->message_id;
                    //     $msgArr->parent_id = $getMessageData->message_id;
                    //     $msgArr->message = 'Review Message';
                    //     $msgArr->request_id= (int)request('jobid');
                    //     if($msgArr->save()) {
                    //     } else {
                    //         return response()->json(['error'=>'validationError'], 401);
                    //     }
                    // } else {
                    //     return response()->json(['error'=>'validationError'], 401);
                    // }
                    return response()->json(['success' => true,'latestReview'=> $latestReview,'rating' =>$rate ,'review' => $review], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        public function askForReview(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required',
                'requestuserid' => 'required',
                'requestid' => 'required',
                'message' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }

            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                return response()->json(['success' => true], $this->successStatus);
                $getMessageData = Messages::where('message_from',(int)$id)->where('message_to',(int)request('requestuserid'))->where('request_id',(int)request('requestid'))->first();
                $from_usertype = 0;
                $usertype = Auth::select('usertype')->where('id',(int)request('requestuserid'))->first();
                $CompanyData = Companydetail::where('authid','=',(int)$id)->where('status', '!=', 'deleted')->first();
                $slugName = '';
                if(!empty($CompanyData)) {
                    $slugName = $CompanyData->slug;
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
                if(!empty($usertype)) {
                    $from_usertype = $usertype->usertype;
                }
                if(!empty($getMessageData)) {
                    //$reqid = $insertRequestArr->id;
                    $messagelink = '<br><a href="https://www.marinecentral.com/detail/'.$slugName.'?review=true&reqid='.request('requestid').'" target=”_blank” class="comment-msg">click here</a> to view comment';
                    $msgArr   = new Messages; 
                    $msgArr->message_to = (int)request('requestuserid');
                    $msgArr->message_from = (int)$id;
                    $msgArr->message_type = 'lead';
                    $msgArr->to_usertype = $from_usertype;
                    $msgArr->from_usertype = 'company' ;
                    $msgArr->subject = 'Review';
                    $msgArr->message_id = $getMessageData->message_id;
                    $msgArr->parent_id = $getMessageData->message_id;
                    $msgArr->message = request('message').$messagelink;
                    $msgArr->request_id= (int)request('requestid');
                    if($msgArr->save()) {
						// $message_id = $msgArr->id;
      //                   $update_message_id = Messages::where('id',$message_id)->update(['message_id'=>$message_id]);
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
        
        public function sendReplyReviewRate(Request $request) {
            $validate = Validator::make($request->all(), [
                'rating' => 'required',
                'message' => 'required',
                'id' => 'required',
                'sendtoid' => 'required',
                'reviewid' => 'required'
            ]);
            
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
				$from_usertype = 0;
                $from_usertypedata = Auth::select('usertype')->where('id',(int)$id)->first();
                if(!empty($from_usertypedata)) {
					$from_usertype = $from_usertypedata->usertype;
                }
                
                $to_usertype = 0;
                $to_usertypedata = Auth::select('usertype')->where('id',(int)request('sendtoid'))->first();
                if(!empty($to_usertypedata)) {
					$to_usertype = $to_usertypedata->usertype;
                }
                $rating = request('rating');
                $message = request('message');
                $reviewsData = new ServiceRequestReviews;
                $reviewsData->fromid = (int)$id;
                $reviewsData->toid = (int)request('sendtoid');
                $reviewsData->rating = (int)$rating +1;
                $reviewsData->comment = $message;
                $reviewsData->subject = 'Review';
                $reviewsData->isdeleted = '0';
                $reviewsData->requestid = 0;
                $reviewsData->from_usertype = $from_usertype;
                $reviewsData->to_usertype = $to_usertype;
                $reviewsData->parent_id = request('reviewid');
                if($reviewsData->save()) {
					$reviewID = request('reviewid');
					$latestReview = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
                        from service_request_reviews as msgmain
                        left join (
                            (select authid, firstname, lastname,profile_image from userdetails)
                            union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
                            union (select authid, firstname, lastname,profile_image from talentdetails)
                            union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
                        ) unionSub1 on unionSub1.authid = msgmain.fromid where msgmain.parent_id = '".$reviewID."' AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
					return response()->json(['success' => true,'reviewData' => $latestReview], $this->successStatus);
				} else {
					return response()->json(['error'=>'networkerror'], 401);
				}
           	} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
        }
        
        public function checkValidReviewRequest(Request $request) {
			$validate = Validator::make($request->all(), [
                'reqID' => 'required',
                'userid' => 'required',
                'companySlug' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $id = request('userid');
            if(!empty($id) && (int)$id) {
				$CompanyData = Companydetail::where('slug','=',request('companySlug'))->where('status', '!=', 'deleted')->first();
				if(!empty($CompanyData)) {
					$companyID = $CompanyData->authid;
					$reviewRequest = DB::table('users_service_requests as usr')
                        ->select('usr.*','rp.companyid')
                        ->Join('request_proposals as rp','rp.requestid','=','usr.id')
                        ->where('usr.id',(int)request('reqID'))
                        ->where('usr.status','completed')
                        ->where('usr.authid',(int)$id)
                        ->where('rp.companyid',$companyID)
                        ->first();
					if(!empty($reviewRequest)) {
						$reviewAlready = ServiceRequestReviews::where('fromid',(int)$id)->where('toid',$companyID)->where('requestid',(int)request('reqID'))->first();
						if(!empty($reviewAlready)) {
							return response()->json(['error'=>'networkerror'], 401);
						} else {
						return response()->json(['success' => true], $this->successStatus);
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
		
		public function getreviewThreadInfo(Request $request) {
			$validate = Validator::make($request->all(), [
                'reqID' => 'required',
                'userid' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $id = request('userid');
            $reqID = (int)request('reqID');
            if(!empty($id) && (int)$id) {
				$MainReviewData = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
				from service_request_reviews as msgmain
				left join (
					(select authid, firstname, lastname,profile_image from userdetails)
					union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
					union (select authid, firstname, lastname,profile_image from talentdetails)
					union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
				) unionSub1 on unionSub1.authid = msgmain.fromid where msgmain.id = ".$reqID." AND msgmain.isdeleted != '1'");
				
				$ThreadData = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
				from service_request_reviews as msgmain
				left join (
					(select authid, firstname, lastname,profile_image from userdetails)
					union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
					union (select authid, firstname, lastname,profile_image from talentdetails)
					union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
				) unionSub1 on unionSub1.authid = msgmain.fromid where msgmain.parent_id = ".$reqID." AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
				if(!empty($MainReviewData) && count($MainReviewData) > 0 && !empty($MainReviewData)) {
					return response()->json(['success' => true,'review'=>$MainReviewData[0],'threadData' => $ThreadData,'thread' => (int)$id],$this->successStatus);
				} else {
					return response()->json(['error'=>'validationError'], 401);
				}
			} else {
				return response()->json(['error'=>'validationError'], 401);
			}
		}
		
		public function replyReviewThread(Request $request) {
            $validate = Validator::make($request->all(), [
                'message' => 'required',
                'userid' => 'required',
                'companySlug' => 'required',
                'reqid' => 'required'
            ]); 
            
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $replyID = request('replyid');
            $iseditCase = false;
            if(!empty($replyID) && $replyID != '0') {
				$iseditCase = true;
			}
          
            
            $id = request('userid');
            if(!empty($id) && (int)$id) {
				$from_usertype = 0;
                $from_usertypedata = Auth::select('usertype')->where('id',(int)$id)->first();
                if(!empty($from_usertypedata)) {
					$from_usertype = $from_usertypedata->usertype;
                }
                $to_usertype = 0;
                $rating = 0;
                $toId = ServiceRequestReviews::select('fromid')->where('id',request('reqid'))->first();
                if(!empty($toId)) {
                    $toId = $toId->fromid;
                }
                $message = request('message');
                if($iseditCase) {
					$reviewsData  = ServiceRequestReviews::find((int)$replyID);
				} else {
					$reviewsData = new ServiceRequestReviews;
				}
                $reviewsData->fromid = (int)$id;
                $reviewsData->toid = $toId;
                $reviewsData->rating = (int)$rating;
                $reviewsData->comment = $message;
                $reviewsData->subject = 'Review';
                $reviewsData->isdeleted = '0';
                $reviewsData->requestid = 0;
                $reviewsData->from_usertype = $from_usertype;
                $reviewsData->to_usertype = $to_usertype;
                $reviewsData->parent_id = request('reqid');
                if($reviewsData->save()) {
                    $commentId = $reviewsData->id;
					$slugName = request('companySlug');
					$reviewID = request('reqid');
					$queryString = ServiceRequestReviews::select('fromid','from_usertype')->where('isdeleted','!=','1');
					$queryString = $queryString->where(function ($queryString) use ($reviewID) {
										$queryString->where('parent_id', '=', (int)$reviewID)
										->orWhere('id', '=', (int)$reviewID);
									});
					$getAllThreadUsersData = $queryString->get();
					if(!empty($getAllThreadUsersData)) {
                        if(isset($getAllThreadUsersData[0])){
                            $fromId = $getAllThreadUsersData[0]->fromid;
                            $userData = Auth::select('usertype')->where('id',$fromId)->first();
                            if(!empty($userData)) {
                                $to_usertype = $userData->usertype;
                                $notificationDate = date('Y-m-d H:i:s');    
                                $reviewID = request('reqid');
                                $this->addNotification($fromId,$to_usertype,NULL,$message,NULL,$commentId,$notificationDate,'comment');
                            }
                        }
					// 	foreach($getAllThreadUsersData as $getAllThreadUsersDatas) {
					// 		if((int)$getAllThreadUsersDatas->fromid != (int)$id) {
					// 			$messagelink = '<br><a href="https://www.marinecentral.com/detail/'.$slugName.'?reply=true&reqid='.request('reqid').'" target=”_blank” class="comment-msg">click here</a> to view comment';
					// 			$msgArr   = new Messages; 
					// 			$msgArr->message_to = (int)$getAllThreadUsersDatas->fromid;
					// 			$msgArr->message_from = (int)$id;
					// 			$msgArr->message_type = 'comment';
					// 			$msgArr->to_usertype = $getAllThreadUsersDatas->from_usertype;
					// 			$msgArr->from_usertype = $from_usertype;
					// 			$msgArr->subject = 'Review';
					// 			$msgArr->message_id = 0;
					// 			$msgArr->parent_id = 0;
					// 			$msgArr->message = request('message').' '.$messagelink;
					// 			$msgArr->request_id= (int)request('requestid');
					// 			if($msgArr->save()) {
					// 				$message_id = $msgArr->id;
					// 				$update_message_id = Messages::where('id',$message_id)->update(['message_id'=>$message_id]);
					// 			} else {
					// 				return response()->json(['error'=>'networkerror'], 401);
					// 			}
					// 		}
					// 	}
					}
					$reviewID = request('reqid');
					$queryString = ServiceRequestReviews::select('fromid','from_usertype')->where('isdeleted','!=','1');
					$queryString = $queryString->where(function ($queryString) use ($reviewID) {
										$queryString->where('parent_id', '=', (int)$reviewID)
										->orWhere('id', '=', (int)$reviewID);
									});
					$getAllThreadUsersData = $queryString->get();
					//$getAllThreadUsersData
					$ThreadData = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
					from service_request_reviews as msgmain
					left join (
						(select authid, firstname, lastname,profile_image from userdetails)
						union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
						union (select authid, firstname, lastname,profile_image from talentdetails)
						union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
					) unionSub1 on unionSub1.authid = msgmain.fromid where msgmain.parent_id = ".$reviewID." AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
					if(!empty($ThreadData) && count($ThreadData) > 0) {
						return response()->json(['success' => true,'reviewData' => $ThreadData], $this->successStatus);
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
        
        
         public function deleteReviewThread(Request $request) {
            $validate = Validator::make($request->all(), [
                'reqID' => 'required',
                'parentid' => 'required',
                'companySlug'=> 'required'
            ]); 
            
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $isParentID = (int)request('parentid');
            $requestID = (int)request('reqID');
            if($isParentID == 0) {
				$companyID = 0;
				$CompanyData = Companydetail::where('slug','=',request('companySlug'))->where('status', '!=', 'deleted')->first();
				if(!empty($CompanyData)) {
					$companyID = $CompanyData->authid;
				    $getAllNotification = Notifications::select('id','reviews','usertype')->where('authid',$companyID)->first();
                    if(!empty($getAllNotification)) {
                        $review = $getAllNotification->reviews;
                        $notificationId = $getAllNotification->id;
                        $reviews = json_decode($getAllNotification->reviews);
                        $updateReview = [];
                        $count = 0;
                        foreach($reviews as $val) {
                            if($val->from != $requestID) {
                                $updateReview[$count]['from']  = $val->from;
                                $updateReview[$count]['review']  = $val->review;
                                $updateReview[$count]['type']  = $val->type;
                                $updateReview[$count]['created_at']  = $val->created_at;
                                $updateReview[$count]['is_read']  = $val->is_read;
                                $count++;
                            }
                        }
                        if(count($updateReview)) {
                            $nObj = Notifications::find($notificationId);
                            $nObj->reviews = json_encode($updateReview);
                            $nObj->save();
                        }
                    }
                }
				$detailArr['isdeleted'] = '1';
				//$queryString = ServiceRequestReviews::where('isdeleted','!=','1');
				$queryString = '';
				$queryString = ServiceRequestReviews::where(function ($queryString) use ($requestID) {
										$queryString->where('parent_id', '=', (int)$requestID)
										->orWhere('id', '=', (int)$requestID);
									});
                $getAllThreadUsersData = $queryString->update($detailArr);
				if($getAllThreadUsersData) {
					$latestReview = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
					from service_request_reviews as msgmain
					left join (
						(select authid, firstname, lastname,profile_image from userdetails)
						union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
						union (select authid, firstname, lastname,profile_image from talentdetails)
						union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
					) unionSub1 on unionSub1.authid = msgmain.fromid where  msgmain.toid = ".(int)$companyID." AND msgmain.parent_id = 0 AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
					$rate = 0;
					$review = 0;
					$dataView = DB::table('reviewsview as r')->select(DB::raw('coalesce( r.totalreviewed , 0 ) as totalreviewed,coalesce( r.totalrating , 0 ) as totalrated'))
					->where('r.toid','=',(int)$companyID)
					->get();
					if(!empty($dataView) && count($dataView) > 0) {
						$rate = $dataView[0]->totalrated;
						$review = $dataView[0]->totalreviewed;
					}
                    return response()->json(['success' => true,'latestReview'=> $latestReview,'rating' =>$rate ,'review' => $review,'all'=>true], $this->successStatus);
				} else {
					return response()->json(['error'=>'networkerror'], 401);
				}
			} else {
				$detailArr['isdeleted'] = '1';
				//$queryString = ServiceRequestReviews::where('isdeleted','!=','1');
				$queryString = ServiceRequestReviews::where('parent_id', '=', (int)$isParentID)->Where('id', '=', (int)$requestID)->update($detailArr);
				if($queryString) {
					$MainReviewData = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
					from service_request_reviews as msgmain
					left join (
					(select authid, firstname, lastname,profile_image from userdetails)
					union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
					union (select authid, firstname, lastname,profile_image from talentdetails)
					union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
					) unionSub1 on unionSub1.authid = msgmain.fromid where msgmain.id = ".$isParentID." AND msgmain.isdeleted != '1'");
				
					$ThreadData = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
					from service_request_reviews as msgmain
					left join (
					(select authid, firstname, lastname,profile_image from userdetails)
					union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
					union (select authid, firstname, lastname,profile_image from talentdetails)
					union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
					) unionSub1 on unionSub1.authid = msgmain.fromid where msgmain.parent_id = ".$isParentID." AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
					if(!empty($MainReviewData) && count($MainReviewData) > 0 && !empty($MainReviewData)) {
						return response()->json(['success' => true,'review'=>$MainReviewData[0],'threadData' => $ThreadData,'all'=>false],$this->successStatus);
					} else {
						return response()->json(['error'=>'networkerror'], 401);
					}
				} else {
					return response()->json(['error'=>'networkerror'], 401);
				}
			}
		}
		
		public function checkReplyRequest(Request $request) {
			$validate = Validator::make($request->all(), [
                'reqID' => 'required',
                'userid' => 'required',
                'companySlug' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $id = (int)request('userid');
            $reqID = (int)request('reqID');
            $companySlug = request('companySlug');
            if(!empty($id) && (int)$id) {
				$CompanyData = Companydetail::where('slug','=',request('companySlug'))->where('status', '!=', 'deleted')->first();
				if(!empty($CompanyData)) {
					$companyID = $CompanyData->authid;
					$reviewRequest = DB::table('service_request_reviews as rv')
                        ->where('rv.parent_id',$reqID)
                        ->where('rv.isdeleted','0')
                        ->where('rv.fromid',(int)$id)
                         ->first();
                   if(!empty($reviewRequest)) {
						$MainReviewData = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
						from service_request_reviews as msgmain
						left join (
						(select authid, firstname, lastname,profile_image from userdetails)
						union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
						union (select authid, firstname, lastname,profile_image from talentdetails)
						union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
						) unionSub1 on unionSub1.authid = msgmain.fromid where msgmain.id = ".$reqID." AND msgmain.isdeleted != '1'");
				
						$ThreadData = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
						from service_request_reviews as msgmain
						left join (
						(select authid, firstname, lastname,profile_image from userdetails)
						union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
						union (select authid, firstname, lastname,profile_image from talentdetails)
						union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
						) unionSub1 on unionSub1.authid = msgmain.fromid where msgmain.parent_id = ".$reqID." AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
						if(!empty($MainReviewData) && count($MainReviewData) > 0 && !empty($MainReviewData)) {
							return response()->json(['success' => true,'review'=>$MainReviewData[0],'threadData' => $ThreadData,'thread' => (int)$id],$this->successStatus);
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

        public function getAllReviewAndComments(Request $request) {
            $id = decrypt(request('id'));
            
        }

        public function getAllNotification(Request $request) {
            $userID = decrypt(request('authid'));
            if(empty($userID)) {
                return response()->json(['error'=>'networkerror'], 401);
            } else {
                $authid = (int)$userID;
            }
            if(!empty($authid) && (int)$authid) {
                $totalCount = 0;
                $data = [];
                $notification = Notifications::select('id','service_requests','reviews','jobs','usertype')->where('authid',$authid)->first();
                $now = time(); 
                if(!empty($notification)) {
                    $serviceNotification = json_decode($notification->service_requests);
                    $reviewNotification = json_decode($notification->reviews);
                    $jobsNotification = json_decode($notification->jobs);
                    $notificationUsertype = $notification->usertype;
                    $usrId = '';
                    if(!empty($serviceNotification) && count($serviceNotification)) {
                        foreach ($serviceNotification as $key => $value) {
                            if($usrId == '') {
                                $usrId = $value->requestid;
                            } else {
                                $usrId .= ','.$value->requestid;
                            }
                        }
                        $useInfo = DB::select("SELECT  usr.id,
                                COALESCE(NULLIF(ud.firstname,''), yd.firstname) as firstname,
                                COALESCE(NULLIF(ud.lastname,''), yd.lastname) as lastname,
                                COALESCE(NULLIF(ud.profile_image,''), yd.primaryimage) as profile_image,ud.firstname as boatowner,yd.firstname as yachtowner
                                ,usr.created_at FROM users_service_requests as usr LEFT JOIN userdetails as ud ON  usr.authid = ud.authid LEFT JOIN yachtdetail as yd ON yd.authid = usr.authid
                                    WHERE usr.id  IN (".$usrId.") order by usr.id desc");
                        $businessInfo = [];
                        foreach ($serviceNotification as $key => $value) {
                            if($value->is_read != 1) {
                                $totalCount++;    
                            }
                            $your_date = strtotime($value->created_at);
                            $datediff = $now - $your_date;
                            $day =  round($datediff / (60 * 60 * 24));
                            $businessInfo[$key]['date_part'] = $day;
                            $businessInfo[$key]['id'] =  $value->requestid;
                            $businessInfo[$key]['subject'] =  $value->service_action;
                            $businessInfo[$key]['created_at'] = $value->created_at;
                            $firstname = $lastname = $profile_image = $usertype = '';
                            foreach ($useInfo as $ukey => $uval) {
                                if($uval->id == $value->requestid){
                                    $firstname = $uval->firstname;
                                    $lastname = $uval->lastname;
                                    $profile_image = $uval->profile_image;
                                    if(!empty($uval->yachtowner)) {
                                        $usertype = 'yacht';
                                    } else {
                                        $usertype = 'regular';
                                    }
                                }
                            }
                            $businessInfo[$key]['firstname'] = $firstname;
                            $businessInfo[$key]['lastname'] = $lastname;
                            $businessInfo[$key]['profile_image'] = $profile_image;
                            $businessInfo[$key]['usertype'] = $usertype;
                            $businessInfo[$key]['is_read'] = $value->is_read;
                        }
                       $useInfo = $businessInfo; 
                    }
                    $reviewId = '';
                    if(!empty($reviewNotification) && count($reviewNotification)) {
                        foreach ($reviewNotification as $key => $value) {
                            if($reviewId == '') {
                                $reviewId = $value->from;
                            } else {
                                $reviewId .= ','.$value->from;
                            }
                        }
                        $allReview =   DB::select("select msgmain.id,msgmain.fromid,msgmain.subject,COALESCE(unionSub1.firstname,NULL) as firstname, unionSub1.lastname as lastname ,unionSub1.profile_image as profile_image ,msgmain.created_at,msgmain.from_usertype,unionSub1.slug
                        from service_request_reviews as msgmain
                        left join (
                            (select authid, firstname, lastname,COALESCE(NULL,NULL) as slug,profile_image from userdetails)
                            union (select authid, firstname, lastname,COALESCE(NULL,NULL) as slug,primaryimage as profile_image from yachtdetail)
                            union (select authid, firstname, lastname,COALESCE(NULL,NULL) as slug,profile_image from talentdetails)
                            union (select authid, name as firstname,COALESCE(NULL,NULL) as lastname,slug,primaryimage as profile_image from companydetails)
                        ) unionSub1 on unionSub1.authid = msgmain.fromid where  msgmain.id IN (".$reviewId.") AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
                        $reviewInfo = [];
                        $firstname = $lastname = $profile_image = $usertype = $companyId = '';
                        foreach ($reviewNotification as $key => $value) {
                            if($value->is_read != 1) {
                                $totalCount++;    
                            }
                            $your_date = strtotime($value->created_at);
                            $datediff = $now - $your_date;
                            $day =  round($datediff / (60 * 60 * 24));
                            $reviewInfo[$key]['date_part'] = $day;
                            $reviewInfo[$key]['id'] =  $value->from;
                            $reviewInfo[$key]['subject'] =  $value->type;
                            $reviewInfo[$key]['created_at'] = $value->created_at;
                            foreach ($allReview as $ukey => $uval) {
                                if($uval->id == $value->from){
                                    $firstname = $uval->firstname;
                                    $lastname = $uval->lastname;
                                    $profile_image = $uval->profile_image;
                                    $usertype = $uval->from_usertype;
                                    $companyId = $uval->slug;
                                }
                            }
                            $reviewInfo[$key]['firstname'] = $firstname;
                            $reviewInfo[$key]['lastname'] = $lastname;
                            $reviewInfo[$key]['profile_image'] = $profile_image;
                            $reviewInfo[$key]['usertype'] = $usertype;
                            $reviewInfo[$key]['company_slug'] = $companyId;
                            $reviewInfo[$key]['is_read'] = $value->is_read;
                        }
                        $allReview = $reviewInfo;
                    }
                    //Jobs notification 
                    $jobId = '';
                    if(!empty($jobsNotification) && count($jobsNotification)) {
                        foreach ($jobsNotification as $key => $value) {
                            if($jobId == '') {
                                $jobId = $value->jobid;
                            } else {
                                $jobId .= ','.$value->jobid;
                            }
                        }
                        $allJobs = DB::select("SELECT  j.id,cd.primaryimage,cd.name as firstname,
                            COALESCE(NULLIF(cd.name,''), yd.firstname) as firstname,
                            COALESCE(NULLIF(cd.name,''), yd.lastname) as lastname,
                            COALESCE(NULLIF(cd.primaryimage,''), yd.primaryimage) as profile_image,cd.name as company,yd.firstname as yachtowner,
                            j.created_at FROM jobs as j 
                            LEFT JOIN companydetails as cd ON  j.authid = cd.authid
                            LEFT JOIN yachtdetail as yd ON  j.authid = yd.authid
                            WHERE j.id  IN (".$jobId.") order by j.id desc");
                        $jobInfo = [];
                        // echo '<pre>';print_r($allJobs);die;
                        foreach ($jobsNotification as $key => $value) {
                            if($value->is_read != 1) {
                                $totalCount++;    
                            }
                            $your_date = strtotime($value->created_at);
                            $datediff = $now - $your_date;
                            $day =  round($datediff / (60 * 60 * 24));
                            $jobInfo[$key]['date_part'] = $day;
                            $jobInfo[$key]['id'] =  $value->jobid;
                            $jobInfo[$key]['subject'] =  'job_notification';
                            $jobInfo[$key]['created_at'] = $value->created_at;
                            $firstname = '';
                            $profile_image = '';
                            $usertype = '';
                            foreach ($allJobs as $ukey => $uval) {
                                if($uval->id == $value->jobid){
                                    
                                    $profile_image = $uval->primaryimage;
                                    if(!empty($uval->yachtowner)) {
                                        $usertype = 'yacht';
                                        $firstname = $uval->firstname.' '.$uval->lastname;
                                    } else {
                                        $usertype = 'company';
                                        $firstname = $uval->firstname;
                                    }
                                }
                            }
                            $jobInfo[$key]['firstname'] = $firstname;
                            // $jobInfo[$key]['lastname'] = $lastname;
                            $jobInfo[$key]['profile_image'] = $profile_image;
                            $jobInfo[$key]['usertype'] = $usertype;
                            $jobInfo[$key]['is_read'] = $value->is_read;
                        }
                        $allJobs = $jobInfo;
                    }
                    if($notificationUsertype == 'company') {
                        if(!empty($allReview) && !empty($useInfo)) {
                        $data = array_merge($allReview,$useInfo);
                        // usort($data,function ($a, $b) use ($data) {
                        //     if ($a["created_at"] == $b["created_at"]) {return 0;}
                        //     return (strtotime($a["created_at"]) > strtotime($b["created_at"])) ? -1 : 1;
                        // });
                        } else if(!empty($allReview) && empty($useInfo)) {
                            $data = $allReview;
                        } else if(empty($allReview) && !empty($useInfo)){
                            $data = $useInfo;
                        }
                    }

                    if($notificationUsertype == 'professional') {
                        if(!empty($allReview) && !empty($jobInfo)) {
                            $data = array_merge($allReview,$jobInfo);
                            // usort($data,function ($a, $b) use ($data) {
                            //     if ($a["created_at"] == $b["created_at"]) {return 0;}
                            //     return (strtotime($a["created_at"]) > strtotime($b["created_at"])) ? -1 : 1;
                            // });
                        } else if(!empty($allReview) && empty($jobInfo)) {
                            $data = $allReview;
                        } else if(empty($allReview) && !empty($jobInfo)){
                            $data = $jobInfo;
                        }
                    } 
                    if($notificationUsertype == 'regular' || $notificationUsertype == 'yacht') {
                        $data = $allReview;
                    }
                    if(!empty($data)) {
                        usort($data,function ($a, $b) use ($data) {
                            if ($a["created_at"] == $b["created_at"]) {return 0;}
                            return (strtotime($a["created_at"]) > strtotime($b["created_at"])) ? -1 : 1;
                        });
                    }
                    // print_r($data);die;
                    return response()->json(['success' => true,'data' => $data,'total' => $totalCount], $this->successStatus);
                } else {
                    return response()->json(['success' => false,'data' => []], $this->successStatus);
                }
            } else {
                return response()->json(['success' => false,'data' => []], $this->successStatus);                    
            }
        }

        //Update Notification
        public function updateNotification(Request $request){
            $userID = decrypt(request('authid'));
            if(empty($userID)) {
                return response()->json(['error'=>'networkerror'], 401);
            } else {
                $authid = (int)$userID;
            }
            if(!empty($authid) && (int)$authid) {
                $notification = Notifications::select('id','service_requests','reviews','jobs','usertype')->where('authid',$authid)->first();
                if(!empty($notification)) {
                    $serviceNotification = (array)json_decode($notification->service_requests);
                    $reviewNotification = (array)json_decode($notification->reviews);
                    $jobsNotification = (array)json_decode($notification->jobs);
                    $notificationId = $notification->id;
                    $now = time();
                    $service_notification = $job_notification = $review_notification = [];
                    
                    if(!empty($serviceNotification) && count($serviceNotification)) {
                        $count = 0;
                        foreach ($serviceNotification as $key => $value) {
                            $notification_date = strtotime($value->created_at);
                            $datediff = $now - $notification_date;
                            $day =  round($datediff / (60 * 60 * 24));
                            if(!($day > 30)){
                               $service_notification[$count]['requestid'] =  $value->requestid;
                               $service_notification[$count]['service_action'] =  $value->service_action;
                               $service_notification[$count]['notification'] =  $value->notification;
                               $service_notification[$count]['created_at'] =  $value->created_at;
                               $service_notification[$count]['is_read'] =  1;
                               $count++;
                            }
                        }
                    }
                    if(!empty($reviewNotification) && count($reviewNotification)) {
                        $count = 0;
                        foreach ($reviewNotification as $key => $value) {
                            $notification_date = strtotime($value->created_at);
                            $datediff = $now - $notification_date;
                            $day =  round($datediff / (60 * 60 * 24));
                            if(!($day > 30)){
                               $review_notification[$count]['from'] =  $value->from;
                               $review_notification[$count]['type'] =  $value->type;
                               $review_notification[$count]['review'] =  $value->review;
                               $review_notification[$count]['created_at'] =  $value->created_at;
                               $review_notification[$count]['is_read'] =  1;
                               $count++;
                            }
                        }
                    }
                    if(!empty($jobsNotification) && count($jobsNotification)) {
                        $count = 0;
                        foreach ($jobsNotification as $key => $value) {
                            $notification_date = strtotime($value->created_at);
                            $datediff = $now - $notification_date;
                            $day =  round($datediff / (60 * 60 * 24));
                            if(!($day > 30)){
                               $job_notification[$count]['jobid'] =  $value->jobid;
                                
                               $job_notification[$count]['notification'] =  $value->notification;
                               $job_notification[$count]['created_at'] =  $value->created_at;
                               $job_notification[$count]['is_read'] =  1;
                               $count++;
                            }
                        }
                    }
                    $nObj = Notifications::find($notificationId);
                    $nObj->service_requests = json_encode($service_notification);
                    $nObj->reviews = json_encode($review_notification);
                    $nObj->jobs = json_encode($job_notification);
                    if($nObj->save()) {
                        return response()->json(['success' => true], $this->successStatus);
                    } else {
                        return response()->json(['success' => false], $this->successStatus);
                    }

                } else {
                    return response()->json(['success' => false], $this->successStatus);
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } 
        public function checkBusinessSendLeadLimit(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $currentTime = Carbon\Carbon::now();
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                 $usersdata = DB::table('companydetails')
                ->Join('paymenthistory', 'paymenthistory.companyid', '=', 'companydetails.authid')
                ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'companydetails.next_paymentplan')
                ->select('companydetails.authid','paymenthistory.created_at','paymenthistory.expiredate','subscriptionplans.planname', 'subscriptionplans.leadaccess','companydetails.subscriptiontype','companydetails.lead_payment','companydetails.servicerequest_content','companydetails.is_discount','companydetails.nextpaymentdate','companydetails.plansubtype','companydetails.remaintrial')
                ->where('companydetails.authid','=',(int)$id)
                ->where('paymenthistory.expiredate','>',$currentTime)
                ->where('paymenthistory.transactionfor','registrationfee')
                ->where('companydetails.account_type','paid')
                ->orderBy('paymenthistory.id','DESC')
                ->first();
                if(empty($usersdata)) {
                    $checkFreeAccount = DB::table('companydetails')->where('companydetails.authid','=',(int)$id)->where('account_type','=','free')->first();
                    if(!empty($checkFreeAccount) && isset($checkFreeAccount->free_subscription_period)) {
                        if($checkFreeAccount->free_subscription_period == 'unlimited'){
                           return response()->json(['success' => true,'companyleadLimit'=>9999,'leadSent' => 0,'servicerequest_content' => $checkFreeAccount->servicerequest_content],$this->successStatus);
                        } else {
                            if($checkFreeAccount->free_subscription_end > $currentTime){
                                return response()->json(['success' => true,'companyleadLimit'=>9999,'leadSent' => 0,'servicerequest_content' => $checkFreeAccount->servicerequest_content],$this->successStatus);
                            } else {
                                return response()->json(['success' => true,'companyleadLimit'=>0,'leadSent' => 0,'servicerequest_content' => $checkFreeAccount->servicerequest_content],$this->successStatus);
                            }
                        }
                    } else {
					    return response()->json(['error'=>'networkerror'], 401);
                    }
                } else {
                    
                    $freerPlan = false;
                    $day = 0;
                    if($usersdata->plansubtype == 'free' && $usersdata->remaintrial > 0) {
                        $freerPlan = true;
                        $createdDate = strtotime($usersdata->created_at);
                        $currentDates = strtotime(date('Y-m-d H:i:s'));
                        $differStrTime = $currentDates - $createdDate;
                        if($differStrTime > 0) {
                            $day = round($differStrTime/(24*60*60));
                        }
                        $remaintrial = 0;
                        if($day <= $usersdata->remaintrial) {
                            $remaintrial = $usersdata->remaintrial-$day;
                            $leadLimit = 999999;
                            $Userreqdata = 0;
                        } else {

                            $createdDate = date('Y-m-d H:i:s',strtotime("+ 60 days", strtotime($usersdata->created_at)));
                            $Userreqdata = RequestProposals::where('companyid',(int)$id)->where('created_at','>',$createdDate)->count();
                            $leadLimit = $usersdata->leadaccess + $usersdata->lead_payment;
                            //update business
                            $update = Companydetail::where('authid', '=', (int)$id)->update(['remaintrial' => $remaintrial]);
                        }
                    } else {
                        $Userreqdata = RequestProposals::where('companyid',(int)$id)->where('created_at','>=',$usersdata->created_at)->count();
                        $leadAccessLimit = 0;
                        if ((strpos('Basic', $usersdata->planname) !== false) && (($usersdata->created_at < env('BASIC_UNLIMITED_ACCESS_END')) || ($usersdata->is_discount == '1'))) {
                            $leadLimit = 999999;
                        } else {
                            $leadLimit = $usersdata->leadaccess + $usersdata->lead_payment;
                        }
                    }
                     return response()->json(['success' => true,'companyleadLimit'=>$leadLimit,'leadSent' => $Userreqdata,'servicerequest_content' => $usersdata->servicerequest_content],$this->successStatus);
                }
                
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }
        
        public function getanalytics(Request $request) {
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
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $companyData = Companydetail::select('authid','latitude','longitude','free_subscription_period')
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
                $analyticsArray = array('viewCount' => $ViewCount,'payment' => $paymentPlanArr,'latestreview' => $getTop5Review , 'myleadlimited' => $Myleads50 ,'mylead' => $Myleads ,'jobs' => $totalJobs , 'myreviews' => $MyReviewArr,'quoterequests' => $totalQuterRequests,'appeared_listing' => $appeared_in_listing,'business_contact' => $businessContactByUser);
                return response()->json(['success' => true,'data'=>$analyticsArray],$this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);
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
			$authid = decrypt($useridencrypt);
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
		
		 //contact now
        public function changeEmailAddress(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $usertype =strtolower(request('usertype'));
            $userEmail = strtolower(request('email'));
            $newsletter = (!empty(request('newsletter')) && (request('newsletter') == 'true')) ? '1':'0';
            $text_notification = (!empty(request('text_notification')) && (request('text_notification') == 'true')) ? '1':'0';
            $text_notification_other = (!empty(request('text_notification_other')) && (request('text_notification_other') == 'true')) ? '1':'0';

            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                $userData = Auth::where('email',$userEmail)->where('id',$id)->count();
                if($usertype == 'company') {
					$userDatasCheck = Auth::where('id',$id)->get();
				}
				if($userData > 0 || (!empty($userDatasCheck) && $userDatasCheck[0]->is_social == '1' && $usertype == 'company' )) {
                    $updateNewsletter =Auth::where('id','=',$id)->update(['newsletter' =>$newsletter]);
                    $updateTextAlter =Talentdetail::where('authid','=',$id)->update(['text_notification' => $text_notification,'text_notification_other' => $text_notification_other]);
                  
                    return response()->json(['success' => true,'isSame'=>true], $this->successStatus);
                } else {
                    $query = Auth::where(function ($query) use ($userEmail) {
                                        $query->where('email', '=', $userEmail)
                                        ->orWhere('requested_email', '=', $userEmail);
                                    })->where('id','!=',$id);
                    $count = $query->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
                    $query2 = dummy_registration::where('email', '=', $userEmail);
                    $count2 = $query2->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
                    $count = $count + $count2;
                    if(!empty($count) && $count > 0) {
                        return response()->json(['error'=>'networkerror'], 401);
                    } else {
                        $userDatas = Auth::where('id',$id)->get();
                        if($usertype == 'regular') {
                            $userDataDetail  =  Userdetail::where('authid',$id)->where('status','!=','deleted')->get();
                        } else if($usertype == 'yacht') {
                            $userDataDetail  =  Yachtdetail::where('authid',$id)->where('status','!=','deleted')->get();
                        } else if($usertype == 'professional') {
                            $userDataDetail  =  Talentdetail::where('authid',$id)->where('status','!=','deleted')->get();
                        } else if($usertype == 'company') {
                            $userDataDetail  =  Companydetail::where('authid',$id)->where('status','!=','deleted')->get();
                        } else {
                            return response()->json(['error'=>'networkerror'], 401);      
                        }
                        if(!empty($userDataDetail) && count($userDataDetail) > 0 && !empty($userDatas) && count($userDatas) > 0) {
                            $random_hashed = Hash::make(md5(uniqid($id, true)));
                            $updateHash = Auth::where('id','=',$id)->update(['email_hash' => $random_hashed,'requested_email' => $userEmail,'newsletter' =>$newsletter ]);
                            $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                            $link = $website_url.'/activate-email?token='.urlencode($random_hashed);
                            $ACTIVATION_LINK = $link;

                            /*
                            $ACTIVATION_OTP=rand(10000,99999);
                            $updateHash = Auth::where('id','=',$id)->update(['email_hash' => $ACTIVATION_OTP,'requested_email' => $userEmail,'newsletter' =>$newsletter ]);
                            $emailArr['otp'] = $ACTIVATION_OTP;
                            */
                            $emailArr = [];    
                            $emailArr['link'] = $ACTIVATION_LINK;
                            if($usertype == 'company') {
                                $emailArr['name'] = $userDataDetail[0]->name;
                            } else {
                                $emailArr['firstname'] = $userDataDetail[0]->firstname;
                                $emailArr['lastname'] = $userDataDetail[0]->lastname;
                                $emailArr['name'] = $emailArr['firstname'].' '.$emailArr['lastname'];
                            }
                            $emailArr['to_email'] = $userDatas[0]->email;
                            //Send activation email notification
                            $emailArr['emaillink'] = $userEmail;
                            $status1 = $this->sendEmailNotification($emailArr,'email_change_notification');
                            $emailArr['to_email'] = $userEmail;

                            $status2 = $this->sendEmailNotification($emailArr,'email_change_confirmation');
                            //~ if($status != 'sent') {
                                //~ return array('status' =>'emailsentfail');
                            //~ }
                            //~ return array('status' =>'success');
                            return response()->json(['success' => true,'isSame'=>false,'userid' => encrypt($id)], $this->successStatus);
                        } else {
                            return response()->json(['error'=>'networkerror'], 401);
                        }
                        
                    }
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);                
            }           
        }  
        
        public function deleteReviewReply(Request $request) {
            $validate = Validator::make($request->all(), [
                'replyid' => 'required',
                'userid' => 'required',
                'companySlug' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $id = (int)request('userid');
            if(!empty($id) && (int)$id) {
				$reqID = (int)request('replyid');
				$detailArr['isdeleted'] = '1';	
				$detailUpdate =  ServiceRequestReviews::where('id', '=', $reqID)->where('fromid',(int)$id)->update($detailArr);
				if($detailUpdate) {
					return response()->json(['success' => true], $this->successStatus);
				} else {
					return response()->json(['error'=>'networkerror'], 401);
				}
			} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
			
        }
        
        public function getReviewInfoProfile(Request $request) {
			$validate = Validator::make($request->all(), [
	            'userid' => 'required'
	        ]);
			if ($validate->fails()) {
				print_r($validate->message());die;
	            return response()->json(['error'=>'validationError'], 401); 
	        }
	        $id = decrypt(request('userid'));
            if(!empty($id) && (int)$id) {
				$authid = $id;
				$latestReview = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype ,rplyCmp.name as replycompanyname,rplyCmp.primaryimage as replycompanyprimaryimage,rply.created_at as replycreated_at,rply.id as replyid,rply.fromid as replyfromid,rply.comment as replycomment 
					from service_request_reviews as msgmain
					left join (
						(select authid, firstname, lastname,profile_image from userdetails)
						union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
						union (select authid, firstname, lastname,profile_image from talentdetails)
						union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
					) unionSub1 on unionSub1.authid = msgmain.fromid LEFT JOIN service_request_reviews as rply ON rply.parent_id = msgmain.id AND rply.isdeleted ='0' LEFT JOIN companydetails as rplyCmp ON rply.fromid = rplyCmp.authid where  msgmain.toid = ".(int)$authid." AND msgmain.parent_id = 0 AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
				return response()->json(['success' => true,'data' => $latestReview], $this->successStatus);
				
			} else {
				return response()->json(['success' => false,'data' => []], $this->successStatus); 
			} 
		} 
		
		public function getreviewThreadInfoProfile(Request $request) {
			$validate = Validator::make($request->all(), [
                'reqID' => 'required',
                'userid' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $id = decrypt(request('userid'));
            $reqID = (int)request('reqID');
            if(!empty($id) && (int)$id) {
				$MainReviewData = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
				from service_request_reviews as msgmain
				left join (
					(select authid, firstname, lastname,profile_image from userdetails)
					union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
					union (select authid, firstname, lastname,profile_image from talentdetails)
					union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
				) unionSub1 on unionSub1.authid = msgmain.fromid where msgmain.id = ".$reqID." AND msgmain.isdeleted != '1'");
				
				$ThreadData = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
				from service_request_reviews as msgmain
				left join (
					(select authid, firstname, lastname,profile_image from userdetails)
					union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
					union (select authid, firstname, lastname,profile_image from talentdetails)
					union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
				) unionSub1 on unionSub1.authid = msgmain.fromid where msgmain.parent_id = ".$reqID." AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
				if(!empty($MainReviewData) && count($MainReviewData) > 0 && !empty($MainReviewData)) {
					return response()->json(['success' => true,'review'=>$MainReviewData[0],'threadData' => $ThreadData,'thread' => (int)$id],$this->successStatus);
				} else {
					return response()->json(['error'=>'validationError'], 401);
				}
			} else {
				return response()->json(['error'=>'validationError'], 401);
			}
		}
		
		public function deleteReviewReplyProfile(Request $request) {
            $validate = Validator::make($request->all(), [
                'replyid' => 'required',
                'userid' => 'required',
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $id = decrypt(request('userid'));
            if(!empty($id) && (int)$id) {
				$reqID = (int)request('replyid');
				$detailArr['isdeleted'] = '1';	
				$detailUpdate =  ServiceRequestReviews::where('id', '=', $reqID)->where('fromid',(int)$id)->update($detailArr);
				if($detailUpdate) {
					return response()->json(['success' => true], $this->successStatus);
				} else {
					return response()->json(['error'=>'networkerror'], 401);
				}
			} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
			
        }     
        
        public function replyReviewThreadProfile(Request $request) {
            $validate = Validator::make($request->all(), [
                'message' => 'required',
                'userid' => 'required',
                'reqid' => 'required'
            ]); 
            
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $replyID = request('replyid');
            $iseditCase = false;
            if(!empty($replyID) && $replyID != '0') {
				$iseditCase = true;
			}
            $id = decrypt(request('userid'));
            if(!empty($id) && (int)$id) {
				$from_usertype = 0;
                $from_usertypedata = Auth::select('usertype')->where('id',(int)$id)->first();
                if(!empty($from_usertypedata)) {
					$from_usertype = $from_usertypedata->usertype;
                }
                $to_usertype = 0;
                $rating = 0;
                $toId = ServiceRequestReviews::select('fromid')->where('id',request('reqid'))->first();
                if(!empty($toId)) {
                    $toId = $toId->fromid;
                }
                $message = request('message');
                if($iseditCase) {
					$reviewsData  = ServiceRequestReviews::find((int)$replyID);
				} else {
					$reviewsData = new ServiceRequestReviews;
				}
                $reviewsData->fromid = (int)$id;
                $reviewsData->toid = $toId;
                $reviewsData->rating = (int)$rating;
                $reviewsData->comment = $message;
                $reviewsData->subject = 'Review';
                $reviewsData->isdeleted = '0';
                $reviewsData->requestid = 0;
                $reviewsData->from_usertype = $from_usertype;
                $reviewsData->to_usertype = $to_usertype;
                $reviewsData->parent_id = request('reqid');
                if($reviewsData->save()) {
                    $commentId = $reviewsData->id;
					$slugName = request('companySlug');
					$reviewID = request('reqid');
					$queryString = ServiceRequestReviews::select('fromid','from_usertype')->where('isdeleted','!=','1');
					$queryString = $queryString->where(function ($queryString) use ($reviewID) {
										$queryString->where('parent_id', '=', (int)$reviewID)
										->orWhere('id', '=', (int)$reviewID);
									});
					$getAllThreadUsersData = $queryString->get();
					if(!empty($getAllThreadUsersData)) {
                        if(isset($getAllThreadUsersData[0])){
                            $fromId = $getAllThreadUsersData[0]->fromid;
                            $userData = Auth::select('usertype')->where('id',$fromId)->first();
                            if(!empty($userData)) {
                                $to_usertype = $userData->usertype;
                                $notificationDate = date('Y-m-d H:i:s');    
                                $reviewID = request('reqid');
                                $this->addNotification($fromId,$to_usertype,NULL,$message,NULL,$commentId,$notificationDate,'comment');
                            }
                        }
					}
					$reviewID = request('reqid');
					$queryString = ServiceRequestReviews::select('fromid','from_usertype')->where('isdeleted','!=','1');
					$queryString = $queryString->where(function ($queryString) use ($reviewID) {
										$queryString->where('parent_id', '=', (int)$reviewID)
										->orWhere('id', '=', (int)$reviewID);
									});
					$getAllThreadUsersData = $queryString->get();
					//$getAllThreadUsersData
					$ThreadData = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype
					from service_request_reviews as msgmain
					left join (
						(select authid, firstname, lastname,profile_image from userdetails)
						union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
						union (select authid, firstname, lastname,profile_image from talentdetails)
						union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
					) unionSub1 on unionSub1.authid = msgmain.fromid where msgmain.parent_id = ".$reviewID." AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
					if(!empty($ThreadData) && count($ThreadData) > 0) {
						return response()->json(['success' => true,'reviewData' => $ThreadData], $this->successStatus);
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
        
        //Get Business details by id
        public function getBusinessContent(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required'
            ]);
            $userid = request('id');
            $decryptUserid = decrypt($userid);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $data = Companydetail::where('authid',(int)$decryptUserid)->where('status','!=','deleted')->first();
            if(!empty($data)) {
				return response()->json(['success' => true,'data' => $data], $this->successStatus);
			} else {
				return response()->json(['success' => false,'data' => []], $this->successStatus);
			}
        } 
        //Get P details by id
        public function getProfessionalContent(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required'
            ]);
            $userid = request('id');
            $decryptUserid = decrypt($userid);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $data = Talentdetail::where('authid',(int)$decryptUserid)->where('status','!=','deleted')->first();
            if(!empty($data)) {
				return response()->json(['success' => true,'data' => $data], $this->successStatus);
			} else {
				return response()->json(['success' => false,'data' => []], $this->successStatus);
			}
        } 
        //Get Yacht details by id
        public function getYachtContent(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required'
            ]);
            
            $userid = request('id');
            $decryptUserid = decrypt($userid);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $data = Yachtdetail::where('authid',(int)$decryptUserid)->where('status','!=','deleted')->first();
            if(!empty($data)) {
				return response()->json(['success' => true,'data' => $data], $this->successStatus);
			} else {
				return response()->json(['success' => false,'data' => []], $this->successStatus);
			}
        } 
        //Get User details by id
        public function getUserContent(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required'
            ]);
            $userid = request('id');
            $decryptUserid = decrypt($userid);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $data = Userdetail::where('authid',(int)$decryptUserid)->where('status','!=','deleted')->first();
            if(!empty($data)) {
				return response()->json(['success' => true,'data' => $data], $this->successStatus);
			} else {
				return response()->json(['success' => false,'data' => []], $this->successStatus);
			}
        } 
        
        // update request content //
	    public function changeRequestContent(Request $request) {
	        $validate = Validator::make($request->all(), [
	            'id' => 'required',
	            'request' => 'required',
	        ]);
	   
	        if ($validate->fails()) {
	           return response()->json(['error'=>'validationError'], 401); 
	        }
	        $userid = request('id');
        	$authid = decrypt($userid);
			if(!empty($authid) && $authid > 0) {
				$updatesContent['servicerequest_content'] = request('request');
				$updated =  Companydetail::where('authid', '=', (int)$authid)->where('status', '!=', 'deleted')->update($updatesContent);
				if($updated) {
					return response()->json(['success' => true], $this->successStatus);
				} else {
					return response()->json(['error'=>'networkerror'], 401);
				}
			} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
	    }
	    
	    // update quotes content //
	    public function changeQuoteContent(Request $request) {
	        $validate = Validator::make($request->all(), [
	            'id' => 'required',
	            'request' => 'required',
	            'type' => 'required'
	        ]);
	   
	        if ($validate->fails()) {
	           return response()->json(['error'=>'validationError'], 401); 
	        }
	        $userid = request('id');
        	$authid = decrypt($userid);
        	$userType = request('type');
			if(!empty($authid) && $authid > 0) {
				$updatesContent['quote_content'] = request('request');
				if($userType == 'company') {
					$updated =  Companydetail::where('authid', '=', (int)$authid)->where('status', '!=', 'deleted')->update($updatesContent);
				} else if($userType == 'regular') {
					$updated =  Userdetail::where('authid', '=', (int)$authid)->where('status', '!=', 'deleted')->update($updatesContent);
				} else if($userType == 'professional') {
					$updated =  Talentdetail::where('authid', '=', (int)$authid)->where('status', '!=', 'deleted')->update($updatesContent);
				} else if($userType == 'yacht') {
					$updated =  Yachtdetail::where('authid', '=', (int)$authid)->where('status', '!=', 'deleted')->update($updatesContent);
				}
				if($updated) {
					return response()->json(['success' => true], $this->successStatus);
				} else {
					return response()->json(['error'=>'networkerror'], 401);
				}
			} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
	    }
	    // update quotes content //
	    public function changeContactContent(Request $request) {
	        $validate = Validator::make($request->all(), [
	            'id' => 'required',
	            'request' => 'required',
	        ]);
	   
	        if ($validate->fails()) {
	           return response()->json(['error'=>'validationError'], 401); 
	        }
	        $userid = request('id');
        	$authid = decrypt($userid);
			if(!empty($authid) && $authid > 0) {
				$updatesContent['contact_content'] = request('request');
				$updated =  Companydetail::where('authid', '=', (int)$authid)->where('status', '!=', 'deleted')->update($updatesContent);
				if($updated) {
					return response()->json(['success' => true], $this->successStatus);
				} else {
					return response()->json(['error'=>'networkerror'], 401);
				}
			} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
	    }
	    
	    // update quotes content //
	    public function changeJobContent(Request $request) {
	        $validate = Validator::make($request->all(), [
	            'id' => 'required',
	            'request' => 'required',
	        ]);
	   
	        if ($validate->fails()) {
	           return response()->json(['error'=>'validationError'], 401); 
	        }
	        $userid = request('id');
        	$authid = decrypt($userid);
			if(!empty($authid) && $authid > 0) {
				$updatesContent['applyjob_content'] = request('request');
				$updated =  Talentdetail::where('authid', '=', (int)$authid)->where('status', '!=', 'deleted')->update($updatesContent);
				if($updated) {
					return response()->json(['success' => true], $this->successStatus);
				} else {
					return response()->json(['error'=>'networkerror'], 401);
				}
			} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
	    }
        public function getAllBusinessCards(Request $request){
            $cvalue = $request->bearerToken();
            $cid= (new Parser())->parse($cvalue)->getHeader('jti');
            $cAuthid = DB::table('oauth_access_tokens')->where('id', '=', $cid)->where('revoked', '=', false)->first();
            $cAuthid =  $cAuthid->user_id;
            $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
            $authid = request('authid');
            $decryptUserid = decrypt($authid);
            if((int)$decryptUserid &&  ((int)$cAuthid == (int)$decryptUserid)) {
                $cusId = Companydetail::Select('customer_id','subscription_id')->where('authid',$decryptUserid)->first();
                if(!empty($cusId)) {
                    $subPaymentCard = [];
                    $otherCards = $paypalAccountsInfo = [];
                    $customerId = $cusId->customer_id;
                    $subscription = $cusId->subscription_id;
                    if(empty($customerId)) {
                        return response()->json(['error'=>'Customer id doest exist.'], 401);
                    }
                    $allCard = [];
                    try {
                        $customer = $stripe->customers()->find($customerId);
                        $default_card = $customer['default_source'];
                        if(isset($customer['sources']['data']) && count($customer['sources']['data'])) {
                            foreach ($customer['sources']['data'] as $key => $card) {
                                
                                if($card['id'] == $default_card){
                                    $allCard[$key]['tokenhash'] = $subPaymentCard['tokenhash'] = encrypt($card['id']);
                                    $allCard[$key]['expirationDate'] = $subPaymentCard['expirationDate'] = $card['exp_month'].'/'.$card['exp_year'];
                                    $allCard[$key]['last4'] = $subPaymentCard['last4'] = $card['last4'];
                                    $allCard[$key]['aType'] = $subPaymentCard['aType'] = $card['brand'];
                                    $allCard[$key]['default'] = true;
                                    $subPaymentCard['payment_type'] = 'card';
                                } else {
                                    $allCard[$key]['tokenhash'] = $otherCards[$key]['tokenhash'] = encrypt($card['id']);
                                    $allCard[$key]['expirationDate'] = $otherCards[$key]['expirationDate'] = $card['exp_month'].'/'.$card['exp_year'];
                                    $allCard[$key]['last4'] = $otherCards[$key]['last4'] = $card['last4'];
                                    $allCard[$key]['aType'] = $otherCards[$key]['aType'] = $card['brand'];
                                    $allCard[$key]['default'] = false;
                                    $otherCards[$key]['payment_type'] = 'card';
                                }
                            }
                        }
                        $subpayment = $showotherCards = $showPaypalAccount = $totalCard = false;

                        if(count($subPaymentCard)) {
                            $subpayment = true;
                        }
                        if(count($otherCards)) {
                            $showotherCards = true;
                        }
                        if(count($paypalAccountsInfo)) {
                            $showPaypalAccount = true;
                        }

                        if(count($allCard)) {
                            $totalCard = true;
                        }

                        return response()->json(['success' => true,'data' => ['otherCards' => $otherCards,'subscriptionMethod'=>$subPaymentCard,'showSubscriptionPlan' => $subpayment,'showOtherCards'=>$showotherCards,'showPaypalAccount' => $showPaypalAccount,'paypalAccount' => $paypalAccountsInfo,'totalCard' => $totalCard,'allCard' => $allCard]], $this->successStatus);
                    } catch(Exception $e) {
                        // if(get_class($e) == 'Cartalyst\Stripe\Exception\NotFoundException') {
                        //     return response()->json(['error'=> 'No such Customer or token found.'], 401);    
                        // }
                        return response()->json(['error'=>$e->getMessage()], 401); 
                    } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
                        $ex_message = $e->getMessage();
                        return response()->json(['error'=>$e->getMessage()], 401); 
                    } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
                        $ex_message = $e->getMessage();
                        return response()->json(['error'=>$e->getMessage()], 401); 
                    }
                } else {
                    return response()->json(['error'=>'User not found.'], 401);                
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);                
            }
        }


        public function deleteToken(Request $request) {
            $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
            $authid = request('authid');
            $token = decrypt(request('tokenhash'));
            $decryptUserid = decrypt($authid);
            if((int)$decryptUserid && !empty($token)) {
                $cusId = Companydetail::Select('customer_id','subscription_id')->where('authid',$decryptUserid)->first();
                if(!empty($cusId)) {
                    $customerId = $cusId->customer_id;
                    try {
                        $customer = $stripe->customers()->find($customerId);
                        if($customer['default_source'] == $token) {
                            return response()->json(['error' => 'You can not delete payment default card.'],401);
                        }
                        $card = $stripe->cards()->delete($customerId,$token);
                        return response()->json(['success' => true], $this->successStatus);
                    } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
                        return response()->json(['error'=>$e->getMessage()], 401); 
                    } catch(Exception $e) {
                        if(get_class($e) == 'Cartalyst\Stripe\Exception\NotFoundException') {
                            return response()->json(['error'=> 'No such Customer or token found.'], 401);    
                        }
                        return response()->json(['error'=>$e->getMessage()], 401);
                    }
                } else {
                    return response()->json(['error'=>'Record not found.'], 401);
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }
        }
        
        // get business leads
	    public function getBusinessDashboardLead(Request $request) {
			$id = decrypt(request('id'));
			
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

        public function addPaymentMethod(Request $request){
            $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
            $rid = request('authid');
            $decryptUserid = (int)decrypt($rid);
            $token = request('token');
			$usersdata =CompanyDetail::where('authid', '=', (int)$decryptUserid)->first();
            if(!empty($usersdata) && !empty($token)) {
                $ismakeprimary = request('ismakeprimary');
                $cardholder = request('cardholder');
                $isprimary = false;
                if($ismakeprimary == 'true') {
                    $isprimary = true;
                }
                $customer_id = $usersdata->customer_id;
                if(!empty($customer_id)) {
                    try {
                        $card = $stripe->cards()->create($customer_id, $token);
                        if($isprimary) {
                            $cardId = $card['id'];
                            $customer = $stripe->customers()->update($customer_id, [
                                'default_source' => $cardId
                            ]);
                        }
                        return response()->json(['success' => true,],$this->successStatus);
                    } catch(Exception $e) {
                        if(get_class($e) == 'Cartalyst\Stripe\Exception\NotFoundException') {
                            return response()->json(['error'=> 'No such Customer or token found.'], 401);    
                        }
                        return response()->json(['error'=>$e->getMessage()], 401); 
                    } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
                        $ex_message = $e->getMessage();
                        return response()->json(['error'=>$e->getMessage()], 401); 
                    } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
                        $ex_message = $e->getMessage();
                        return response()->json(['error'=>$e->getMessage()], 401); 
                    }
                } else {
                    return response()->json(['error'=>'User not found'], 401);
                }
            } else {
                return response()->json(['error'=>'User not found'], 401);
            }
        }
        //Update default payment card
        public function changeDefaultPaymentCard(Request $request){
            $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
            $authid = request('authid');
            $token = decrypt(request('tokenhash'));
            $decryptUserid = decrypt($authid);
            $cusId = Companydetail::Select('customer_id','subscription_id')->where('authid',$decryptUserid)->first();
            if(!empty($cusId)) {
                $subPaymentCard = [];
                $otherCards = [];
                $customerId = $cusId->customer_id;
                $subId = $cusId->subscription_id;
                if((int)$decryptUserid && !empty($token)) {
                    try {
                        $customer = $stripe->customers()->update($customerId, [
                            'default_source' => $token
                        ]);
                        return response()->json(['success' => true,],$this->successStatus);
                    } catch(Exception $e) {
                        if(get_class($e) == 'Cartalyst\Stripe\Exception\NotFoundException') {
                            return response()->json(['error'=> 'No such Customer or token found.'], 401);    
                        }
                        return response()->json(['error'=>$e->getMessage()], 401); 
                    } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
                        return response()->json(['error'=>$e->getMessage()], 401); 
                    } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
                        return response()->json(['error'=>$e->getMessage()], 401); 
                    }
                } else {
                    return response()->json(['error'=>'There seems to be a network error.'], 401);
                }
            } else {
                return response()->json(['error'=>'User not found.'], 401);    
            }
        }

        public function getBraintreeTokenUser() {
            return response()->json([
                'token' => Braintree_ClientToken::generate(),
            ]);
        }

        //Add Website Reivews
        public function sendWebsiteReviewandRating(Request $request) {
            $validate = Validator::make($request->all(), [
                'rating' => 'required',
                'comment' => 'required',
                'authid' => 'required',
                'reviewid' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $reviewid = (int)request('reviewid');
            $rating = request('rating');
            $message = request('comment');
            $firstNotificaiton = true; 
            if(!empty($reviewid) || $reviewid != '0') {
                $firstNotificaiton = false;
                $reviewsData = WebsiteReviews::find($reviewid);
                $reviewsData->rating = (int)$rating +1;
                $reviewsData->comment = $message;
            } else {
                $reviewsData = new WebsiteReviews;
                $reviewsData->authid = (int)request('authid');
                $reviewsData->comment = $message;
                $reviewsData->rating = (int)$rating +1;
                $reviewsData->isdeleted = '0';
            }
            if($reviewsData->save()) {
				$website_url = env('NG_APP_URL','https://www.marinecentral.com');
				$Authdata = Auth::where('id',(int)request('authid'))->where('status','!=','deleted')->get();
				if(!empty($Authdata) && count($Authdata) > 0) {
					if($Authdata[0]->usertype == 'yacht') {
                        $select_sender = 'firstname,lastname';
                        $table_sender = 'yachtdetail';
                    } else if($Authdata[0]->usertype == 'company') {
                        $select_sender = 'name as firstname,slug';
                        $table_sender = 'companydetails';
                    } else if($Authdata[0]->usertype == 'regular') {
                        $select_sender = 'firstname,lastname';
                        $table_sender = 'userdetails';
                    } else if($Authdata[0]->usertype == 'professional') {
                        $select_sender = 'firstname,lastname';
                        $table_sender = 'talentdetails';
                    } 
                    $user_detail = DB::table($table_sender)->select(DB::Raw($select_sender))->where('authid',(int)request('authid'))->get();
                    $userName = '';
                    $userlink = '';
					if(!empty($user_detail) && count($user_detail) > 0) {
						if($Authdata[0]->usertype == 'yacht') {
							$userName = $user_detail[0]->firstname.' '.$user_detail[0]->lastname;
							$userlink = $website_url.'/yacht-detail/'.$Authdata[0]->id;
						} else if($Authdata[0]->usertype == 'company') {
							$userName = $user_detail[0]->firstname;
							$userlink = $website_url.'/biz/'.$user_detail[0]->slug;
						} else if($Authdata[0]->usertype == 'regular') {
							$userName = $user_detail[0]->firstname.' '.$user_detail[0]->lastname;
							$userlink = $website_url.'/boat-owner-detail/'.$Authdata[0]->id;
						} else if($Authdata[0]->usertype == 'professional') {
							$userName = $user_detail[0]->firstname.' '.$user_detail[0]->lastname;
							$userlink = $website_url.'/job-seeker-detail/'.$Authdata[0]->id;
						}
						$emailArr = [];  
						$emailArr['username'] = $userName;
						$emailArr['link'] = $userlink.'?cf=marine';
						//$emailArr['name'] = $detailBiz->name;
						$emailArr['to_email'] = 'info@marinecentral.com';
						$emailArr['rating'] = (int)$rating +1;
						$emailArr['review'] = $message;
						//Send activation email notification
						$status = $this->sendEmailNotification($emailArr,'website_rating_notification');
						$reviewsID = $reviewsData->id;
						return response()->json(['success' => true,'review' => $reviewsID], $this->successStatus);
            
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

        public function checkWebsiteReviewandRating(Request $request) {
            $validate = Validator::make($request->all(), [
                'authid' => 'required'
            ]);
            $userid = request('authid');
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $data = WebsiteReviews::where('authid',$userid)->first();
            if(!empty($data)) {
                return response()->json(['success' => true,'data' => $data,'reviewExist' => true], $this->successStatus);
            } else {
                return response()->json(['success' => false,'reviewExist' => false ,'data' => []], $this->successStatus);
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

        public function braintreeTransaction($userID,$subplan,$card_token,$cardHolder,$type,$degrade) {
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
    		if(!empty($PlanData)) {
    			$basicTrialDays = 0;
                /*Old Payment
    			if (strpos('Basic', $PlanData[0]->planname) !== false) {
    				$currentDate = date('Y-m-d 00:00:00');
    				if(env('BASIC_PLAN_UNLIMITED_END') > $currentDate) {
    					$basicTrialDays = 60;
    				}
    			}
                */
    		} else {
    			return 'network';
    		}
            $renewPlan = $usersdata->subscriptiontype;
            $isRecur = false;
            if($renewPlan == 'automatic' || $renewPlan == null ) {
                $isRecur = true;
            } else {
                $isRecur = false;
            }
            $isdiscountremain = false;
            $DateNext = $usersdata->nextpaymentdate;
            $customer_id = $usersdata->customer_id;
            //Check if user day left for previous plan
            $IsDayLeft = false;
            $days = 0;

            if($usersdata->plansubtype != 'free' && $degrade){
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
                $plan_id =$PlanData[0]->stripe_plan_id;
                
                $isPending = true;
                $subscription_id = $usersdata->subscription_id;
                //Card Token Setup
                try {
                    $cardId=$card_token;
                    if($type =='new'){
                        $card = $stripe->cards()->create($customer_id, $card_token);
                        $cardId = $card['id'];
                    }
                    
                    $customer = $stripe->customers()->update($customer_id, [
                        'default_source' => $cardId
                    ]);
                }  catch(Exception $e) {
                    return $e->getMessage();
                }
                //Delete prevous subscription if exist
                if($usersdata->subscription_id != null) {
                    try {
                        $subscription = $stripe->subscriptions()->cancel($customer_id, $subscription_id);
                    } catch(\Cartalyst\Stripe\Exception\NotFoundException $e) {
                        $subscription_id = NULL;
                    } catch(Exception $e) {
                        return $e->getMessage();
                    }
                } 
                $chargeTrs=''; 
                try { 
                    if($isRecur){ //Create a subscription
                        if($IsDayLeft) {
                            $subscription = $stripe->subscriptions()->create($customer_id, [
                                'plan' => $plan_id,
                                'trial_end' => strtotime( '+'.$days.' day' ),
                                'metadata' => ['name' => $cardHolder]
                            ]);
                        } else {
                            $subscription = $stripe->subscriptions()->create($customer_id, [
                                'plan' => $plan_id,
                                'metadata' => ['name' => $cardHolder]
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
        public function checkBusinessSendQuoteLimit(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $currentTime = Carbon\Carbon::now();
            $id = decrypt(request('id'));
            if(!empty($id) && (int)$id) {
                 $usersdata = DB::table('companydetails')
                ->Join('paymenthistory', 'paymenthistory.companyid', '=', 'companydetails.authid')
                ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'companydetails.next_paymentplan')
                ->select('paymenthistory.created_at','paymenthistory.expiredate','subscriptionplans.planname', 'subscriptionplans.leadaccess','companydetails.subscriptiontype','companydetails.quotes_payment','companydetails.quote_content','companydetails.is_discount','companydetails.nextpaymentdate','companydetails.plansubtype','companydetails.remaintrial')
                ->where('companydetails.authid','=',(int)$id)
                ->where('paymenthistory.expiredate','>',$currentTime)
                ->where('paymenthistory.transactionfor','registrationfee')
                ->where('companydetails.account_type','paid')
                ->orderBy('paymenthistory.id','DESC')
                ->first();
                if(empty($usersdata)) {
                    $checkFreeAccount = DB::table('companydetails')->where('companydetails.authid','=',(int)$id)->where('account_type','=','free')->first();
                    if(!empty($checkFreeAccount) && isset($checkFreeAccount->free_subscription_period)) {
                        if($checkFreeAccount->free_subscription_period == 'unlimited'){
                           return response()->json(['success' => true,'companyquoteLimit'=>99999,'quoteSent' => 0,'quote_content' => $checkFreeAccount->quote_content],$this->successStatus);
                        } else {
                            if($checkFreeAccount->free_subscription_end > $currentTime){
                                return response()->json(['success' => true,'companyquoteLimit'=>99999,'quoteSent' => 0,'quote_content' => $checkFreeAccount->quote_content],$this->successStatus);
                            } else {
                                return response()->json(['success' => true,'companyquoteLimit'=>0,'quoteSent' => 0,'quote_content' => $checkFreeAccount->quote_content],$this->successStatus);
                            }
                        }
                    } else {
                        return response()->json(['error'=>'planExpireError'], 401);
                    }
                } else {
                    $freerPlan = false;
                    $day = 0;
                    if($usersdata->plansubtype == 'free'  && $usersdata->remaintrial > 0) {
                        $freerPlan = true;
                        $createdDate = strtotime($usersdata->created_at);
                        $currentDates = strtotime(date('Y-m-d H:i:s'));
                        $differStrTime = $currentDates - $createdDate;
                        if($differStrTime > 0) {
                            $day = round($differStrTime/(24*60*60));
                        }
                        $remaintrial = 0;
                        if($day <= $usersdata->remaintrial) {
                            $remaintrial = $usersdata->remaintrial-$day;
                            $leadLimit = 999999;
                            $Userreqdata = 0;
                        } else {
                            $createdDate = date('Y-m-d H:i:s',strtotime("+ 60 days", strtotime($usersdata->created_at)));
                            $Userreqdata = Quoterequests::where('userid',(int)$id)->where('created_at','>',$createdDate)->count();
                            $leadLimit = $usersdata->quotes_payment;
                            $update = Companydetail::where('authid', '=', (int)$id)->update(['remaintrial' => $remaintrial]);
                        }

                        
                        return response()->json(['success' => true,'companyquoteLimit'=>$leadLimit,'quoteSent' => $Userreqdata,'quote_content' => $usersdata->quote_content],$this->successStatus);
                        //update business
                    } else {
                        return response()->json(['success' => true,'companyquoteLimit'=>99999,'quoteSent' => 0,'quote_content' => $usersdata->quote_content],$this->successStatus);
                    }
                     
                }
                
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }
	}
?>
