<?php 
namespace App\Http\Traits;
use App\Mail\SendNotification;
use App\Userdetail;
use Illuminate\Support\Facades\Hash;
use DB;
use App\Auth;
use App\Service;
use App\Category;
use App\Jobtitles;
use App\Companydetail;
use App\Boat_Engine_Companies;
use App\Emailtemplates;
use Illuminate\Support\Facades\Mail;
use Geocoder;
use App\Yachtdetail;
use App\Talentdetail;
use View;
trait ImportTrait {

    public function Importdata($csvData,$adminID){
        $errorEmail = $insertIds = [];
            
        foreach($csvData as $csvDatas) {
            //Check Duplicate email
            $isError = false;
            $alredyExist =  Auth::where('email',$csvDatas->email)->count();
            if($alredyExist) {
                $errorEmail[] = $csvDatas->email;  
            } else {      
                DB::beginTransaction();                       //Insert records in auth
                $auth  = new Auth; 
                $userid = 0;
                $auth->email = strtolower($csvDatas->email);
                $auth->password = Hash::make($csvDatas->password);
                $auth->usertype = 'regular';
                $auth->ipaddress = \Request::ip();
                $auth->status = 'active';
                $auth->stepscompleted ='2';    
                $auth->addedby=1;
                $auth->is_activated = '1';
                $newsletter = (!empty($csvDatas->newsletter) && (strtolower($csvDatas->newsletter) == 'yes')) ? '1':'0';
                $auth->newsletter = $newsletter;
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
                    $output = $this->getCord($location); //Get User longitude and latitude

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
                    $userdetail->status     = 'active';
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
							$this->sendAccountCreateZapierTrait($zapierData);
						}
                        $insertIds[] = $userid;
                    } else {
                        $isError = true;   
                    }
                    DB::commit();
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
        //Get Admin Email and name 
        $adminInfo = Auth::where('id',$adminID)->first();
        $emailArr = [];
        if(!empty($adminInfo)) {
            if($adminInfo->firstname_admin != null && $adminInfo->lastname_admin != null) {
                $emailArr['name'] = $adminInfo->firstname_admin.' '.$adminInfo->lastname_admin;
            } else {
                $emailArr['name'] = 'Administrator';
            }
            $emailArr['to_email'] = !empty($adminInfo->contact_email)? $adminInfo->contact_email:$adminInfo->email;
            
            //Notifiy admin 
            if(count($insertIds)) { 
                if(count($errorEmail) >0) {         
                    $emailArr['failedEmail'] = implode('<br>',$errorEmail);
                    $status = $this->sendEmail($emailArr,'import_success_failed');    
                } else {
                    $status = $this->sendEmail($emailArr,'import_success');
                }
            } else {               
                
                $emailArr['failedEmail'] = implode('<br>',$errorEmail);
                $status = $this->sendEmail($emailArr,'import_failed');
            }
        }
    }
    public function importDataBusiness($csvData,$adminID) {
		$boartAndYachtData = Boat_Engine_Companies::where(function($query) {
								$query->where('category', '=', 'boats')
								->orWhere('category', '=', 'yachts');})->where('status','=','1')->select('id', DB::raw('lower(name) as name'))->get()->toArray();
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
                    DB::beginTransaction();  
                    $auth   = new Auth; 
                    $authid = 0;
                    
                    $auth->email = strtolower($csvDatas->email);
                    $auth->password = Hash::make($csvDatas->password);
                    $auth->usertype = 'company';
                    $auth->ipaddress = \Request::ip();
                    $auth->status = 'pending';
                    $auth->stepscompleted ='2'; 
                    $auth->addedby =1; 
                    $auth->is_activated = 1;
                    $newsletter = (!empty($csvDatas->newsletter) && (strtolower($csvDatas->newsletter) == 'yes')) ? '1':'0';
                    $auth->newsletter = $newsletter;
                    if($auth->save()) {
                        $authid = $auth->id;
                    }  else {
                        $isError = true;
                    }
                    if($authid) {
                        $address = $csvDatas->address;
                        $locAddress = ((isset($address) && $address !='') ? $csvDatas->address.' ': '');
                        $location = $locAddress.$csvDatas->city.' '.$csvDatas->zipcode.' '.$csvDatas->state.' , United States';
                        $output = $this->getCord($location); //Get Location from location Trait
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
								$boatandYachtexist = array_search(strtolower(trim($val),$boats_yachts_workedArr));
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
								$engineexist = array_search(strtolower(trim($val),$engines_workedArr));
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
                        $companydetail->authid  = $authid;
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
        
        
        //Get Admin Email and name 
        $adminInfo = Auth::where('id',$adminID)->first();
        $emailArr = [];
        if(!empty($adminInfo)) {
            if($adminInfo->firstname_admin != null && $adminInfo->lastname_admin != null) {
                $emailArr['name'] = $adminInfo->firstname_admin.' '.$adminInfo->lastname_admin;
            } else {
                $emailArr['name'] = 'Administrator';
            }
            $emailArr['to_email'] = !empty($adminInfo->contact_email)? $adminInfo->contact_email:$adminInfo->email;
            
            //Notifiy admin 
            if(count($insertIds)) { 
                if(count($errorEmail) >0) {         
                    $emailArr['failedEmail'] = implode('<br>',$errorEmail);
                    $status = $this->sendEmail($emailArr,'import_success_failed');    
                } else {
                    $status = $this->sendEmail($emailArr,'import_success');
                }
            } else {                
                $emailArr['failedEmail'] = implode('<br>',$errorEmail);
                $status = $this->sendEmail($emailArr,'import_failed');
            }
        }
    }

    public function importDataDummyBusiness($csvData,$adminID) {
		$boartAndYachtData = Boat_Engine_Companies::where(function($query) {
							$query->where('category', '=', 'boats')
							->orWhere('category', '=', 'yachts');})->where('status','=','1')->select('id', DB::raw('lower(name) as name'))->get()->toArray();
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
                    DB::beginTransaction();  
                    $auth   = new Auth; 
                    $authid = 0;
                    
                    $auth->email = strtolower($csvDatas->email);
                    $auth->password = Hash::make($csvDatas->password);
                    $auth->usertype = 'company';
                    $auth->ipaddress = \Request::ip();
                    $auth->status = 'active';
                    
                    $auth->stepscompleted ='3';    
                    $auth->addedby=1;
                    $auth->accounttype = 'dummy';
                    $auth->is_activated = 1;
                    // $newsletter = (!empty($csvDatas->newsletter) && (strtolower($csvDatas->newsletter) == 'yes')) ? '1':'0';
                    // $auth->newsletter = $newsletter;
                    if($auth->save()) {
                        $authid = $auth->id;
                    }  else {
                        $isError = true;
                    }
                    if($authid) {
                        $address = $csvDatas->address;
                        $locAddress = ((isset($address) && $address !='') ? $csvDatas->address.' ': '');
                         $location = $locAddress.((isset($csvDatas->city) && $csvDatas->city !='') ? $csvDatas->city.' ': '').((isset($csvDatas->zipcode) && $csvDatas->zipcode !='') ? $csvDatas->zipcode.' ': '').((isset($csvDatas->state) && $csvDatas->state !='') ? $csvDatas->state.' ': '').' , United States';
                        $output = $this->getCord($location); //Get Location from location Trait
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
								$boatandYachtexist = array_search(strtolower(trim($val),$boats_yachts_workedArr));
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
								$engineexist = array_search(strtolower(trim($val),$engines_workedArr));
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
                        $companydetail->authid  = $authid;
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
						$companydetail->contact    = $csvDatas->telephone;
						$companydetail->longitude  = $longitude;
						$companydetail->latitude   = $latitude;
						$companydetail->contactname  = ((isset($csvDatas->contactname) && trim($csvDatas->contactname) !='') ? $csvDatas->contactname: NULL);
						$companydetail->contactmobile  = ((isset($csvDatas->contactmobile) && trim($csvDatas->contactmobile) !='') ? $csvDatas->contactmobile: NULL);
						$companydetail->contactemail  = ((isset($csvDatas->contactemail) && trim($csvDatas->contactemail) !='') ? $csvDatas->contactemail: NULL);
                        $companydetail->accounttype   = 'dummy';
                        $companydetail->status      = '1';
                        // $companydetail->country_code  = request('country_code');
                        $companydetail->country_code   = "+1";
                        $companydetail->boats_yachts_worked    = ($emptyBoatAndYacht) ? NULL : $boatYachtObj;
						$companydetail->engines_worked    = ($emptyEngines) ? NULL : $enginesObj;
                        if($companydetail->save()) {
                            $insertIds[] = $companydetail->id;
                            $plandata = DB::table('subscriptionplans')->where('isadminplan', '=', '1')->where('status', '=', 'active')->first();
                            if(empty($plandata)) {
                                $isError = true;
                            }
                            $subplan = $plandata->id;
                            $nextDate = date('Y-m-d 00:00:00', strtotime("+18 years", strtotime(date('Y-m-d H:i:s'))));
                            $statusCompany = Companydetail::where('authid', (int)$authid)->update(['subscriptiontype' => 'manual','nextpaymentdate' => $nextDate, 'paymentplan' => (int)($subplan),'next_paymentplan' => (int)($subplan),'plansubtype' => 'free','status' => 'active']);
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
        
        
        //Get Admin Email and name 
        $adminInfo = Auth::where('id',$adminID)->first();
        $emailArr = [];
        if(!empty($adminInfo)) {

            if($adminInfo->firstname_admin != null && $adminInfo->lastname_admin != null) {
                $emailArr['name'] = $adminInfo->firstname_admin.' '.$adminInfo->lastname_admin;
            } else {
                $emailArr['name'] = 'Administrator';
            }
            $emailArr['to_email'] = !empty($adminInfo->contact_email)? $adminInfo->contact_email:$adminInfo->email;
            
            //Notifiy admin 
            if(count($insertIds)) { 
                if(count($errorEmail) >0) {         
                    $emailArr['failedEmail'] = implode('<br>',$errorEmail);
                    $status = $this->sendEmail($emailArr,'import_success_failed');    
                } else {
                    $status = $this->sendEmail($emailArr,'import_success');
                }
            } else {                
                $emailArr['failedEmail'] = implode('<br>',$errorEmail);
                $status = $this->sendEmail($emailArr,'import_failed');
            }
        }
    }
    public function importYacht($csvData,$adminID){
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
                    $auth->ipaddress = \Request::ip();
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
                            $output = $this->getCord($location); //Get Location from location Trait
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
										$this->sendAccountCreateZapierTrait($zapierData);
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
        //Get Admin Email and name 
        $adminInfo = Auth::where('id',$adminID)->first();
        $emailArr = [];
        if(!empty($adminInfo)) {

            if($adminInfo->firstname_admin != null && $adminInfo->lastname_admin != null) {
                $emailArr['name'] = $adminInfo->firstname_admin.' '.$adminInfo->lastname_admin;
            } else {
                $emailArr['name'] = 'Administrator';
            }
            $emailArr['to_email'] = !empty($adminInfo->contact_email)? $adminInfo->contact_email:$adminInfo->email;
            
            //Notifiy admin 
            if(count($insertIds)) { 
                if(count($errorEmail) >0) {         
                    $emailArr['failedEmail'] = implode('<br>',$errorEmail);
                    $status = $this->sendEmail($emailArr,'import_success_failed');    
                } else {
                    $status = $this->sendEmail($emailArr,'import_success');
                }
            } else {                
                $emailArr['failedEmail'] = implode('<br>',$errorEmail);
                $status = $this->sendEmail($emailArr,'import_failed');
            }
        }
    }

    public function importProfessional($csvData,$adminID){
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
                        $auth->ipaddress = \Request::ip();
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
                                $output = $this->getCord($location); //Get Location from location Trait
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
                                $talentdetail->willingtravel  = $csvDatas->willingtravel;
                                $talentdetail->workexperience  = json_encode($csvDatas->workexperience);
                                $talentdetail->status = 'active';
                                $talentdetail->certification= NULL;
                                $talentdetail->licences     = NULL;
                                $talentdetail->address      = ((isset($address) && $address !='') ? request('address'): NULL);
                                $talentdetail->city         = $csvDatas->city;
                                $talentdetail->state        = $csvDatas->state;
                                $talentdetail->country      = 'United States';
                                $talentdetail->zipcode      = $csvDatas->zipcode;
                                $talentdetail->mobile       = $csvDatas->contactmobile;
                                $talentdetail->profile_image = NULL;
                                $talentdetail->resume       = NULL;
                                $talentdetail->longitude    = $longitude;
                                $talentdetail->latitude     = $latitude;
                                $talentdetail->status       = 'active';
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
										$this->sendAccountCreateZapierTrait($zapierData);
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
        //Get Admin Email and name 
        $adminInfo = Auth::where('id',$adminID)->first();
        $emailArr = [];
        if(!empty($adminInfo)) {

            if($adminInfo->firstname_admin != null && $adminInfo->lastname_admin != null) {
                $emailArr['name'] = $adminInfo->firstname_admin.' '.$adminInfo->lastname_admin;
            } else {
                $emailArr['name'] = 'Administrator';
            }
            $emailArr['to_email'] = !empty($adminInfo->contact_email)? $adminInfo->contact_email:$adminInfo->email;
            
            //Notifiy admin 
            if(count($insertIds)) { 
                if(count($errorEmail) >0) {         
                    $emailArr['failedEmail'] = implode('<br>',$errorEmail);
                    $status = $this->sendEmail($emailArr,'import_success_failed');    
                } else {
                    $status = $this->sendEmail($emailArr,'import_success');
                }
            } else {                
                $emailArr['failedEmail'] = implode('<br>',$errorEmail);
                $status = $this->sendEmail($emailArr,'import_failed');
            }
        }
    }
    public function sendEmail($data,$template_name) {
        $getTemplate = Emailtemplates::select('subject','body')->where('template_name','=',$template_name)->where('status','1')->first();
        if(!empty($getTemplate)) {
            $emailArr = [];
            if($template_name == 'import_failed' || $template_name == 'import_success_failed' || $template_name == 'import_success') {
                
                $data['failedEmail'] = isset($data['failedEmail'])?$data['failedEmail']:'';
                $data['name'] = isset($data['name'])?$data['name']:'';
                
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $email_subject = $getTemplate->subject;
                $search  = array('%FAILED_EMAIL%','%NAME%');
                $replace = array($data['failedEmail'],$data['name']);
                $emailArr['subject'] = str_replace($search, $replace, $email_subject);
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if(count($emailArr)) {
				$IS_POSTMARK_APPLY = env('IS_POSTMARK_APPLY','NO');
				if($IS_POSTMARK_APPLY == 'YES') {
					return $this->send_Email_curl_Import($emailArr,$emailArr['to_email']);
				} else {
					Mail::to($emailArr['to_email'])->send(new SendNotification($emailArr));
					if (Mail::failures()) {
						return 'Not sent';
					} else {
						return 'sent';
					}
				}
            } else {
                return 'Not sent';
            }
         } else {
            return 'Not sent';    
         }
    }
    
    public function send_Email_curl_Import($emailArray,$emailaddress) {
		$url = 'https://api.postmarkapp.com/email';
        $header = [
            'Accept:application/json',
            'Content-type:application/json',
            'X-Postmark-Server-Token:'.env('POSTMARK_SERVER_TOKEN').'',
        ];
        $data = [];
        $data['body'] = $emailArray['body'];
        //$body = '';
        $body = View::make('emails.register',$data);
        $post = ['From'=> env('POSTMARK_EMAIL_FROM'),'To' => $emailaddress,'Subject' =>$emailArray['subject'],'HtmlBody' => trim($body)];
        $ch = curl_init();    
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);    
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));

        $response = curl_exec($ch);
        $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->last_api_call = $url;
        curl_close($ch);
        if(!empty($response)) {
			$result = json_decode($response);
			if(!empty($result) && $result->ErrorCode == 0  && $result->Message == 'OK') {
				return 'sent';
			} else {
				return 'Not sent';
			}
		} else {
			return 'Not sent';
		}
    }   

    public function getCord($location) {
        $output = app('geocoder')->geocode($location)->dump('geojson');
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
        $data['longitude'] = $longitude;
        $data['latitude'] = $latitude;
        return $data;
    }
    
    public function sendAccountCreateZapierTrait($data){
		$zapierData = array();
		$zapierData['type'] 		= $data['type'];
		$zapierData['id'] 			= base64_encode('6565'.$data['id']);
		$zapierData['email'] 		= (!empty($data['email']) && $data['email'] != null)?$data['email']:'';
		$zapierData['firstname']	= (!empty($data['firstname']) && $data['firstname'] != null)?$data['firstname'] : '';
		$zapierData['lastname'] 	= (!empty($data['lastname']) && $data['lastname'] != null)?$data['lastname'] : '';
		$zapierData['contact'] 		= (!empty($data['contact']) && $data['contact'] != null)?$data['contact'] : '';
		$zapierData['address'] 		= (!empty($data['address']) && $data['address'] != null)?$data['address'] : '';
		$zapierData['city'] 		= (!empty($data['city']) && $data['city'] != null)?$data['city'] : '';
		$zapierData['state'] 		= (!empty($data['state']) && $data['state'] != null)?$data['state'] : '';
		$zapierData['country'] 		= (!empty($data['country']) && $data['country'] != null)?$data['country'] : '';
		$zapierData['zipcode'] 		= (!empty($data['zipcode']) && $data['zipcode'] != null)?$data['zipcode'] : '';
		$zapierData['jobtitle'] 	= (!empty($data['jobtitle']) && $data['jobtitle'] != null)?$data['jobtitle'] : '';
		$zapierData['objective'] 	= (!empty($data['objective']) && $data['objective'] != null)?$data['objective'] : '';
		$experience = '';
		if(!empty($data['totalexperience']) && $data['totalexperience'] != null) {
			if($data['totalexperience'] == '15') {
				$experience = '15+ years';
			} else if($data['totalexperience'] == '11') {
				$experience = '11 - 15 years';
			} else if($data['totalexperience'] == '6') {
				$experience = '6 - 10 years';
			} else if($data['totalexperience'] == '1') {
				$experience = '1 - 5 years';
			} 
		}
		$zapierData['totalexperience'] = $experience;
		$zapierData['homeport'] = (!empty($data['homeport']) && $data['homeport'] != null)?$data['homeport'] : '';
		$zaiperHookUrl = env('ZAIPER_HOOK');
		$url = $zaiperHookUrl;
        $header = [
            'Content-type:text/html;charset=utf-8'
        ];
        $ch = curl_init();    
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);    
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($zapierData));
        $response = curl_exec($ch);
        $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->last_api_call = $url;
        curl_close($ch);
        return $response;
    }
}

?>
