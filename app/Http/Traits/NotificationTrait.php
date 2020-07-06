<?php 
namespace App\Http\Traits;
use App\Mail\SendNotification;
use Illuminate\Support\Facades\Mail;
use App\Emailtemplates;
use App\Notifications;
use View;
// use Twilio\Rest\Client;
// use Twilio\Exceptions\TwilioException;
use App\notifications_error_logs;
trait NotificationTrait {
    // email notification message //
  
    public function sendEmailNotification($data,$template_name) {
        $getTemplate = Emailtemplates::select('subject','body')->where('template_name','=',$template_name)->where('status','1')->first();
        if(!empty($getTemplate)) {
            $emailArr = [];
            if($template_name == 'business_registration_activation' || $template_name == 'resend_confirmation') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%ACTIVATION_LINK%','%NAME%');
                $replace = array($data['link'],$data['name']);
                // $search  = array('%OTP%','%NAME%');
                // $replace = array($data['otp'],$data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if($template_name == 'claimed_business_notification') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%ACTIVATION_LINK%','%NAME%');
                $replace = array($data['link'],$data['name']);
                // $data['otp'] = (isset($data['otp']))?$data['otp']:'';
                // $data['name'] = (isset($data['name']))?$data['name']:'';
                // $search  = array('%OTP%','%NAME%');
                // $replace = array($data['otp'],$data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if($template_name == 'registration_activation') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%ACTIVATION_LINK%','%FIRSTNAME%','%LASTNAME%');
                $replace = array($data['link'],$data['firstname'],$data['lastname']);
                // $search  = array('%OTP%','%FIRSTNAME%','%LASTNAME%');
                // $replace = array($data['otp'],$data['firstname'],$data['lastname']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if($template_name == 'forget_password') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%ACTIVATION_LINK%');
                $replace = array($data['link']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            // if($template_name == 'resend_confirmation') {
            //     $emailArr['to_email'] = $data['to_email'];
            //     $email_body = $getTemplate->body;
            //     $search  = array('%ACTIVATION_LINK%','%NAME%');
            //     $replace = array($data['link'],$data['name']);
            //     $emailArr['subject'] = $getTemplate->subject;
            //     $emailArr['body'] = str_replace($search, $replace, $email_body);
            // }
            if($template_name == 'approve_claimbusiness') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $email_subject  = $getTemplate->subject;
                $search  = array('%ACTIVATION_LINK%','%NAME%');
                $replace = array($data['link'],$data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
                $emailArr['subject'] = str_replace($search, $replace, $email_subject);
            }
            if($template_name == 'reject_claimbusiness') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $email_subject  = $getTemplate->subject;
                $search  = array('%REGISTRATION_LINK%','%NAME%');
                $replace = array($data['link'],$data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
                $emailArr['subject'] = str_replace($search, $replace, $email_subject);
            }
            if ($template_name == 'lead_notification') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%SERVICE_REQUEST_LINK%','%FIRSTNAME%','%LASTNAME%');
                $replace = array($data['link'],$data['firstname'],$data['lastname']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if ($template_name == 'job_notification') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%JOB_DETAIL_LINK%','%NAME%');
                $replace = array($data['link'],$data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if($template_name == 'approve_claimbusiness_social') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $email_subject  = $getTemplate->subject;
                $search  = array('%ACTIVATION_LINK%','%NAME%');
                $replace = array($data['link'],$data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
                $emailArr['subject'] = str_replace($search, $replace, $email_subject);
            }
            if($template_name == 'user_added_by_admin') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%FIRSTNAME%','%LASTNAME%','%EMAIL%','%PASSWORD%');
                $replace = array($data['firstname'],$data['lastname'],$data['to_email'],$data['password']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if($template_name == 'business_added_by_admin') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%NAME%','%EMAIL%','%PASSWORD%');
                $replace = array($data['name'],$data['to_email'],$data['password']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if($template_name == 'user_deleted') {
                $emailArr['to_email'] = $data['to_email'];
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = $getTemplate->body;
            }
            if($template_name == 'bad_rating_notification') {
                $emailArr['to_email'] = $data['to_email'];
                $emailArr['subject'] = $getTemplate->subject;
                $search  = array('%LINK%');
                $replace = array($data['link']);
                $emailArr['body'] = $getTemplate->body;
            }
            if($template_name == 'email_change_notification') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%EMAIL%','%NAME%');
                $replace = array($data['emaillink'],$data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if($template_name == 'email_change_confirmation' || $template_name == 'resend_email_otp') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                // $search  = array('%OTP%','%NAME%');
                // $replace = array($data['otp'],$data['name']);
                $search  = array('%LINK%','%NAME%');
                $replace = array($data['link'],$data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            
            if($template_name == 'reject_lead_notification' || $template_name == 'approved_lead_notification') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%NAME%','%TITLE%');
                $replace = array($data['name'],$data['title']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
             if($template_name == 'request_quotes_notification') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%NAME%','%TITLE%','%MESSAGE_LINK%');
                $replace = array($data['name'],$data['title'],$data['link']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
                
            }
            if ($template_name == 'success_lead_sent') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%ACTIVATION_LINK%');
                $replace = array($data['link']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if ($template_name == 'subscription_reminder') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%NAME%','%DATE%','%DAY%','%LINK%');
                $replace = array($data['name'],$data['paymentdate'],$data['remain'],$data['link']);
                $subject_txt = $getTemplate->subject;
                $search_sub  = array('%DAY%');
                $replace_sub = array($data['remain']);
                $emailArr['subject'] = str_replace($search_sub, $replace_sub, $subject_txt);
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if ($template_name == 'new_lead_request') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%BUSINESS_NAME%','%SERVICE_LINK%','%NAME%');
                $replace = array($data['business_name'],$data['link'],$data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
                // echo '<pre>';print_r($emailArr);die;
            }
            if ($template_name == 'job_applied') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%PROFESSIONAL_NAME%','%JOB_LINK%','%NAME%');
                $replace = array($data['professional_name'],$data['link'],$data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
                // echo '<pre>';print_r($emailArr);die;
            }
            
            if($template_name == 'admin_emailchange_notification') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%NAME%');
                $replace = array($data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if($template_name == 'admin_emailchange_notification_new') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%NAME%');
                $replace = array($data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if($template_name == 'admin_passwordchange_notification') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%NAME%','%PASSWORD%');
                $replace = array($data['name'],$data['password']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if ($template_name == 'admin_emailPwdchange_notification') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%NAME%');
                $replace = array($data['name']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if ($template_name == 'admin_emailPwdchange_notification_new') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%NAME%','%PASSWORD%');
                $replace = array($data['name'],$data['password']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            
            if ($template_name == 'unreadMessage_reminder') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $email_subject = $getTemplate->subject;
                $search  = array('%FROM_NAME%','%LINK%','%NAME%');
                $replace = array($data['from_name'],$data['link'],$data['to_name']);
                $emailArr['subject'] = str_replace($search, $replace, $email_subject);
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            
            if($template_name == 'business_registration_activation_discount' || $template_name == 'user_registration_and_request_quote' || $template_name == 'user_registration_and_service_request' ) {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%ACTIVATION_LINK%','%NAME%','%EMAIL%','%PASSWORD%');
                $replace = array($data['link'],$data['name'],$data['logEmail'],$data['password']);
                // $search  = array('%OTP%','%NAME%','%EMAIL%','%PASSWORD%');
                // $replace = array($data['otp'],$data['name'],$data['logEmail'],$data['password']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if($template_name == 'website_rating_notification') {
                $emailArr['to_email'] = $data['to_email'];
                $email_body = $getTemplate->body;
                $search  = array('%USERNAME%','%LINK%','%COMMENT%','%RATE%');
                $replace = array($data['username'],$data['link'],$data['review'],$data['rating']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
            if($template_name == 'contact_us_notification_admin') {
               $emailArr['to_email'] = $data['to_email'];
               $email_body = $getTemplate->body;
               $search  = array('%NAME%','%EMAIL%','%CONTACT%','%MESSAGE%');
               $replace = array($data['name'],$data['email'],$data['contact'],$data['message']);
               $emailArr['subject'] = $getTemplate->subject.':-'.$data['subject'];
               $emailArr['body'] = str_replace($search, $replace, $email_body);
           }
            if($template_name == 'admin_new_user_notification' || $template_name == 'admin_new_service_notification' || $template_name == 'admin_new_user_new_service_notification') {
               $emailArr['to_email'] = $data['to_email'];
                $data['userType'] = (isset($data['userType']))?$data['userType']:'';
                $data['link'] = (isset($data['link']))?$data['link']:'';
                $data['userFirstname'] = (isset($data['userFirstname']))?$data['userFirstname']:'';
                $data['userEmail'] = (isset($data['userEmail']))?$data['userEmail']:'';
                $email_body = $getTemplate->body;
                $search  = array('%TYPE%','%NAME%','%EMAIL%','%LINK');
                $replace = array($data['userType'],$data['userFirstname'],$data['userEmail'],$data['link']);
                $emailArr['subject'] = $getTemplate->subject;
                $emailArr['body'] = str_replace($search, $replace, $email_body);
            }
           if(count($emailArr)) {
				$IS_POSTMARK_APPLY = env('IS_POSTMARK_APPLY','NO');
				if($IS_POSTMARK_APPLY == 'YES') {
					return $this->send_Email_curl($emailArr,$emailArr['to_email']);
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
    
    public function testing($email, $name){
        $emailArr['to_email'] = $$email;
        $email_body = $getTemplate->body;
        $emailArr['subject'] = 'test sub';
        $emailArr['body'] = 'test body';
        $this->send_Email_curl($emailArr,$emailArr['to_email']);
    }
    public function send_Email_curl($emailArray,$emailaddress) {
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
    
    
    //Send Sms
    // public function sendSms($sms,$tonumber,$notification_type,$notification_from,$notification_to) {
    //     $account_sid = getenv('TWILIO_ACCOUNT_SID');
    //     $auth_token = getenv('TWILIO_AUTH_TOKEN');
    //     $twilio_number = getenv('TWILO_PHONE_NUMBER');
    //     $client = new Client($account_sid, $auth_token);
    //     try {
    //        $message = $client->messages->create(
    //             $tonumber,
    //             array(
    //                 'from' => $twilio_number,
    //                 'body' => $sms
    //             )
    //         );
    //         return $message->sid;
    //     } catch (TwilioException $e) {
    //       $msg = $e->getMessage();
    //       $error_logs = new notifications_error_logs;
    //       $error_logs->notification_to = $notification_to;
    //       $error_logs->notification_from = $notification_from;
    //       $error_logs->errortype = 'sms';
    //       $error_logs->notification_type = $notification_type;
    //       $error_logs->messages = $msg;
    //       $error_logs->save();
    //     }
    // }

    //Add notifications to table
    public function addNotification($userId,$usertype,$service_requests,$reviews,$jobs,$request_id,$notificationDate,$reviewType=null,$is_read=null,$service_action=null){
        $notfication_service = $notfication_job = $notfication_review = [];
        if((int)$userId) {
            $notficationData = Notifications::select()->where('authid',$userId)->first();
            if(!empty($notficationData)) {

                $notfication_service = (array)json_decode($notficationData->service_requests);
                $notfication_job = (array)json_decode($notficationData->jobs);
                $notfication_review = (array)json_decode($notficationData->reviews);
                if(!empty($service_requests)) {
                    $newArr = [];
                    for($i = 0; $i< count($notfication_service);$i++) {
                        $newArr[$i]['requestid'] = $notfication_service[$i]->requestid;
                        $newArr[$i]['service_action'] = $notfication_service[$i]->service_action;
                        $newArr[$i]['notification'] = $notfication_service[$i]->notification;
                        $newArr[$i]['created_at'] = $notfication_service[$i]->created_at;
                        $newArr[$i]['is_read'] = $notfication_service[$i]->is_read;
                    }
                    $newArr[$i]['requestid'] = $request_id;
                    $newArr[$i]['notification'] = $service_requests;
                    $newArr[$i]['service_action'] = $service_action;
                    $newArr[$i]['created_at'] = $notificationDate;
                    $newArr[$i]['is_read'] = 0;
                    
                    $notfication_service = $newArr;
                }
                if(!empty($reviews)){
                    $newArr = [];
                    for($i = 0; $i< count($notfication_review);$i++) {
                        $newArr[$i]['from'] = $notfication_review[$i]->from;
                        $newArr[$i]['type'] = $notfication_review[$i]->type;//Review or comment
                        $newArr[$i]['review'] = $notfication_review[$i]->review;
                        $newArr[$i]['created_at'] = $notfication_review[$i]->created_at;
                        $newArr[$i]['is_read'] = $notfication_review[$i]->is_read;                                               
                    }
                    $newArr[$i]['from'] = $request_id;
                    $newArr[$i]['type'] = $reviewType;
                    $newArr[$i]['review'] = $reviews;
                    $newArr[$i]['created_at'] = $notificationDate;
                    $newArr[$i]['is_read'] = 0;
                    $notfication_review = $newArr;
                }
                if(!empty($jobs)){
                    $newArr = [];
                    for($i = 0; $i< count($notfication_job);$i++) {
                        $newArr[$i]['jobid'] = $notfication_job[$i]->jobid;
                        // $newArr[$i]['type'] = $notfication_job[$i]->type;
                        $newArr[$i]['notification'] = $notfication_job[$i]->notification;
                        $newArr[$i]['created_at'] = $notfication_job[$i]->created_at;
                        $newArr[$i]['is_read'] = $notfication_job[$i]->is_read;                                               
                    }
                    $newArr[$i]['jobid'] = $request_id;
                    // $newArr[$i]['type'] = $reviewType;
                    $newArr[$i]['notification'] = $jobs;
                    $newArr[$i]['created_at'] = $notificationDate;
                    $newArr[$i]['is_read'] = 0;
                    $notfication_job = $newArr;
                } 
                $id = $notficationData->id;
                $notifications = Notifications::find($id);
            } else {
                if(!empty($service_requests)) {
                    $notfication_service[0]['requestid'] = $request_id;
                    $notfication_service[0]['notification'] = $service_requests;
                    $notfication_service[0]['service_action'] = $service_action;
                    $notfication_service[0]['created_at'] = $notificationDate;
                    $notfication_service[0]['is_read'] = 0;
                }
                if(!empty($reviews)){
                    $notfication_review[0]['from'] = $request_id;
                    $notfication_review[0]['type'] = $reviewType;
                    $notfication_review[0]['review'] = $reviews;
                    $notfication_review[0]['created_at'] = $notificationDate;
                    $notfication_review[0]['is_read'] = 0;
                }

                if(!empty($jobs)){
                    $notfication_job[0]['jobid'] = $request_id;
                    // $notfication_job[0]['type'] = $reviewType;
                    $notfication_job[0]['notification'] = $jobs;
                    $notfication_job[0]['created_at'] = $notificationDate;
                    $notfication_job[0]['is_read'] = 0;
                }
                $notifications = new Notifications; 
            }
            if(isset($notifications)) {
                $notifications->authid = $userId;
                $notifications->usertype = $usertype;
                $notifications->service_requests = json_encode($notfication_service);
                $notifications->reviews = json_encode($notfication_review);
                $notifications->jobs = json_encode($notfication_job);
                if($notifications->save()) {
                    return 'success';
                } else {
                    return 'failed';
                }
            }
        }
    }

    
}
?>
