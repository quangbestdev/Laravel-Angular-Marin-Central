<?php
	namespace App\Http\Controllers;
	use Lcobucci\JWT\Parser;
	use Illuminate\Http\Request;
	use App\Auth;
	use App\Companydetail;
	use App\Talentdetail;
	use App\Userdetail;
	use App\Yachtdetail;
	use App\Claimed_business;
	use App\dummy_registration;
	use App\Geolocation;
	use DB;
	use Session;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Support\Facades\Auth as AuthFac;
	class AuthController extends Controller
	{
	    public $successStatus = 200;
	    // admin login //
	    public function adminlogin(Request $request) {
	    	$email = strtolower(request('email'));
	    	$password = request('password');
	    	if(!empty ($email) && $email != '' && !empty ($password) && $password != '') {
		    	if(AuthFac::attempt(['email' => $email, 'password' => $password,'status' => 'active','usertype' => 'admin'])){
		            $user = AuthFac::user();
		           	$userdata = Auth::where('email', '=', $email)->first();
		            $success['token'] =  $user->createToken('MyApp')->accessToken;
		            return response()->json(['success' => $success,'type' => $userdata->usertype,'email' => $userdata->email,'authid' => $userdata->id], $this->successStatus);
				} else{
		            return response()->json(['error'=>'An invalid email address or password was entered.'], 401);
		      	}
		    } else {
		    	return response()->json(['error'=>'Username and password required.'], 401);
		    }
	    }
	   
	    // check for vaild login //
	    public function checkUserLoggedIn(Request $request) {
	     	$value = $request->bearerToken();
	     	if(!empty($value)) {
	     		$id= (new Parser())->parse($value)->getHeader('jti');
	     		$userid = DB::table('oauth_access_tokens')->where('id', '=', $id)->where('revoked', '=', false)->first()->user_id;
	     		if(!empty($userid) && $userid > 0) {
	     			$userdata = Auth::where('id', '=', $userid)->where('status' ,'=','active')->first();
	     			if(!empty($userdata)) {
            			return response()->json(['success' => 'success','type' => $userdata->usertype,'token' =>$value, 'email' => $userdata->email,'newsletter' => $userdata->newsletter,'isSocial' => $userdata->is_social,'provider' => $userdata->provider], $this->successStatus);
            		} else {die('er1');
            			return response()->json(['error'=>'Unauthorised'], 401);
            		}
	     		} else {die('er2');
	     			return response()->json(['error'=>'Unauthorised'], 401);
	     		}
	     	} else {
	     		return response()->json(['error'=>'Unauthorised'], 401);
	     	}
	     	
	    }
	     // check for vaild login //
	    public function checkBusinessLoggedIn(Request $request) {
	     	$value = $request->bearerToken();
	     	if(!empty($value)) {
	     		$id= (new Parser())->parse($value)->getHeader('jti');
	     		$userid = DB::table('oauth_access_tokens')->where('id', '=', $id)->where('revoked', '=', false)->first()->user_id;
	     		if(!empty($userid) && $userid > 0) {
	     			$userdata = Auth::where('id', '=', $userid)->where('status' ,'=','active')->first();
	     			if(!empty($userdata) && ($userdata->usertype == 'company')) {
						return response()->json(['success' => 'success'], $this->successStatus);
					} else if(!empty($userdata)) {
            			return response()->json(['error'=>'Unauthorisedlogin'], 401);
            		} else {
            			return response()->json(['error'=>'Unauthorised'], 401);
            		}
	     		} else {
	     			return response()->json(['error'=>'Unauthorised'], 401);
	     		}
	     	} else {
	     		return response()->json(['error'=>'Unauthorised'], 401);
	     	}
	     	
	    }

	    // To logout user
	    public function adminlogout(Request $request) {
	    	$request->user()->token()->revoke();
        	$request->user()->token()->delete(); 
	     	return response()->json(['success' => 'success'], $this->successStatus);
	    }

	    public function userlogin(Request $request) {
	    	$email = strtolower(request('email'));
	    	$password = request('password');
	    	$success = [];
	    	if(!empty ($email) && $email != '' && !empty ($password) && $password != '') {
	    		if(AuthFac::attempt(['email' => $email, 'password' => $password,'is_social' => '0','status' => 'active'])){
	    			$userdata = Auth::where('email', '=', $email)->where('status','!=','deleted')->first();
	    			if(!empty($userdata)) {
	    				if($userdata->usertype != 'admin' && $userdata->status == 'active' ) {
	    					if($userdata->usertype == 'company') {
	    						$CompanydetailData = Companydetail::where('authid', '=', (int)$userdata->id)->first();
    							if(!empty($CompanydetailData)) {
    								if($CompanydetailData->accounttype == 'real') {
    									if($userdata->is_activated == '1') {
			    							$user = AuthFac::user();
			    							$success['type'] = $userdata->usertype;
								            $success['authid'] = encrypt($userdata->id);
								            $success['email'] = $userdata->email;
								            $success['stepscompleted'] = $userdata->stepscompleted;
						           			$success['token'] =  $user->createToken('MyApp')->accessToken;
						           			return response()->json(['success' => true,'data' => $success], $this->successStatus);
			    						} else {
			    							$success['authid'] = encrypt($userdata->id);
			    							return response()->json(['error'=>'not_activated','data'=> $success], 401);
			    						}
    								} else {
    									return response()->json(['error'=>'Unauthorised'], 401);
    								}
    							} else {
    								return response()->json(['error'=>'Unauthorised'], 401);
    							}
	    					} else {
								if($userdata->is_activated == '1') {
									$user = AuthFac::user();
									$success['type'] = $userdata->usertype;
						            $success['authid'] = encrypt($userdata->id);
						            $success['email'] = $userdata->email;
						            $success['stepscompleted'] = $userdata->stepscompleted;
				           			$success['token'] =  $user->createToken('MyApp')->accessToken;
				           			return response()->json(['success' => true,'data' => $success], $this->successStatus);
								} else {
									$success['authid'] = encrypt($userdata->id);
						            return response()->json(['error'=>'not_activated','data'=> $success], 401);
								}
							}
						} else {
							return response()->json(['error'=>'Unauthorised'], 401);
						}

	    			} else {
	    				return response()->json(['error'=>'Unauthorised'], 401);
	    			}
	    		} else {
	    			return response()->json(['error'=>'Unauthorised'], 401);
	    		}
	    	} else {
	    		return response()->json(['error'=>'Unauthorised'], 401);
	    	}
	  //   	$userdata = Auth::where('email', '=', $email)->first();
	  //   	//print_r($userdata);die;
	  //   	if(!empty($userdata) && ($userdata->usertype != 'admin')) {
	  //   		if($userdata->is_activated == '1') {
	  //   			$success['type'] = $userdata->usertype;
		 //            $success['authid'] = encrypt($userdata->id);
		 //            $success['email'] = $userdata->email;
		 //            $success['stepscompleted'] = $userdata->stepscompleted;
		 //            $success['logintype'] = 'unclaimed';
	  //   		} else {
	  //   			return response()->json(['error'=>'not_activated'], 401);
	  //   		}
	  //   	} else {
	  //   		$claimeduserdata = Claimed_business::where('email', '=', $email)->first();
	  //   		if(!empty($claimeduserdata)) {
		 //    		$success['type'] = 'company';
		 //            $success['authid'] = encrypt($claimeduserdata->id);
		 //            $success['email'] = $claimeduserdata->email;
		 //            $success['stepscompleted'] = $claimeduserdata->stepscompleted;
		 //            $success['logintype'] = 'claimed';
		 //            $claimedPassword = $claimeduserdata->password;
		 //            $success['claimid'] = $claimeduserdata->authid;;
		 //    	} else {
		 //    		return response()->json(['error'=>'Unauthorised'], 401);
		 //    	}
	  //   	}
	  //   	if(!empty ($email) && $email != '' && !empty ($password) && $password != '') {
	  //   		if(!empty($success['logintype']) && $success['logintype'] == 'unclaimed') {
		 //    		if(AuthFac::attempt(['email' => $email, 'password' => $password])){
		 //    			if($userdata->status == 'active') {
		 //    				if($userdata->usertype == 'company') {
			//     				$Companydetail = Companydetail::where('authid', '=', (int)$userdata->id)->first();
			//     				if(empty($Companydetail)) {
			//     					return response()->json(['error'=>'Unauthorised'], 401);
			//     				}
			//     			}
		 //    				if(($userdata->usertype == 'company') && (($Companydetail->accounttype != 'real') || ($Companydetail->paymentplan == '0'))) {
			//     				return response()->json(['success' => false,'data' => $success], $this->successStatus);
			//     			} else if($userdata->usertype == 'company') {
			// 					$geolocationCount = Geolocation::where('authid','=',$userdata->id)->where('status', '=', '1')->count();
		 //            			if(!empty($geolocationCount) && $geolocationCount > 0) {
		 //            				$success['geolocation'] = true;
		 //            			} else {
		 //            				$success['geolocation'] = false;
		 //            			}
		 //            		}

			// 	            $user = AuthFac::user();
			// 	           	$success['token'] =  $user->createToken('MyApp')->accessToken;
			// 	           	return response()->json(['success' => true,'data' => $success], $this->successStatus);
			// 		    } else {
			// 		    	return response()->json(['success' => false,'data' => $success], $this->successStatus);
			// 		    }
			// 	    } else {
			// 	    	return response()->json(['error'=>'Unauthorised'], 401);
			// 	    }
			// 	} else if (!empty($success['logintype']) && $success['logintype'] == 'claimed') {
			// 		if(!Hash::check($password,$claimedPassword)) {
			// 			return response()->json(['error'=>'Unauthorised'], 401);
			// 		} else {
			// 			if($claimeduserdata->status == 'active') {
		 //    			   	return response()->json(['error'=>'Unauthorised'], 401);
			// 		    } else {
			// 		    	return response()->json(['success' => false,'data' => $success], $this->successStatus);
			// 		    }
			// 		}
			// 	} else {
			// 		return response()->json(['error'=>'Unauthorised'], 401);
			// 	}
			// } else {
			// 	return response()->json(['error'=>'Unauthorised'], 401);
			// }
	    }

	     // To logout user //
	    public function userlogout(Request $request) {
	    	$request->user()->token()->revoke();
        	$request->user()->token()->delete(); 
	     	return response()->json(['success' => 'success'], $this->successStatus);
	    }

	    // check for vaild login //
	    public function checkLoggedIn(Request $request) {
	     	$value = $request->bearerToken();
	     	if(!empty($value) && $value != 'statics') {
	     		$id= (new Parser())->parse($value)->getHeader('jti');
	     		$userid = DB::table('oauth_access_tokens')->where('id', '=', $id)->where('revoked', '=', false)->first()->user_id;
	     		if(!empty($userid) && $userid > 0) {
	     			$userdata = Auth::where('id', '=', $userid)->where('status' ,'=','active')->first();
	     			if(!empty($userdata)) {
	     				if($userdata->usertype == 'company') {
	     					$userdataArr = Companydetail::where('authid', '=', $userid)->where('status' ,'=','active')->first();
	     				} else if($userdata->usertype == 'regular') {
	     					$userdataArr = Userdetail::where('authid', '=', $userid)->where('status' ,'=','active')->first();
	     				} else if($userdata->usertype == 'professional') {
	     					$userdataArr = Talentdetail::where('authid', '=', $userid)->where('status' ,'=','active')->first();
	     				} else if($userdata->usertype == 'yacht') {
	     					$userdataArr = Yachtdetail::where('authid', '=', $userid)->where('status' ,'=','active')->first();
	     				}
	     				if(!empty($userdataArr)) {
	     					$userDetail = [];
	     					$userDetail['email'] =  $userdata->email;
	     					$userDetail['usertype'] = $userdata->usertype;
	     					$userDetail['authid'] = $userdata->id;
	     					if($userdata->usertype == 'company') {
	     						$userDetail['firstname'] =  $userdataArr->name;
	     					} else {
	     						$userDetail['firstname'] =  $userdataArr->firstname;
	     					}
	     					if($userdata->usertype == 'company') {
								$userDetail['contact_content'] =  $userdataArr->contact_content;
								$userDetail['servicerequest_content'] =  $userdataArr->servicerequest_content;
								$userDetail['quote_content'] =  $userdataArr->quote_content;
							} else if($userdata->usertype == 'regular') {
								$userDetail['quote_content'] =  $userdataArr->quote_content;
							} else if($userdata->usertype == 'professional') {
								$userDetail['applyjob_content'] =  $userdataArr->applyjob_content;
								$userDetail['quote_content'] =  $userdataArr->quote_content;
							} else if($userdata->usertype == 'yacht') {
								$userDetail['quote_content'] =  $userdataArr->quote_content;
							}
	     					return response()->json(['success' => 'success','data' => $userDetail], $this->successStatus);
	     				} else {
	     					return response()->json(['error'=>'Unauthorised'], 401);
	     				}
            			
            		} else {
            			return response()->json(['error'=>'Unauthorised'], 401);
            		}
	     		} else {
	     			return response()->json(['error'=>'Unauthorised'], 401);
	     		}
	     	} else {
	     		return response()->json(['error'=>'Unauthorised'], 401);
	     	}
	    }

	    // check for vaild yacht login //
	    public function checkYachtLoggedIn(Request $request) {
	     	$value = $request->bearerToken();
	     	if(!empty($value)) {
	     		$id= (new Parser())->parse($value)->getHeader('jti');
	     		$userid = DB::table('oauth_access_tokens')->where('id', '=', $id)->where('revoked', '=', false)->first()->user_id;
	     		if(!empty($userid) && $userid > 0) {
	     			$userdata = Auth::where('id', '=', $userid)->where('status' ,'=','active')->first();
	     			if(!empty($userdata) && ($userdata->usertype == 'yacht')) {
            			return response()->json(['success' => 'success'], $this->successStatus);
            		} else {
            			return response()->json(['error'=>'Unauthorised'], 401);
            		}
	     		} else {
	     			return response()->json(['error'=>'Unauthorised'], 401);
	     		}
	     	} else {
	     		return response()->json(['error'=>'Unauthorised'], 401);
	     	}
	    }

	     // check for vaild login //
	    public function checkBoatOwnerLoggedIn(Request $request) {
	     	$value = $request->bearerToken();
	     	if(!empty($value)) {
	     		$id= (new Parser())->parse($value)->getHeader('jti');
	     		$userid = DB::table('oauth_access_tokens')->where('id', '=', $id)->where('revoked', '=', false)->first()->user_id;
	     		if(!empty($userid) && $userid > 0) {
	     			$userdata = Auth::where('id', '=', $userid)->where('status' ,'=','active')->first();
	     			if(!empty($userdata) && ($userdata->usertype == 'regular')) {
            			return response()->json(['success' => 'success'], $this->successStatus);
            		} else if(!empty($userdata)) {
            			return response()->json(['error'=>'Unauthorisedlogin'], 401);
            		} else {
            			return response()->json(['error'=>'Unauthorised'], 401);
            		}
	     		} else {
	     			return response()->json(['error'=>'Unauthorised'], 401);
	     		}
	     	} else {
	     		return response()->json(['error'=>'Unauthorised'], 401);
	     	}
	     	
	    }

	    // check for vaild proffesional login //
	    public function checkProffLoggedIn(Request $request) {
	     	$value = $request->bearerToken();
	     	if(!empty($value)) {
	     		$id= (new Parser())->parse($value)->getHeader('jti');
	     		$userid = DB::table('oauth_access_tokens')->where('id', '=', $id)->where('revoked', '=', false)->first()->user_id;
	     		if(!empty($userid) && $userid > 0) {
	     			$userdata = Auth::where('id', '=', $userid)->where('status' ,'=','active')->first();
	     			if(!empty($userdata) && ($userdata->usertype == 'professional')) {
            			return response()->json(['success' => 'success'], $this->successStatus);
            		} else {
            			return response()->json(['error'=>'Unauthorised'], 401);
            		}
	     		} else {
	     			return response()->json(['error'=>'Unauthorised'], 401);
	     		}
	     	} else {
	     		return response()->json(['error'=>'Unauthorised'], 401);
	     	}
	    }
	    
	    
	    public function checkSocialUserExist(Request $request) {
	    	$email = strtolower(request('socialemail'));
	    	$socialid = (string)request('socialid');
	    	$provider = request('socialprovider');
	    	$isSame = false;
	    	$isExist = false;
	    	$needapprove = false;
	    	$isSuccess = true;
	    	$isLogged = false;
	    	$isTwitter = false;
			if($provider == 'twitter') {
				$isTwitter = true;
			}
	    	if((!empty($email) || $isTwitter) && !empty($socialid)) {
				$userdata = Auth::where('social_id', '=', $socialid)->where('accounttype','=','real')->where('status','!=','deleted')->get();
				if(!empty($userdata) && count($userdata) > 0) {
					if($userdata[0]->is_social == '1' && $provider == $userdata[0]->provider && $socialid == $userdata[0]->social_id) {
						$isSame = true;
						$isExist = true;
						$user = $userdata[0];
						$success['type'] = $userdata[0]->usertype;
						$success['authid'] = encrypt($userdata[0]->id);
						$success['email'] = $userdata[0]->email;
						$success['stepscompleted'] = $userdata[0]->stepscompleted;
						$success['token'] =  $user->createToken('MyApp')->accessToken;
						$isLogged = true;
					} else {
						$isSame = false;
						$isExist = true;
					}
				} else {
					$query2 = dummy_registration::where('social_id', '=', $socialid);
					$UserDummyQuery = $query2->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->get();
					if(!empty($UserDummyQuery) && count($UserDummyQuery) > 0) {
						if($UserDummyQuery[0]->is_social == '1' && $provider == $UserDummyQuery[0]->provider && $socialid == $userdata[0]->social_id) {
							$isSame = true;
							$isExist = true;
							$needapprove = true;
						} else {
							$isSame = false;
							$isExist = true;
						}
					} else {
						$isotherthanTwitter = true;
						if($provider == 'twitter') {
							$isotherthanTwitter = false;
						}
						if($isotherthanTwitter) {
							$userdataEmail = Auth::where(function ($query) use ($email) {
										$query->where('email', '=', $email)
										->orWhere('requested_email', '=', $email);
									})->where('accounttype','=','real')->where('status','!=','deleted')->get();
						}
						if($isotherthanTwitter && !empty($userdataEmail) && count($userdataEmail) > 0) {
							$isSame = false;
							$isExist = true;
						} else {
							if($isotherthanTwitter) {
								$query2Email = dummy_registration::where('email', '=', strtolower(request('socialemail')));
								$UserDummyQueryEmail = $query2Email->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->get();
							}
							if($isotherthanTwitter && !empty($UserDummyQueryEmail) && count($UserDummyQueryEmail) > 0) {
								$isSame = false;
								$isExist = true;
							} else {
								$isSuccess = false;
							}
						}
					}
				}
				if($isSuccess) {
					if($isLogged) {
						return response()->json(['success' => true,'isSame' => $isSame,'isExist' => $isExist ,'needapprove' => $needapprove,'isLogged' => $isLogged,'data' => $success], $this->successStatus);
					} else {
						return response()->json(['success' => true,'isSame' => $isSame,'isExist' => $isExist ,'needapprove' => $needapprove], $this->successStatus);
					}
				} else {
					return response()->json(['success' => false], $this->successStatus);
				}
			} else {
				return response()->json(['error'=>'networkError'], 401);
			}
	    }
	}
?>
