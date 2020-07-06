<?php 
	namespace App\Http\Controllers;
	use Illuminate\Http\Request;
	use Lcobucci\JWT\Parser;
	use App\Http\Traits\LocationTrait;
	use App\Auth;
	use DB;
	use App\Userdetail;
	use App\Service;
	use App\Companydetail;
	use App\Talentdetail;
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
	use App\Messages;
	use App\Geolocation;
	use App\jobsviewpage;
	use App\Professionalviewpage;
	use App\Apply_Jobs;
	use App\Subcategory;
	use App\Advertisement;
	use App\dummy_registration;
	use App\Contactus;
	use App\Boat_Engine_Companies;
	use App\Blogs;
	use App\Businesslistingcount;
	use App\Businesstelephone;
	use App\Jobs\SendSmsToBusinesses;
	use Carbon;
	use App\Http\Traits\NotificationTrait;
	use App\Http\Traits\SpellcheckerTrait;
	use Illuminate\Support\Facades\Validator;
    use App\Badword;
    use Aws\S3\S3Client;
    use Geocoder;

	class HomeController extends Controller
	{	
		use LocationTrait;
		use NotificationTrait;
		use SpellcheckerTrait;
		public $successStatus = 200;
	 	public function __construct(Request $request) {
    	}
    	
    	//Get all businesses based on longitude and latitude NewCode
    	public function getAllBusinessesByLocation(Request $request) {
    		$servicename = request('servicename');
        	$address = request('locations');
        	$distance = (request('distance')!= null) ? request('distance'):20;
        	$orderBy = request('sortby');
        	$price = request('price');
        	$filter_services = json_decode(request('service_filter'));
        	$longitude =0;
        	$latitude = 0;
        	$isExactSearch = request('isExactSearch');
        	$ExactMatch = false;
        	$companyExactMatch = false;
        	if($isExactSearch == 'true') {
				$ExactMatch = true;
			} else {
				$ExactMatch = false;
			}
        	
        	if(!empty($address)) {
				$output = $this->getGeoLocation($address);
	        	$longitude = $output['longitude'];
	            $latitude = $output['latitude'];            	
	            if($longitude == 0 && $latitude == 0) {
					return response()->json(['error'=>'networkerror','correctsearch' => $servicename ], 401);
            	}
            } else {
            	$longitude = '-81.515754';
	            $latitude = '27.664827';
            }
            $whereServiceName = '';
            $sameServiceSugestion = false;
            $correctedServiceName = $correctedServiceNameQuery = $notUriServicename = $notUricorrectedServiceName = $notUricorrectedServiceNameQuery = '';
            /* Check Sevicename exist in service table*/
            if((!empty($filter_services)) || (!empty($servicename) && $servicename !='')) {
				
            	$fitlerservices = $filter_services;
            	$boatOrYachtOrEngine = $boatOrYachtOrEngineDic = '';
				if(!empty($servicename) && $servicename !='') {
					$boatOrYachtOrEngine = $servicename;
					$this->checkSuggestionInit($servicename);
					$correctedServiceNameArr =  $this->corrected();
					$boatOrYachtOrEngineDic = $correctedServiceName = $correctedServiceNameArr['word'];
					if($correctedServiceNameArr['match']) {
						$companyExactMatch = true;
					}
					if($correctedServiceName != 'error') {
						$notUricorrectedServiceName = implode("''",explode("'",trim($correctedServiceName)));
						$correctedServiceName = urlencode(trim($correctedServiceName));
						$correctedServiceName =  implode(' & ',explode('+',$correctedServiceName));
						if($ExactMatch) {
							$correctedServiceNameQuery = " OR to_tsvector('english',name) @@ to_tsquery('english','".$correctedServiceName."') OR to_tsvector('english',about) @@ to_tsquery('english','".$correctedServiceName."') ";
						} else {
							$correctedServiceNameQuery = "";
						}
					}
					$notUriServicename = implode("''",explode("'",trim($servicename)));
					$servicename = urlencode(trim($servicename));
					$servicename =  implode('% %',explode('+',$servicename));
					if($servicename == $correctedServiceName) {
						$sameServiceSugestion = true;
					} else {
						$sameServiceSugestion = false;
					}
					if($ExactMatch) {
						$serviceNameQuery="OR to_tsvector('english',s.service) @@ to_tsquery('english','".$correctedServiceName."')";
					} else {
						$serviceNameQuery = '';
					}
					
					if($boatOrYachtOrEngine != '') {
						$boatOrYachtOrEngineTS = urlencode(trim($boatOrYachtOrEngine));
						$boatOrYachtOrEngineTS =  implode(' & ',explode('+',$boatOrYachtOrEngineTS));
						$boatOrYachtOrEngineDicTS = urlencode(trim($boatOrYachtOrEngineDic));
						$boatOrYachtOrEngineDicTS =  implode(' & ',explode('+',$boatOrYachtOrEngineDicTS));
						if($ExactMatch) {
							$getbYorEngineName = DB::select("SELECT id,name from boat_engine_companies where status = '1' and ( name ILIKE '%".$boatOrYachtOrEngineDic."%' OR to_tsvector('english',name) @@ to_tsquery('english','".$boatOrYachtOrEngineDicTS."'))");
						} else {
							$getbYorEngineName = DB::select("SELECT id,name from boat_engine_companies where status = '1' and (name ILIKE '%".$boatOrYachtOrEngine."%' OR to_tsvector('english',name) @@ to_tsquery('english','".$boatOrYachtOrEngineTS."'))");
						}
					}
					
					if(!empty($getbYorEngineName)) {
						foreach ($getbYorEngineName as $skey => $sval) {
							$jsonArrNameEngine = [];
							$jsonArrNameEngine['saved'] = [$sval->id];
							if($whereServiceName != '' ) {
								$whereServiceName = $whereServiceName." OR boats_yachts_worked::jsonb @> '".json_encode($jsonArrNameEngine)."' OR engines_worked::jsonb @> '".json_encode($jsonArrNameEngine)."'";
							} else {
								$whereServiceName = "AND ( boats_yachts_worked::jsonb @> '".json_encode($jsonArrNameEngine)."' OR engines_worked::jsonb @> '".json_encode($jsonArrNameEngine)."'";
							}
						}
					}
					if($boatOrYachtOrEngine != '') {
						//$jsonArrNameEngine['others'] = $boatOrYachtOrEngine;
						//$jsonArrNameEngineDic['others'] = $boatOrYachtOrEngineDic;
						if($whereServiceName != '' ) {
							if($ExactMatch) {
								$whereServiceName = $whereServiceName."OR boats_yachts_worked::jsonb->>'other' ILIKE '%".$boatOrYachtOrEngineDic."%' OR engines_worked::jsonb->>'other' ILIKE '%".$boatOrYachtOrEngineDic."%'";
							} else {
								$whereServiceName = $whereServiceName." OR boats_yachts_worked::jsonb->>'other' ILIKE '%".$boatOrYachtOrEngine."%' OR engines_worked::jsonb->>'other' ILIKE '%".$boatOrYachtOrEngine."%' ";
							}
						} else {
							if($ExactMatch) {
								$whereServiceName = "AND ( boats_yachts_worked::jsonb->>'other' like '%".$boatOrYachtOrEngineDic."%' OR engines_worked::jsonb->>'other' like '%".$boatOrYachtOrEngineDic."%'";
							} else {
								$whereServiceName = "AND ( boats_yachts_worked::jsonb->>'other' like '%".$boatOrYachtOrEngine."%' OR engines_worked::jsonb->>'other' like '%".$boatOrYachtOrEngine."%'";
							}
						}
					}
					
					if($ExactMatch) {
						$getAllServiceName = DB::select("SELECT s.id,s.service,s.category from services as s  where s.status = '1' and (s.service ILIKE '%".$servicename."%' ".$serviceNameQuery.")");
					} else {
						$getAllServiceName = DB::select("SELECT s.id,s.service,s.category from services as s  where s.status = '1' and (s.service ILIKE '%".$notUriServicename."%' ".$serviceNameQuery.")");
					}
					if(!empty($getAllServiceName)) {
						foreach ($getAllServiceName as $skey => $sval) {
							$jsonArrName = [];
							$jsonArrName[$sval->category] = [$sval->id];
							if($whereServiceName != '' ) {
								$whereServiceName = $whereServiceName." OR services::jsonb @> '".json_encode($jsonArrName)."'";
							} else {
								$whereServiceName = "AND ( services::jsonb @> '".json_encode($jsonArrName)."'";
							}
						}
					}
					
					$searchserviceName = '';
					$searchCompanyName = '';
					$searchExactCompanyQuery = '';
					
					if($ExactMatch) {
						$searchserviceName = $notUriServicename;
						$searchCompanyName = $notUriServicename;
					} else {
						$searchserviceName = $servicename;
						$searchCompanyName = $servicename;
					}
					if($companyExactMatch) {
						$searchCompanyName = $notUriServicename;
					}
					
					if($whereServiceName != '' && (!empty($servicename) && $servicename !='')) {
						$whereServiceName = $whereServiceName ." OR name ILIKE '%".$searchCompanyName."%' OR about ILIKE '%".$searchserviceName."%' ".$correctedServiceNameQuery." ";
						$notUricorrectedServiceNameQuery = "AND (name ILIKE '%".$searchCompanyName."%' OR about ILIKE '%".$searchserviceName."%' ";
					} else if ($whereServiceName == '' && (!empty($servicename) && $servicename !='')){
						$whereServiceName = " AND (name ILIKE '%".$searchCompanyName."%' OR about ILIKE '%".$searchserviceName."%' ".$correctedServiceNameQuery." ";
						$notUricorrectedServiceNameQuery = "AND (name ILIKE '%".$searchCompanyName."%' about ILIKE '%".$searchserviceName."%' ";
					}
            	}
            	
            	///////////////////////
				
				if($whereServiceName != '') {
					$whereServiceName = $whereServiceName." ) ";
				}
				$whereService = '';
            	if(count($fitlerservices) > 0) {
					
					foreach($fitlerservices as $serval){
						if($whereService != '' ) {
							$whereService = $whereService." OR allservices::jsonb @> '".json_encode($serval)."'";
						} else {
							$whereService = "AND ( allservices::jsonb @> '".json_encode($serval)."'";
						}
					}
				} else {
					$whereService = "";
				}
            } else {
            	$whereService = "";
            }
            if(!empty($fitlerservices) && count($fitlerservices) > 0) {
				$fitlerservicesStr = implode( ',', $fitlerservices);
				$getAllServiceCate = DB::select("SELECT s.id,s.service,s.category from services as s  where s.status = '1' and (s.id IN (".$fitlerservicesStr."))");
				if(!empty($getAllServiceCate)) {
					foreach ($getAllServiceCate as $skey => $sval) {
						$svalSearch = '';
						$svalSearch = trim($sval->service);
						$svalSearch = urlencode(trim($svalSearch));
						$svalSearchLike = urlencode(trim($servicename));
						$svalSearch =  implode(' & ',explode('+',$svalSearch));
						$svalSearchLike =  implode('% %',explode('+',$svalSearchLike));
						if($whereService != '') {
							$whereService = $whereService." OR to_tsvector('english',name) @@ to_tsquery('english','".$svalSearch."') OR to_tsvector('english',about) @@ to_tsquery('english','".$svalSearch."') OR name ILIKE '%".$svalSearchLike."%' OR about ILIKE '%".$svalSearchLike."%' ";
						} else {
							$whereService = "AND ( to_tsvector('english',name) @@ to_tsquery('english','".$svalSearch."') OR to_tsvector('english',about) @@ to_tsquery('english','".$svalSearch."')  OR name ILIKE '%".$svalSearchLike."%' OR about ILIKE '%".$svalSearchLike."%' ";
						}
					}
				}
			}
			if($whereService != '') {
				$whereService = $whereService ." )";
			} else {
				$whereService = "";
			}

			$calDisMain = "";
			$rndSelect = '';
			
			/* Order By filter */
			if(empty($orderBy) || $orderBy == '' || $orderBy == 'default' || $orderBy == 'rated'){
				if($orderBy == 'rated') {
					$order_by = " ORDER BY advertisebusiness DESC , totalrating DESC ";
				} else {
					$rndSelect = 'random() as g,';
					$order_by = " ORDER BY advertisebusiness DESC , accounttype  ASC, paymentplans ASC, g DESC ";
				}
				
			} else if($orderBy == 'reviewed') {
				$order_by = " ORDER BY advertisebusiness DESC ,totalreviewed DESC"; 
			} else if($orderBy == 'distance') {
				$calDisMain = " 2 * 3961 * asin(sqrt((sin(radians((cd.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(cd.latitude)) * (sin(radians((cd.longitude - ".$longitude.") / 2))) ^ 2)) as closedistance, ";
				$order_by = " ORDER BY advertisebusiness DESC , closedistance  ASC"; 
			}
			if($notUricorrectedServiceName == $notUriServicename) {
				$notCorrect = false;
			} else {
				//echo $notUriServicename.'   '.$notUricorrectedServiceName;
				//~ similar_text( trim($notUriServicename),trim($notUricorrectedServiceName), $percent);
				//~ if($percent >=75) {
					//~ $notCorrect = true;
				//~ } else {
					$notCorrect = true;
				//~ }
			}
			if($ExactMatch) {
				$notCorrect = false;
				$notUricorrectedServiceName = $notUriServicename;
			}
         	$Currentdate = date('Y-m-d H:i:s');
			$whereCompany = " AND (accounttype = 'dummy' OR ( accounttype = 'real' AND nextpaymentdate > '".$Currentdate."') OR  ( account_type = 'free' AND free_subscription_end > '".$Currentdate."') OR  ( account_type = 'free' AND free_subscription_period = 'unlimited')  ) ";
            $calDis = "2 * 3961 * asin(sqrt((sin(radians((latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(latitude)) * (sin(radians((longitude - ".$longitude.") / 2))) ^ 2))";

            $authIDarr = [];
            $strInID = '';
			$searchBusinessArr = [];
			$searchBusinessArr = DB::select("SELECT authid from companydetails WHERE  status = 'active' ".$whereCompany." ".$whereService." ".$whereServiceName." AND ".$calDis." <= ".$distance);
            //echo "SELECT authid from companydetails WHERE  status = 'active' ".$whereCompany." ".$whereService." ".$whereServiceName;die;
            if(empty($searchBusinessArr) || count($searchBusinessArr) ==0) {
				$searchBusinessArr = [];
				$strInID = '';
			} else {
				foreach($searchBusinessArr as $searchBusinessArrs) {
					$authIDarr[] = $searchBusinessArrs->authid;
				}
				$strInID = implode(' , ',$authIDarr);
			}
			if($strInID == null || trim($strInID) =='') {
				return response()->json(['success' => false,'data'=>['long'=>$longitude,'lat' =>$latitude ],'result' => [],'isnotcorrect'=>$notCorrect,'correctsearch' => $notUricorrectedServiceName ,'isBusinessDetail' => false], $this->successStatus);
			} 
			
			//echo "SELECT  * FROM(SELECT DISTINCT ON (companyid) cd.authid as companyid,random() as g,cd.id,cd.name,cd.slug,cd.services,cd.address,cd.city,cd.state,cd.country,cd.longitude,cd.latitude,cd.zipcode,cd.contact,cd.images,cd.businessemail,cd.contactmobile,cd.country_code,CASE WHEN cd.plansubtype = 'free' THEN 'C' WHEN cd.plansubtype = 'paid' THEN 'A' ELSE 'B' END as paymentplans ,cd.websiteurl,cd.advertisebusiness,cd.primaryimage,cd.allservices,cd.about,cd.accounttype,coalesce( r.totalrating , 0 ) as totalrating,coalesce( r.totalreviewed , 0 ) as totalreviewed  FROM companydetails as cd LEFT JOIN reviewsview as r ON r.toid = cd.authid where cd.authid IN(".$strInID.")  ) temp ".$order_by;die;
			$result = DB::select("SELECT  * FROM(SELECT DISTINCT ON (companyid) cd.authid as companyid,".$calDisMain.$rndSelect."cd.id,cd.name,cd.slug,cd.services,cd.address,cd.city,cd.state,cd.country,cd.longitude,cd.latitude,cd.zipcode,cd.contact,cd.images,cd.businessemail,CASE WHEN cd.plansubtype = 'free' THEN 'C' WHEN cd.plansubtype = 'paid' THEN 'A' ELSE 'B' END as paymentplans ,cd.websiteurl,cd.advertisebusiness,cd.primaryimage,cd.allservices,cd.about,cd.accounttype,coalesce( r.totalrating , 0 ) as totalrating,coalesce( r.totalreviewed , 0 ) as totalreviewed  FROM companydetails as cd LEFT JOIN reviewsview as r ON r.toid = cd.authid where cd.authid IN(".$strInID.")  ) temp ".$order_by."");
            if(!empty($result)) {
	        	$add_business_count = [];
	        	$count = 0;
	        	foreach ($result as $value) {
	        		$add_business_count[$count]['company_id'] = $value->companyid;
	        		$add_business_count[$count]['updated_at'] = date('Y-m-d H:i:s');
	        		$add_business_count[$count]['created_at'] = date('Y-m-d H:i:s');
	        		$count++;
	        	}
	        	if(count($add_business_count) > 0) {
	        		Businesslistingcount::insert($add_business_count);	
	        	}
	        	$isbusinessDetail = false;
	        	$businessslug = '';
	        	//~ $companyDetail = Companydetail::where('name' , $servicename)->where('status','active')->get();
	        	//~ if(!empty($companyDetail) && count($companyDetail) == 1  && !empty($result) && count($result) == 1) {
					//~ if($companyDetail[0]->authid == $result[0]->companyid ) {
						//~ $isbusinessDetail = true;
						//~ $businessslug = $companyDetail[0]->slug;
					//~ }
				//~ }
	        	return response()->json(['success' => true,'data'=>['long'=>$longitude,'lat' =>$latitude ],'result' => $result,'isBusinessDetail' => $isbusinessDetail,'businessslug' => $businessslug,'isnotcorrect'=>$notCorrect,'correctsearch' => $notUricorrectedServiceName], $this->successStatus); 
			} else {
				return response()->json(['success' => false,'data'=>['long'=>$longitude,'lat' =>$latitude ],'result' => [],'isnotcorrect'=>$notCorrect,'correctsearch' => $notUricorrectedServiceName ,'isBusinessDetail' => false], $this->successStatus);
			}
    	}

    	//Get Highest Rated Companies 
    	public function getHighestRatedCompanies(Request $request) {
			$query = DB::table('companydetails as cd')
						->select('cd.authid','cd.id','cd.name','cd.slug','cd.services','cd.about','cd.primaryimage','r.totalrating','r.totalreviewed')
						->Join('reviewsview as r','r.toid','=','cd.authid')
						->where('cd.status','=','active')
						->orderBy('r.totalrating','DESC')						
						->limit(10)
						->get();    		
			if(!empty($query)) {
				foreach ($query as $qkey => $val) {
					$max = 0;
					$service = json_decode($val->services);
					$servicedata = Category::where('status','=','1')->select('id', 'categoryname as itemName')->get();
					if(!empty($servicedata)) {
			        	$servicedata = $servicedata->toArray();
			        	foreach ($service as $key => $value) {
							if($max < 2) {
								foreach ($servicedata as $val) {
										if($val['id'] == $key) {
											$query[$qkey]->servicenames[$max] = $val['itemName'];
										}
									}	
							}
							$max++;
						}
					}
				}
				return response()->json(['success' => true,'data' => $query], $this->successStatus);
			}	else {
				return response()->json(['success' => false,'data' => []], $this->successStatus);	
			}			

    	}

    	public function contactus(Request $request) {
	        $validate = Validator::make($request->all(), [
	            'name' => 'required',
	            'email' => 'required',
	            'subject' => 'required',
	            'message' => 'required',
	        ]);
	        if ($validate->fails()) {
	            return response()->json(['error'=>'validationError'], 401); 
	        }
	        $contactUsArr = array();
	        $authid = request('userid');
	        $contactno = request('mobile');
	        $contactUsArr['authid'] = (isset($authid) && $authid != '')?$authid:NULL;
			$contactUsArr['contact_no'] = (isset($contactno) && $contactno != '')?$contactno:NULL;
			$contactUsArr['name'] =  request('name');
			$contactUsArr['email'] =  request('email');
			$contactUsArr['subject'] =  request('subject');
			$contactUsArr['message'] =  request('message');
			$contactUsArr['status'] =  '1';
			$contactUsArr['created_at'] = date('Y-m-d H:i:s');
	        $contactUsArr['updated_at'] = date('Y-m-d H:i:s');
	        $id = DB::table('contactus')->insertGetId($contactUsArr);
	        if(!empty($id)) {
	        	$emailArr['to_email'] = 'info@marinecentral.com';
				$emailArr['name'] = request('name');
				$emailArr['subject'] = request('subject');
				$emailArr['email'] = request('email');
				$emailArr['message'] = request('message');
				$emailArr['contact'] = (isset($contactno) && $contactno != '')?$contactno:' ';
				$status = $this->sendEmailNotification($emailArr,'contact_us_notification_admin');
	            return response()->json(['success' => true], $this->successStatus);
	        } else {
	            return response()->json(['error'=>'networkerror'], 401); 
	        }
		}
		
		//Get All vacancies or Jobs by location and services NewCode
		public function getAllVacanciesByLocation() {
			$servicename = request('servicename');
        	$address = request('locations');
        	$distance = (request('distance')!= null) ? request('distance'):10;
        	$orderBy = request('sortby');
        	//$salaryType = request('salaryType');
        	$salary = json_decode(request('salary'));
        	$filter_experience = json_decode(request('experience_filter'));
        	$longitude =0;
        	$latitude = 0;
        	$userid = request('authid');
        	if(!empty($userid) && $userid != '') {
        		$authId = decrypt($userid); 
        	}
			$isExactSearch = request('isExactSearch');
        	$ExactMatch = false;
        	if($isExactSearch == 'true') {
				$ExactMatch = true;
			} else {
				$ExactMatch = false;
			}
        	
        	if(!empty($address)) {
        		$output = $this->getGeoLocation($address);
	        	$longitude = $output['longitude'];
	            $latitude = $output['latitude'];            	
	            if($longitude == 0 && $latitude == 0) {
            		return response()->json(['error'=>'networkerror','correctsearch' => $servicename], 401);
            	}
            } else {
            	$longitude = '-81.515754';
	            $latitude = '27.664827';
            }
             /* Check Sevicename exist in service table*/
            //~ if(!empty($filter_services) || !empty($servicename)) {
            	//~ $fitlerservices = $filter_services;
            	//~ if(!empty($servicename) && $servicename !='') {
            		//~ $fitlerservices[] = $servicename;
            	//~ }
            	//~ $like = '';
            	//~ foreach($fitlerservices as $serval){
            		//~ if($like == '') {
            			//~ $like .= "service ILIKE '%".$serval."%'";
            		//~ } else {
            			//~ $like.= " or service ILIKE '%".$serval."%'";
            		//~ }
            	//~ }
            	//~ //$like .= ")'";
            	//~ $getAllService = DB::select("SELECT s.id,s.service,s.category from services as s where s.status = '1' and ".$like."");
            	//~ $jsonArr = []; 
            	//~ $keyArr = [];
            	//~ $whereService = '';
            	//~ if(!empty($getAllService)) {
	            	//~ foreach ($getAllService as $skey => $sval) {
	            		//~ $jsonArr = [];
	        			//~ $jsonArr[$sval->category] = [$sval->id];
	        			//~ if($whereService != '' ) {
	        				//~ $whereService = $whereService." OR jb.services::jsonb @> '".json_encode($jsonArr)."'";
	        			//~ } else {
	        				//~ $whereService = "AND ( jb.services::jsonb @> '".json_encode($jsonArr)."'";
	        			//~ }
	            	//~ }
	            	//~ if($whereService != '') {
	            		//~ $whereService = $whereService ." )";
	            	//~ }
	            //~ } else {
	            	//~ return response()->json(['success' => false,'data'=>['long'=>$longitude,'lat' =>$latitude ],'result' => []], $this->successStatus); 
	            //~ }
            //~ } else {
            	$whereService = "";
            //~ }

            /* Order By filter */
			if($orderBy == '' || $orderBy == 'default') { 
				$order_by = '';	
				$order_by_temp = 'ORDER BY salaryOrder DESC';
			} else if($orderBy == 'relevance'){
				$order_by = ''; 
				$order_by_temp = 'ORDER BY distance ASC';
				
			} else if($orderBy == 'date') {
				$order_by = 'ORDER BY jb.request_uniqueid'; 
				$order_by_temp = 'ORDER BY salaryOrder DESC, published_date DESC';
			}
			 
			$authSelectJoin ='';
			if(!empty($authId)) {
				$authSelect = ', COALESCE(app.id,0) as applyJob,COALESCE(book.id,0) as bookmarkJob';
				$authSelectJoin = 'LEFT JOIN apply_jobs as app ON app.authid = '.$authId.' AND app.jobid = jb.id LEFT JOIN bookmark_jobs as book ON book.authid = '.$authId.' AND book.jobid = jb.id';
			} else {
				$authSelect = ', COALESCE(0) as applyJob,COALESCE(0) as bookmarkJob';

			}
			
			if(!empty($filter_experience)) {
				$betweenExp = '';
				foreach ($filter_experience as $value) {
					if($value == '5') {
						$betweenExp .= (empty($betweenExp))?" experience BETWEEN 0 and 5":" OR experience BETWEEN 0 and 5";
					}
					if($value == '10') {
						$betweenExp .= (empty($betweenExp))?" jb.experience BETWEEN 6 and 10":" OR jb.experience BETWEEN 6 and 10"; 	
					}
					if($value == '15') {
						$betweenExp .= (empty($betweenExp))?" jb.experience BETWEEN 11 and 15":" OR jb.experience BETWEEN 11 and 15";
					}
					if($value == '20') {
						$betweenExp .= (empty($betweenExp))?" jb.experience BETWEEN 16 and 20":" OR jb.experience BETWEEN 16 and 20";	
					}
					if($value == '25') {
						$betweenExp .= (empty($betweenExp))?" jb.experience BETWEEN 21 and 25":" OR jb.experience BETWEEN 21 and 25"; 	
					}
					if($value == '30') {
						$betweenExp .= (empty($betweenExp))?" jb.experience BETWEEN 26 and 30":" OR jb.experience BETWEEN 26 and 30";
					}
					if($value == '31') {
						$betweenExp .= (empty($betweenExp))?" jb.experience > 30 ":" OR jb.experience > 30 ";	
					}
				}
				$whereService .= " AND (".$betweenExp.")";
			}
			
			$correctedServiceName = $correctedServiceNameQuery = $notUriServicename = $notUricorrectedServiceName = $notUricorrectedServiceNameQuery = '';
			if(!empty($servicename) && $servicename != '' && $servicename != null) {
				$this->checkSuggestionInit($servicename);
				$correctedServiceNameArr =  $this->corrected();
				$correctedServiceName = $correctedServiceNameArr['word'];
				if($correctedServiceName != 'error') {
					$notUricorrectedServiceName = trim($correctedServiceName);
					$correctedServiceName = urlencode(trim($correctedServiceName));
					$correctedServiceName =  implode(' & ',explode('+',$correctedServiceName));
					if($ExactMatch) {
						$correctedServiceNameQuery = " to_tsvector('english',jb.title) @@ to_tsquery('english','".$correctedServiceName."') OR to_tsvector('english',jb.description) @@ to_tsquery('english','".$correctedServiceName."') ";
					} else {
						$correctedServiceNameQuery = '';
					}
				}
				$notUriServicename = trim($servicename);
				$servicename = urlencode(trim($servicename));
				$servicename =  implode('% %',explode('+',$servicename));
				
				if($servicename == $correctedServiceName) {
					$sameServiceSugestion = true;
				} else {
					$sameServiceSugestion = false;
				}
				$searchServiceString = '';
				if($ExactMatch) {
					$searchServiceString = $servicename;
				} else {
					$searchServiceString = implode("''",explode("'",$notUriServicename));
				}
				//$whereService .= " AND title ILIKE '%".$servicename."%' ";
				if($ExactMatch) {
					$whereService .= " AND (".$correctedServiceNameQuery." ) "; 
				} else {
					$whereService .= " AND (jb.title ILIKE '%".$searchServiceString."%' OR jb.description ILIKE '%".$searchServiceString."%' ".$correctedServiceNameQuery." ) "; 
				}
				
			}
			
			//salart filter
			if(!empty($salary)) {
				$between = '';
				foreach ($salary as $value) {
					if($value == '9') {
						$between .= (empty($between))?" salary BETWEEN '0' and '9'":" OR salary BETWEEN '0' and '9'";
					}
					if($value == '99') {
						$between .= (empty($between))?" jb.salary BETWEEN '10' and '99'":" OR jb.salary BETWEEN '10' and '99'"; 	
					}
					if($value == '999') {
						$between .= (empty($between))?" jb.salary BETWEEN '100' and '999'":" OR jb.salary BETWEEN '100' and '999'";
					}
					if($value == '9999') {
						$between .= (empty($between))?" jb.salary BETWEEN '1000' and '9999'":" OR jb.salary BETWEEN '1000' and '9999'";	
					}
				}
				$whereService .= "AND (".$between.")";
			}
			
			if($notUricorrectedServiceName == $notUriServicename) {
				$notCorrect = false;
			} else {
				$notCorrect = true;
			}
			if($ExactMatch) {
				$notCorrect = false;
				$notUricorrectedServiceName = $notUriServicename;
			}
			
			//~ if(!empty($salaryType)) {
    			//~ $whereService .= "AND salarytype ='".$salaryType."'";
    		//~ }
			// $calDis = ',(((acos(sin(('.$latitude.'*pi()/180)) * sin(((COALESCE(NULLIF(cd.latitude,NULL), yd.latitude)) *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos(((COALESCE(NULLIF(cd.latitude,NULL), yd.latitude)) *pi()/180)) * cos((('.$longitude.'- (COALESCE(NULLIF(cd.longitude,NULL), yd.longitude)))*pi()/180))))*180/pi())*60*1.1515) as distance';
			$calDis = ",2 * 3961 * asin(sqrt((sin(radians((  COALESCE(NULLIF(cd.latitude,NULL), yd.latitude) - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians( COALESCE(NULLIF(cd.latitude,NULL), yd.latitude) )) * (sin(radians(( COALESCE(NULLIF(cd.longitude,NULL), yd.longitude) - ".$longitude.") / 2))) ^ 2)) as distance";

            $result = DB::select("SELECT * FROM(SELECT DISTINCT ON  (jb.request_uniqueid) jb.id as jobid,jb.authid as companyid, jb.title,jb.created_at as published_date,jb.salary,jb.description,
            	COALESCE(NULLIF(cd.authid,NULL), yd.authid) as authid,
            	COALESCE(NULLIF(cd.name,''), CONCAT(yd.firstname,' ',yd.lastname)) as authid,
            	cd.slug,jb.services as vacancyservices,jb.salarytype,CASE WHEN jb.salary > '0' THEN 'B' ELSE 'A' END as salaryOrder,

            	COALESCE(0)as geoid,
            	COALESCE(NULLIF(cd.city,NULL), yd.city) as geocity,
            	COALESCE(NULLIF(cd.state,NULL), yd.state) as geostate,
            	COALESCE(NULLIF(cd.country,NULL), yd.country) as geocountry,
            	COALESCE(NULLIF(cd.address,NULL), yd.address) as geoaddress,
            	COALESCE(NULLIF(cd.zipcode,NULL), yd.zipcode) as geozipcode,
            	COALESCE(NULLIF(cd.longitude,NULL), yd.longitude) as geolongitude,
            	COALESCE(NULLIF(cd.latitude,NULL), yd.latitude) as geolatitude , coalesce( r.totalrating , 0 ) as totalrating,coalesce( r.totalreviewed , 0 ) as totalreviewed  ".$authSelect." ".$calDis." FROM jobs as jb LEFT JOIN companydetails as cd ON cd.authid = jb.authid ".$authSelectJoin." LEFT JOIN yachtdetail as yd ON yd.authid = jb.authid LEFT JOIN reviewsview as r ON r.toid = jb.authid WHERE  jb.status = 'active' and (cd.status = 'active' OR yd.status='active')".$whereService." ".$order_by.") temp ".$order_by_temp."");
           // echo "SELECT * FROM(SELECT DISTINCT ON  (jb.request_uniqueid) jb.id as jobid,jb.authid as companyid, jb.title,jb.created_at as published_date,jb.salary,jb.description,
           //  	COALESCE(NULLIF(cd.authid,NULL), yd.authid) as authid,
           //  	COALESCE(NULLIF(cd.name,''), CONCAT(yd.firstname,' ',yd.lastname)) as authid,
           //  	cd.slug,jb.services as vacancyservices,jb.salarytype,

           //  	COALESCE(0)as geoid,
           //  	COALESCE(NULLIF(cd.city,NULL), yd.city) as geocity,
           //  	COALESCE(NULLIF(cd.state,NULL), yd.state) as geostate,
           //  	COALESCE(NULLIF(cd.country,NULL), yd.country) as geocountry,
           //  	COALESCE(NULLIF(cd.zipcode,NULL), yd.zipcode) as geozipcode,
           //  	COALESCE(NULLIF(cd.longitude,NULL), yd.longitude) as geolongitude,
           //  	COALESCE(NULLIF(cd.latitude,NULL), yd.latitude) as geolatitude , coalesce( r.totalrating , 0 ) as totalrating,coalesce( r.totalreviewed , 0 ) as totalreviewed  ".$authSelect." ".$calDis." FROM jobs as jb LEFT JOIN companydetails as cd ON cd.authid = jb.authid ".$authSelectJoin." LEFT JOIN yachtdetail as yd ON yd.authid = jb.authid LEFT JOIN reviewsview as r ON r.toid = jb.authid WHERE  jb.status = 'active' and (cd.status = 'active' OR yd.status='active')".$whereService." ".$order_by.") temp WHERE distance <= ".$distance." ".$order_by_temp."";
            	
            if(!empty($result)) {
				return response()->json(['success' => true,'result' => $result,'isnotcorrect'=>$notCorrect,'correctsearch' => $notUricorrectedServiceName], $this->successStatus); 
			} else {
				return response()->json(['success' => false,'data'=>['long'=>$longitude,'lat' =>$latitude ],'result' => [],'isnotcorrect'=>$notCorrect,'correctsearch' => $notUricorrectedServiceName], $this->successStatus);
			}
		}
		
		//Get All users service requests by location and services
		public function getAllJobsByLocation() {
			$servicename = request('servicename');
        	$address = request('locations');
        	$userid = request('authid');
        	if(!empty($userid) && $userid != 'null') {
        		$authId = decrypt($userid); 
        	}
        	$isExactSearch = request('isExactSearch');
        	$ExactMatch = false;
        	if($isExactSearch == 'true') {
				$ExactMatch = true;
			} else {
				$ExactMatch = false;
			}
        	$distance = (request('distance')!= null) ? request('distance'):50;
        	$filter_services = json_decode(request('jobtype'));
        	$longitude =0;
        	$latitude = 0;
        	if(!empty($address)) {
        		$output = $this->getGeoLocation($address);
	        	$longitude = $output['longitude'];
	            $latitude = $output['latitude'];            	
	            if($longitude == 0 && $latitude == 0) {
            		return response()->json(['error'=>'networkerror','correctsearch' => $servicename], 401);
            	}
            } else {
            	$longitude = '-81.515754';
	            $latitude = '27.664827';
            }
           
            $sameServiceSugestion = false;
            $correctedServiceName = $correctedServiceNameQuery = $notUriServicename = $notUricorrectedServiceName = $notUricorrectedServiceNameQuery = '';
           		
            if(!empty($filter_services) || !empty($servicename)) {
            	$fitlerservices = $filter_services;
            	$this->checkSuggestionInit($servicename);
				$correctedServiceNameArr =  $this->corrected();
				$correctedServiceName = $correctedServiceNameArr['word'];
				if($correctedServiceName != 'error') {
					$notUricorrectedServiceName = trim($correctedServiceName);
					$correctedServiceName = urlencode(trim($correctedServiceName));
					$correctedServiceName =  implode(' & ',explode('+',$correctedServiceName));
					if($ExactMatch) {
						$correctedServiceNameQuery = "  to_tsvector('english',usr.title) @@ to_tsquery('english','".$correctedServiceName."') OR to_tsvector('english',usr.description) @@ to_tsquery('english','".$correctedServiceName."') ";
					} else {
						$correctedServiceNameQuery = '';
					}
				}
				$notUriServicename = trim($servicename);
				$servicename = urlencode(trim($servicename));
				$servicename =  implode('% %',explode('+',$servicename));
				if($servicename == $correctedServiceName) {
					$sameServiceSugestion = true;
				} else {
					$sameServiceSugestion = false;
				}
					
            	if(!empty($servicename) && $servicename !='') {
            		$fitlerservices[] = $servicename;
            	}
            	$like = '';
            	foreach($fitlerservices as $serval){
            		if($like == '') {
            			$like .= "service ILIKE '%".$serval."%'";
            		} else {
            			$like.= " or service ILIKE '%".$serval."%'";
            		}
            	}
				if($ExactMatch) {
					if($like == '') {
						$like .= " to_tsvector('english',service) @@ to_tsquery('english','".$correctedServiceName."') ";
					} else {
						$like.= " or to_tsvector('english',service) @@ to_tsquery('english','".$correctedServiceName."') ";
					}
				} else {
				}
				
            	$getAllService = DB::select("SELECT s.id,s.service,s.category from services as s where s.status = '1' and ".$like."");
            	$jsonArr = []; 
            	$keyArr = [];
            	$whereService = '';
            	if(!empty($getAllService)) {
            		foreach ($getAllService as $skey => $sval) {
	            		$jsonArr = [];
	        			$jsonArr[$sval->category] = [$sval->id];
	        			if($whereService != '' ) {
	        				$whereService = $whereService." OR usr.services::jsonb @> '".json_encode($jsonArr)."'";
	        			} else {
	        				$whereService = "AND ( usr.services::jsonb @> '".json_encode($jsonArr)."'";
	        			}
	            	}
	            	//notUricorrectedServiceName
	            	$searchServiceString = '';
	            	if($ExactMatch) {
						$searchServiceString = $servicename;
					} else {
						$searchServiceString = implode("''",explode("'",$notUriServicename));
					}
	            	//~ if($whereService != '') {
	            		//~ $whereService = $whereService ." )";
	            	//~ }
	            	//implode("''",explode("'",
	            	if($whereService != '' && (!empty($servicename) && $servicename !='')) {
						if($ExactMatch) {
							$whereService = $whereService ." OR ".$correctedServiceNameQuery." )";
						} else {
							$whereService = $whereService ." OR usr.title ILIKE '%".$searchServiceString."%' OR usr.description ILIKE '%".$searchServiceString."%' )";
						}
					} else if ($whereService == '' && (!empty($servicename) && $servicename !='')){
						if($ExactMatch) {
							$whereService = " AND ( ".$correctedServiceNameQuery." )";
						} else {
							$whereService = " AND ( usr.title ILIKE '%".$searchServiceString."%' OR usr.description ILIKE '%".$searchServiceString."%' )";
						}
					} else {
						$whereService = $whereService ." )";
					}
	            	
	            	
	            } else {
					$searchServiceString = '';
	            	if($ExactMatch) {
						$searchServiceString = $servicename;
					} else {
						$searchServiceString = implode("''",explode("'",$notUriServicename));
					}
					if($ExactMatch) {
						$whereService = " AND ( ".$correctedServiceNameQuery." )";
					} else {
						$whereService = " AND ( usr.title ILIKE '%".$searchServiceString."%' OR usr.description ILIKE '%".$searchServiceString."%' )";
					}
					
					//return response()->json(['success' => false,'data'=>['long'=>$longitude,'lat' =>$latitude ],'result' => []], $this->successStatus); 
	            }
            } else {
            	$whereService = "";
            }
            $get_request_proposal = '';
            $get_count = '';
            $get_countJob = '';
            $get_request_proposalJob = '';
            $get_request_proposalGroup = '';
            $rp = '';
            if(!empty($authId) && (int)$authId) {
            	$get_count = ',rp.id as proposalId';
            	$get_request_proposal = "LEFT JOIN request_proposals as rp ON rp.requestid = usr.id AND rp.companyid =".$authId."";
            	$rp = ',rp.id';
            	
            }

			$authSelectJoin ='';
			$bookGroup = '';
			if(!empty($authId)) {
				$authSelect = ', COALESCE(book.id,0) as bookmarkJob';
				$authSelectJoin = 'LEFT JOIN bookmark_requests as book ON book.authid = '.$authId.' AND book.requestid = usr.id';
				$bookGroup = ',book.id';
			} else {
				$authSelect = ', COALESCE(0) as bookmarkJob';

			}

            $get_countJob = ',count(rps.requestid) as recievedlead';
        	$get_request_proposalJob = "LEFT JOIN request_proposals as rps ON rps.requestid = usr.id AND rps.status = 'pending'";
        	$get_request_proposalGroup = "group by rps.requestid,usr.id,ud.id".$rp."".$bookGroup.",yd.id ";
            
            // $calDis = ',(((acos(sin(('.$latitude.'*pi()/180)) * sin((usr.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((usr.latitude *pi()/180)) * cos((('.$longitude.'- usr.longitude)*pi()/180))))*180/pi())*60*1.1515) as distance';
            if($notUricorrectedServiceName == $notUriServicename) {
				$notCorrect = false;
			} else {
				$notCorrect = true;
			}
			if($ExactMatch) {
				$notCorrect = false;
				$notUricorrectedServiceName = $notUriServicename;
			}
			
            $calDis = ",2 * 3961 * asin(sqrt((sin(radians((usr.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(usr.latitude)) * (sin(radians((usr.longitude - ".$longitude.") / 2))) ^ 2)) as distance";
        
            $result = DB::select("SELECT * FROM(SELECT usr.id,usr.authid,usr.title as jobtitle,usr.description,usr.services as jobtype,usr.addspecialrequirement as job_session,usr.numberofleads as lead,usr.created_at
            	,usr.city,usr.state,usr.country,usr.address,usr.zipcode,
            	COALESCE(NULLIF(ud.firstname,''), yd.firstname) as firstname,
            	COALESCE(NULLIF(ud.lastname,''), yd.lastname) as lastname,
            	COALESCE(NULLIF(ud.profile_image,''), yd.primaryimage) as profile_image,
            	COALESCE(NULLIF(ud.latitude,0), yd.latitude) as latitude,
            	COALESCE(NULLIF(ud.longitude,0), yd.longitude) as longitude,
            	yd.firstname as yacht_name,ud.firstname as user_name
            	".$get_count." ".$authSelect." ".$get_countJob." ".$calDis." FROM users_service_requests as usr LEFT JOIN userdetails as ud on usr.authid = ud.authid LEFT JOIN yachtdetail as yd ON usr.authid = yd.authid JOIN auths as auth ON  usr.authid = auth.id ".$get_request_proposal." ".$get_request_proposalJob." ".$authSelectJoin." WHERE auth.is_activated = '1' AND usr.status = 'posted' AND (ud.status = 'active' OR yd.status = 'active') ".$whereService." ".$get_request_proposalGroup.") temp WHERE distance <= ".$distance." ORDER BY distance ASC");
            if(!empty($result)) {
            	// $whereIn = [];
            	// foreach ($result as $key => $val) {
            	// 	$whereIn[] = $val->id;
            	// }
            	// $totalCount = DB::Select("SELECT count(requestid) as total_requests, requestid FROM request_proposals WHERE requestid IN (".implode(',',$whereIn).") GROUP BY requestid");
            	// $result['totalcount'] = $totalCount;
				return response()->json(['success' => true,'result' => $result,'isnotcorrect'=>$notCorrect,'correctsearch' => $notUricorrectedServiceName], $this->successStatus); 
			} else {
				return response()->json(['success' => false,'data'=>['long'=>$longitude,'lat' =>$latitude ],'result' => [],'isnotcorrect'=>$notCorrect,'correctsearch' => $notUricorrectedServiceName], $this->successStatus);
			}
			 
		}

		//Get all professional listsing
		public function getAllProfessionalByLocation() {
			$jobtitle = json_decode(request('jobtitle'));
			$jobtitleSearch = request('servicename'); 
        	$address = request('locations');
        	$distance = (request('distance')!= null) ? request('distance'):100;
        	$experience = request('experience');
        	$longitude =0;
        	$latitude = 0;
        	$isExactSearch = request('isExactSearch');
        	$ExactMatch = false;
        	if($isExactSearch == 'true') {
				$ExactMatch = true;
			} else {
				$ExactMatch = false;
			}
        	if(!empty($address)) {
        		$output = $this->getGeoLocation($address);
	        	$longitude = $output['longitude'];
	            $latitude = $output['latitude'];            	
	            if($longitude == 0 && $latitude == 0) {
            		return response()->json(['error'=>'networkerror','correctsearch' => $jobtitleSearch], 401);
            	}
            } else {
            	$longitude = '-81.515754';
	            $latitude = '27.664827';
            }
            $where = '';
            //Job Title filter
            $like = [];
            if(!empty($jobtitle)) {
            	foreach($jobtitle as $title){
            		if(!empty($title) && !in_array($title,$like)) {
            			$like[] = $title;
            		}	
            	}
            }
            $seachedTitle = '';
            $checkOtherTitle = '';
            $arr = [];
            $correctedServiceName = $correctedServiceNameQuery = $correctedServiceNameQuery2 = $withWhereQuery = '';
            $notUriServicename = $notUricorrectedServiceName = $notUricorrectedServiceNameQuery = '';
            if(!empty($jobtitleSearch)) {
				$this->checkSuggestionInit($jobtitleSearch);
				$correctedServiceNameArr =  $this->corrected();
				$correctedServiceName = $correctedServiceNameArr['word'];
				if($correctedServiceName != 'error') {
					$notUricorrectedServiceName = implode("''",explode("'",trim($correctedServiceName)));
					$correctedServiceName = urlencode(trim($correctedServiceName));
					$correctedServiceName =  implode(' & ',explode('+',$correctedServiceName));
					$jobtitleSearch2 = implode("''",explode("'",$jobtitleSearch));
					//echo $correctedServiceName;die;
					if($ExactMatch) {
						$correctedServiceNameQuery = " OR to_tsvector('english',td.objective) @@ to_tsquery('english','".$correctedServiceName."') OR to_tsvector('english',obj->>'exptitle') @@ to_tsquery('english','".$correctedServiceName."')";
						$correctedServiceNameQuery2 = " to_tsvector('english',td.objective) @@ to_tsquery('english','".$correctedServiceName."') OR to_tsvector('english',obj->>'exptitle') @@ to_tsquery('english','".$correctedServiceName."')";
						$withWhereQuery = " , json_array_elements(td.workexperience) obj ";
					} else {
						$correctedServiceNameQuery = " OR td.objective ILIKE '%".$jobtitleSearch2."%' OR obj->>'exptitle' ILIKE '%".$jobtitleSearch2."%' ";
						$correctedServiceNameQuery2 = " td.objective ILIKE '%".$jobtitleSearch2."%' OR obj->>'exptitle' ILIKE '%".$jobtitleSearch2."%' ";
						$withWhereQuery = " , json_array_elements(td.workexperience) obj ";
					}
				}
				$notUriServicename = trim($jobtitleSearch);
				$jobtitleSearch = urlencode(trim($jobtitleSearch));
				$jobtitleSearch =  implode('% %',explode('+',$jobtitleSearch));
				if($jobtitleSearch == $correctedServiceName) {
					$sameServiceSugestion = true;
				} else {
					$sameServiceSugestion = false;
				}
				if($ExactMatch) {
					$jobtitleSearchQuery = '';
					$jobtitleotherSearchQuery = '';
				} else {
					$jobtitleSearchQuery = " ";
					$jobtitleotherSearchQuery = " ";
				}
				if($ExactMatch) {
					
					$seachedTitle =  DB::select("select * from Jobtitles where to_tsvector('english',title) @@ to_tsquery('english','".$correctedServiceName."') ");
					$checkOtherTitle =  DB::select("select * from talentdetails where jobtitleid = '1' and ( to_tsvector('english',otherjobtitle) @@ to_tsquery('english','".$correctedServiceName."')  )");
				} else {
					$jobtitleSearchStr = urlencode(trim($jobtitleSearch)); 
					$jobtitleSearchStr =  implode(' & ',explode('+',$jobtitleSearchStr));
					$seachedTitle =  DB::select("select * from Jobtitles where to_tsvector('english',title) @@ to_tsquery('english','".$jobtitleSearchStr."' ) ");
					$checkOtherTitle =  DB::select("select * from talentdetails where jobtitleid = '1'  and ( to_tsvector('english',otherjobtitle) @@ to_tsquery('english','".$jobtitleSearchStr."')  )");
				}
				
				//~ $getJobTitle = Jobtitles::where(function($q)  use ($jobtitleSearch,$correctedServiceName) {
					//~ $q->where('title','ILIKE',"'%".$jobtitleSearch."%'")
					//~ ->orWhere("to_tsvector(title)","@@","to_tsquery('".$correctedServiceName."')");
				//~ })->get();
				
				if(!empty($checkOtherTitle)) {
					foreach ($checkOtherTitle as $key => $value) {
						$arr[] = $value->authid;
					}
				}
            	if(!empty($seachedTitle)) {
					//echo "<pre>";print_r($seachedTitle);
					if(count($seachedTitle) > 0) {
						foreach ($seachedTitle as $seachedTitles) {
							$like[] = $seachedTitles->id;
						}
					}
            	}
            }
            //~ if((int)$seachedTitle) {
            	//~ $like[] = $seachedTitle;
            //~ }
            if(!empty($like) && empty($arr)) {
            	$where.= "AND (jobtitleid IN ('" . implode("', '", $like) . "') ".$correctedServiceNameQuery.") ";
            } else if(empty($like) && !empty($arr)) {
            	$where .= " AND ( authid IN ('" . implode("', '", $arr) . "') ".$correctedServiceNameQuery.")";
            } else if(!empty($like) && !empty($arr)) {
            	$where .= " AND (jobtitleid IN('" . implode("', '", $like) . "') OR authid IN('" . implode("', '", $arr) . "') ".$correctedServiceNameQuery.")";
            } else if($correctedServiceNameQuery2 != '') {
				$where .= " AND ( ".$correctedServiceNameQuery2.")";
			}
           
            //Experience Filter
            $exp = '';
            if(!empty($experience)) {
            	if($experience == '1') {
            		$exp = 'totalexperience ='.$experience;	
            	} else if($experience == '6') {
            	 	$exp = 'totalexperience ='.$experience;	
            	} else if($experience == '11') {
            		$exp = 'totalexperience > 10';	
            	} else if($experience == 'all') {
            		$exp = 'totalexperience > 0';	
            	} 
            }
	        if($exp != '') {
	         	$where .= 'AND '.$exp.'';
	        }
	        
	        if($notUricorrectedServiceName == $notUriServicename) {
				$notCorrect = false;
			} else {
				$notCorrect = true;
			}
			if($ExactMatch) {
				$notCorrect = false;
				$notUricorrectedServiceName = $notUriServicename;
			}
			//echo "SELECT * FROM(SELECT td.authid,td.jobtitleid,td.firstname,td.lastname,td.city,td.state,td.country,td.zipcode,td.address,td.profile_image,td.workexperience,td.willingtravel,td.objective,td.otherjobtitle  FROM talentdetails as td ".$withWhereQuery." WHERE status = 'active' ".$where." GROUP BY td.id ) temp ORDER BY distance ASC";die;
            // if(!empty($address) && $address != '') {
            	/* Get Companies list close to the distance*/
            	if($distance == 'all') {
					// $calDis = ',(((acos(sin(('.$latitude.'*pi()/180)) * sin((td.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((td.latitude *pi()/180)) * cos((('.$longitude.'- td.longitude)*pi()/180))))*180/pi())*60*1.1515) as distance';
					$calDis = ",2 * 3961 * asin(sqrt((sin(radians((td.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(td.latitude)) * (sin(radians((td.longitude - ".$longitude.") / 2))) ^ 2)) as distance";
					$result = DB::select("SELECT * FROM(SELECT td.authid,td.jobtitleid,td.firstname,td.lastname,td.city,td.state,td.country,td.zipcode,td.address,td.profile_image,td.workexperience,td.willingtravel,td.objective,td.otherjobtitle ".$calDis." FROM talentdetails as td ".$withWhereQuery." WHERE status = 'active' ".$where." GROUP BY td.id ) temp ORDER BY distance ASC");
				} else {
					// $calDis = ',(((acos(sin(('.$latitude.'*pi()/180)) * sin((td.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((td.latitude *pi()/180)) * cos((('.$longitude.'- td.longitude)*pi()/180))))*180/pi())*60*1.1515) as distance';
					$calDis = ",2 * 3961 * asin(sqrt((sin(radians((td.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(td.latitude)) * (sin(radians((td.longitude - ".$longitude.") / 2))) ^ 2)) as distance";
					$result = DB::select("SELECT * FROM(SELECT td.authid,td.jobtitleid,td.firstname,td.lastname,td.city,td.state,td.country,td.zipcode,td.address,td.profile_image,td.workexperience,td.willingtravel,td.objective,td.otherjobtitle ".$calDis." FROM talentdetails as td ".$withWhereQuery." WHERE status = 'active' ".$where." GROUP BY td.id ) temp WHERE distance <= ".$distance." ORDER BY distance ASC");
				}
			//	echo "SELECT * FROM(SELECT td.authid,td.jobtitleid,td.firstname,td.lastname,td.city,td.state,td.country,td.zipcode,td.address,td.profile_image,td.workexperience,td.willingtravel,td.objective,td.otherjobtitle ".$calDis." FROM talentdetails as td ".$withWhereQuery." WHERE status = 'active' ".$where.") temp ORDER BY distance ASC";
	  //       } else {
   //          	$result = DB::select("SELECT td.authid,td.jobtitle,td.firstname,td.lastname,td.city,td.state,td.country,td.zipcode,td.address,td.mobile,td.profile_image,td.longitude,td.latitude,td.workexperience,td.willingtravel,td.objective FROM talentdetails as td ".$where." ORDER BY td.created_at DESC");
			// }
			if(!empty($result)) {
				return response()->json(['success' => true,'result' => $result,'isnotcorrect'=>$notCorrect,'correctsearch' => $notUricorrectedServiceName], $this->successStatus); 
			} else {
				return response()->json(['success' => false,'data'=>['long'=>$longitude,'lat' =>$latitude ],'result' => [],'isnotcorrect'=>$notCorrect,'correctsearch' => $notUricorrectedServiceName], $this->successStatus);
			}
		}

		public function getAllJobTitles() {
			$noOther = request('other');
			
			$where = '';
			if(!empty($noOther)) {
				$where = "AND LOWER(title) != 'others'";
			}
			$contact = DB::select("SELECT  id,title from jobtitles where status = '1' ".$where." ORDER BY title='Others' ASC, title asc");
			if(!empty($contact)) {
				return response()->json(['success' => true,'data' => $contact], $this->successStatus); 
			} else {
				return response()->json(['success' => false], $this->successStatus);
			}
		}

		public function getAllJobsTitle() {
			// $contact = Jobtitles::select('id as value','title as itemName')->where('status','1')->orderBy('created_at','DESC')->get();
			$contact = DB::select("SELECT  id as value,title as itemName from jobtitles where status = '1' ORDER BY title='Others' ASC, title asc");
			if(!empty($contact)) {
				foreach ($contact as $key => $value) {
					$contact[$key]->checked = FALSE;
					$contact[$key]->itemName = $value->itemname;
				}
				return response()->json(['success' => true,'result' => $contact], $this->successStatus); 
			} else {
				return response()->json(['success' => false], $this->successStatus);
			}
		}

		public function getAllUserServiceRequests(Request $request) {
			$id = request('id');
			if(!empty($id) && (int)$id) {
				$requested_service = User_request_services::select('title','description','id')->where('authid',$id)->where('status','=','posted')->get();
				if(!empty($requested_service)) {
					return response()->json(['success' => true,'data' => $requested_service], $this->successStatus);
				} else {
					return response()->json(['success' => false], $this->successStatus);
				}
			} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
		} 

    	// qoute request //
    	public function saveQuoteRequest(Request $request) {
	        $validate = Validator::make($request->all(), [
	            'businessid' => 'required',
	            'firstname' => 'required',
	            'email' => 'required',
	            'subject' => 'required',
	            'objective' => 'required',
	        ]);
	        if ($validate->fails()) {
	            return response()->json(['error'=>'validationError'], 401); 
	        }
	        $contactUsArr = array();
	        $userid = request('userid');
	        $title = request('title');
	        $contactUsArr['userid'] = (isset($userid) && $userid != '')?$userid:NULL;
			$contactUsArr['title'] = (isset($title) && $title != '' && $title != 'undefined')?$title:NULL;
			$contactUsArr['name'] =  request('firstname');
			$contactUsArr['email'] =  request('email');
			$contactUsArr['objective'] =  request('objective');
			$contactUsArr['title'] =  request('subject');
			$contactUsArr['businessid'] =  request('businessid');
			$contactUsArr['status'] =  '1';
			$contactUsArr['is_read'] =  '1';
			$contactUsArr['created_at'] = date('Y-m-d H:i:s');
	        $contactUsArr['updated_at'] = date('Y-m-d H:i:s');
	        $id = DB::table('quoterequests')->insertGetId($contactUsArr);
	        if(!empty($contactUsArr['userid']) && $contactUsArr['userid'] != 'null') {
	        	$usertype = Auth::select('usertype')->where('id',$contactUsArr['userid'])->first();
	            if(!empty($usertype)) {
	                $from_usertype = $usertype->usertype;
	            } else {
	            	$from_usertype = NULL;	
	            }	
	        } else {
	        	$from_usertype = NULL;
	        }
	        
	        if(!empty($id)) {
	        	$msgArr   = new Messages; 
                $msgArr->message_to = $contactUsArr['businessid'];
                $msgArr->message_from = (!empty($userid) && (int)$userid)?$userid:0;
                $msgArr->subject = request('subject');
                $msgArr->message = $contactUsArr['objective'];
                $msgArr->message_type = 'request_quote';
                $msgArr->from_usertype = $from_usertype;
                $msgArr->request_id = $id;
                $msgArr->quote_name = $contactUsArr['name'];
                $msgArr->quote_email = $contactUsArr['email'];
                
                $msgArr->to_usertype = 'company';
                if($msgArr->save()) {
            	 	$message_id = $msgArr->id;
            		$update_message_id = Messages::where('id',$message_id)->update(['message_id'=>$message_id]);
            		$Companydata = Companydetail::where('authid', '=', (int)request('businessid'))
					->where('status','!=','deleted')
					->first();
					$emailArr['to_email'] = $Companydata->contactemail;
            		$emailArr['name'] = $Companydata->name;
            		$emailArr['title']  = request('subject');
            		$website_url = env('NG_APP_URL','https://www.marinecentral.com');
					$emailArr['link']  = $website_url.'/business/messages?id='.$message_id.'&type=request_quote&cf=marine';
            		$mobilenumber = $Companydata->country_code.$Companydata->contactmobile;
            		$status = $this->sendEmailNotification($emailArr,'request_quotes_notification');
            		$sms = 'Hello '.$Companydata->name.','."\n".'A user on Marine Central has requested a quote. Click here '.$emailArr['link'].' to view';
            		$fromId = (isset($userid) && $userid != '')?$userid:0;
                 	SendSmsToBusinesses::dispatch($sms,$mobilenumber,'service_request',$fromId,$Companydata->authid);
                 	
                 	$NotActiveUser = false;
                 	$UserInfo = [];
                 	//~ if(!empty($usertype) && !empty($usertype->usertype)) {
						//~ if($usertype->usertype == 'company') {
							//~ $userData = Companydetail::select('contactemail','name')->where('authid', '=', (int)$userid)->where('status','active')->first();
							//~ if(!empty($userData)) {
								//~ $UserInfo['from_name'] = $userData->name;
							//~ } else {
								//~ $NotActiveUser = true;
							//~ }
						//~ } else if($usertype->usertype == 'regular') {
							//~ $userData = Userdetail::select('firstname','lastname')->where('authid', '=', (int)$userid)->where('status','active')->first();
							//~ if(!empty($userData)) {
								//~ $UserInfo['from_name'] = $userData->firstname.' '.$userData->lastname;
							//~ } else {
								//~ $NotActiveUser = true;
							//~ }
						//~ } else if($usertype->usertype == 'yacht') {
							//~ $userData = Yachtdetail::select('firstname','lastname')->where('authid', '=', (int)$userid)->where('status','active')->first();
							//~ if(!empty($userData)) {
								//~ $UserInfo['from_name'] = $userData->firstname.' '.$userData->lastname;
							//~ } else {
								//~ $NotActiveUser = true;
							//~ }
						//~ }  else if($usertype->usertype == 'professional') {
							//~ $userData = Talentdetail::select('firstname','lastname')->where('authid', '=', (int)$userid)->where('status','active')->first();
							//~ if(!empty($userData)) {
								//~ $UserInfo['from_name'] = $userData->firstname.' '.$userData->lastname;
							//~ } else {
								//~ $NotActiveUser = true;
							//~ }
						//~ }
						 //~ if(!empty($Companydata)) {
							 //~ $UserInfo['to_name'] = $Companydata->name;
							 //~ $UserInfo['to_email'] = $Companydata->contactemail;
						 //~ } else {
							 //~ $NotActiveUser = true;
						 //~ }
						 
						 //~ if($NotActiveUser == false ) {
							//~ $website_url = env('NG_APP_URL','https://www.marinecentral.com');
							//~ $link = '';
							//~ $link = $website_url.'/business/messages?id='.$message_id.'&type=request_quote';
							
							//~ if($link != '' && !empty($UserInfo['to_email'])) {
								//~ $UserInfo['link'] = $link;
								//~ $status = $this->sendEmailNotification($UserInfo,'unreadMessage_reminder');
							//~ }
						//~ }
					//~ }
                	/*
                	$getTemplate = Emailtemplates::select('subject','body','email_from')->where('template_name','=','contact_us')->where('status','1')->first();
                    if(!empty($getTemplate)) {
                        //Send notification email to business to lead sent successfully
                        $emailArr = [];
                        $emailArr['to_email'] = $contactUsArr['email'];	
                        $email_body = $getTemplate->body;
                        $search  = array('%firstname%', '%lastname%');
                        $replace = array($firstname, $lastname);
                        $email_from = getenv('EMAIL_FROM');
                        $emailArr['subject'] = $getTemplate->subject;
                        $emailArr['body'] = str_replace($search, $replace, $email_body);
                        $status = $this->sendEmailNotification($emailArr);
                        if($status == 'sent') {
                            return response()->json(['success' => true,'data' => $id], $this->successStatus);
                        }  else {
                           return response()->json(['error'=>'networkerror'], 401);  
                        }

	                }
					*/
		        	return response()->json(['success' => true], $this->successStatus);
		        } else {
		            return response()->json(['error'=>'networkerror'], 401); 
		        }
			} else {
				    return response()->json(['error'=>'networkerror'], 401); 
			}
		}


		   

		public function getUserRequestDetails(Request $request) {
			$validate = Validator::make($request->all(), [
	            'id' => 'required',
	            // 'authid' => 'required'
	        ]);
	        if ($validate->fails()) {
				return response()->json(['error'=>'validationError'], 401); 
	        }
	        $id = request('id');
	        $userid = request('authid');
	        $showShowReviews = false;
        	if(!empty($userid) && $userid != 'null') {
        		$authId = decrypt($userid);
        		$value = $request->bearerToken();
				if(!empty($value) && $value != 'statics') {
					$uid= (new Parser())->parse($value)->getHeader('jti');
	     			$userid = DB::table('oauth_access_tokens')->where('id', '=', $uid)->where('revoked', '=', false)->first()->user_id;
	     			if(!empty($userid) && $userid > 0) {
	     				$userdata = Auth::where('id', '=', $userid)->where('status' ,'=','active')->first();
	     				if(!empty($userdata)) {
	     					if($userdata->usertype == 'company' || $userdata->usertype == 'yacht') {
	     						$showShowReviews = true;
	     					}
	     				}
	     			}
				} 
        	}
        	$getRequestUserType = DB::table('users_service_requests as usr')->
        						LeftJoin('auths','auths.id','=','usr.authid')->
        						select('auths.usertype')->where('usr.id','=',$id)->first();
        	$uType = '';
        	if(!empty($getRequestUserType)) {
        		if($getRequestUserType->usertype == 'yacht') {
        			$uType = 'yacht';
        			$dataQuery = DB::table('yachtdetail as ud')->select('ud.authid','usr.*',DB::raw("CONCAT(ud.firstname, ' ', ud.lastname) as username"),'ud.primaryimage as profile_image','ud.address');
        		} else if($getRequestUserType->usertype == 'regular') {
        			$uType = 'regular';
        			$dataQuery = DB::table('userdetails as ud')->select('ud.authid','usr.*',DB::raw("CONCAT(ud.firstname, ' ', ud.lastname) as username"),'ud.profile_image');	
        		} else {
        			return response()->json(['error'=>'networkerror'], 401);	
        		}
	       	} else {
	       		return response()->json(['error'=>'networkerror'], 401);
	       	} 
	        
	        if(!empty($authId) && (int)$authId) {
	        	$dataQuery = $dataQuery->addSelect('rp.id as proposalId',DB::raw("COUNT(rps.requestid) as recievedlead,rp.status as request_status"));
	        	$dataQuery = $dataQuery->addSelect(DB::raw('COALESCE(book.id,0) as bookmarkJob'));
	        	if($showShowReviews) {
	        		$dataQuery = $dataQuery->addSelect('srr.id','srr.comment','srr.subject','srr.rating','srr.created_at as completed_on');	
	        	}
	        } else {
	        	$dataQuery = $dataQuery->addSelect(DB::raw('COALESCE(0) as applyJob'),DB::raw('COALESCE(0) as bookmarkJob'));
	        }


	        $dataQuery = $dataQuery->Join('users_service_requests as usr','ud.authid','=','usr.authid');		    
    		if(!empty($authId) && (int)$authId) {
    			$dataQuery = $dataQuery->leftJoin('bookmark_requests as book',function($join)use ($authId){
    				$join->on('book.requestid', '=', 'usr.id')
        			->where('book.authid', '=', (int)$authId);
				});
    			$dataQuery = $dataQuery->leftJoin('request_proposals as rp',function($join)  use ($authId){
        			$join->on('rp.requestid', '=', 'usr.id')
            		->where('rp.companyid', '=',(int)$authId);
    			});
    			$dataQuery = $dataQuery->leftJoin('request_proposals as rps',function($join){
        			$join->on('rps.requestid', '=', 'usr.id')
            		->whereIn('rps.status',  array("pending","active", "declined","completed"));
    			});
    			if($showShowReviews) {
	        		$dataQuery = $dataQuery->leftJoin('service_request_reviews as srr',function($join)use ($id){
        				$join->on('srr.fromid', '=', 'usr.authid')
            			->where('srr.requestid', '=', $id);
    				});	
	        	}
    		}
        	$dataQuery = $dataQuery->where('ud.status','=',"active")
        		->where('usr.id','=',$id);
        	if(!empty($authId) && (int)$authId) {
        		if($showShowReviews) {
	        		$dataQuery->groupBy('rps.requestid','rp.id','ud.id','usr.id','srr.id','book.id');	
	        	} else {
	        		$dataQuery->groupBy('rps.requestid','rp.id','ud.id','usr.id','book.id');	
	        	}
        		
        	}
	        $data =	$dataQuery->first();
	        if(!empty($data)) {
	        	$isSameUser = false;
	        	if(!empty($authId)) {
	        		if((int)$authId === (int)$data->authid) {
	        			$isSameUser = true;
	        		}
	        	}
	        	if($uType == 'regular') {
	        		$data->utype = 'regular';
	        	} else {
	        		$data->utype = 'yacht';
	        	}
	        	$service = json_decode($data->services);
				$allservices = Service::where('status','=','1')->whereOr('category','11')->select('id', 'service as itemName')->get()->toArray();
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
					if($catId == '11') {
						$newService['Service & Repair'][] = $SerIds;
					} else {
						$newService[$newallCategory[$catId]] = [];
						foreach ($SerIds as $sid => $sval) {
							if(isset($newallservices[$sval])) {
								$newService[$newallCategory[$catId]][] =  $newallservices[$sval];
							}
						}
					}
				}
				$data->newservices =  $newService;
				if($data->charterDays) $data->charter_formatted_days = explode(",",$data->charterDays);
				$someRequest = array('completed','pending','active','declined','rejected');
				if($isSameUser) {
					$CompanyData = DB::table('request_proposals as rq')->select('rq.status','cmp.name','cmp.primaryimage','cmp.authid','cmp.slug','cmp.address','cmp.city','cmp.country','cmp.state','cmp.zipcode')->LeftJoin('companydetails as cmp','cmp.authid','=','rq.companyid')->where('rq.requestid','=',(int)$id)->where('rq.status','!=','deleted')->orderBy('rq.status','ASC')->get();
					//echo "<pre>";print_r($CompanyData);
					if(!empty($CompanyData)) {
						$data->appliedUser = $CompanyData;
					}
				}
	        	return response()->json(['success' => true,'data' => $data,'isUser'=>$isSameUser], $this->successStatus);
	        } else {
	        	return response()->json(['success' => false,'data' => []], $this->successStatus); 
	        }
		}


		//Get latest ratings
		public function getLatestRatings() {
			//~ $getLatestReview = DB::table('service_request_reviews as sr')->select(DB::raw("CONCAT(ud.firstname, ' ', ud.lastname) as username"),'ud.city','ud.authid','ud.state','sr.rating','sr.comment','sr.created_at','ud.profile_image')
				//~ ->Join('userdetails as ud','ud.authid','=','sr.fromid')
				//~ ->where('isdeleted','!=','1')->orderBy('sr.created_at','DESC')->limit(10)->get();
			$latestReview = DB::select("select msgmain.subject,msgmain.comment,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as firstname, unionSub1.lastname as lastname ,unionSub1.profile_image as profile_image,unionSub1.city,unionSub1.state,unionSub1.usertype 
                    from service_request_reviews as msgmain
                    left join (
                        (select authid, firstname, lastname,profile_image,city,state,COALESCE('regular') as usertype from userdetails)
                        union (select authid, firstname, lastname,primaryimage as profile_image,city,state,COALESCE('yacht') as usertype from yachtdetail)
                        union (select authid, firstname, lastname,profile_image,city,state,COALESCE('professional') as usertype from talentdetails)
                        union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image,city,state,COALESCE('company') as usertype from companydetails)
                    ) unionSub1 on unionSub1.authid = msgmain.fromid LEFT JOIN service_request_reviews as rply ON rply.parent_id = msgmain.id AND rply.isdeleted ='0' and  msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC LIMIT 10");
			if(!empty($latestReview)) {
				return response()->json(['success' => true,'data' => $latestReview], $this->successStatus); 
			} else {
				return response()->json(['success' => false,'data' => []], $this->successStatus); 
			}
		}
		//Get Jobs Details pages
		public function getjobsDetailById(Request $request) {
			$id = request('id');
			$address = request('locations');
			$showResume = request('searchbyname');
    		$longitude =0;
        	$latitude = 0;
        	$job_added_by = DB::table('jobs as j')
        				->select('auth.usertype','auth.id')
        				->JOIN('auths as auth', 'j.authid','=','auth.id')
        				->where('j.id','=',$id)
        				->first();
        	$userid = request('authid');
        	if(!empty($userid) && $userid != '') {
        		$authId = decrypt($userid); 
        	}
        
        	if(!empty($address)) {
        		$output = $this->getGeoLocation($address);
	        	$longitude = $output['longitude'];
	            $latitude = $output['latitude'];            	
	            if($longitude == 0 && $latitude == 0) {
            		return response()->json(['error'=>'networkerror'], 401);
            	}
            	$calDis = '(((acos(sin(('.$latitude.'*pi()/180)) * sin((g.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((g.latitude *pi()/180)) * cos((('.$longitude.'- g.longitude)*pi()/180))))*180/pi())*60*1.1515) as distance';
            	$orderBy = 'ORDER BY distance ASC';
            } else {
            	$calDis = '';
            	$orderBy = '';
            }
			if(!empty($id) && (int)$id) {
				$job_added_byID = encrypt($job_added_by->id);
				if ($job_added_by->usertype == 'company') {
					$dataQuery = DB::table('jobs as j')
							->select('j.services','j.title','j.description','j.salary','j.salarytype','cd.name','cd.address','j.request_uniqueid','cd.coverphoto','cd.primaryimage','j.created_at','cd.contact','businessemail','cd.websiteurl',DB::Raw('coalesce( r.totalrating , 0 ) as totalrating,coalesce( r.totalreviewed , 0 ) as totalreviewed'),'j.status as job_status','j.addedby','j.skillset');
						 if(!empty($authId) && (int)$authId) {
			        		$dataQuery = $dataQuery->addSelect(DB::raw('COALESCE(app.id,0) as applyJob'),DB::raw('COALESCE(book.id,0) as bookmarkJob'));
			        	 } else {
			        	 	$dataQuery = $dataQuery->addSelect(DB::raw('COALESCE(0) as applyJob'),DB::raw('COALESCE(0) as bookmarkJob'));
			        	 }
						$dataQuery	= $dataQuery->Join('companydetails as cd','cd.authid','=','j.authid')
							->leftJoin('reviewsview as r','r.toid','=','cd.authid');
						if(!empty($authId) && (int)$authId) {
							$dataQuery = $dataQuery->leftJoin('apply_jobs as app',function($join)use ($authId){
		        				$join->on('app.jobid', '=', 'j.id')
		            			->where('app.authid', '=', $authId);
		    				});	
		    				$dataQuery = $dataQuery->leftJoin('bookmark_jobs as book',function($join)use ($authId){
		        				$join->on('book.jobid', '=', 'j.id')
		            			->where('book.authid', '=', $authId);
		    				});
		    			}	
							// ->where('j.status','=','1')
						$job_detail	= $dataQuery->where('j.id','=',$id)
							->where('cd.status','=','active')
							->first();
				} else {
					$dataQuery = DB::table('jobs as j')
							->select('j.services','j.title','j.description','j.salary','j.salarytype',DB::raw("CONCAT(yd.firstname,' ',yd.lastname) AS name"),'yd.address','yd.country','yd.city','yd.state','yd.zipcode','j.request_uniqueid','yd.coverphoto','yd.primaryimage','j.created_at','yd.contact',DB::Raw('coalesce( r.totalrating , 0 ) as totalrating,coalesce( r.totalreviewed , 0 ) as totalreviewed'),'j.status as job_status','auth.email as businessemail','j.addedby','j.skillset');
							if(!empty($authId) && (int)$authId) {
				        		$dataQuery = $dataQuery->addSelect(DB::raw('COALESCE(app.id,0) as applyJob'),DB::raw('COALESCE(book.id,0) as bookmarkJob'));
				        	 } else {
				        	 	$dataQuery = $dataQuery->addSelect(DB::raw('COALESCE(0) as applyJob'),DB::raw('COALESCE(0) as bookmarkJob'));
				        	 }
							$dataQuery = $dataQuery->Join('yachtdetail as yd','yd.authid','=','j.authid')
							->leftJoin('reviewsview as r','r.toid','=','yd.authid')
							->leftJoin('auths as auth','auth.id','=','yd.authid');
							if(!empty($authId) && (int)$authId) {
								$dataQuery = $dataQuery->leftJoin('apply_jobs as app',function($join)use ($authId){
			        				$join->on('app.jobid', '=', 'j.id')
			            			->where('app.authid', '=', $authId);
			    				});	
			    				$dataQuery = $dataQuery->leftJoin('bookmark_jobs as book',function($join)use ($authId){
			        				$join->on('book.jobid', '=', 'j.id')
			            			->where('book.authid', '=', $authId);
			    				});
			    			}	
							$job_detail = $dataQuery->where('j.id','=',$id)
							->where('yd.status','=','active')
							->first();
				}
				
				
				if(!empty($job_detail)) {
					// if($job_added_by->usertype == 'company') {
					// 	if(isset($job_detail->request_uniqueid) && !empty($job_detail->request_uniqueid)) {
					// 		$get_same_uniqueid = DB::select("SELECT * FROM (SELECT g.id as geoid,city as geocity, state as geostate, country, zipcode,address as geoaddress FROM jobs as j JOIN geolocation as g ON g.id = j.geolocation WHERE request_uniqueid = '".$job_detail->request_uniqueid."' ) temp ".$orderBy."");		
					// 		if(!empty($get_same_uniqueid)) {
					// 			$job_detail->geolocation = $get_same_uniqueid;
					// 		}
					// 	}	
					// }
					
					// $service = json_decode($job_detail->services);
					// $allservices = Service::where('status','=','1')->select('id', 'service as itemName')->get()->toArray();
					// $newallservices = [];
					// foreach ($allservices as $val) {
					// 	$newallservices[$val['id']] = $val['itemName'];
					// }
					// $allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
					// $newallCategory = [];
					// foreach ($allCategory as $val) {
					// 	$newallCategory[$val['id']] = $val['categoryname'];
					// }
					// $newService = [];
					// $temCateArr = [];
					// foreach ($service as $catId => $SerIds) {
					// 	$newService[$newallCategory[$catId]] = [];
					// 	foreach ($SerIds as $sid => $sval) {
					// 		if(isset($newallservices[$sval])) {
					// 			$newService[$newallCategory[$catId]][] =  $newallservices[$sval];
					// 		}
					// 	}
					// }
					// $job_detail->newservices =  $newService;

					if(!empty($showResume) && ($showResume == 'true')) {
						$user_detail = DB::table('apply_jobs as ap')
							->select('usr.firstname','usr.lastname','usr.profile_image','usr.resume','usr.city','usr.state','usr.country','usr.zipcode','usr.address','usr.authid')
							->Join('talentdetails as usr','usr.authid','=','ap.authid')
							->where('ap.status','=','active')
							->where('ap.jobid','=',$id)->get();
						if(!empty($user_detail)) {
							$job_detail->applieduser = $user_detail;
						} else {
							$job_detail->applieduser = [];
						}
			        }
			        $same = false;
			        if(!empty($authId)) {
				        if((int)$authId === (int)$job_added_by->id) {
				        	$same = true;
				        }
				    }

					return response()->json(['success' => true,'data' => $job_detail,'jobuser' => $same], $this->successStatus); 
				} else {
					return response()->json(['success' => false,'data' => []], $this->successStatus);
				}	 	
			} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
		}

		//Add click business listing click
		public function addListingClickEvent(Request $request) {

			$ip = $request->ip();
			/* Use this code in case load balancer
			$ip = $this->getIp();	
			*/
			$userid =   request('authid');
			if(!empty($userid) && $userid != 'null') {
				$userid = decrypt(request('authid'));		
			} else {
				$userid = 0;
			}
			$eventType = request('event_name');
			if($eventType == 'business_click_event') {
				$saveObject = new BusinessListingClicks;
				$where = 'company_id';
				$authid = request('companyid');
			} else if($eventType == 'request_click_event') {
				$saveObject = new Requestlistingclicks;
				$where = 'request_id';
				$authid = request('request_id');
			} else if($eventType == 'job_click_event') {
				$saveObject = new Jobslistingclicks;
				$where = 'job_id';
				$authid = request('job_id');
			} else if($eventType == 'professional_click_event') {
				$saveObject = new Professionallistingclicks;
				$where = 'professional_id';
				$authid = request('professional_id');
			} else if($eventType == 'professional_page_event') {
				$saveObject = new Professionalviewpage;
				$where = 'professional_id';
				$authid = request('professional_id');
			} else if($eventType == 'request_page_event') {
				$saveObject = new Requestviewpage;
				$where = 'request_id';
				$authid = request('request_id');
			} else if($eventType == 'job_page_event') {
				$saveObject = new Jobviewpage;
				$where = 'job_id';
				$authid = request('professional_id');
			} else if($eventType == 'business_page_event') {
				$saveObject = new Businessviewpage;
				$where = 'company_id';
				$slug = request('companySlug');
				$Companydata = Companydetail::select('authid')
				->where('slug', '=',$slug)
				->where('status','!=','deleted')
				->first();
				if(!empty($Companydata)) {
					$authid = $Companydata->authid;
				} else {
					return response()->json(['error'=>'networkerror'], 401);
				}
			} else if($eventType == 'business_telephone_event') {
				$saveObject = new Businesstelephone;
				$where = 'company_id';
				$authid = request('companyid');
				if($authid == $userid) {
					$ip = null;
				}
			}
			
			if(!empty($ip)) {
				$checkIfExist = $saveObject->where('ip_address','=',$ip)->orderBy('created_at','DESC')->whereRaw("".$where." = ".$authid."")->first();
				if(!empty($checkIfExist)) {
					$created_at = strtotime($checkIfExist->created_at);
					$currentTime = strtotime(Carbon\Carbon::now());
					$diff = $currentTime - $created_at;
					if($diff < 3600) {
						return response()->json(['success' => false], $this->successStatus);	
					} else {
						$saveObject->ip_address = $ip;
						$saveObject->$where = $authid;
						$saveObject->authid = (!empty($userid) ? $userid:NULL);
						if($saveObject->save()) {
							return response()->json(['success' => true], $this->successStatus);
						} else {
							return response()->json(['error'=>'networkerror'], 401);		
						}	
					}
				} else {
					$saveObject->ip_address = $ip;
					$saveObject->$where = $authid;
					$saveObject->authid = (!empty($userid) ? $userid:NULL);
					if($saveObject->save()) {
						return response()->json(['success' => true], $this->successStatus);
					} else {
						return response()->json(['error'=>'networkerror'], 401);		
					}
				}
			} else {
				return response()->json(['error'=>'networkerror'], 401);	
			}
		}

		//get Ip Address 
		public function getIp(){
		    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
		        if (array_key_exists($key, $_SERVER) === true){
		            foreach (explode(',', $_SERVER[$key]) as $ip){
		                $ip = trim($ip); // just to be safe
		                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
		                    return $ip;
		                }
		            }
		        }
		    }
		}

		//Get Professional details
		public function getProfessionalDetailById(Request $request) {
			$id = request('id');
			// $leadsend = request('isbusiness');
			// if($leadsend == 'false') {
			// 	$leadsend =false;
			// } else if($leadsend == 'true'){
			// 	$leadsend =true;
			// }
			$leadsend =false;
			$isLogedIn = request('isloggedin');
			$selectArr = [];
			$selectArr =array('td.firstname','td.lastname','td.coverphoto','td.objective','td.workexperience','td.willingtravel','td.profile_image','td.otherjobtitle','jt.title','td.jobtitleid','td.created_at','td.resume','td.longitude','td.latitude','td.city','td.state'); 
			if(!empty($isLogedIn) && $isLogedIn) {
				$value = $request->bearerToken();
				if(!empty($value) && $value != 'statics') {
					$uid= (new Parser())->parse($value)->getHeader('jti');
	     			$userid = DB::table('oauth_access_tokens')->where('id', '=', $uid)->where('revoked', '=', false)->first()->user_id;
	     			if(!empty($userid) && $userid > 0) {
	     				$userdata = Auth::where('id', '=', $userid)->where('status' ,'=','active')->first();
	     				if(!empty($userdata)) {
	     					if($userdata->usertype == 'company' || $userdata->usertype == 'yacht') {
     							$leadsend = true;
	     					} else {
	     						$leadsend = false;
	     					}
	     					$isLogedIn = true;
	     				}
	     			}
				}
			}
			if($leadsend === true) {
				$selectArr[] = 'td.address';
				$selectArr[] = 'td.city';
				$selectArr[] = 'td.state';
				$selectArr[] = 'a.email';
				$selectArr[] = 'td.zipcode';
				$selectArr[] = 'td.mobile';
				$selectArr[] = 'td.certification';
				$selectArr[] = 'td.licences';				
			}
			if(!empty($id) && (int)$id) {
				$professionalDetails = DB::table('talentdetails as td')->select($selectArr)
				->leftJoin('jobtitles as jt','jt.id','=','td.jobtitleid')
				->Join('auths as a','a.id','=','td.authid')
				->where('td.status','=','active')
				->where('td.authid','=',$id)
				->get();
				if(!empty($professionalDetails) && count($professionalDetails)) {
					if(!$leadsend) {
						$professionalDetails[0]->resume = null;
						$professionalDetails[0]->certification = null;
						$professionalDetails[0]->licences = null;
 					}
					return response()->json(['success' => true,'data' => $professionalDetails], $this->successStatus);
				} else {
					return response()->json(['success' => false,'data' => []], $this->successStatus);
				}
			} else {
				return response()->json(['error'=>'networkerror'], 401);
			}
		}
		//Boat ower detail page (userdetails table)
		public function getBoatOwnerDetails(Request $request) {
			$id = request('id');
			if(!empty($id) && (int)$id) {
				// $userdetail = Userdetail::find($id);
				// $data = $userdetail->serviceRequests;
				$data = DB::table('userdetails as ud')
						->select('ud.authid','ud.firstname','ud.coverphoto','ud.lastname','ud.profile_image','ud.created_at','ud.latitude','ud.longitude','ud.state','ud.city','ud.country',DB::Raw('coalesce( r.totalrating , 0 ) as totalrating,coalesce( r.totalreviewed , 0 ) as totalreviewed'))
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
								// $newService[$newallCategory[$catId]] = [];
								foreach ($SerIds as $sid => $sval) {
									if(isset($newallservices[$sval]) && !in_array($newallservices[$sval], $newService)) {
										// $newService[$newallCategory[$catId]][] =  $newallservices[$sval];
										$newService[] =  $newallservices[$sval];
									}
								}
							}
							$data[0]->service_requests[$skey]->newservice = $newService;
							// unset($data[0]->service_requests[$skey]->services);
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

		public function getYachtDetail(Request $request) {
			$id = request("id");
			if(!empty($id) && (int)$id) {
				$data = DB::table('yachtdetail as yd')
					->select('yd.authid','yd.firstname','yd.lastname','yd.contact','yd.address','yd.city','yd.state','yd.zipcode','yd.country','yd.longitude','yd.latitude','yd.yachtdetail','yd.homeport','yd.images','yd.coverphoto','yd.created_at','yd.primaryimage','a.email',DB::Raw('coalesce( r.totalrating , 0 ) as totalrating,coalesce( r.totalreviewed , 0 ) as totalreviewed'))
						->Join('auths as a','a.id','=','yd.authid')
						//->leftJoin('jobs as j','j.authid','=','yd.authid')
						//->Join('users_service_requests as usr','yd.authid','=','usr.authid')
						//(,'j.services as jobservices','j.title as jobtitle','j.description as jobdescription','j.salary as jobsalary','j.salarytype as jobsalarytype')
						// ->leftJoin('jobs as j', function($join){
						//         $join->on('j.authid','=','yd.authid')
						//         ->where('j.status' ,'=', '1');
						        
						// })
						->leftJoin('reviewsview as r','r.toid','=','yd.authid')
	        			->where('yd.authid' ,'=', (int)$id)
						//->where('usr.status','!=',"deleted")
						->where('yd.status' ,'=', 'active')
						->get();
				 	//echo "<pre>";print_r($data);die;
				 	if(!empty($data) && isset($data[0])) {
				 		$authid = $data[0]->authid;
						$jobs = DB::table('jobs')->select(DB::Raw('id,title,services,salarytype,salary,description,created_at'))
						->where('authid',$authid)
						->orderBy('created_at','DESC')->get();
						$data[0]->jobs = $jobs;
						$requested_service = User_request_services::select('id',	'title','description','numberofleads','services','addspecialrequirement','created_at')->where('authid','=',$id)->where('status','!=','deleted')->orderBy('created_at','DESC')->get();

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
						//$allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
						//$newallCategory = [];
						//foreach ($allCategory as $val) {
							//$newallCategory[$val['id']] = $val['categoryname'];
						//}
						// foreach ($data[0]->service_requests as $skey => $val) {
						// 	$service = json_decode($val->services);
						// 	$newService = [];
						// 	$temCateArr = [];
						// 	foreach ($service as $catId => $SerIds) {
						// 		$newService[$newallCategory[$catId]] = [];
						// 		foreach ($SerIds as $sid => $sval) {
						// 			if(isset($newallservices[$sval])) {
						// 				$newService[$newallCategory[$catId]][] =  $newallservices[$sval];
						// 			}
						// 		}
						// 	}
						// 	$data[0]->service_requests[$skey]->newservice = $newService;
						// 	unset($data[0]->service_requests[$skey]->services);
						// }
						$data[0]->allservicename = $newallservices;
				 		return response()->json(['success' => true,'data' => $data], $this->successStatus);
				 	} else {
				 		return response()->json(['success' => false,'data' => []], $this->successStatus);
				 	}
			} else {
				return response()->json(['error'=>'networkerror'], 401);		
			}
		}

		// get all regular users //
	    public function getadvertisementdata(Request $request) {
	    	$validate = Validator::make($request->all(), [
				'page' => 'required'
        	]);
        	$city = request('business_serviceCity');
        	$state = request('state');
        	$zipcode = request('zipcode');

	        if ($validate->fails()) {
	            return response()->json(['error'=>'validationError'], 401); 
	        }
	        $page_type = '';

        	$route =  json_decode(request('page'));
        	$pageQuery = '';
        	$zipcodeArr = [];
        	$cityQuery = $stateQuery = $zipQuery= '' ;
        	if(count($route) && isset($route[0])){
		 		if(strpos($route[0],'biz')) {
		 			$page_type = 'biz';
		 		} else if(strpos($route[0],'view')) {
		 			$page_type = 'view';
		 		} else if(strpos($route[0],'find')) {
	 				$page_type = 'find';
		 		} else {
		 			$page_type = 'resume';
		 		}
	 		 	$pageQuery = "pages::jsonb @>  '[\"top_bar_".$page_type."\"]' OR pages::jsonb @>  '[\"side_bar_top_".$page_type."\"]' OR pages::jsonb @> '[\"side_bar_bottom_".$page_type."\"]' OR pages::jsonb @> '[\"bottom_bar_".$page_type."\"]'";
    		 
        	} else {
        		return response()->json(['error'=>'networkerror'], 401); 
        	}
        	$pageQuery =  'AND ('.$pageQuery.')';
        	if(empty($state)) {
        		//Get city state zipcode from geoencoding
        		$adderess = request('address');
        		$locInfo = $this->zip($adderess);
        		$state = $locInfo['state'];
        		$city = $locInfo['city'];
        		$zipcode = $locInfo['zipcode'];
        	}
        	$stateQuery = "AND ('".$state."' = ANY(SELECT json_array_elements_text(selectedstate)) OR 'All States' = ANY(SELECT json_array_elements_text(selectedstate)))";
			if(!empty($city)) {
			$cityQuery = "AND ('".$city."' = ANY(SELECT json_array_elements_text(selectedcity)) OR 'All Cities' = ANY(SELECT json_array_elements_text(selectedcity)))";
        	}
        	if(!empty($zipcode)) {
    			$zipQuery = "AND ('".$zipcode."' = ANY(SELECT json_array_elements_text(selectedzipcode)) OR 'All Zipcodes' = ANY(SELECT json_array_elements_text(selectedzipcode)))";
        	}
        	$cuurent_time = date('Y-m-d H:i:s');
        	$advertisedata = DB::select("SELECT id, name,link,vertical_image,horizontal_image,home_image,keywords,pages,vertical_image_bottom,horizontal_image_bottom FROM advertisement where status = '1' ".$pageQuery." ".$cityQuery." ".$stateQuery." ".$zipQuery." AND start_time <= '".$cuurent_time."' AND '".$cuurent_time."' <= end_time" );
         	 
        	if(!empty($advertisedata)) {
	        	$verAds = $horAds = $verAds2 = $horAds2 = [];
	        	$horCount = $verCount = $verCount2 = $horCount2 = 0;	       
        	 	foreach ($advertisedata as $key => $value) {
        	 		$page = json_decode($value->pages,true);
        	 		
        			if(!empty($value->horizontal_image) && (in_array('top_bar_'.$page_type,$page))) {
        				$horAds[$horCount] = $value;
        				$horCount++;
        			} 
        			if(!empty($value->vertical_image) && (in_array('side_bar_top_'.$page_type,$page))) {
    					$verAds[$verCount] = $value;	
    					$verCount++;
        			}

        			if(!empty($value->vertical_image_bottom) &&  (in_array('side_bar_bottom_'.$page_type,$page))) {
    					$verAds2[$verCount2] = $value;	
    					$verCount2++;
        			}
        			if(!empty($value->horizontal_image_bottom) &&  (in_array('bottom_bar_'.$page_type,$page))) {
    					$horAds2[$horCount2] = $value;	
    					$horCount2++;
        			}
	        	}
	            return response()->json(['success' => true,'horAds' => $horAds,'verAds' => $verAds,'verAds_bottom' => $verAds2,'horAds_bottom' => $horAds2], $this->successStatus);
	        } else {
	            return response()->json(['error'=>'networkerror'], 401); 
	        }
	    }

	    //Get Business details by Slug
		public function getBusinessDetailBySlug(Request $request) {
			$validate = Validator::make($request->all(), [
	            'slug' => 'required'
	        ]);
			if ($validate->fails()) {
				print_r($validate->message());die;
	            return response()->json(['error'=>'validationError'], 401); 
	        }
	        $address = request('location');
	        $loggedUser = (int)request('loggedUserID');
	        if(!empty($address)) {
        		$output = $this->getGeoLocation($address);
	        	$longitude = $output['longitude'];
	            $latitude = $output['latitude'];            	
	            if($longitude == 0 && $latitude == 0) {
            		return response()->json(['error'=>'networkerror'], 401);
            	} 	
            } else {
            	$longitude = '-81.515754';
	            $latitude = '27.664827';
            }
            // $calDis = '((((acos(sin(('.$latitude.'*pi()/180)) * sin((cd.latitude *pi()/180))+cos(('.$latitude.'*pi()/180)) * cos((cd.latitude *pi()/180)) * cos((('.$longitude.'- cd.longitude)*pi()/180))))*180/pi())*60*1.1515))';
	        $calDis = "2 * 3961 * asin(sqrt((sin(radians((cd.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(cd.latitude)) * (sin(radians((cd.longitude - ".$longitude.") / 2))) ^ 2))";
			$slug = strtolower(request('slug'));
			$addressslug = strtolower(request('addressslug'));
			$addressslug = str_replace('-',' ',$addressslug);
			$data = DB::table('companydetails as cd')->select('cd.authid','cd.name as businessname','cd.about as description','cd.contact','cd.contactmobile','cd.country_code','cd.city','cd.coverphoto','cd.primaryimage','cd.businessemail','cd.created_at','cd.images','cd.services','cd.allservices','cd.address','cd.state','cd.country','cd.zipcode','cd.businessemail','cd.websiteurl','cd.accounttype','cd.longitude','cd.latitude','cd.boats_yachts_worked','cd.engines_worked','cd.country_code',DB::raw('coalesce( r.totalreviewed , 0 ) as totalreviewed,coalesce( r.totalrating , 0 ) as totalrated'))
				->leftJoin('reviewsview as r','cd.authid','=','r.toid')
				->where('cd.status','=','active')
				->whereRaw("LOWER(cd.slug) ='".$slug."'")
				->orderBy(DB::Raw($calDis),'ASC')
				->get();
			if(!empty($data)) {
				// if(count($data) > 0) {
				// 	$geoArr = [];
				// 	foreach ($data as $key => $val) {
				// 		$geoArr[$key]['zipcode'] = $val->zipcode;
				// 		$geoArr[$key]['address'] = $val->address;
				// 		$geoArr[$key]['country'] = $val->country;
				// 		$geoArr[$key]['city'] = $val->city;
				// 		$geoArr[$key]['state'] = $val->state;	
				// 		$geoArr[$key]['longitude'] = $val->longitude;
				// 		$geoArr[$key]['latitude'] = $val->latitude;
				// 	}	
				// }
				if(isset($data[0])) {
					$authid = $data[0]->authid;
					$isSame = false;
					if($loggedUser == $authid) {
						$isSame = true;
					}
					//~ $latestReview = DB::table('service_request_reviews as sr')
						//~ ->select(DB::raw("CONCAT(ud.firstname, ' ', ud.lastname) as username"),'ud.profile_image','sr.rating','sr.subject','sr.comment','sr.created_at')
						//~ ->Join('userdetails as ud','ud.authid','=','sr.fromid')
						//~ ->where('isdeleted','!=','1')
						//~ ->where('toid','=',$authid)
						//~ ->orderBy('sr.created_at','DESC')->get();
					$geolocations = Geolocation::select('id','authid','city','state','zipcode','created_at')
                        ->where('authid', '=',(int)$authid)
                        ->where('status','=','1')
                        ->get();
                     $notificationDate = date('Y-m-d H:i:s');
					$notificationAfter72 = date('Y-m-d H:i:s', strtotime("- 3 days", strtotime(date('Y-m-d H:i:s'))));
                     if($isSame) {
						 $latestReview = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype ,DATE_PART('day','".$notificationDate."'::timestamp - msgmain.created_at::timestamp) as datediff,rplyCmp.name as replycompanyname,rply.id as replyid,rplyCmp.primaryimage as replycompanyprimaryimage,rply.created_at as replycreated_at,rply.fromid as replyfromid,rply.comment as replycomment 
							from service_request_reviews as msgmain
							left join (
								(select authid, firstname, lastname,profile_image from userdetails)
								union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
								union (select authid, firstname, lastname,profile_image from talentdetails)
								union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
							) unionSub1 on unionSub1.authid = msgmain.fromid LEFT JOIN service_request_reviews as rply ON rply.parent_id = msgmain.id AND rply.isdeleted ='0' LEFT JOIN companydetails as rplyCmp ON rply.fromid = rplyCmp.authid where  msgmain.toid = ".(int)$authid." AND msgmain.parent_id = 0 AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
					} else {
						$latestReview = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype ,DATE_PART('day','".$notificationDate."'::timestamp - msgmain.created_at::timestamp) as datediff,rplyCmp.name as replycompanyname,rply.id as replyid,rplyCmp.primaryimage as replycompanyprimaryimage,rply.created_at as replycreated_at,rply.fromid as replyfromid,rply.comment as replycomment 
							from service_request_reviews as msgmain
							left join (
								(select authid, firstname, lastname,profile_image from userdetails)
								union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
								union (select authid, firstname, lastname,profile_image from talentdetails)
								union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
							) unionSub1 on unionSub1.authid = msgmain.fromid LEFT JOIN service_request_reviews as rply ON rply.parent_id = msgmain.id AND rply.isdeleted ='0' LEFT JOIN companydetails as rplyCmp ON rply.fromid = rplyCmp.authid where  msgmain.toid = ".(int)$authid." AND ((msgmain.created_at < '".$notificationAfter72."' AND msgmain.rating < 3) OR (msgmain.rating > 2) OR (msgmain.created_at >= '".$notificationAfter72."' AND msgmain.rating < 3 AND msgmain.fromid = ".$loggedUser."))  AND msgmain.parent_id = 0 AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
					}
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
					$allSubCategoryArr = [];
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
							// unset($newService[$newallCategory[$catId]]);
						}
					}
					// echo '<pre>';print_r($newService);die;
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

		public function getReviewInfo(Request $request) {
						$validate = Validator::make($request->all(), [
							'slug' => 'required'
						]);
						if ($validate->fails()) {
							print_r($validate->message());die;
							return response()->json(['error'=>'validationError'], 401); 
						}
						$loggedUser = (int)request('loggedUserID');
						$slug = strtolower(request('slug'));
						$data = DB::table('companydetails as cd')->select('cd.authid')
							->where('cd.status','=','active')
							->whereRaw("LOWER(cd.slug) ='".$slug."'")
							->get();
						if(!empty($data)) {
							if(isset($data[0])) {
								$authid = $data[0]->authid;
								$isSame = false;
								if($loggedUser == $authid) {
									$isSame = true;
								}
								$notificationDate =  date('Y-m-d H:i:s');
								$notificationAfter72 = date('Y-m-d H:i:s', strtotime("- 3 days", strtotime(date('Y-m-d H:i:s'))));
								if($isSame) {
									$latestReview = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype ,rplyCmp.name as replycompanyname,rplyCmp.primaryimage as replycompanyprimaryimage,rply.created_at as replycreated_at,rply.id as replyid,rply.fromid as replyfromid,rply.comment as replycomment 
									from service_request_reviews as msgmain
									left join (
										(select authid, firstname, lastname,profile_image from userdetails)
										union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
										union (select authid, firstname, lastname,profile_image from talentdetails)
										union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
									) unionSub1 on unionSub1.authid = msgmain.fromid LEFT JOIN service_request_reviews as rply ON rply.parent_id = msgmain.id AND rply.isdeleted ='0' LEFT JOIN companydetails as rplyCmp ON rply.fromid = rplyCmp.authid where  msgmain.toid = ".(int)$authid." AND msgmain.parent_id = 0 AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
									
								 } else {
									$latestReview = DB::select("select msgmain.id,msgmain.parent_id, msgmain.toid,msgmain.subject,msgmain.comment,msgmain.rating,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname ,unionSub1.profile_image as from_profile_image,msgmain.created_at,msgmain.from_usertype ,DATE_PART('day','".$notificationDate."'::timestamp - msgmain.created_at::timestamp) as datediff,rplyCmp.name as replycompanyname,rply.id as replyid,rplyCmp.primaryimage as replycompanyprimaryimage,rply.created_at as replycreated_at,rply.fromid as replyfromid,rply.comment as replycomment 
										from service_request_reviews as msgmain
										left join (
											(select authid, firstname, lastname,profile_image from userdetails)
											union (select authid, firstname, lastname,primaryimage as profile_image from yachtdetail)
											union (select authid, firstname, lastname,profile_image from talentdetails)
											union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image from companydetails)
										) unionSub1 on unionSub1.authid = msgmain.fromid LEFT JOIN service_request_reviews as rply ON rply.parent_id = msgmain.id AND rply.isdeleted ='0' LEFT JOIN companydetails as rplyCmp ON rply.fromid = rplyCmp.authid where  msgmain.toid = ".(int)$authid." AND ((msgmain.created_at < '".$notificationAfter72."' AND msgmain.rating < 3) OR (msgmain.rating > 2) OR (msgmain.created_at >= '".$notificationAfter72."' AND msgmain.rating < 3 AND msgmain.fromid = ".$loggedUser."))  AND msgmain.parent_id = 0 AND msgmain.isdeleted != '1' ORDER BY msgmain.created_at DESC");
								}
								return response()->json(['success' => true,'data' => $latestReview], $this->successStatus);
							} else {
								return response()->json(['success' => false,'data' => []], $this->successStatus);
							}
						} else {
							return response()->json(['success' => false,'data' => []], $this->successStatus); 
						} 
					}

		public function getBlogById(Request $request) {
            $validate = Validator::make($request->all(), [
                'id' => 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            $blogid = request('id');
            if(!empty($blogid) && $blogid > 0) {
                $blogs = Blogs::where('id',(int)$blogid)
                    ->get();
                if(!empty($blogs)) {
                    return response()->json(['success' => true,'data' => $blogs], $this->successStatus); 
                } else {
                    return response()->json(['error' => 'networkerror'], $this->successStatus);
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }

        //Get Blogs
	    public function getBlogs(Request $request) {
	        $blogs = Blogs::select('id','title','blogimage','description','created_at','status')
	            ->orderBy('created_at','DESC')
	            ->where('status','!=','deleted')
	            ->where('status','=','publish')
	            ->get();
	        if(!empty($blogs)) {
	            return response()->json(['success' => true,'data' => $blogs], $this->successStatus); 
	        } else {
	            return response()->json(['success' => false], $this->successStatus);
	        }
	    }
	    //Get All subcategories and services 
	    public function getAllSubcategories(Request $request){
			// $allCategoryData = DB::select("SELECT c.id as category_id ,coalesce( sub.id , 0 )as subcategory_id,c.categoryname,sub.subcategory_name,s.service FROM category as c LEFT JOIN subcategory as sub ON c.id = sub.category_id AND sub.status = '1' LEFT JOIN services as s ON c.id = s.category AND (sub.id = s.subcategory OR s.subcategory = 0) AND s.status = '1' where c.status = '1' ORDER BY c.categoryname ASC");
			$allCategory = DB::select("SELECT * from subcategory where status = '1'");    
			
			// $allCategoryData = DB::select("SELECT s.id as service_id,s.category as category_id,coalesce( sub.id , 0 ) as subcategory_id,sub.subcategory_name,s.service FROM services as s LEFT JOIN subcategory as sub ON s.subcategory = sub.id   AND sub.status = '1' where s.status = '1'");
			$allServices = DB::select("SELECT * from services where status = '1'");    
			if(!empty($allServices) && $allCategory) {
				// $category = [];
				// $subcategory = [];
				// $data = [];
				// foreach ($allCategoryData as $key => $value) {
				// 	if(in_array($value->category_id, $category)){
				// 		$data[$value->category_id][$value->subcategory_name][] = $value->service;
				// 	} else {
				// 		$category[] = $value->category_id;					  
				// 		$data[$value->category_id][$value->subcategory_name][] = $value->service;
				// 	}
				// }
				return response()->json(['success' => true,'allCategory' => $allCategory,'subcategory' => $allServices], $this->successStatus);
			} else {
				return response()->json(['success' => false], $this->successStatus);
			}						
	    }
	    //Get All subcategories and services 
        public function getAllcategoryDataDemo(Request $request){
            $allcategory = DB::select("SELECT id,categoryname from category where categoryname != 'Others'");
	        $showFinalArr = [];
	        if(!empty($allcategory)) {
	        	foreach ($allcategory as $ckey => $cval) {
	        		$id = $cval->id;
	        		$allSubCategory = DB::select("SELECT id,subcategory_name,category_id from subcategory where status = '1' and category_id=".$id."ORDER BY subcategory_name ASC");
					$allServices = DB::select("SELECT id,COALESCE('service') as type,COALESCE(false) as checked,service as name,subcategory,category from services where status = '1' AND category=".$id." ORDER BY subcategory ASC");
					if(!empty($allSubCategory)) {
						$finalArr = [];
						$count = 0;
						foreach ($allSubCategory as $key => $value) {
							// if($id == '2'){print_r($value);die;}
							$finalArr[$count]['id'] = $value->id;
							$finalArr[$count]['type'] = 'subcategory';
							$finalArr[$count]['name'] = $value->subcategory_name;
							$count++;
							foreach ($allServices as $skey => $sval) {
								if($sval->subcategory == $value->id){
									$finalArr[$count]['id'] = $sval->id;
									$finalArr[$count]['type'] = 'service';
									$finalArr[$count]['name'] = $sval->name;
									$finalArr[$count]['checked'] = false;
									$finalArr[$count]['disable'] = false;	
									$count++;
								}
							}
							$category = Category::find($id);
					        $category->serviceslist = json_encode($finalArr);
					        $category->save();
						}
	        		} else {
	        			$category = Category::find($id);
				        $category->serviceslist = json_encode($allServices);
				        $category->save();	
	        		}
	        	}
	        }
        }
        //Get All subcategories and services 
        public function getAllcategoryData(Request $request){
            $allCategoryData = DB::select("SELECT c.id as category_id ,coalesce( sub.id , 0 )as subcategory_id,c.categoryname,sub.subcategory_name,s.service,s.id as service_id FROM category as c LEFT JOIN subcategory as sub ON c.id = sub.category_id AND sub.status = '1' LEFT JOIN services as s ON c.id = s.category AND (sub.id = s.subcategory OR s.subcategory = 0) AND s.status = '1' where c.status = '1' ORDER BY c.categoryname ASC");
            if(!empty($allCategoryData)) {
             $category = [];
             $subcategory = [];
             $data = [];
             foreach ($allCategoryData as $key => $value) {
             	if(in_array($value->category_id, $category)){
                 	if(!empty($value->subcategory_id)) {
						$data[$value->category_id]['category'] = $value->categoryname;
						$data[$value->category_id]['checked'] = false;
						$data[$value->category_id]['subcategory'] = true;
						$data[$value->category_id]['categoryid'] = $value->category_id;
						$data[$value->category_id]['sub_category'][$value->subcategory_id]['subcategory'] = $value->subcategory_name;
						$data[$value->category_id]['sub_category'][$value->subcategory_id]['subcategory_id'] = $value->subcategory_id;
						 $data[$value->category_id]['sub_category'][$value->subcategory_id]['services'][$value->service_id]['servicename'] = $value->service;
                         $data[$value->category_id]['sub_category'][$value->subcategory_id]['services'][$value->service_id]['checked'] = false;
                         $data[$value->category_id]['sub_category'][$value->subcategory_id]['services'][$value->service_id]['disable'] = false;
                         $data[$value->category_id]['sub_category'][$value->subcategory_id]['services'][$value->service_id]['id'] = $value->service_id;
                 	} else {
						$data[$value->category_id]['category'] = $value->categoryname;
						$data[$value->category_id]['checked'] = false;
						$data[$value->category_id]['categoryid'] = $value->category_id;
						$data[$value->category_id]['subcategory'] = false;
						$data[$value->category_id]['allservices'][$value->service_id]['servicename'] = $value->service;
						$data[$value->category_id]['allservices'][$value->service_id]['disable'] = false;
						$data[$value->category_id]['allservices'][$value->service_id]['id'] = $value->service_id;
						$data[$value->category_id]['allservices'][$value->service_id]['checked'] = false;
                     }
             	} else {
                 	$category[] = $value->category_id;  
                 	if(!empty($value->subcategory_id)) {
                         $data[$value->category_id]['category'] = $value->categoryname;
                         $data[$value->category_id]['subcategory'] = true;
                         $data[$value->category_id]['categoryid'] = $value->category_id;
                         $data[$value->category_id]['checked'] = false;
                         $data[$value->category_id]['sub_category'][$value->subcategory_id]['subcategory'] = $value->subcategory_name;
                         $data[$value->category_id]['sub_category'][$value->subcategory_id]['subcategory_id'] = $value->subcategory_id;
                         $data[$value->category_id]['sub_category'][$value->subcategory_id]['services'][$value->service_id]['servicename'] = $value->service;
                         $data[$value->category_id]['sub_category'][$value->subcategory_id]['services'][$value->service_id]['checked'] = false;
                         $data[$value->category_id]['sub_category'][$value->subcategory_id]['services'][$value->service_id]['disable'] = false;
                         $data[$value->category_id]['sub_category'][$value->subcategory_id]['services'][$value->service_id]['id'] = $value->service_id;

                 	} else {
                         $data[$value->category_id]['category'] = $value->categoryname;
                         $data[$value->category_id]['subcategory'] = false;
                         $data[$value->category_id]['categoryid'] = $value->category_id;
                         $data[$value->category_id]['checked'] = false;
                         $data[$value->category_id]['allservices'][$value->service_id]['servicename'] = $value->service;
						$data[$value->category_id]['allservices'][$value->service_id]['id'] = $value->service_id;
						$data[$value->category_id]['allservices'][$value->service_id]['disable'] = false;
						$data[$value->category_id]['allservices'][$value->service_id]['checked'] = false;
                 	}                 
                 }
             }
      //    	usort($data,function ($a, $b) use ($data) {
		    //     if ($a["category"] == 'Others') {return 1;}
		    //     return (strcmp($a["category"],$b["category"]));
		    // });
             // echo '<pre>';print_r($data);die;
             return response()->json(['success' => true,'data' => $data], $this->successStatus);
            } else {
             return response()->json(['success' => false], $this->successStatus);
            }                        
        }

        public function getAllsubCategoryData(Request $request){
        	$id=request("category");
        	if(empty($id)){
        		return response()->json(['success' => false], $this->successStatus);
        	}
        	$allCategory = DB::select("SELECT id,subcategory_name,category_id from subcategory where status = '1' and category_id=".$id."ORDER BY subcategory_name ASC");
			$allServices = DB::select("SELECT id,COALESCE('service') as type,service as name,subcategory from services where status = '1' AND category=".$id." ORDER BY subcategory ASC");
			// Service::select('id','')->where('category',$id)->get();
			if(!empty($allCategory)) {
				$category = [];
				$finalArr = [];
				$count = 0;
				foreach ($allCategory as $key => $value) {
					$finalArr[$count]['id'] = $value->id;
					$finalArr[$count]['type'] = 'subcategory';
					$finalArr[$count]['name'] = $value->subcategory_name;
					$count++;
					foreach ($allServices as $skey => $sval) {		
						if($sval->subcategory == $value->id){
							$finalArr[$count]['id'] = $sval->id;
							$finalArr[$count]['type'] = 'service';
							$finalArr[$count]['name'] = $sval->name;
							$finalArr[$count]['checked'] = false;
							$finalArr[$count]['subcategory'] = $sval->subcategory;	
							$count++;
						}
					}		
				}
				return response()->json(['success' => true,'data' => $finalArr], $this->successStatus);
			} else {
		 	 	return response()->json(['success' => true,'data' => $allServices], $this->successStatus);
			}
        }

        public function getAllCategory(){
        	$data = Category::where('status','!=','0')->orderBy('id','ASC')->get();
        	if(!empty($data)){
        		foreach ($data as $key => $value) {
        			$data[$key]['serviceslist'] = json_decode($value->serviceslist);
        		}
        		return response()->json(['success' => true,'data' => $data], $this->successStatus);
        	} else {
        		return response()->json(['success' => false], $this->successStatus);
        	}
        }

        public function getAllSubcategory(Request $request){
        	$id = request('id');
        	if(!empty($id)) {
        		$subcategory = Subcategory::select('id','category_id','subcategory_name')->where('category_id',$id)->where('status','1')->get();
        		if(!empty($subcategory)) {
        			return response()->json(['success' => true,'data' =>$subcategory ], $this->successStatus);
        		} else {
        			return response()->json(['success' => false], $this->successStatus);
        		}
        		
        	} else {
        		return response()->json(['error'=>'networkerror'], 401);
        	}
        }
        public function getAllservices(Request $request){
    		$id = request('categoryId');
    		$subId = request('subcategoryid');
    		if(!empty($id)) {
    			$services = Service::select('id','service','category','subcategory')->where('category_id',$id)->where('status','1')->get();
    			if($services) {
    				return response()->json(['success' => true,'data' =>$services ], $this->successStatus);
    			} else {
    				return response()->json(['success' => false], $this->successStatus);
    			}
    		} else {
        		return response()->json(['error'=>'networkerror'], 401);    			
    		}
        }
        public function getAllServiceAndRepair(){
        	$services = Service::select('service as itemName',DB::Raw('COALESCE(false) as checked'))->where('category','=','6')->get();
        	if(!empty($services)) {

        		return response()->json(['success' => true,'data' =>$services ], $this->successStatus);
        	} else {
        		return response()->json(['success' => false], $this->successStatus);
        	}
        }
        public function allSuggestion(){
			
        	$services = Service::select('service as itemName',DB::raw('COALESCE(\'service\') as itemtype'),DB::raw('COALESCE(\'service\') as itemslug'))->where('status','1')->distinct('service')->orderBy('service','ASC')->get();
        	//~ $boats_yacht_engineData = Boat_Engine_Companies::select('name as itemName',DB::raw('COALESCE(\'service\') as itemtype'),DB::raw('COALESCE(\'service\') as itemslug'))->where('status','1')->orderBy('itemName','ASC')->get();
        	$services = JSON_decode($services,true); 
        	//~ $boats_yacht_engineData = JSON_decode($boats_yacht_engineData,true); 
        	//~ $services = array_merge( $services, $boats_yacht_engineData);
        	if(!empty($services)) {
        		return response()->json(['success' => true,'data' =>$services ], $this->successStatus);
        	} else {
        		return response()->json(['success' => false], $this->successStatus);
        	}
        }
        
        public function getallcountdata(){
			$dateBefore72 = date('Y-m-d H:i:s', strtotime("- 3 days", strtotime(date('Y-m-d H:i:s'))));
			$getCountRegistor  = Auth::where('usertype','!=','admin')->where('status','!=','deleted')->count();
			$getCountRegistorBiz  = Auth::where('usertype','=','company')->where('status','!=','deleted')->count();
			$getCountReview  = DB::table('website_reviews')->select(DB::raw('avg(rating) as avgrate'))->where('isdeleted','=','0')->get();
			if(!empty($getCountReview)) {
				$getAvgReview = number_format((float)$getCountReview[0]->avgrate, 1, '.', '');
			} else {
				$getAvgReview = 0;
			}
				
        	return response()->json(['success' => true,'registor' =>$getCountRegistor,'countReview' => $getCountReview,'avgReview' => $getAvgReview , 'registorBiz' => $getCountRegistorBiz ], $this->successStatus);
        }
        
        //Get latest ratings
		public function getLatestRatingsHome() {
			//~ $getLatestReview = DB::table('service_request_reviews as sr')->select(DB::raw("CONCAT(ud.firstname, ' ', ud.lastname) as username"),'ud.city','ud.authid','ud.state','sr.rating','sr.comment','sr.created_at','ud.profile_image')
				//~ ->Join('userdetails as ud','ud.authid','=','sr.fromid')
				//~ ->where('isdeleted','!=','1')->orderBy('sr.created_at','DESC')->limit(10)->get();
			$latestReview = DB::select("select msgmain.subject,msgmain.id,msgmain.comment,msgmain.fromid,COALESCE(unionSub1.firstname,NULL) as firstname, unionSub1.lastname as lastname ,unionSub1.profile_image as profile_image,unionSub1.city,unionSub1.state,unionSub1.usertype 
                    from service_request_reviews as msgmain
                    join (
                        (select authid, firstname, lastname,profile_image,city,state,COALESCE('regular') as usertype from userdetails)
                        union (select authid, firstname, lastname,primaryimage as profile_image,city,state,COALESCE('yacht') as usertype from yachtdetail)
                        union (select authid, firstname, lastname,profile_image,city,state,COALESCE('professional') as usertype from talentdetails)
                        union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image,city,state,COALESCE('company') as usertype from companydetails)
                    ) unionSub1 on unionSub1.authid = msgmain.fromid  where  msgmain.isdeleted != '1' and  msgmain.rating = '5'  ORDER BY msgmain.created_at DESC LIMIT 10");
            if(!empty($latestReview)) {
				return response()->json(['success' => true,'data' => $latestReview], $this->successStatus); 
			} else {
				return response()->json(['success' => false,'data' => []], $this->successStatus); 
			}
		}

		public function allSuggestionHome(){
        	// $services = Service::select('service as itemName')->where('status','1')->distinct('service')->orderBy('service','ASC')->get();
        	$services = DB::select("SELECT DISTINCT ON  (service) id,service as itemname,COALESCE('service') as itemtype,COALESCE('service') as itemslug from services WHERE status = '1' ORDER BY service ASC");
        	//$boats_yacht_engineData = DB::select("SELECT name as itemname,id,COALESCE('service') as itemtype,COALESCE('service') as itemslug from boat_engine_companies WHERE status = '1' ORDER BY itemname ASC");
        	//$services = array_merge($services,$boats_yacht_engineData);
        	if(!empty($services)) {
        		return response()->json(['success' => true,'data' =>$services ], $this->successStatus);
        	} else {
        		return response()->json(['success' => false], $this->successStatus);
        	}
        }
        
         public function allSuggestionHomeBiz(){
			$address = request('city');
			if(!empty($address)) {
        		$output = $this->getGeoLocation($address);
	        	$longitude = $output['longitude'];
	            $latitude = $output['latitude'];            	
	        } else {
            	$longitude = '-81.515754';
	            $latitude = '27.664827';
            }
			$Currentdate = date('Y-m-d H:i:s');
        	$calDis = "2 * 3961 * asin(sqrt((sin(radians((cmp.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(cmp.latitude)) * (sin(radians((cmp.longitude - ".$longitude.") / 2))) ^ 2))";
        	$CompanyData = DB::select("SELECT  DISTINCT ON  (itemname) cmp.name as itemname,COALESCE('biz') as itemtype,COALESCE(cmp.slug) as itemslug,coalesce( r.totalreviewed , 0 ) as totalreviewed,cmp.primaryimage,".$calDis." as distance from companydetails as cmp LEFT JOIN reviewsview as r ON r.toid = cmp.authid   WHERE  cmp.nextpaymentdate > '".$Currentdate."' AND ".$calDis." <= 50 AND cmp.status = 'active' ORDER BY itemname ASC ");
        	
        	//$CompanyData = DB::table('companydetails as cmp')->LeftJoin('reviewsview as r','r.toid','=','cmp.authid')->select('cmp.name as itemname',DB::raw('COALESCE(\'biz\') as itemtype'),DB::raw('COALESCE(cmp.slug) as itemslug'),DB::raw('coalesce( r.totalreviewed , 0 ) as totalreviewed'),'cmp.primaryimage')->where('cmp.nextpaymentdate','>',$Currentdate)->where('cmp.city',$city)->where('cmp.status','active')->distinct('itemname')->orderBy('itemname','ASC')->get();
        	if(!empty($CompanyData)) {
        		return response()->json(['success' => true,'data' =>$CompanyData ], $this->successStatus);
        	} else {
        		return response()->json(['success' => false], $this->successStatus);
        	}
        }
        
        
        public function allSuggestionBiz(){
			
			$address = request('city');
			//~ echo $address;
			//~ $er = $this->checkSuggestionInit($address);
			//~ $correctedServiceName =  $this->corrected();
			//~ echo $correctedServiceName; die;
			$dist = request('distance');
			$distance = (!empty($dist) && $dist > '0' && $dist !='')?(int)$dist :50;
			if(!empty($address)) {
        		$output = $this->getGeoLocation($address);
	        	$longitude = $output['longitude'];
	            $latitude = $output['latitude'];            	
	        } else {
            	$longitude = '-81.515754';
	            $latitude = '27.664827';
            }
            $Currentdate = date('Y-m-d H:i:s');
            $calDis = "2 * 3961 * asin(sqrt((sin(radians((cmp.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(cmp.latitude)) * (sin(radians((cmp.longitude - ".$longitude.") / 2))) ^ 2))";
            $CompanyData = DB::select("SELECT  DISTINCT ON  (\"itemName\") cmp.name as \"itemName\",COALESCE('biz') as itemtype,COALESCE(cmp.slug) as itemslug,coalesce( r.totalreviewed , 0 ) as totalreviewed,cmp.primaryimage,".$calDis." as distance from companydetails as cmp LEFT JOIN reviewsview as r ON r.toid = cmp.authid   WHERE  cmp.nextpaymentdate > '".$Currentdate."' AND ".$calDis." <= ".$distance." AND cmp.status = 'active' ORDER BY \"itemName\" ASC ");
           
        	if(!empty($CompanyData)) {
        		return response()->json(['success' => true,'data' =>$CompanyData ], $this->successStatus);
        	} else {
        		return response()->json(['success' => false], $this->successStatus);
        	}
        }

        public function getCategoryAndServices(){
        	$category = Category::select('id','categoryname as name','subcategory')->where('status','1')->where('categoryname' ,'!=','Others')->get();
        	$subcategory = Subcategory::select('id as subid','category_id','subcategory_name as name')->where('status','1')->orderBy('subcategory_name','ASC')->get();
        	$services = Service::select('id as sid','service as name','category','subcategory')->where('status','1')->get();
        	if(!empty($category) && !empty($subcategory) && !empty($services)) {
        		return response()->json(['success' => true,'services' =>$services,'category' => $category,'subcategory' => $subcategory ], $this->successStatus);
        	} else {
        		return response()->json(['success' => false], $this->successStatus);
        	}
        }

        // get Bad word
	    public function getBadword() {
	        $data = Badword::select('id','word','created_at','status')
	            ->orderBy('created_at','DESC')
	            ->where('status','!=', '0')
	            ->get();
	        if(!empty($data)) {
	            return response()->json(['success' => true,'data' => $data], $this->successStatus); 
	        } else {
	            return response()->json(['success' => false], $this->successStatus);
	        }
	    }

	    public function check(){
	    	$companyDetail = DB::table('companydetails as cmp')
				->Join('subscriptionplans as sub','sub.id','=','cmp.next_paymentplan')
                ->where('cmp.subscription_id', '=', 'bc6gsg')
                ->select('cmp.*','sub.amount as planamount')
                ->first();
			
						$statusPayment =  DB::table('paymenthistory')->insert(
							['companyid' => (int)$companyDetail->authid,
							'transactionid' => 'bghjhjhjjh',
							'transactionfor' => 'registrationfee',
							'amount' => 89,
							'payment_type' =>$companyDetail->next_paymentplan,
							'status' => 'approved' ,
							'customer_id' => $companyDetail->customer_id,
							'subscription_id' => '899',
							'expiredate' => date('Y-m-d H:i:s'),
							'created_at' => date('Y-m-d H:i:s'),
							'updated_at' => date('Y-m-d H:i:s')
							]);
	    }
	     //Get Website Reviews
		public function getLatestWebsiteRatings(Request $request) {
			$limitCount = request('limit');
			$limit = '';
			$where = "";
			$select = '';
			if(!empty($limitCount)) {
				$limit = 'LIMIT '.$limitCount;
				$select = "count(*) OVER() AS totalreview, ";
				$where = "";
			} else {
				$where = "AND msgmain.rating = '5'";
				$limit = 'LIMIT 10';
			}
			$latestReview = DB::select("select ".$select." msgmain.id,msgmain.comment,msgmain.created_at,COALESCE(unionSub1.firstname,NULL) as firstname, unionSub1.lastname as lastname ,unionSub1.profile_image as profile_image,unionSub1.city,unionSub1.state,unionSub1.usertype ,msgmain.rating
	                from website_reviews as msgmain
	                join (
	                    (select authid, firstname, lastname,profile_image,city,state,COALESCE('regular') as usertype from userdetails)
	                    union (select authid, firstname, lastname,primaryimage as profile_image,city,state,COALESCE('yacht') as usertype from yachtdetail)
	                    union (select authid, firstname, lastname,profile_image,city,state,COALESCE('professional') as usertype from talentdetails)
	                    union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname,primaryimage as profile_image,city,state,COALESCE('company') as usertype from companydetails)
	                ) unionSub1 on unionSub1.authid = msgmain.authid  where  msgmain.isdeleted != '1' ".$where."  ORDER BY msgmain.created_at DESC ".$limit."");
	        if(!empty($latestReview)) {
	        	$totalComment = 0;
	        	if(isset($latestReview[0]->totalreview)) {
        			$totalComment = $latestReview[0]->totalreview;
	        	}
				return response()->json(['success' => true,'data' => $latestReview,'totalComment' => $totalComment], $this->successStatus); 
			} else {
				return response()->json(['success' => false,'data' => []], $this->successStatus);
			}
		}
		public function getservicesuggestion() {
			$service = request('servicename');
			if($service != '') {
				$name =  str_replace(' ',' & ',$service);
				$serviceRec =  Service::select('service as itemName')->whereRaw("to_tsvector('english',service) @@ to_tsquery('english','".$name."')")->first();
				if(!empty($serviceRec)) {
					$itemname = $serviceRec->itemName;
					return response()->json(['success' => true,'data' => $itemname], $this->successStatus);
				} else {
					return response()->json(['success' => false,'data' => []], $this->successStatus);
				}
			} else {

			}			
		}
		
		public function searchHomepage(){
			$text = request('text');
			$whereSer = $whereBYacht = $whereBiz =  '';
			if(!empty($text) && $text != '') {
				$whereSer = " service ILIKE '%".trim($text)."%' AND ";
				$whereBYacht = " name ILIKE '%".trim($text)."%' AND ";
				$whereBiz = " cmp.name ILIKE '%".trim($text)."%' AND ";
			}
			$services = DB::select("SELECT DISTINCT ON  (service) id,service as itemname,COALESCE('service') as itemtype,COALESCE('service') as itemslug from services WHERE ".$whereSer." status = '1' ORDER BY service ASC");
        	$boats_yacht_engineData = DB::select("SELECT name as itemname,id,COALESCE('service') as itemtype,COALESCE('service') as itemslug from boat_engine_companies WHERE ".$whereBYacht." status = '1' ORDER BY itemname ASC");
        	
        	$address = request('city');
			if(!empty($address)) {
        		$output = $this->getGeoLocation($address);
	        	$longitude = $output['longitude'];
	            $latitude = $output['latitude'];            	
	        } else {
            	$longitude = '-81.515754';
	            $latitude = '27.664827';
            }
			$Currentdate = date('Y-m-d H:i:s');
        	$calDis = "2 * 3961 * asin(sqrt((sin(radians((cmp.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(cmp.latitude)) * (sin(radians((cmp.longitude - ".$longitude.") / 2))) ^ 2))";
        	$CompanyData = DB::select("SELECT  DISTINCT ON  (itemname) cmp.name as itemname,COALESCE('biz') as itemtype,COALESCE(cmp.slug) as itemslug,coalesce( r.totalreviewed , 0 ) as totalreviewed,cmp.primaryimage,".$calDis." as distance from companydetails as cmp LEFT JOIN reviewsview as r ON r.toid = cmp.authid   WHERE ".$whereBiz."   cmp.nextpaymentdate > '".$Currentdate."' AND ".$calDis." <= 50 AND cmp.status = 'active' ORDER BY itemname ASC ");
        	$services = array_merge($services,$boats_yacht_engineData,$CompanyData);
        	if(!empty($services)) {
        		return response()->json(['success' => true,'data' =>$services ], $this->successStatus);
        	} else {
        		return response()->json(['success' => false,'data' =>[]], $this->successStatus);
        	}
        }
        
        public function searchBizpage(){
			$text = request('text');
			$whereSer = $whereBYacht = $whereBiz =  '';
			if(!empty($text) && $text != '') {
				$whereSer = " service ILIKE '%".trim($text)."%' AND ";
				$whereBYacht = " name ILIKE '%".trim($text)."%' AND ";
				$whereBiz = " cmp.name ILIKE '%".trim($text)."%' AND ";
			}
			
			$services = DB::select("SELECT DISTINCT ON  (service) id,service as \"itemName\",COALESCE('service') as itemtype,COALESCE('service') as itemslug from services WHERE ".$whereSer." status = '1' ORDER BY service ASC");
			$boats_yacht_engineData = DB::select("SELECT name as \"itemName\",id,COALESCE('service') as itemtype,COALESCE('service') as itemslug from boat_engine_companies WHERE ".$whereBYacht." status = '1' ORDER BY \"itemName\" ASC");
			
			$address = request('city');
			if(!empty($address)) {
				$output = $this->getGeoLocation($address);
				$longitude = $output['longitude'];
				$latitude = $output['latitude'];            	
			} else {
				$longitude = '-81.515754';
				$latitude = '27.664827';
			}
			$Currentdate = date('Y-m-d H:i:s');
			$calDis = "2 * 3961 * asin(sqrt((sin(radians((cmp.latitude - ".$latitude.") / 2))) ^ 2 + cos(radians(".$latitude.")) * cos(radians(cmp.latitude)) * (sin(radians((cmp.longitude - ".$longitude.") / 2))) ^ 2))";
			$CompanyData = DB::select("SELECT  DISTINCT ON  (\"itemName\") cmp.name as \"itemName\",COALESCE('biz') as itemtype,COALESCE(cmp.slug) as itemslug,coalesce( r.totalreviewed , 0 ) as totalreviewed,cmp.primaryimage,".$calDis." as distance from companydetails as cmp LEFT JOIN reviewsview as r ON r.toid = cmp.authid   WHERE ".$whereBiz."   cmp.nextpaymentdate > '".$Currentdate."' AND ".$calDis." <= 50 AND cmp.status = 'active' ORDER BY \"itemName\" ASC ");
			$services = array_merge($services,$boats_yacht_engineData,$CompanyData);
        	if(!empty($services)) {
        		return response()->json(['success' => true,'data' =>$services ], $this->successStatus);
        	} else {
        		return response()->json(['success' => false], $this->successStatus);
        	}
        }
        
        public function showBoatYachtResult(){
			$text = request('text');
			$whereSer =  '';
			$limit = 50;
			$offset = 0;
			if(!empty($text) && $text != '') {
				$whereSer = " name ILIKE '%".trim($text)."%' AND ";
			}
			
			$boats_yacht_Data = DB::select("SELECT name as \"itemName\",id,COALESCE('service') as itemtype,COALESCE('service') as itemslug from boat_engine_companies WHERE ".$whereSer." (category = 'boats' OR category = 'yachts') AND status = '1' ORDER BY \"itemName\" ASC LIMIT ".$limit." OFFSET ".$offset);
			
			if(!empty($boats_yacht_Data)) {
        		return response()->json(['success' => true,'data' =>$boats_yacht_Data ], $this->successStatus);
        	} else {
        		return response()->json(['success' => false], $this->successStatus);
        	}
        }
        
        public function showEngineResult(){
			$text = request('text');
			$whereSer =  '';
			if(!empty($text) && $text != '') {
				$whereSer = " name ILIKE '%".trim($text)."%' AND ";
			}
			
			$engines_Data = DB::select("SELECT name as \"itemName\",id,COALESCE('service') as itemtype,COALESCE('service') as itemslug from boat_engine_companies  WHERE ".$whereSer." category = 'engines' AND status = '1' ORDER BY \"itemName\" ASC");
			
			if(!empty($engines_Data)) {
        		return response()->json(['success' => true,'data' =>$engines_Data ], $this->successStatus);
        	} else {
        		return response()->json(['success' => false], $this->successStatus);
        	}
        }
        
        public function getSeachedCities(){
			$text = request('text');
			if(!empty($text)){
				$cities = DB::table('usareas')->select('city')
				->where('city', 'ILIKE', $text.'%')
				->groupBy('city')
				->offset(0)
                ->limit(50)
    			->get();
    			$cities = $cities->toArray();
    			// for($a = 0; $a < 10; $a++) $selected_cities
	    			if(count($cities)) {
		        		return response()->json(['success' => true,'data' =>$cities ]);
		        	} else {
		        		return response()->json(['success' => false]	);
		        	}
			}
        }
        
		function extractPostalCodeFromAdd(){
		 phpinfo();
	    }
	    public function generateS3Url(Request $request) {
	       try {
	           $imageData = $request->all();
	           $credentials = new \Aws\Credentials\Credentials(env('AWS_KEY'),env('AWS_SECRET'));
	            $options = [
	               'region'            => 'us-east-1',
	               'version'     => 'latest',
	               'credentials' => $credentials
	           ];

	           $s3Client = new S3Client($options);
	            $cmd = $s3Client->getCommand('PutObject', [
	                  'Bucket' => 'marine-pros',
	                  'Key'    => $imageData['type'].'/'.$imageData['uploadedfileName'],
	                  'ContentType' => $imageData['mineType'],
	                  'ACL' => 'public-read'
	              ]);

	           $url = $s3Client->createPresignedRequest($cmd, '+20 minutes');
	           // Get the actual presigned-url
	           $presignedUrl = (string)$url->getUri();
	           return response()->json(['success' => true,'data' => $presignedUrl]);
	       }

	       catch (S3Exception $e) {
	           return response()->json(['success' => false,'data' => $e->getMessage()]);  
	       }
	   }
	}	


?>
