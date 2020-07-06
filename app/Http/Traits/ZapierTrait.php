<?php 
namespace App\Http\Traits;
use App\Auth;
use DB;
use App\Userdetail;
use App\Companydetail;
use App\Talentdetail;
use App\Yachtdetail;
trait ZapierTrait {
    public function sendAccountCreateZapier($data){
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
		$zapierData['tag'] 			= (!empty($data['tag']) && $data['tag'] != null)?$data['tag'] : '';
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
		$zapierData['objective'] 	= (!empty($data['objective']) && $data['objective'] != null)?$data['objective'] : '';
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
    
    public function sendAccountCreateZapierbyID($authid,$type = null,$email = null){
		$zapierData = array();
		if(!empty($email) && $email != null) {
			$useremail = $email;
			$zapierData['email'] = $email;
			$userType = $type;
		} else {
			$userData = Auth::where('id',(int)$authid)->get();
			if(!empty($userData) && count($userData) > 0) {
				$zapierData['email'] = $userData[0]->email;
				$userType = $userData[0]->usertype;
			} else {
				return false;
			}
		}
		$zapierData['id'] = base64_encode('6565'.$authid);
			
		if($userType == 'yacht') {
			$zapierData['type'] = 'Yacht Owner';
			$zapierData['tag'] = 'Signed Up - Captain';
			$detailData = Yachtdetail::where('authid',(int)$authid)->get(); 
			if(!empty($detailData) && count($detailData) > 0) {
				$zapierData['contact'] 		= $detailData[0]->country_code.$detailData[0]->contact;
				$zapierData['jobtitle'] 	= '';
				$zapierData['objective'] 	= '';
				$zapierData['totalexperience'] = '';
				$zapierData['homeport'] = $detailData[0]->homeport;
			} else {
				return false;
			}
		} else if($userType == 'regular') {
			$zapierData['type'] = 'Boat Owner';
			$zapierData['tag'] = 'Signed Up - Boat Owner';
			$detailData = Userdetail::where('authid',(int)$authid)->get();
			if(!empty($detailData) && count($detailData) > 0) {
				$zapierData['contact'] 		= $detailData[0]->country_code.$detailData[0]->mobile;
				$zapierData['jobtitle'] 	= '';
				$zapierData['objective'] 	= '';
				$zapierData['totalexperience'] = '';
				$zapierData['homeport'] = '';
			} else {
				return false;
			}
		} else if($userType == 'professional') {
			$zapierData['tag'] = 'Signed Up - Job Seeker';
			$zapierData['type'] = 'Professional';
			$detailData = Talentdetail::where('authid',(int)$authid)->get();
			if(!empty($detailData) && count($detailData) > 0) {
				if($detailData[0]->jobtitleid == '1') {
					$jobtitle = $detailData[0]->otherjobtitle;
				} else {
					$jobtitleArray = DB::table('jobtitles')->where('id',$detailData[0]->jobtitleid)->get();
					if(!empty($jobtitleArray) && count($jobtitleArray) > 0) {
						$jobtitle = $jobtitleArray[0]->title;
					} else {
						$jobtitle = '';
					}
				}
				$zapierData['contact'] 		= $detailData[0]->country_code.$detailData[0]->mobile;
				$zapierData['jobtitle'] 	= $jobtitle;
				$zapierData['objective'] 	= $detailData[0]->objective;
				$experience = '';
				if(!empty($detailData[0]->totalexperience) && $detailData[0]->totalexperience != null) {
					$totalExp = $detailData[0]->totalexperience;
					if($totalExp == '15') {
						$experience = '15+ years';
					} else if($totalExp == '11') {
						$experience = '11 - 15 years';
					} else if($totalExp == '6') {
						$experience = '6 - 10 years';
					} else if($totalExp == '1') {
						$experience = '1 - 5 years';
					} 
				}
				$zapierData['totalexperience'] 	= $experience;
				$zapierData['homeport'] = '';
			} else {
				return false;
			}
			
		} else {
			return false;
		}
		
		$zapierData['firstname']	= $detailData[0]->firstname;
		$zapierData['lastname'] 	= $detailData[0]->lastname;
		$zapierData['address'] 		= (!empty($detailData[0]->address) && $detailData[0]->address != null)?$detailData[0]->address : '';
		$zapierData['city'] 		= $detailData[0]->city;
		$zapierData['state'] 		= $detailData[0]->state;
		$zapierData['country'] 		= $detailData[0]->country;
		$zapierData['zipcode'] 		= $detailData[0]->zipcode;
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

    public function sendAccountCreateZapierBiz($data){
		$zapierData = array();
		$zapierData['type'] 		= $data['type'];
		$zapierData['id'] 			= base64_encode('6565'.$data['id']);
		$zapierData['email'] 		= (!empty($data['email']) && $data['email'] != null)?$data['email']:'';
		$zapierData['name']			= (!empty($data['name']) && $data['name'] != null)?$data['name'] : '';
		$zapierData['businessemail']= (!empty($data['businessemail']) && $data['businessemail'] != null)?$data['businessemail'] : '';
		$zapierData['contact'] 		= (!empty($data['contact']) && $data['contact'] != null)?$data['contact'] : '';
		$zapierData['address'] 		= (!empty($data['address']) && $data['address'] != null)?$data['address'] : '';
		$zapierData['city'] 		= (!empty($data['city']) && $data['city'] != null)?$data['city'] : '';
		$zapierData['state'] 		= (!empty($data['state']) && $data['state'] != null)?$data['state'] : '';
		$zapierData['country'] 		= (!empty($data['country']) && $data['country'] != null)?$data['country'] : '';
		$zapierData['zipcode'] 		= (!empty($data['zipcode']) && $data['zipcode'] != null)?$data['zipcode'] : '';
		$zapierData['subscriptiontype'] = (!empty($data['subscriptiontype']) && $data['subscriptiontype'] != null)?$data['subscriptiontype'] : '';
        $zapierData['contactmobile'] = (!empty($data['contactmobile']) && $data['contactmobile'] != null)?$data['contactmobile'] : '';
        $zapierData['contactemail'] = (!empty($data['contactemail']) && $data['contactemail'] != null)?$data['contactemail'] : '';
        $zapierData['contactname'] 	= (!empty($data['contactname']) && $data['contactname'] != null)?$data['contactname'] : '';
        $zapierData['plan'] 		= (!empty($data['plan']) && $data['plan'] != null)?$data['plan'] : '';
	    if($data['newsletter'] == '1'){
	     	$zapierData['newsletter'] 		= 'Yes';
	    } else {
	     	$zapierData['newsletter'] 		= 'No';
	    }
	    $plan = strtolower($zapierData['plan']);
	    if($plan == 'free') {
	    	$zapierData['tag'] = 'Purchased Free Plan';
	    } else if($plan == 'basic') {
	    	$zapierData['tag'] = 'Purchased Basic Plan';
	    } else if($plan == 'advanced') {
	    	$zapierData['tag'] = 'Purchased Advanced Plan';
	    } else if($plan == 'Marine Pro' || $plan == 'Pro') {
	    	$zapierData['tag'] = 'Purchased Marine Pro';
	    }
	    $zapierData['website'] 		= (!empty($data['website']) && $data['website'] != null)?$data['website'] : '';
	    $zapierData['about'] 		= (!empty($data['about']) && $data['about'] != null)?$data['about'] : '';
         
		$zaiperHookUrl = env('ZAIPER_HOOK_BIZ','https://hooks.zapier.com/hooks/catch/4233742/xjn05e/');
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
    public function stepOneCompleted($data,$type){
		$zapierData = array();
		$zapierData = array();
		$zapierData['type'] 		= $data['type'];
		$zapierData['email'] 		= (!empty($data['email']) && $data['email'] != null)?$data['email']:'';
		$zapierData['firstname']	= (!empty($data['firstname']) && $data['firstname'] != null)?$data['firstname'] : '';
		$zapierData['lastname'] 	= (!empty($data['lastname']) && $data['lastname'] != null)?$data['lastname'] : '';
		$zapierData['contact'] 		= (!empty($data['contact']) && $data['contact'] != null)?$data['contact'] : '';
		$zapierData['address'] 		= (!empty($data['address']) && $data['address'] != null)?$data['address'] : '';
		$zapierData['city'] 		= (!empty($data['city']) && $data['city'] != null)?$data['city'] : '';
		$zapierData['state'] 		= (!empty($data['state']) && $data['state'] != null)?$data['state'] : '';
		$zapierData['country'] 		= (!empty($data['country']) && $data['country'] != null)?$data['country'] : '';
		$zapierData['zipcode'] 		= (!empty($data['zipcode']) && $data['zipcode'] != null)?$data['zipcode'] : '';
		$zapierData['tag'] 			= (!empty($data['tag']) && $data['tag'] != null)?$data['tag'] : '';
		$zaiperHookUrl = env('ZAIPER_HOOK_STEP_ONE','https://hooks.zapier.com/hooks/catch/4233742/p66rhc/');
	    // print_r($zapierData);die; 
		$this->send_curl($zapierData,$zaiperHookUrl);
    }
     public function stepCompleteBiz($data){
		$zapierData = array();
		
    	$zapierData['type'] 		= $data['type'];
		$zapierData['email'] 		= (!empty($data['email']) && $data['email'] != null)?$data['email']:'';
		$zapierData['name']			= (!empty($data['name']) && $data['name'] != null)?$data['name'] : '';
		$zapierData['businessemail']= (!empty($data['businessemail']) && $data['businessemail'] != null)?$data['businessemail'] : '';
		$zapierData['contact'] 		= (!empty($data['contact']) && $data['contact'] != null)?$data['contact'] : '';
		$zapierData['address'] 		= (!empty($data['address']) && $data['address'] != null)?$data['address'] : '';
		$zapierData['city'] 		= (!empty($data['city']) && $data['city'] != null)?$data['city'] : '';
		$zapierData['state'] 		= (!empty($data['state']) && $data['state'] != null)?$data['state'] : '';
		$zapierData['country'] 		= (!empty($data['country']) && $data['country'] != null)?$data['country'] : '';
		$zapierData['zipcode'] 		= (!empty($data['zipcode']) && $data['zipcode'] != null)?$data['zipcode'] : '';
		$zapierData['contactmobile'] = (!empty($data['contactmobile']) && $data['contactmobile'] != null)?$data['contactmobile'] : '';
        $zapierData['contactemail'] = (!empty($data['contactemail']) && $data['contactemail'] != null)?$data['contactemail'] : '';
        $zapierData['contactname'] 	= (!empty($data['contactname']) && $data['contactname'] != null)?$data['contactname'] : '';
        $zapierData['website'] 		= (!empty($data['website']) && $data['website'] != null)?$data['website'] : '';
    	$zapierData['about'] 		= (!empty($data['about']) && $data['about'] != null)?$data['about'] : '';
        $zapierData['tag'] 			= 'Completed Business Registration Step 1';
        if($data['newsletter'] == '1'){
	     	$zapierData['newsletter'] 		= 'Yes';
	    } else {
	     	$zapierData['newsletter'] 		= 'No';
	    }
		     
    	$zaiperHookUrl = env('ZAIPER_HOOK_STEP_ONE_BIZ','https://hooks.zapier.com/hooks/catch/4233742/xvs4z7/');
		$this->send_curl($zapierData,$zaiperHookUrl);
    }

    public function send_curl($zapierData,$zaiperHookUrl) {
    	
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

    public function companyCreateZapierbyID($authid,$free_account=null){
    	$tempData = DB::table('companydetails')->Join('auths','auths.id','companydetails.authid')->select('companydetails.*','auths.email','auths.newsletter')->where('companydetails.authid',(int)$authid)->first(); 
		if(!empty($tempData)) {
		 	
            $zapierData['type']    = 'Business';
            $zapierData['email']    = $tempData->email;
            $zapierData['businessemail'] = $tempData->businessemail;
            $zapierData['name']     = $tempData->name;
            $zapierData['contact']  = $tempData->contact;
            $zapierData['address']  = $tempData->address;
            $zapierData['city']     = $tempData->city;
            $zapierData['state']    = $tempData->state;
            $zapierData['country']  = $tempData->country;
            $zapierData['zipcode']  = $tempData->zipcode;
            
	        if(empty($free_account)) {
		        $plandata = DB::table('subscriptionplans')->where('id', '=', (int)$tempData->paymentplan)->where('status', '=', 'active')->first();
		        $zapierData['plan'] = $zapierData['tag'] = '';
		        if(isset($plandata->planname)) {
		        	$zapierData['plan']     = $plandata->planname;	
		        }
		        $plan = strtolower($zapierData['plan']);
			    if($plan == 'free') {
			    	$zapierData['tag'] = 'Purchased Free Plan';
			    } else if($plan == 'basic') {
			    	$zapierData['tag'] = 'Purchased Basic Plan';
			    } else if($plan == 'advanced') {
			    	$zapierData['tag'] = 'Purchased Advanced Plan';
			    } else if($plan == 'Marine Pro' || $plan == 'Pro') {
			    	$zapierData['tag'] = 'Purchased Marine Pro';
			    }
			} else {
				$zapierData['tag'] = 'Admin Created Free Account';
			}
            $zapierData['subscriptiontype'] = $tempData->subscriptiontype;
            $zapierData['contactmobile'] = $tempData->country_code.$tempData->contactmobile;
            $zapierData['contactemail'] = $tempData->contactemail;
            $zapierData['contactname'] = $tempData->contactname;
            $zapierData['newsletter'] = $tempData->newsletter;
            $zapierData['website'] = $tempData->websiteurl;
            $zapierData['about'] = $tempData->about;
            $zaiperHookUrl = env('ZAIPER_HOOK_BIZ','https://hooks.zapier.com/hooks/catch/4233742/xjn05e/');
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
		} else {
			return false;
		}
    }
}	

?>
