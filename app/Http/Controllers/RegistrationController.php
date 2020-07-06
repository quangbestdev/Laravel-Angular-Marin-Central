<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Auth;
use DB;
use App\Userdetail;
use App\Companydetail;
use App\Talentdetail;
use App\Yachtdetail;
use App\Service;
use App\Usarea;
use App\Subscriptionplans;
use App\Paymenthistory;
use App\Geolocation;
use App\Claimed_business;
use App\Claimed_geolocation;
use App\Emailverification;
use App\dummy_registration;
use App\Dummy_geolocation;
use App\User_request_services;
use App\Dummy_paymenthistory;
use App\Boat_Engine_Companies;
use Illuminate\Support\Facades\Hash;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth as AuthFac;
use CountryState;
use Stripe\Error\Card;
use Cartalyst\Stripe\Stripe;
use Exception;
use Carbon;
use View;
use Braintree_ClientToken;
use Braintree_Transaction;
use Braintree_Customer;
use Braintree_WebhookNotification;
use Braintree_WebhookTesting;
use Braintree_Subscription;
use Braintree_PaymentMethod;
use Braintree_PaymentMethodNonce;
use App\Http\Traits\LocationTrait;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendNotification;
use App\Http\Traits\NotificationTrait;
use App\Http\Traits\SpellcheckerTrait;
use App\CountyCode;
use App\Webhook;
use App\Messages;
use App\Dictionary;
use App\Http\Traits\ZapierTrait;
use App\Jobs\SendNewLeadNotificationEmails;
use App\Jobs\SendSmsToBusinesses;
use App\Jobs\SaveNotifications;
use App\Dummy_registration_backup;

//~ use Victorybiz\GeoIPLocation\GeoIPLocation;


class RegistrationController extends Controller
{
    public $successStatus = 200;
    private $regAuthId = '';
    private $regTableName = '';
    private  $regRanPas = '';
    //public $customer_id_env = '65237887';
    use LocationTrait;
    use NotificationTrait;
    use SpellcheckerTrait;
    use ZapierTrait;

    public function __construct(Request $request) {
       
    }

    // generate OTP //
    public function generateOTP2() {
        $data = array();
        $data['rnd'] = 0;
        /*$data['body'] = '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Welcome aboard %FIRSTNAME% %LASTNAME%,</span>
                    <p style="font-size: 15px;line-height: 24px;margin-top: auto;">Please click <a style="color:#3a91cd" href="%ACTIVATION_LINK%">here</a> to activate your account!</p>
                    <p style="font-size: 15px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                    <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                    <p style="font-size: 15px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                    <p style="font-size: 15px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>
                    Palm Beach Gardens, FL 33410</p>
                ';*/
        $data['body'] = '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello admin,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">There is a new rating done for the website and below are the details for it</p>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Username - %USERNAME% <br>
                Profilelink - %LINK% <br>
                Comment - %COMMENT% <br>
                Rating - %RATE%/5
                </p> 
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
            
                    
                
                
                
                ';
        return View::make('emails.register',$data);
    }

    // generate OTP //
    public function generateOTP(Request $request) {
        $validate = Validator::make($request->all(), [
            'email' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $randNumber = rand(100000, 999999);
        $email =  strtolower(request('email'));
        $data = [];
        $data['email'] = $email;
        $data['rnd'] = $randNumber;
        $data['subject'] = 'Verify OTP';
       // $status = 'sent';
        $status = $this->sendEmailNotification($data);
        if($status == 'sent') {
            $existingOTP = Emailverification::where('email', '=',$email)->first();
            if($existingOTP) {
                $otpId = $existingOTP->id;
                $emailrow  = Emailverification::find($otpId);
            } else {
                $emailrow  = new Emailverification;     
            }
            $emailrow->email = $email;
            $emailrow->otp = $randNumber;
            $emailrow->status = '1';
            if($emailrow->save()) {   
                return response()->json(['success' => true,'email' => $email], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'emailsenderror'], 401); 
        }
    }

    // email notification message //
    // public function sendEmailNotification($data) {
    //     $sendData = [];
    //     $sendData['rnd']     = $data['rnd'];
    //     $sendData['subject'] = $data['subject'];
    //     Mail::to($data['email'])->send(new SendNotification($sendData));
    //     if (Mail::failures()) {
    //         return 'Not sent';
    //     } else {
    //         return 'sent';
    //     }
    // }
 
    // registor all type of user //
    public function registrationStage1(Request $request) {
        $validate = Validator::make($request->all(), [
            'email' => 'bail|required|E-mail',
            'password' => 'required',
            'rtype' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $newsletter = request('newsletter');
        $query = Auth::where('email', '=', strtolower(request('email')));
        $count = $query->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
        $query2 = dummy_registration::where('email', '=', strtolower(request('email')));
        $count2 = $query2->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
        $count = $count + $count2;
        if(!empty($count) && $count > 0) {
            return response()->json(['error'=>'validationError'], 401); 
        } 
        $email =  strtolower(request('email'));
        $rtype = request('rtype');
        switch ($rtype) {
            case 'ur':
                $registerType = 'regular';    
            break;
            case 'pr':
                $registerType = 'professional'; 
            break;
            case 'bn':
                $registerType = 'company'; 
            break;
            case 'yt':
                $registerType = 'yacht'; 
            break;
            default:
                return response()->json(['error'=>'networkerror'], 401);
        }
        $isclaimed = request('isclaimed');
        $userid = 0;
        $isdummy = false;
        $dummyReg = new dummy_registration;

        if(!empty($isclaimed) && ($isclaimed == 'true')) {
           $dummyReg->is_claim_user = '1';
           $isdummy = true;  
        } else {
            $dummyReg->is_claim_user = '0';
        }
        //For receiving offers and discounts
        $dummyReg->newsletter = (!empty($newsletter) && ($newsletter == 'true')) ? '1':'0';
        $dummyReg->email = strtolower(request('email'));
        $dummyReg->password = Hash::make(request('password'));
        $dummyReg->ipaddress = $this->getIp();
        $dummyReg->usertype = $registerType;
        $dummyReg->stepscompleted ='1'; 
        if($dummyReg->save()) {
            $userid = $dummyReg->id;
            $chiperUserid = encrypt($userid);
            if($isdummy) {
                $getDummyData = Companydetail::where('authid','=',(int)request('claimid'))->first();
                if(!empty($getDummyData)) {
                    $dummyReg  = dummy_registration::find($userid);
                    $dummyReg->authid  = (int)request('claimid');
                    $dummyReg->name  = $getDummyData->name;
                    $dummyReg->services   = ((isset($getDummyData->services) && $getDummyData->services !='') ? $getDummyData->services: NULL);
                    $dummyReg->address    = ((isset($getDummyData->address) && $getDummyData->address !='') ? $getDummyData->address: NULL);
                    $dummyReg->city       = $getDummyData->city;
                    $dummyReg->state      = $getDummyData->state;
                    $dummyReg->about      = ((isset($getDummyData->about) && $getDummyData->about !='') ? $getDummyData->about: NULL);
                    $dummyReg->businessemail =((isset($getDummyData->businessemail) && $getDummyData->businessemail !='') ? $getDummyData->businessemail: NULL);
                    $dummyReg->primaryimage =  ((isset($getDummyData->primaryimage) && $getDummyData->primaryimage !='') ? $getDummyData->primaryimage: NULL);
                    $dummyReg->allservices = ((isset($getDummyData->allservices) && $getDummyData->allservices !='') ? $getDummyData->allservices: NULL);
                    $dummyReg->websiteurl = ((isset($getDummyData->websiteurl) && $getDummyData->websiteurl !='') ? $getDummyData->websiteurl: NULL);
                    $dummyReg->country    = $getDummyData->country;
                    // $dummyReg->county    = $getDummyData->county;
                    $dummyReg->zipcode    = $getDummyData->zipcode;
                    $dummyReg->contact    = ((isset($getDummyData->contact) && $getDummyData->contact !='') ? $getDummyData->contact: NULL);
                    $dummyReg->contactname    = ((isset($getDummyData->contactname) && $getDummyData->contactname !='') ? $getDummyData->contactname: NULL);
                    $dummyReg->contactmobile    = ((isset($getDummyData->contactmobile) && $getDummyData->contactmobile !='') ? $getDummyData->contactmobile: NULL);
                    $dummyReg->contactemail    = ((isset($getDummyData->contactemail) && $getDummyData->contactemail !='') ? $getDummyData->contactemail: NULL);
                    $dummyReg->images     = ((isset($getDummyData->images) && $getDummyData->images !='') ? $getDummyData->images: NULL);
                    $dummyReg->longitude  = $getDummyData->longitude;
                    $dummyReg->latitude   = $getDummyData->latitude;
                    if($dummyReg->save()) {
                        // $dumygeolocation  = new Claimed_geolocation; 
                        // $dumygeolocation->authid = (int)request('claimid');
                        // $dumygeolocation->city = $getDummyData->city;
                        // $dumygeolocation->zipcode = $getDummyData->zipcode;
                        // $dumygeolocation->country = $getDummyData->country;
                        // $dumygeolocation->county = $getDummyData->county;
                        // $dumygeolocation->state = $getDummyData->state;
                        // $dumygeolocation->address    = ((isset($getDummyData->address) && $getDummyData->address !='') ? $getDummyData->address: NULL);
                        // $dumygeolocation->longitude = $getDummyData->longitude;
                        // $dumygeolocation->latitude = $getDummyData->latitude;
                        // $dumygeolocation->status = '1';
                        // if($dumygeolocation->save()) {
                            return response()->json(['success' => true,'userid' => $chiperUserid,'steps' => '1'], $this->successStatus);
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
            return response()->json(['success' => true,'userid' => $chiperUserid,'steps' => '1'], $this->successStatus);
             } 
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
   
    // registor all type of user //
    public function registrationStage2(Request $request) {
        $rtype = request('rtype');
        if(!isset($rtype) || $rtype == '') {
            return response()->json(['error'=>'validationError'], 401);
        }
        switch ($rtype) {
            case 'ur':
                $registerType = 'regular';
                $statusArr = $this->registerUserStage2($request,$registerType); 
                $status =  $statusArr['status'];
            break;
            case 'pr':
                $registerType = 'professional'; 
                $status = $this->registerProfessionalStage2($request,$registerType);  
            break;
            case 'bn':
                $registerType = 'company'; 
                $status = $this->registerCompanyStage2($request,$registerType); 
            break;
            case 'yt':
                $registerType = 'yacht'; 
                $status = $this->registerYachtStage2($request,$registerType); 
            break;
            default:
                return response()->json(['error'=>'networkerror'], 401);
        }
        if(isset($status) && $status !='') {
            switch ($status) {
                case 'success':
                    $authid = 0;
                    if(isset($statusArr['authid'])) {
                        $authid = encrypt($statusArr['authid']);
                    }
                    return response()->json(['success' => true,'isSocial' => false,'userid' => request('id'),'steps' => '2','authid' => $authid], $this->successStatus); 
                break;
                case 'successSocial':
                    $authid = $statusArr['authid'];
                    $userid = (int)$authid;
                    $userdata = Auth::where('id', '=', $userid)->get();
                    $user = $userdata[0];
                    $success['type']    = $userdata[0]->usertype;
                    $success['authid']  = encrypt($userdata[0]->id);
                    $success['email']   = $userdata[0]->email;
                    $success['stepscompleted'] = $userdata[0]->stepscompleted;
                    $success['token']   =  $user->createToken('MyApp')->accessToken;
                    return response()->json(['success' => true,'isSocial' => true,'userid' => request('id'),'steps' => '2','data' => $success], $this->successStatus); 
                break;
                case 'networkerror':
                case 'emailExist':
                case 'validationError':
                    return response()->json(['error'=>$status], 401);  
                break;
                default:
                    return response()->json(['error'=>'networkerror'], 401);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }
    
    // registor function for user stage 2 //
    public function registerUserStage2($request,$userType) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'rtype' => 'required',
            'rstep' => 'required',
            'firstname' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            // 'county' => 'required',
            'zipcode' => 'required',
            'mobile' => 'required',
            'lastname' => 'required' 
            // 'birthdate' => 'required',
        ]);
        if ($validate->fails()) {
            return array('status' =>'validationError'); 
        }
        $userid = request('id');
        $decryptUserid = decrypt($userid);
        if(empty($decryptUserid) || $decryptUserid == '') {
            return array('status' =>'networkerror'); 
        } else {
            $checkEmailExist =  dummy_registration::where('id', '=', (int)$decryptUserid)->where('usertype', '=', 'regular')->first();
            if(!empty($checkEmailExist)) {
                $checkEmailAddressExist = $checkEmailExist->email;
                $queryChecks = Auth::where('email', '=', $checkEmailAddressExist);
                $countChecks = $queryChecks->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
                $query2Checks = dummy_registration::where('email', '=', $checkEmailAddressExist);
                $count2Checks = $query2Checks->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
                $countChecks = $countChecks + $count2Checks;
                if(!empty($countChecks) && $countChecks > 0) {
                    
                     return array('status' =>'emailExist');
                }
            } else {
                 return array('status' =>'networkerror');
            }
        }
        $auth   = array(); 
        $steps = request('rstep');
        $updated = 0;
        $auth['stepscompleted'] = '2';
        $address = request('address');
        $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
        $updated =  dummy_registration::where('id', '=', (int)$decryptUserid)->where('usertype', '=', 'regular')->where('stepscompleted', '=',$steps )->update($auth);
        if($updated) {            
            $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];

            
            $userImage = request('profile_img');
            $userdetail = dummy_registration::find((int)$decryptUserid);
            $userdetail->firstname  = request('firstname');
            $userdetail->lastname   = request('lastname');
            $userdetail->city       = request('city');
            $userdetail->state      = request('state');
            $userdetail->country    = request('country');
            // $userdetail->county    = request('county');
            $userdetail->address    = ((isset($address) && $address !='') ? request('address'): NULL);
            $userdetail->zipcode    = request('zipcode');
            $userdetail->mobile     = request('mobile');
            if(isset($userImage) && $userImage !='') {
                $userdetail->profile_image     = request('profile_img'); 
            } else {
                $userdetail->profile_image     = NULL; 
            }
            // $userdetail->birthdate  = request('birthdate');
            $userdetail->longitude  = $longitude;
            $userdetail->latitude   = $latitude;
            if($userdetail->save()) {
                $tempData = dummy_registration::where('id',(int)$decryptUserid)->first();
                if(!empty($tempData)) {
                    $checkExistingUser = Auth::where('email',$tempData->email)->where('status','!=','deleted')->count();
                    if(!$checkExistingUser) {
                        $authData = new Auth;
                        $authData->email = $tempData->email;
                        $authData->password = $tempData->password;
                        $authData->usertype = $tempData->usertype;
                        $authData->ipaddress = $tempData->ipaddress;
                        $authData->stepscompleted = $tempData->stepscompleted;
                        $authData->newsletter = $tempData->newsletter;
                        if($tempData->is_social == '1') {
                            $authData->is_activated = '1';
                        } else {
                            $authData->is_activated = '0';
                        }
                        $authData->status = 'active';
                        $authData->is_social = $tempData->is_social;
                        $authData->social_id = $tempData->social_id;
                        $authData->provider  = $tempData->provider;
                        if($authData->save()) {
                            $authid = $authData->id;
                            $userdetails = new Userdetail;
                            $userdetails->authid = $authid;
                            $userdetails->firstname = $tempData->firstname;
                            $userdetails->lastname = $tempData->lastname;
                            $userdetails->sex = $tempData->sex;
                            $userdetails->city = $tempData->city;
                            $userdetails->state = $tempData->state;
                            $userdetails->country = $tempData->country;
                            $userdetails->zipcode = $tempData->zipcode;
                            $userdetails->address = $tempData->address;
                            $userdetails->mobile = $tempData->mobile;
                            $userdetails->birthdate = $tempData->birthdate;
                            $userdetails->profile_image = $tempData->profile_image;
                            $userdetails->longitude = $tempData->longitude;
                            $userdetails->latitude = $tempData->latitude;
                            $userdetails->status = 'active';
                            $country_code = request('country_code');
                            if($country_code != '') {
                                $pos = strpos($country_code, '+');
                                if(!$pos){
                                    $country_code ='+'.$country_code;
                                }
                            }   
                            $userdetails->country_code   = $country_code;
                            // $userdetails->county = $tempData->county;
                            if($userdetails->save()) {
                                $rejectedRegistration = dummy_registration::where('id', '=', (int)$decryptUserid)->delete();
                                $zaiperenv = env('ZAIPER_ENV','local');
                                if($zaiperenv == 'live') {
                                    $zapierData = array();
                                    $zapierData['type']     = 'Boat Owner';
                                    $zapierData['id']   = $authid;
                                    $zapierData['email']    = $tempData->email;
                                    $zapierData['firstname']= $tempData->firstname;
                                    $zapierData['lastname'] = $tempData->lastname;
                                    $zapierData['contact']  = $country_code.$tempData->mobile;
                                    $zapierData['address']  = $tempData->address;
                                    $zapierData['city']     = $tempData->city;
                                    $zapierData['state']    = $tempData->state;
                                    $zapierData['country']  = $tempData->country;
                                    $zapierData['zipcode']  = $tempData->zipcode;
                                    $zapierData['tag']      = 'Signed Up - Boat Owner';
                                    $this->sendAccountCreateZapier($zapierData);
                                }
                                if($tempData->is_social == '0') {
                                    $random_hashed = Hash::make(md5(uniqid($authid, true)));
                                    $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                                    $link = $website_url.'/activate?token='.urlencode($random_hashed);
                                    $ACTIVATION_LINK = $link;
                                    $emailArr = [];
                                     // temp otp
                                    // $ACTIVATION_OTP=rand(10000,99999);
                                    // $emailArr['otp'] = $ACTIVATION_OTP;
                                    
                                    $emailArr['link'] = $ACTIVATION_LINK;
                                    
                                    $emailArr['firstname'] = $tempData->firstname;
                                    $emailArr['lastname'] = $tempData->lastname;
                                    $emailArr['to_email'] = $tempData->email;


                                    $status = SendNewLeadNotificationEmails::dispatch($emailArr,'registration_activation');
                                    $adminEmailArr = array();
                                    $adminEmailArr['userEmail'] = $tempData->email;
                                    $adminEmailArr['userType'] = 'Boat Owner';
                                    $adminEmailArr['userFirstname'] = $tempData->firstname.' '.$tempData->lastname;
                                    $adminEmailArr['to_email'] = env("Admin_Email");
                                    //Send activation email notification
                                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                                    $adminEmailArr['to_email'] = env("Info_Email");
                                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                                    if($status != 'sent') {
                                        return array('status' =>'emailsentfail');
                                    }
                                    return array('status' =>'success','authid' =>$authid);
                                } else {
                                    return array('status' =>'successSocial','authid' =>$authid);
                                }
                                
                            } else {
                                return array('status' =>'networkerror');  
                            } 
                        } else {
                            return array('status' =>'networkerror');
                        }
                    } else {
                        return array('status' =>'networkerror'); 
                    } 
                    
                } else {
                    return array('status' =>'networkerror');    
                }
            } else {
                return array('status' =>'networkerror'); 
            }   
        } else {
            return array('status' =>'networkerror');  
        }     
    }

     // registration function for company //
      public function registerCompanyStage2($request,$usertype) {
        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'required',
            'rtype' => 'required',
            'rstep' => 'required',
            'services' => 'required',
            'city' => 'required',
            'state' => 'required',
            'about' => 'required',
            'businessemail' => 'required',
            'country' => 'required',
            // 'county' => 'required',
            'zipcode' => 'required',
            'contact' => 'required',
            'contactname' => 'required',
            'contactemail' => 'required',
            'contactmobile' => 'required'
        ]);
        if ($validate->fails()) {
            return 'validationError'; 
        }
        
        $userid = request('id');
        $decryptUserid = decrypt($userid);
        $rtype = request('rtype');
        if(empty($rtype) || $rtype == '' || $rtype != 'bn') {
            return 'networkerror'; 
        }
        if(empty($decryptUserid) || $decryptUserid == '') {
            return 'networkerror'; 
        }
        $auth   = array(); 
        $updated = 0;
        $isclaimed = request('isclaimed');
        // if(!empty($isclaimed) && ($isclaimed == 'true')) {
        //     $checkStepCompleted = Claimed_business::select('stepscompleted')->where('id','=',$decryptUserid)->where('status', '!=', 'deleted')->where('stepscompleted', '>', '1')->first();
        // } else {
           $checkStepCompleted = dummy_registration::select('stepscompleted','email')->where('id','=',$decryptUserid)->where('usertype', '=', 'company')->where('stepscompleted', '>', '1')->first();
        // }

        $updated = false;
        $isUpdated = false;
        if($checkStepCompleted) {
            $updated = true;     
            $isUpdated = true;
        } 
        if(!$updated) {
            $auth['stepscompleted'] = '2';
                $updated =  dummy_registration::where('id', '=', (int)$decryptUserid)->where('usertype', '=', 'company')->where('stepscompleted', '=' ,request('rstep') )->update($auth);
        }
        $address = request('address');
        $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
        
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
            $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];

            $CompanyImage = request('images');
            $CompanyImages = json_decode(request('companyimages'));
            $imagesArr = [];
            if(!empty($CompanyImages)) {
                for($i=0;$i< count($CompanyImages);$i++){
                    $imagesArr[$i]['image'] = $CompanyImages[$i];
                    $imagesArr[$i]['primary'] = 0;
                }
                if(isset($CompanyImage) && $CompanyImage != '') {
                    $imagesArr[count($CompanyImages)]['image'] = $CompanyImage;
                    $imagesArr[count($CompanyImages)]['primary'] = 1;
                }
                $imagesObj =  json_encode($imagesArr,JSON_UNESCAPED_SLASHES);
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
                        $otherserviceArr[$ii] = $id;
                    } else {
                        return 'networkerror';
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
            /*
            if(!empty($isclaimed) && ($isclaimed == 'true')) {
                 $existingCompany = Claimed_business::where('id', '=',(int)$decryptUserid)->first();
                if($existingCompany) {
                    $companyId = $existingCompany->id;
                    $companydetail  = Claimed_business::find($companyId);
                } else {
                    $companydetail  = new Claimed_business;     
                }
                $company_name = request('name'); 
            } else { */
                $existingCompany = dummy_registration::where('id', '=',$decryptUserid)->first();
                $company_name = request('name');
                
                $company_name_new  = preg_replace('/[^a-zA-Z0-9_ -]/s','',$company_name);
                $slug = implode("-",explode(" ",$company_name_new));
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
                    $companydetail  = dummy_registration::find($companyId);
                    for($i = 0 ; $validSlug != true ; $i++) {
                        $checkSlug = dummy_registration::where('actualslug','=',strtolower($slug))->where('id', '!=', (int)$companyId)->count();
                        $checkSlugEdit = dummy_registration::where('slug','=',strtolower($slug))->where('id', '!=', (int)$companyId)->count();
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
                    $companydetail  = new dummy_registration;   
                    for($i = 0 ; $validSlug != true ; $i++) {
                        $checkSlug = dummy_registration::where('actualslug','=',strtolower($slug))->count();
                        $checkSlugEdit = dummy_registration::where('slug','=',strtolower($slug))->count();
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
            // }
            //$address = request('address');
            $companydetail->name  = request('name');
            $companydetail->services   = $services;
            $companydetail->address    = ((isset($address) && $address !='') ? request('address'): NULL);
            $companydetail->city       = request('city');
            $companydetail->state      = request('state');
            $companydetail->about      = request('about');
            $companydetail->businessemail = request('businessemail');
            $companydetail->primaryimage =   ((isset($CompanyImage) && $CompanyImage !='') ? $CompanyImage: NULL);
            $companydetail->allservices =  ((isset($allservices) && $allservices !='') ? json_encode($allservices,JSON_UNESCAPED_SLASHES): NULL);
            $companydetail->websiteurl = request('websiteurl');
            $companydetail->country    = request('country');
            // $companydetail->county    = request('county');
            $companydetail->zipcode    = request('zipcode');
            $companydetail->contact    = request('contact');
            $companydetail->contactname    = request('contactname');
            $companydetail->contactmobile    = request('contactmobile');
            $companydetail->contactemail    = request('contactemail');
            
            $companydetail->images     = $imagesObj;
            $companydetail->longitude  = $longitude;
            $companydetail->latitude   = $latitude;
            $companydetail->country_code   = request('country_code');
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
                $email = dummy_registration::select('email')->where('id',$companydetail->id)->first();
                // print_r($checkStepCompleted);die;
                $zaiperenv = env('ZAIPER_ENV','local');
                if($zaiperenv == 'live') {
                    $zapierData = array();
                    $zapierData['type']     = 'Business';
                    $zapierData['email']    = strtolower($email->email);
                    $zapierData['businessemail'] = request('businessemail');
                    $zapierData['name']     = request('name');
                    $zapierData['contact']  = request('contact');
                    $zapierData['address']  = request('address');
                    $zapierData['city']     = request('city');
                    $zapierData['state']    = request('state');
                    $zapierData['country']  = request('country');
                    $zapierData['zipcode']  = request('zipcode');
                    $zapierData['contactmobile'] = request('contactmobile');
                    $zapierData['contactemail'] = request('contactemail');
                    $zapierData['contactname'] = request('contactname');
                    $zapierData['newsletter'] = '0';
                    $zapierData['website'] = request('websiteurl');
                    $zapierData['about'] = request('about');
                    $this->stepCompleteBiz($zapierData);
                }
                // if(!empty($existingCompany) && $existingCompany->is_claim_user == '1') {
                //     $existingGeo = Dummy_geolocation::where('authid', '=',(int)$decryptUserid)->first();
                //     if($existingGeo) {
                //         $geoId = $existingGeo->id;
                //         $geolocation  = Dummy_geolocation::find($geoId);
                //     } else {
                //         $geolocation  = new Dummy_geolocation;     
                //     }
                //     $city    = request('city');
                //     $state   = request('state');
                //     $zipcode = request('zipcode');
                //     $country = request('country');
                //     $county = request('county');
                //     $geolocation->authid = $decryptUserid;
                //     $geolocation->city = $city;
                //     $geolocation->zipcode = $zipcode;
                //     $geolocation->country = $country;
                //     $geolocation->county = $county;
                //     $geolocation->state = $state;
                //     $geolocation->address    = ((isset($address) && $address !='') ? request('address'): NULL);
                //     $geolocation->longitude = $longitude;
                //     $geolocation->latitude = $latitude;
                //     $geolocation->status = '1';
                //     if($geolocation->save()) {
                //         return 'success';
                //     } else {
                //         return 'networkerror'; 
                //     }
                // } else {
                    return 'success';
                // }
            } else {
                return 'networkerror'; 
            }   
        } else {
            return 'networkerror'; 
        }     
    }

    // registration function for professional //
    public function registerProfessionalStage2($request,$usertype) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'firstname' => 'required',
            'city'      => 'required',
            'state'     => 'required',
            'country'   => 'required',
            // 'county'   => 'required',
            'zipcode'   => 'required',
            'mobile'    => 'required'
        ]);
        if ($validate->fails()) {
            return 'validationError'; 
        }
        $userid = request('id');
        $decryptUserid = decrypt($userid);
        if(empty($decryptUserid) || $decryptUserid == '') {
            return 'networkerror'; 
        }
        $auth   = array(); 
        $updated = 0;
        $checkStepCompleted = dummy_registration::select('stepscompleted','email')->where('id','=',$decryptUserid)->where('usertype', '=', 'professional')->where('stepscompleted', '>', '1')->first();
        if($checkStepCompleted) {
            $updated = 1; 
        } else {
            $auth['stepscompleted'] = '2';
            $updated =  dummy_registration::where('id', '=', (int)$decryptUserid)->where('usertype', '=', 'professional')->update($auth);   
        }
        if($updated) {
            $address = request('address');
            $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
        
            $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];

            $userImage = request('profile_image');
            $lastName = request('lastname');
            $existingPro = dummy_registration::where('id', '=',$decryptUserid)->first();
            if($existingPro) {
                $professionalId = $existingPro->id;
                $talentdetail  = dummy_registration::find($professionalId);
            }
            $talentdetail->authid  = $decryptUserid;
            $talentdetail->firstname  = request('firstname');
            $talentdetail->lastname = ((isset($lastName) && $lastName !='') ? request('lastname'): NULL);
            $talentdetail->address    =  ((isset($address) && $address !='') ? request('address'): NULL);
            $talentdetail->city       = request('city');
            $talentdetail->state      = request('state');
            $talentdetail->country    = request('country');
            // $talentdetail->county      = request('county');
            $talentdetail->zipcode    = request('zipcode');
            $talentdetail->mobile    = request('mobile');
            $talentdetail->profile_image = ((isset($userImage) && $userImage !='') ? request('profile_image'): NULL);
            $talentdetail->longitude  = $longitude;
            $talentdetail->latitude   = $latitude;
            $country_code = request('country_code');
            if($country_code != '') {
                $pos = strpos($country_code, '+');
                if(!$pos){
                    $country_code ='+'.$country_code;
                }
            }   
            $talentdetail->country_code   = $country_code;
            if($talentdetail->save()) {
                $email = dummy_registration::select('email')->where('id',$talentdetail->id)->first();
                $zaiperenv = env('ZAIPER_ENV','local');
                if($zaiperenv == 'live') {
                    $zapierData = array();
                    $zapierData['type']     = 'Professional';
                    $zapierData['email']    = $email->email;
                    $zapierData['firstname']= request('firstname');
                    $zapierData['lastname'] = request('lastname');
                    $zapierData['contact']  = $country_code.request('mobile');
                    $zapierData['address']  = request('address');
                    $zapierData['city']     = request('city');
                    $zapierData['state']    = request('state');
                    $zapierData['country']  = request('country');
                    $zapierData['zipcode']  = request('zipcode');
                    $zapierData['tag']      = 'Completed Job Seeker Step 1 Personal Detail';
                    $this->stepOneCompleted($zapierData,'professional');            
                }
                return 'success';
            } else {
                return 'networkerror'; 
            }   
        } else {
            return 'networkerror'; 
        }     
    }


    //Register Yacht Users //
    public function registerYachtStage2(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'firstname' => 'required',
            'lastname'  => 'required',
            'contact'   => 'required',
            'city'      => 'required',
            'state'     => 'required',
            'country'   => 'required',
            // 'county'   => 'required',
            'zipcode'   => 'required',
        ]);
        if ($validate->fails()) {
            return 'validationError'; 
        }
        $userid = request('id');
        $decryptUserid = decrypt($userid);
        if(empty($decryptUserid) || $decryptUserid == '') {
            return 'networkerror'; 
        }
        $auth   = array(); 
        $updated = 0;
        $checkStepCompleted = dummy_registration::select('stepscompleted')->where('id','=',$decryptUserid)->where('usertype', '=', 'yacht')->where('stepscompleted', '>', '1')->first();
        if($checkStepCompleted) {
            $updated = 1; 
        } else {
            $auth['stepscompleted'] = '2';
            $updated =  dummy_registration::where('id', '=', (int)$decryptUserid)->where('usertype', '=', 'yacht')->update($auth);   
        }
        if($updated) {
            $address = request('address');
            $lastName = request('lastname');
            $image = request('images');
            if(!empty($image)) {
                $imagesArr = [0=>['image' => $image,'primary' => '1']];
                $image = json_encode($imagesArr); 
            }
            $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');
        
            $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];

            $imageprimary = request('images');
            $yachtDetail  = dummy_registration::find($decryptUserid);
            $yachtDetail->authid     = $decryptUserid;
            $yachtDetail->firstname  = request('firstname');
            $yachtDetail->lastname   = request('lastname');
            $yachtDetail->address    =  ((isset($address) && $address !='') ? request('address'): NULL);
            $yachtDetail->city       = request('city');
            $yachtDetail->images     = $image;
            $yachtDetail->primaryimage = ((isset($imageprimary) && $imageprimary !='') ? request('images'): NULL);
            $yachtDetail->state      = request('state');
            $yachtDetail->country    = request('country');
            // $yachtDetail->county      = request('county');
            $yachtDetail->zipcode    = request('zipcode');
            $yachtDetail->contact    = request('contact');
            $yachtDetail->longitude  = $longitude;
            $yachtDetail->latitude   = $latitude;
            $country_code = request('country_code');
            if($country_code != '') {
                $pos = strpos($country_code, '+');
                if(!$pos){
                    $country_code ='+'.$country_code;
                }
            }   
            $yachtDetail->country_code   = $country_code;
            if($yachtDetail->save()) {
                $email = dummy_registration::select('email')->where('id',$yachtDetail->id)->first();
                $zaiperenv = env('ZAIPER_ENV','local');
                if($zaiperenv == 'live') {
                    $zapierData = array();
                    $zapierData['type']     = 'Yacht Owner';
                    $zapierData['email']    = $email->email;
                    $zapierData['firstname']= request('firstname');
                    $zapierData['lastname'] = request('lastname');
                    $zapierData['contact']  = $country_code.request('mobile');
                    $zapierData['address']  = request('address');
                    $zapierData['city']     = request('city');
                    $zapierData['state']    = request('state');
                    $zapierData['country']  = request('country');
                    $zapierData['zipcode']  = request('zipcode');
                    $zapierData['tag']      = 'Completed Captain Step 1 Personal Detail';
                    $this->stepOneCompleted($zapierData,'professional');            
                }
                return 'success';
            } else {
                return 'networkerror'; 
            }   
        } else {
            return 'networkerror'; 
        }
    }
    // registration function for professional //
    public function registerProfessionalStage3(Request $request ) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'jobtitle' => 'required',
            'willingtravel' => 'required',
            'resume' => 'required'
        ]);
            
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401);
        }
        $userid = request('id');
        $decryptUserid = decrypt($userid);
        if(empty($decryptUserid) || $decryptUserid == '') {
            return response()->json(['error'=>'networkerror'], 401);
        } else {
            $checkEmailExist =  dummy_registration::where('id', '=', (int)$decryptUserid)->where('usertype', '=', 'professional')->first();
             $checkEmailAddressExist = $checkEmailExist->email;
            if(!empty($checkEmailExist)) {
                $queryChecks = Auth::where('email', '=', $checkEmailAddressExist);
                $countChecks = $queryChecks->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
                $query2Checks = dummy_registration::where('email', '=', $checkEmailAddressExist);
                $count2Checks = $query2Checks->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
                $countChecks = $countChecks + $count2Checks;
                if(!empty($countChecks) && $countChecks > 0) {
                     return response()->json(['error'=>'emailExist'], 401);
                }
            } else {
                 return response()->json(['error'=>'networkerror'], 401);
            }
        }
        $auth   = array(); 
        $updated = 0;
        // if($updated) {
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
            $skills             = json_decode(request('skills'));
            $objective          = request('objective');
            $detailArr['stepscompleted'] = '3';
            $detailArr['jobtitleid'] =request('jobtitle');
            $detailArr['resume']      = request('resume');
            $detailArr['certification']= ($emptyCertificate)?NULL:$certificateObj;
            $detailArr['licences']     = ($emptyLicence)?NULL:$licenceObj;
            $detailArr['workexperience'] = request('workexperience');
            $detailArr['totalexperience'] = request('experience');
            $detailArr['otherjobtitle'] = request('otherJobTitle');
            $detailArr['willingtravel']= request('willingtravel');
            $detailArr['objective']   = ((isset($objective) && $objective !='') ? request('objective'): NULL);
            $detailUpdate =  dummy_registration::where('id', '=', (int)$decryptUserid)->update($detailArr);
            if($detailUpdate) {
                $tempData = dummy_registration::where('id',(int)$decryptUserid)->first();
                //Insert in auths table
                $checkExistingUser = Auth::where('email',$tempData->email)->where('status','!=','deleted')->count();
                if(!$checkExistingUser) {
                    $authData = new Auth;
                    $authData->email = $tempData->email;
                    $authData->password = $tempData->password;
                    $authData->usertype = $tempData->usertype;
                    $authData->ipaddress = $tempData->ipaddress;
                    $authData->stepscompleted = $tempData->stepscompleted;
                    $authData->newsletter = $tempData->newsletter;
                    if($tempData->is_social == '1') {
                        $authData->is_activated = '1';
                    } else {
                        $authData->is_activated = '0';
                    }
                    $authData->is_social = $tempData->is_social;
                    $authData->social_id = $tempData->social_id;
                    $authData->provider  = $tempData->provider;
                    $authData->status = 'active';
                    if($authData->save()) {
                        $authid = $authData->id;
                        $talentdetail = new Talentdetail;
                        $talentdetail->authid  = $authid;
                        $talentdetail->firstname  = $tempData->firstname;
                        $talentdetail->jobtitleid  = $tempData->jobtitleid;
                        $talentdetail->licences  = $tempData->licences;
                        $talentdetail->certification  = $tempData->certification;
                        $talentdetail->objective  = $tempData->objective;
                        $talentdetail->workexperience  = $tempData->workexperience;
                        $talentdetail->willingtravel  = $tempData->willingtravel;
                        $talentdetail->profile_image  = $tempData->profile_image;
                        $talentdetail->resume  = $tempData->resume;
                        $talentdetail->longitude  = $tempData->longitude;
                        $talentdetail->latitude  = $tempData->latitude;
                        $talentdetail->status  = 'active';
                        $talentdetail->otherjobtitle  = $tempData->otherjobtitle;
                        $talentdetail->totalexperience  = $tempData->totalexperience;
                        $talentdetail->lastname = $tempData->lastname;
                        $talentdetail->address    =  $tempData->address;
                        $talentdetail->city       = $tempData->city;
                        $talentdetail->state      = $tempData->state;
                        $talentdetail->country    = $tempData->country;
                        // $talentdetail->county      = $tempData->county;
                        $talentdetail->zipcode    = $tempData->zipcode;
                        $talentdetail->mobile    = $tempData->mobile;
                        $talentdetail->country_code = $tempData->country_code;
                        $talentdetail->status    = 'active';
                        if($talentdetail->save()) {
                            $rejectedRegistration = dummy_registration::where('id', '=', (int)$decryptUserid)->delete();
                            $zaiperenv = env('ZAIPER_ENV','local');
                            if($zaiperenv == 'live') {
                                if($tempData->jobtitleid == '1') {
                                    $jobtitle = $tempData->otherjobtitle;
                                } else {
                                    $jobtitleArray = DB::table('jobtitles')->where('id',$tempData->jobtitleid)->get();
                                    if(!empty($jobtitleArray) && count($jobtitleArray) > 0) {
                                        $jobtitle = $jobtitleArray[0]->title;
                                    } else {
                                        $jobtitle = '';
                                    }
                                }
                                $zapierData = array();
                                $zapierData['type']     = 'Professional';
                                $zapierData['id']       = $authid;
                                $zapierData['email']    = $tempData->email;
                                $zapierData['firstname']= $tempData->firstname;
                                $zapierData['lastname'] = $tempData->lastname;
                                $zapierData['contact']  = $tempData->country_code.$tempData->mobile;
                                $zapierData['address']  = $tempData->address;
                                $zapierData['city']     = $tempData->city;
                                $zapierData['state']    = $tempData->state;
                                $zapierData['country']  = $tempData->country;
                                $zapierData['zipcode']  = $tempData->zipcode;
                                $zapierData['jobtitle'] = $jobtitle;
                                $zapierData['objective'] = $tempData->objective;
                                $zapierData['totalexperience'] = $tempData->totalexperience;
                                $zapierData['tag']      = 'Signed Up - Job Seeker';
                                $this->sendAccountCreateZapier($zapierData);
                            }
                            if($tempData->is_social == '0') {
                                $random_hashed = Hash::make(str_random(8).$authid);
                                $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                                $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                                $link = $website_url.'/activate?token='.urlencode($random_hashed);
                                $ACTIVATION_LINK = $link;
                                /*temp otp
                                $ACTIVATION_OTP=rand(10000,99999);
                                $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $ACTIVATION_OTP]);
                                 $emailArr['otp'] = $ACTIVATION_OTP;
                                */
                                $emailArr = [];
                                $emailArr['link'] = $ACTIVATION_LINK;
                                $emailArr['firstname'] = $tempData->firstname;
                                $emailArr['lastname'] = $tempData->lastname;
                                $emailArr['to_email'] = $tempData->email;
                                //Send activation email notification
                                $status = $this->sendEmailNotification($emailArr,'registration_activation');
                                $adminEmailArr = array();
                                $adminEmailArr['userEmail'] = $tempData->email;
                                $adminEmailArr['userType'] = 'Job Seeker';
                                $adminEmailArr['userFirstname'] = $tempData->firstname.' '.$tempData->lastname;
                                $adminEmailArr['to_email'] = env("Admin_Email");
                                //Send activation email notification
                                SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                                $adminEmailArr['to_email'] = env("Info_Email");
                                SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                                if($status != 'sent') {
                                    return response()->json(['error'=>'emailsentfail'], 401);
                                }
                                return response()->json(['success' => true,'isSocial'=>false,'userid' => request('id'),'steps' => '3','authid'=>encrypt($authid)], $this->successStatus);
                            } else {
                                $userdata = Auth::where('id', '=', $authid)->get();
                                $user = $userdata[0];
                                $success['type']    = $userdata[0]->usertype;
                                $success['authid']  = encrypt($userdata[0]->id);
                                $success['email']   = $userdata[0]->email;
                                $success['stepscompleted'] = $userdata[0]->stepscompleted;
                                $success['token']   =  $user->createToken('MyApp')->accessToken;
                                return response()->json(['success' => true,'isSocial'=>true,'userid' => request('id'),'steps' => '3','data' => $success], $this->successStatus);
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
            } else {
                 return response()->json(['error'=>'networkerror'], 401);
            }   
        // } else {
        //     return response()->json(['error'=>'networkerror'], 401);
        // }     
    }


    public function registerYachtStage3(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'yachtdetail' => 'required',
            'homeport' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401);
        }
        $userid = request('id');
        $decryptUserid = decrypt($userid);
        if(empty($decryptUserid) || $decryptUserid == '') {
             return response()->json(['error'=>'networkerror'], 401);
        } else {
            $checkEmailExist =  dummy_registration::where('id', '=', (int)$decryptUserid)->where('usertype', '=', 'yacht')->first();
            $checkEmailAddressExist = $checkEmailExist->email;
            if(!empty($checkEmailExist)) {
                $queryChecks = Auth::where('email', '=', $checkEmailAddressExist);
                $countChecks = $queryChecks->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
                $query2Checks = dummy_registration::where('email', '=', $checkEmailAddressExist);
                $count2Checks = $query2Checks->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
                $countChecks = $countChecks + $count2Checks;
                if(!empty($countChecks) && $countChecks > 0) {
                     return response()->json(['error'=>'emailExist'], 401);
                }
            } else {
                 return response()->json(['error'=>'networkerror'], 401);
            }
        }
        $allImages = dummy_registration::select('images')->where('authid','=',$decryptUserid)->first();
        $gallaryImage = request('images');
        if(!empty($gallaryImage)) {
            $gallaryArr = json_decode($gallaryImage);
            $finalImageArr = [];
            foreach ($gallaryArr as $ikey => $ival) {
                $finalImageArr[$ikey]['image'] = $ival;
                $finalImageArr[$ikey]['primary'] = '0';
            }   
        }
        if(!empty($allImages) && !empty($allImages->images)) {
            $profileImageArr = json_decode($allImages->images);
            $count = count($finalImageArr);
            $finalImageArr[$count]['images'] = $profileImageArr[0]->image;
            $finalImageArr[$count]['primary'] = $profileImageArr[0]->primary;
        }
        $auth   = array(); 
        $updated = 0;   
        $auth['stepscompleted'] = '3';
        $updated =  dummy_registration::where('id', '=', (int)$decryptUserid)->where('usertype', '=', 'yacht')->update($auth);
        if($updated) {
            $detailArr['images'] = json_encode($finalImageArr);
            $detailArr['yachtdetail'] = request('yachtdetail');
            $detailArr['homeport'] = request('homeport');
            $detailUpdate =  dummy_registration::where('id', '=', (int)$decryptUserid)->update($detailArr);
            if($detailUpdate) {
                $tempData = dummy_registration::where('id',(int)$decryptUserid)->where('usertype','yacht')->first();
                if(!empty($tempData)) {
                    $checkExistingUser = Auth::where('email',$tempData->email)->where('status','!=','deleted')->count();
                    if(!$checkExistingUser) {
                        $authData = new Auth;
                        $authData->email = $tempData->email;
                        $authData->password = $tempData->password;
                        $authData->usertype = $tempData->usertype;
                        $authData->ipaddress = $tempData->ipaddress;
                        $authData->stepscompleted = $tempData->stepscompleted;
                        $authData->newsletter = $tempData->newsletter;
                        $authData->status = 'active';
                        if($tempData->is_social == '1') {
                            $authData->is_activated = '1';
                        } else {
                            $authData->is_activated = '0';
                        }
                        $authData->is_social = $tempData->is_social;
                        $authData->social_id = $tempData->social_id;
                        $authData->provider  = $tempData->provider;
                        if($authData->save()) {
                            $authid = $authData->id;
                            $yachtdetail = new Yachtdetail;
                            $yachtdetail->authid = $authid;
                            $yachtdetail->firstname = $tempData->firstname;
                            $yachtdetail->lastname = $tempData->lastname;
                            $yachtdetail->contact = $tempData->contact;
                            $yachtdetail->address = $tempData->address;
                            $yachtdetail->city = $tempData->city;
                            $yachtdetail->state = $tempData->state;
                            $yachtdetail->country = $tempData->country;
                            $yachtdetail->zipcode = $tempData->zipcode;
                            $yachtdetail->yachtdetail = $tempData->yachtdetail;
                            $yachtdetail->homeport = $tempData->homeport;
                            $yachtdetail->images = $tempData->images;
                            $yachtdetail->primaryimage = $tempData->primaryimage;
                            $yachtdetail->status = 'active';
                            $yachtdetail->longitude = $tempData->longitude;
                            $yachtdetail->latitude = $tempData->latitude;
                            $yachtdetail->coverphoto = $tempData->coverphoto;
                            $yachtdetail->country_code = $tempData->country_code;
                            if($yachtdetail->save()) {
                                $rejectedRegistration = dummy_registration::where('id', '=', (int)$decryptUserid)->delete();
                                $zaiperenv = env('ZAIPER_ENV','local');
                                if($zaiperenv == 'live') {
                                    $zapierData = array();
                                    $zapierData['type']     = 'Yacht Owner';
                                    $zapierData['id']       = $authid;
                                    $zapierData['email']    = $tempData->email;
                                    $zapierData['firstname']= $tempData->firstname;
                                    $zapierData['lastname'] = $tempData->lastname;
                                    $zapierData['contact']  = $tempData->country_code.$tempData->contact;
                                    $zapierData['address']  = $tempData->address;
                                    $zapierData['city']     = $tempData->city;
                                    $zapierData['state']    = $tempData->state;
                                    $zapierData['country']  = $tempData->country;
                                    $zapierData['zipcode']  = $tempData->zipcode;
                                    $zapierData['homeport'] = $tempData->homeport;
                                    $zapierData['tag']      = 'Signed Up - Captain';
                                    $this->sendAccountCreateZapier($zapierData);
                                }
                                if($tempData->is_social == '0') {
                                    $random_hashed = Hash::make(str_random(8).$authid);
                                    $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                                    $link = $website_url.'/activate?token='.urlencode($random_hashed);

                                    $ACTIVATION_LINK = $link;
                                    $emailArr = [];                                        
                                    $emailArr['link'] = $ACTIVATION_LINK;
                                     
                                    /* temp otp
                                    $ACTIVATION_OTP=rand(10000,99999);
                                    $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $ACTIVATION_OTP]);
                                    $emailArr['otp'] = $ACTIVATION_OTP;
                                    */
                                    // $emailArr['otp'] = $ACTIVATION_OTP;
                                    $emailArr['firstname'] = $tempData->firstname;
                                    $emailArr['lastname'] = $tempData->lastname;
                                    $emailArr['to_email'] = $tempData->email;
                                    $status = $this->sendEmailNotification($emailArr,'registration_activation');
                                    $adminEmailArr = array();
                                    $adminEmailArr['userEmail'] = $tempData->email;
                                    $adminEmailArr['userType'] = 'Yacht Owner/Captain';
                                    $adminEmailArr['userFirstname'] = $tempData->firstname.' '.$tempData->lastname;
                                    $adminEmailArr['to_email'] = env("Admin_Email");
                                    //Send activation email notification
                                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                                    $adminEmailArr['to_email'] = env("Info_Email");
                                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                                    if($status != 'sent') {
                                        return response()->json(['error'=>'emailsentfail'], 401);
                                    }   
                                    return response()->json(['success' => true,'isSocial'=>false,'userid' => request('id'),'steps' => '3','authid'=> encrypt($authid)], $this->successStatus); 
                                } else {
                                    $userdata = Auth::where('id', '=', $authid)->get();
                                    $user = $userdata[0];
                                    $success['type']    = $userdata[0]->usertype;
                                    $success['authid']  = encrypt($userdata[0]->id);
                                    $success['email']   = $userdata[0]->email;
                                    $success['stepscompleted'] = $userdata[0]->stepscompleted;
                                    $success['token']   =  $user->createToken('MyApp')->accessToken;
                                    return response()->json(['success' => true,'isSocial'=>true,'userid' => request('id'),'steps' => '3','data' => $success], $this->successStatus);
                                }       
                            } else {
                                return response()->json(['error'=>'networkerror1'], 401);
                            }
                        } else {
                            return response()->json(['error'=>'networkerror2'], 401);
                        }
                    } else {
                        return response()->json(['error'=>'networkerror3'], 401);
                    }
                } else {
                    return response()->json(['error'=>'networkerror4'], 401);
                }
            } else {
                return response()->json(['error'=>'networkerror5'], 401);
            }  
        }
    }

    /*// registration function for professional //
    public function registerProfessionalStage2($request,$usertype) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'firstname' => 'required',
            'services'  => 'required',
            'jobtitle'  => 'required',
            'bio'       => 'required',
            'workingarea' => 'required',
            'boats_worked_on' => 'required',
            'sex'       => 'required',
            'city'      => 'required',
            'state'     => 'required',
            'country'   => 'required',
            'zipcode'   => 'required',
            'mobile'    => 'required',
            'resume'    => 'required'
        ]);
        if ($validate->fails()) {
            return 'validationError'; 
        }
        $userid = request('id');
        $decryptUserid = decrypt($userid);
        if(empty($decryptUserid) || $decryptUserid == '') {
            return 'networkerror'; 
        }
        $auth   = array(); 
        $updated = 0;
        $auth['stepscompleted'] = '2';
        $auth['status'] = 'active';
        $updated =  Auth::where('id', '=', (int)$decryptUserid)->where('usertype', '=', 'professional')->where('status', '!=', 'deleted')->update($auth);
        if($updated) {
            $output = app('geocoder')->geocode('city - '.request('city').' state - '.request('state').' country - '.request('country').' zipcode - '.request('zipcode'))->dump('geojson');
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
            $address = request('address');
            $userImage = request('profile_img');
            $lastName = request('lastname');
            $certification = request('certification');
            $licences = request('licences');
            $talentdetail   = new Talentdetail; 
            $talentdetail->authid  = $decryptUserid;
            $talentdetail->firstname  = request('firstname');
            $talentdetail->lastname = ((isset($lastName) && $lastName !='') ? request('lastname'): null);
            $talentdetail->jobtitle   = request('jobtitle');
            $talentdetail->bio        = request('bio');
            $talentdetail->workingarea= request('workingarea');
            $talentdetail->certification = ((isset($certification) && $certification !='') ? request('certification'): null);
            $talentdetail->licences = ((isset($licences) && $licences !='') ? request('licences'): null);
            $talentdetail->boats_worked_on  = request('boats_worked_on');
            $talentdetail->sex        = request('sex');
            $talentdetail->resume        = request('resume');
            
            $talentdetail->address    =  ((isset($address) && $address !='') ? request('address'): null);
            $talentdetail->city       = request('city');
            $talentdetail->state      = request('state');
            $talentdetail->country    = request('country');
            $talentdetail->zipcode    = request('zipcode');
            $talentdetail->mobile    = request('mobile');
            $talentdetail->profile_image = ((isset($userImage) && $userImage !='') ? request('profile_img'): null);
            $talentdetail->longitude  = $longitude;
            $talentdetail->latitude   = $latitude;
            if($talentdetail->save()) {
                return 'success';
            } else {
                return 'networkerror'; 
            }   
        } else {
            return 'networkerror'; 
        }     
    }*/
    
    // Get all countries
    public function getAllCountries(Request $request) {
        //$countries = CountryState::getCountries();
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
      
    // check email exist //
    public function checkEmail(Request $request) {
        $userEmail = strtolower(request('email'));
        $success = false;
        if(!empty($userEmail) && $userEmail != '' ) {
            $query = Auth::where('email', '=', $userEmail);
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

    // Get all US States
    public function  getallusstates() {
        $states = Usarea::select('state as value','statename as viewValue')->groupBy('state', 'statename')->orderBy('statename','ASC')->get();
        $formated = [];
        if($states) {
            return response()->json(['success' => true, 'states' => $states ], $this->successStatus);
        } else {
            return response()->json(['success' => false, 'states' => $formated ], $this->successStatus);
        }
    }  

    public function  getallcityZip() {
        $statename = strtolower(request('statename'));
        $states = Usarea::select('city')->whereRaw("LOWER(statename) = '".$statename."'")->groupBy('city')->orderBy('city','ASC')->get();
        $formated = [];
        if($states) {
            return response()->json(['success' => true, 'statedata' => $states ], $this->successStatus);
        } else {
            return response()->json(['success' => false, 'statedata' => $formated ], $this->successStatus);
        }
    }
    
         // Get all US zipcode
    public function  getallZipcode() {
        $city = strtolower(request('city'));
        $cityArr = Usarea::select('zipcode')->whereRaw("LOWER(city) = '".$city."'")->get();
        $formated = [];
        if($cityArr) {
            return response()->json(['success' => true, 'zipdata' => $cityArr ], $this->successStatus);
        } else {
            return response()->json(['success' => false, 'zipdata' => $formated ], $this->successStatus);
        }
    }

    // get subscription plan //
    public function  subscriptionplan() {
        $isUnlimit = false;
        // $currentDate = date('Y-m-d 00:00:00');
        // if ($currentDate < env('BASIC_PLAN_UNLIMITED_END')) {
        //  $isUnlimit = true;
        // }
        $plan = request('plan');
        if(!empty($plan)) {
             $planArr = DB::table('subscriptionplans')->where('status','=', 'active')->where('isadminplan','!=','1')->get();
         } else {
             $planArr = DB::table('subscriptionplans')->where('status','=', 'active')->where('active_status','=', 'active')->where('isadminplan','!=','1')->get();
         }
       
        if(!empty($planArr)) {
            return response()->json(['success' => true, 'plans' => $planArr,'isunlimit' => $isUnlimit ], $this->successStatus);
        } else {
            return response()->json(['success' => false, 'zipdata' => $formated ], $this->successStatus);
        }
    }

    // get business details //
    public function getBusinessDetail(Request $request) {
        $authid = request('id');
        $currentTime = Carbon\Carbon::now();
        if(!empty($authid) && $authid > 0) {
            $usersdata = DB::table('companydetails')
            ->Join('paymenthistory', 'paymenthistory.companyid', '=', 'companydetails.authid')
            ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'companydetails.paymentplan')
            ->select('companydetails.name','paymenthistory.created_at','paymenthistory.updated_at as historyupdate','paymenthistory.expiredate', 'subscriptionplans.*')
            ->where('companydetails.authid','=',$authid)
            ->where('paymenthistory.status','=','approved')
            ->get();
            if(!empty($usersdata)) {
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }
    public function getAllyacht() {
        $data = array();
        $detail = DB::table('category')
            ->Join('services', 'services.category', '=', 'category.id')
            ->select('category.categoryname','services.service as servicename', 'services.id')
            ->where(['category.status'=> '1'])
            ->get();
        $arr = [];
        if(!empty($detail)) {
            $detail = $detail->toArray();
            $dataarr = [];
            foreach ($detail as $key => $value) {  
                if(!in_array($value->categoryname,$arr)){
                    $arr[] = $value->categoryname;
                    $dataarr[$value->categoryname] =0;
                    $data[$value->categoryname][0]['id'] = $value->id;
                    $data[$value->categoryname][0]['servicename'] = $value->servicename;
                    $data[$value->categoryname][0]['checked']=false;
                    $dataarr[$value->categoryname]++;
                } else {
                    $data[$value->categoryname][$dataarr[$value->categoryname]]['id'] = $value->id;
                    $data[$value->categoryname][$dataarr[$value->categoryname]]['servicename'] = $value->servicename;
                    $data[$value->categoryname][$dataarr[$value->categoryname]]['checked'] = false;
                    $dataarr[$value->categoryname]++;
                } 
            }
            return response()->json(['success' => true,'data' => $data], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    public function getProfessionalDataById(Request $request) {
        $rid = request('rid');
        $decryptUserid = decrypt($rid);
        if(!empty($decryptUserid)) {
            $usersdata = DB::table('auths')
            ->leftJoin('talentdetails', 'auths.id', '=', 'talentdetails.authid')
            ->select('talentdetails.firstname','talentdetails.lastname','talentdetails.mobile','talentdetails.address','talentdetails.country','talentdetails.state','talentdetails.city','talentdetails.zipcode','talentdetails.profile_image')
            ->where('auths.usertype', '=', 'professional')
            ->where('auths.stepscompleted', '>', 1)
            ->where('auths.status', '!=', 'deleted')
            ->where('auths.id', '=', (int)$decryptUserid)
            ->first();
            if(!empty($usersdata)) {
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                $usersdata = array();
                return response()->json(['success' => false,'data' => $usersdata], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }        
    }

    public function getTempProfessionalDataById(Request $request) {
        $rid = request('rid');
        $decryptUserid = decrypt($rid);
        if(!empty($decryptUserid)) {
            $usersdata = DB::table('dummy_registration as talentdetails')
            ->select('talentdetails.firstname','talentdetails.lastname','talentdetails.mobile','talentdetails.address','talentdetails.country','talentdetails.state','talentdetails.city','talentdetails.zipcode','talentdetails.country_code','talentdetails.profile_image')
            ->where('talentdetails.usertype', '=', 'professional')
            //->where('talentdetails.stepscompleted', '>', 1)
            ->where('talentdetails.id', '=', (int)$decryptUserid)
            ->first();
            if(!empty($usersdata)) {
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                $usersdata = array();
                return response()->json(['success' => false,'data' => $usersdata], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }        
    }

    public function getTempCompanyDataById(Request $request) {
        $rid = request('rid');
        $decryptUserid = decrypt($rid);
        if(!empty($decryptUserid)) {
            $usersdata = DB::table('dummy_registration as companydetails')
            ->select('companydetails.id','companydetails.name','companydetails.contact','companydetails.images','companydetails.address','companydetails.country','companydetails.state','companydetails.city','companydetails.zipcode','companydetails.websiteurl','companydetails.businessemail','companydetails.services','companydetails.about','companydetails.contactemail','companydetails.contactmobile','companydetails.contactname','companydetails.country_code','companydetails.boats_yachts_worked','companydetails.engines_worked')
            ->where('companydetails.usertype', '=', 'company')
            // ->where('companydetails.stepscompleted', '>', 1)
            ->where('companydetails.id', '=', (int)$decryptUserid)
            ->first();
            if(!empty($usersdata)) {
                $services = $usersdata->services;
                $otherServicename = NULL;
                $servicesArray = (array)json_decode($services);
                //print_r($services);die;
                $otherService = [];
               if(array_key_exists('11', $servicesArray)) {
                    $otherService = Service::select('service')->whereIn('id',$servicesArray['11'])->get();
                }
                $usersdata->otherService = $otherService;
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
                $usersdata = array();
                return response()->json(['success' => false,'data' => $usersdata], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }        
    }
    
    public function getCompanyDataById(Request $request) {
        $rid = request('rid');
        $decryptUserid = decrypt($rid);
        if(!empty($decryptUserid)) {
             $usersdata = DB::table('auths')
            ->leftJoin('companydetails', 'auths.id', '=', 'companydetails.authid')
            ->select('companydetails.name','companydetails.contact','companydetails.images','companydetails.address','companydetails.country','companydetails.state','companydetails.city','companydetails.zipcode','companydetails.websiteurl','companydetails.businessemail','companydetails.services','companydetails.about','companydetails.contactemail','companydetails.contactmobile','companydetails.contactname','companydetails.country_code','companydetails.boats_yachts_worked','companydetails.engines_worked')
            ->where('auths.usertype', '=', 'company')
            ->where('auths.stepscompleted', '>', 1)
            ->where('auths.status', '!=', 'deleted')
            ->where('auths.id', '=', (int)$decryptUserid)
            ->first();
            if(!empty($usersdata)) {
                $services = $usersdata->services;
                $otherServicename = NULL;
                $servicesArray = (array)json_decode($services);
                //print_r($services);die;
                $otherService = [];
               if(array_key_exists('11', $servicesArray)) {
                    $otherService = Service::select('service')->whereIn('id',$servicesArray['11'])->get();
                }
                $usersdata->otherService = $otherService;
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
                $usersdata = array();
                return response()->json(['success' => false,'data' => $usersdata], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }        
    }
    
    public function getAllplans() {
        $allPlans = DB::table('subscriptionplans as sp')->select('sp.planname','sp.plandescription',DB::Raw('cast(sp.amount as integer)'),'sp.plantype','sp.id','sp.planaccessnumber','sp.planaccesstype','sp.geolocationaccess','sp.leadaccess','discounts.current_discount','sp.active_status')->leftJoin('discounts', 'discounts.paymentplan', '=', 'sp.id')->where('status','=','active')->where('isadminplan','!=','1')->orderBy('id', 'ASC')->get();
        if($allPlans) {
            $discountAmount = 0;
            foreach ($allPlans as $key => $val) {
                $amountwithoutdiscount = $val->amount;
                $discountapply = $val->current_discount;
                if($discountapply > 0) {
                    $discountAmount = ceil(($amountwithoutdiscount * $discountapply)/100);
                }
                $discountedAmount = $amountwithoutdiscount - $discountAmount;
                $allPlans[$key]->discountedAmount = $discountedAmount;
            }
            return response()->json(['success' => true,'data' => $allPlans], $this->successStatus);
        } else {
            return response()->json(['success' => false,'data' => $allPlans], $this->successStatus);
        }
    }
    public function allUpdatedPlans() {
        $allPlans = DB::table('subscriptionplans as sp')->select('sp.planname','sp.plandescription',DB::Raw('cast(sp.amount as integer)'),'sp.plantype','sp.id','sp.planaccessnumber','sp.planaccesstype','sp.geolocationaccess','sp.leadaccess')->where('status','=','active')->where('isadminplan','!=','1')->where('active_status','active')->orderBy('id', 'ASC')->get();
        if($allPlans) {
            return response()->json(['success' => true,'data' => $allPlans], $this->successStatus);
        } else {
            return response()->json(['success' => false,'data' => $allPlans], $this->successStatus);
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
            ->where('paymenthistory.transactionfor','registrationfee')
            ->orderBy('paymenthistory.id','DESC')
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


    public function getTempYachtDataById(Request $request) {
        $rid = request('rid');
        $decryptUserid = decrypt($rid);
        if(!empty($decryptUserid)) {
            $usersdata = DB::table('dummy_registration as yachtdetail')
            ->select('yachtdetail.firstname','yachtdetail.lastname','yachtdetail.contact','yachtdetail.address','yachtdetail.country','yachtdetail.state','yachtdetail.city','yachtdetail.country_code','yachtdetail.zipcode','yachtdetail.images')
            ->where('yachtdetail.usertype', '=', 'yacht')
            //->where('yachtdetail.stepscompleted', '>', 1)
            ->where('yachtdetail.id', '=', (int)$decryptUserid)
            ->first();
            if(!empty($usersdata)) {
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                $usersdata = array();
                return response()->json(['success' => false,'data' => $usersdata], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }        
    }

    public function getYachtDataById(Request $request) {
        $rid = request('rid');
        $decryptUserid = decrypt($rid);
        if(!empty($decryptUserid)) {
            $usersdata = DB::table('auths')
            ->leftJoin('yachtdetail', 'auths.id', '=', 'yachtdetail.authid')
            ->select('yachtdetail.firstname','yachtdetail.lastname','yachtdetail.contact','yachtdetail.address','yachtdetail.country','yachtdetail.state','yachtdetail.city','yachtdetail.zipcode','yachtdetail.images')
            ->where('auths.usertype', '=', 'yacht')
            ->where('auths.stepscompleted', '>', 1)
            ->where('auths.status', '!=', 'deleted')
            ->where('auths.id', '=', (int)$decryptUserid)
            ->first();
            if(!empty($usersdata)) {
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                $usersdata = array();
                return response()->json(['success' => false,'data' => $usersdata], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }        
    }

    //Get Plan Geolocation details//
    // public function getUserPlanGeoLocation(Request $request) {
    //     $encryptId = request('id');
    //     $id = decrypt($encryptId);
    //     if(!empty($id) && (int)$id) {
    //         $planData = DB::table('companydetails')
    //         ->select('subscriptionplans.geolocationaccess','subscriptionplans.plantype')
    //         ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'companydetails.paymentplan')
    //         ->where('companydetails.authid', '=', $id)
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

   // trial plan payment //
    public function trialpaymentplan() {
        $isSocial = false;
        $user = request('user');
        if(isset($user) && $user != '') {
            if($user == 'user') {
                $encryptId = request('id');
                $id = decrypt($encryptId);
            } else {
                $id = request('id');
            }

            if($user != 'admin') {
                $checkEmailExist =  dummy_registration::where('id', '=', (int)$id)->where('usertype', '=', 'company')->first();
                $checkEmailAddressExist = $checkEmailExist->email;
                if(!empty($checkEmailExist)) {
                    if($checkEmailExist->is_social == '0') {
                        $queryChecks = Auth::where('email', '=', $checkEmailAddressExist);
                        $countChecks = $queryChecks->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
                        $query2Checks = dummy_registration::where('email', '=', $checkEmailAddressExist);
                        $count2Checks = $query2Checks->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
                        $countChecks = $countChecks + $count2Checks;
                        if(!empty($countChecks) && $countChecks > 0) {
                             return response()->json(['error'=>'emailExist'], 401);
                        }
                    }
                } else {
                     return response()->json(['error'=>'networkerror'], 401);
                }
            }
        
            $isclaimed = request('isClaimBusiness');

            $nextDate = date('Y-m-d 00:00:00', strtotime("+ 90 days", strtotime(date('Y-m-d H:i:s'))));
            // $statusStep = Auth::where('id', (int)$id)->update(['stepscompleted' => '3','status' => 'active']);
            // if($statusStep) {
                // $statusCompany = Companydetail::where('authid', (int)$id)->update(['subscriptiontype' => 'manual','nextpaymentdate' => $nextDate, 'paymentplan' => (int)(request('subplan')),'plansubtype' => 'free','status' => 'active']);
           
            if(!empty($isclaimed) && ($isclaimed == 'true')) {
                $isClaimedData = true;
                $statusCompany = dummy_registration::where('id', (int)$id)->update(['subscriptiontype' => 'manual','nextpaymentdate' => $nextDate, 'paymentplan' => (int)(request('subplan')),'plansubtype' => 'free','stepscompleted' => '3','status' => 'active','lastpaymentdate' =>date('Y-m-d H:i:s')]);
            } else {
                $isClaimedData = false;
                $statusCompany = dummy_registration::where('id', (int)$id)->update(['subscriptiontype' => 'manual','nextpaymentdate' => $nextDate, 'paymentplan' => (int)(request('subplan')),'plansubtype' => 'free','stepscompleted' => '3','lastpaymentdate' =>date('Y-m-d H:i:s')]);
            }
            if(empty($statusCompany)) {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            //insert temp record to actual tables
            $tempData = dummy_registration::where('id',(int)$id)->first();
            $email = $tempData->email;
            if(!empty($email)) {
                $checkIfUserAlreadyExist = Auth::where('email',$email)->where('status','!=','deleted')->count();
                if($checkIfUserAlreadyExist) {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            }
            if(!$isClaimedData) {
                //Insert in auths table
                // if($tempData->is_claim_user == '1') {                
                //     $authid = $tempData->authid;
                //     $authData = Auth::find($authid);
                // } else {
                    $authData = new Auth;
                // }
                $authData->email = $tempData->email;
                $authData->password = $tempData->password;
                $authData->usertype = $tempData->usertype;
                $authData->ipaddress = $tempData->ipaddress;
                $authData->stepscompleted = $tempData->stepscompleted;
                $authData->newsletter = $tempData->newsletter;
                if($tempData->is_social == '1') {
                    $authData->is_activated = '1';
                } else {
                    $authData->is_activated = '0';
                }
                $authData->is_social = $tempData->is_social;
                $authData->social_id = $tempData->social_id;
                $authData->provider  = $tempData->provider;
                $authData->status = 'active';
                $company_name = $tempData->name;
                $company_name_new  = preg_replace('/[^a-zA-Z0-9_ -]/s','',$company_name);
                $slug = implode("-",explode(" ",$company_name_new));
                $slug1 = '';
                $array = explode(" ",$tempData->city);
                if(is_array($array)) {
                    $slug1 = implode("-",$array);       
                }
                $slug = strtolower($slug.'-'.$slug1);
                $realSlug = $slug;
                if($authData->save()) {
                    $authid = $authData->id;
                    if($tempData->is_claim_user == '1') {
                        $getCompany = Companydetail::where('authid','=',$authid)->first();  
                        if(!empty($getCompany)) {
                            $companyid = $getCompany->id;
                            $companyData = Companydetail::find($companyid);
                            if(empty($companyData)) {
                                return response()->json(['error'=>'networkerror'], 401);
                            } 
                        } else {
                            return response()->json(['error'=>'networkerror'], 401);    
                        }
                    } else {
                       $companyData = new Companydetail;     
                    }
                    $companyData->authid = $authid;
                    $companyData->name = $tempData->name;
                    // Calculate slug
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
                    $companyData->slug = $slug;
                    $companyData->actualslug = $realSlug;
                    $companyData->services = $tempData->services;
                    $companyData->address = $tempData->address;
                    $companyData->city = $tempData->city;
                    $companyData->state = $tempData->state;
                    $companyData->country = $tempData->country;
                    $companyData->zipcode = $tempData->zipcode;
                    $companyData->contact = $tempData->contact;
                    $companyData->about = $tempData->about;
                    $companyData->businessemail = $tempData->businessemail;
                    $companyData->websiteurl = $tempData->websiteurl;
                    $companyData->images = $tempData->images;
                    $companyData->longitude = $tempData->longitude;
                    $companyData->latitude = $tempData->latitude;
                    $companyData->nextpaymentdate = $tempData->nextpaymentdate;
                    $companyData->customer_id = $tempData->customer_id;
                    $companyData->subscription_id = $tempData->customer_id;
                    $companyData->paymentplan = $tempData->paymentplan;
                    $companyData->plansubtype = $tempData->plansubtype;
                    $companyData->subscriptiontype = $tempData->subscriptiontype;
                    $companyData->advertisebusiness = '0';
                    $companyData->primaryimage = $tempData->primaryimage;
                    $companyData->allservices = $tempData->allservices;
                    $companyData->contactname = $tempData->contactname;
                    $companyData->contactmobile = $tempData->contactmobile;
                    $companyData->contactemail = $tempData->contactemail;
                    $companyData->status = 'active';
                    $companyData->coverphoto = $tempData->coverphoto;
                    $companyData->accounttype = 'real';
                    $companyData->next_paymentplan = $tempData->paymentplan;
                    $companyData->lastpaymentdate = $tempData->lastpaymentdate;
                    $companyData->boats_yachts_worked    = $tempData->boats_yachts_worked;
                    $companyData->engines_worked    = $tempData->engines_worked;
                    $companyData->engines_worked    = $tempData->engines_worked;
                    $companyData->remaintrial    = 60;
                    if($tempData->is_claim_user == '1') {
                        $companyData->is_admin_approve = '0';
                        $companyData->is_claimed = '1';
                        $companyData->accounttype = 'claimed';
                        // $isclaimed = TRUE;    
                    } else {
                        $companyData->is_admin_approve = '1';
                        $companyData->is_claimed = '0';
                        $companyData->accounttype = 'real';
                        // $isclaimed = FALSE;    
                    }
                    // $companyData->county = $tempData->county;
                    if($companyData->save()) {
                        $DictionaryData = new Dictionary;
                        $DictionaryData->authid = $authid;
                        $DictionaryData->word = $tempData->name;
                        if($DictionaryData->save()) {
                        }
                        $statusStep = true;
                        // if($isclaimed) {
                        //     $existingGeo = Claimed_geolocation::where('authid', '=',$authid)->first();
                        //     if($existingGeo) {
                        //         $geoId = $existingGeo->id;
                        //         $geolocation  = Claimed_geolocation::find($geoId);
                        //     } else {
                        //         $geolocation  = new Claimed_geolocation;     
                        //     }
                        // } else {
                        //     $existingGeo = Geolocation::where('authid', '=',$authid)->first();
                        //     if($existingGeo) {
                        //         $geoId = $existingGeo->id;
                        //         $geolocation  = Geolocation::find($geoId);
                        //     } else {
                        //         $geolocation  = new Geolocation;     
                        //     }
                        // // }
                        // $city    = $tempData->city;
                        // $state   = $tempData->state;
                        // $zipcode = $tempData->zipcode;
                        // $country = $tempData->country;
                        // $county = $tempData->county;
                        // $addressGeo = ((isset($tempData->address) && $tempData->address !='') ? $tempData->address: '').' '.$city.' '.$zipcode.' '.$state.' ,'.$country;
                        // $output = $this->getGeoLocation($addressGeo); //Get Location from location Trait
                        // $longitude = $output['longitude'];
                        // $latitude = $output['latitude'];
                        // $geolocation->authid = $authid;
                        // $geolocation->city = $city;
                        // $geolocation->zipcode = $zipcode;
                        // $geolocation->country = $country;
                        // $geolocation->county = $county;
                        // $geolocation->state = $state;
                        // $geolocation->address    = ((isset($tempData->address) && $tempData->address !='') ? $tempData->address: NULL);
                        // $geolocation->longitude = $longitude;
                        // $geolocation->latitude = $latitude;
                        // $geolocation->status = '1';
                        // if($geolocation->save()) {
                        $geosuccess = TRUE;
                        $rejectedRegistration = dummy_registration::where('id', '=', (int)$id)->delete();
                        if($tempData->is_social == '0') {            
                            $random_hashed = Hash::make(str_random(8).$authid);
                            $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                            $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                            $link = $website_url.'/activate?token='.urlencode($random_hashed);
                            $ACTIVATION_LINK = $link;
                            $emailArr = [];                                        
                            $emailArr['link'] = $ACTIVATION_LINK;
                            /* 
                             //temp otp
                             $ACTIVATION_OTP=rand(10000,99999);
                             $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $ACTIVATION_OTP]);
                             $emailArr['otp'] = $ACTIVATION_OTP;
                             //
                            */
                            $emailArr['to_email'] = $tempData->email;
                            $emailArr['name'] = $tempData->name;
                            //Send activation email notification
                            if($tempData->is_claim_user == '1') {
                                $status = $this->sendEmailNotification($emailArr,'claimed_business_notification');
                            } else {
                                $status = $this->sendEmailNotification($emailArr,'business_registration_activation'); 
                            }
                            if($status != 'sent') {
                                return response()->json(['error'=>'emailsentfail'], 401);
                            }
                            $adminEmailArr = array();
                            $adminEmailArr['userEmail'] = $emailArr['to_email'];
                            $adminEmailArr['userType'] = 'Company';
                            $adminEmailArr['userFirstname'] = $emailArr['name'];
                            $adminEmailArr['to_email'] = env("Admin_Email");
                            //Send activation email notification
                            SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                            $adminEmailArr['to_email'] = env("Info_Email");
                            SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                        } else {
                            $isSocial = true;
                        }
                        // } else {
                        //     $geosuccess = FALSE;    
                        // }
                    } else {
                        return response()->json(['error'=>'entryfail'], 401);
                    }
                } else {
                    return response()->json(['error'=>'entryfail'], 401);
                }
            } else {
                $geosuccess = TRUE;
                $statusStep = TRUE;
                $dummyCompanyID = 0;
                DB::beginTransaction();  
                $userID = $id;
                $usersdata = dummy_registration::where('id', '=', (int)$userID) //Get Dummy Company data from Dummy registration
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
                        if(!empty($dummyCompanydata)) {                     //Save Old dummy Data to a backup Table
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
                                $authData = Auth::find($dummyCompanyID); //Update Dummy company data in Auth with dummy registration data 
                                $authData->email = $usersdata->email;
                                $authData->password = $usersdata->password;
                                $authData->usertype = $usersdata->usertype;   
                                $authData->ipaddress = $usersdata->ipaddress;             
                                $authData->stepscompleted = $usersdata->stepscompleted;
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
                                if($authData->save()) { //Updated Auth Table 
                                    $IsCompanyData = Companydetail::where('authid', '=',$dummyCompanyID)->first();
                                    if(!empty($IsCompanyData)) { //Update Dummy company data in Auth with dummy registration data
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
                                        $companyData->remaintrial = 60;
                                        $companyData->is_claimed = '1';
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
                                            $statusStep = TRUE;
                                            $rejectedRegistration = dummy_registration::where('authid', '=', $dummyCompanyID)->where('is_claim_user', '=', '1')->where('status', '=', 'active')->delete();
                                            DB::commit();
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

                $geosuccess = TRUE;
                $statusCompany = TRUE;
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

                if($tempData->is_social == '0' ) {
                    $emailArr['to_email'] = $tempData->email;
                } else {
                    $emailArr['to_email'] = $tempData->contactemail;
                }
                //Send activation email notification 
                if($isSocial) {
                    $status = $this->sendEmailNotification($emailArr,'approve_claimbusiness_social');
                } else {
                    $status = $this->sendEmailNotification($emailArr,'approve_claimbusiness');
                }
                $adminEmailArr = array();
                $adminEmailArr['userEmail'] = $emailArr['to_email'];
                $adminEmailArr['userType'] = 'Company';
                $adminEmailArr['userFirstname'] = $emailArr['name'];
                $adminEmailArr['to_email'] = env("Admin_Email");
                //Send activation email notification
                SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                $adminEmailArr['to_email'] = env("Info_Email");
                SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                if($tempData->is_social == '1') {
                    $isSocial = true;
                }
                if($status != 'sent') {
                    return response()->json(['error'=>'emailsentfail'], 401);
                }
                /*
                $authid = (int)$id;
                $random_hashed = Hash::make(str_random(8).$authid);
                $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                $link = $website_url.'/activate?token='.urlencode($random_hashed);
                $ACTIVATION_LINK = $link;
                $emailArr = [];                                        
                $emailArr['link'] = $ACTIVATION_LINK;
                if($tempData->is_social == '0' ) {
                    $emailArr['to_email'] = $tempData->email;
                } else {
                    $emailArr['to_email'] = $tempData->contactemail;
                }
                $emailArr['name'] = $tempData->name;
                //Send activation email notification
                $status = $this->sendEmailNotification($emailArr,'claimed_business_notification');
                if($status != 'sent') {
                    return response()->json(['error'=>'emailsentfail'], 401);
                }
                if($tempData->is_social == '1') {
                    $isSocial = true;
                }
                */
            }
            if($statusStep) {
                if($isClaimedData) { 
                    // $paymentTable = 'dummy_paymenthistory';
                    $paymentTable = 'paymenthistory';
                    $authid = $dummyCompanyID;
                } else {
                    $paymentTable = 'paymenthistory';
                }
                $zaiperenv = env('ZAIPER_ENV','local');
                if($zaiperenv == 'live') {
                    $zapierData = array();
                    $zapierData['type']     = 'Business';
                    $zapierData['id']   = $authid;
                    $zapierData['email']    = $tempData->email;
                    $zapierData['businessemail'] = $tempData->businessemail;
                    $zapierData['name']     = $tempData->name;
                    $zapierData['contact']  = $tempData->contact;
                    $zapierData['address']  = $tempData->address;
                    $zapierData['city']     = $tempData->city;
                    $zapierData['state']    = $tempData->state;
                    $zapierData['country']  = $tempData->country;
                    $zapierData['zipcode']  = $tempData->zipcode;
                    $zapierData['plan']  = 'Free';
                    $zapierData['subscriptiontype'] = $tempData->subscriptiontype;
                    $zapierData['contactmobile'] = $tempData->country_code.$tempData->contactmobile;
                    $zapierData['contactemail'] = $tempData->contactemail;
                    $zapierData['contactname'] = $tempData->contactname;
                    $zapierData['newsletter'] = $tempData->newsletter;
                    $zapierData['website'] = $tempData->websiteurl;
                    $zapierData['about'] = $tempData->about;
                    $this->sendAccountCreateZapierBiz($zapierData);
                }
                $statusPayment =  DB::table($paymentTable)->insert(
                                ['companyid' => (int)$authid,'transactionfor' => 'registrationfee',
                                'amount' => '0.00',
                                'status' => 'approved' ,
                                'payment_type'=>(int)(request('subplan')),
                                'expiredate' => $nextDate,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                                ]);
                if($statusPayment) {
                    if($tempData->is_social == '1' && $tempData->is_claim_user == '0') {
                        $userdata = Auth::where('id', '=', (int)$authid)->get();
                        $user = $userdata[0];
                        $success['type']    = $userdata[0]->usertype;
                        $success['authid']  = encrypt($userdata[0]->id);
                        $success['email']   = $userdata[0]->email;
                        $success['stepscompleted'] = $userdata[0]->stepscompleted;
                        $success['token']   =  $user->createToken('MyApp')->accessToken;
                        return response()->json(['success' => true,'isSocial'=>true,'userid' => request('id'),'nextdate'=> $nextDate,'steps' => '3','data' => $success], $this->successStatus);
                    } else {
                        return response()->json(['success' => true,'userid' => encrypt($authid),'nextdate'=> $nextDate,'isSocial' => $isSocial,'authid' => encrypt($authid)], $this->successStatus);
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
    //Save geolocations 
    // public function addGeolocation(Request $request) {
    //     $validate = Validator::make($request->all(), [
    //         'authid' => 'required',
    //         'locations' => 'required',
    //     ]);
    //     if ($validate->fails()) {
    //        return response()->json(['error'=>'validationError'], 401); 
    //     }
    //     $geolocationsArr = json_decode(request('locations'));
    //     $authid = decrypt(request('authid'));
    //     $insertIds = [];
    //     $additionalGeo = (int)(request('additionalgeo'));
    //     $planLocations = count($geolocationsArr)-$additionalGeo;
    //     $countLoc = 0;
    //     //save all location 
    //     foreach ($geolocationsArr as $location) {
    //         $geolocation = new Geolocation;
    //         $city = $location->city;
    //         $state = $location->state;
    //         $zipcode = $location->zipcode;
    //         $country = $location->country;
    //         $geoaddress = $location->address;
    //         $address = $city.' '.$zipcode.' '.$state.' ,'.$country;
    //         $output = $this->getGeoLocation($address); //Get Location from location Trait
    //         $longitude = $output['longitude'];
    //         $latitude = $output['latitude'];
    //         $geolocation->authid = $authid;
    //         $geolocation->city = $city;
    //         $geolocation->zipcode = $zipcode;
    //         $geolocation->country = $country;
    //         $geolocation->state = $state;
    //         $geolocation->longitude = $longitude;
    //         $geolocation->latitude = $latitude;
    //         $geolocation->address = $geoaddress;
    //         $geolocation->status = '1';
    //         if($countLoc >= $planLocations) {
    //             $geolocation->additional_location = '1';
    //         } else {
    //             $geolocation->additional_location = '0';
    //         }
    //         if($geolocation->save()) {
    //             $insertIds[] = $geolocation->id;
    //         } else {
    //             return response()->json(['error'=>'networkerror'], 401);
    //         } 
    //         $countLoc++;   
    //     }
    //     if(count($insertIds)) {
    //         return response()->json(['success' => true], $this->successStatus);
    //     } else {
    //         return response()->json(['success' => false], $this->successStatus);    
    //     }    
    // }

    // //Save geolocations 
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
    //                     $statusPayment =  DB::table('paymenthistory')->insert(
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
    //             $paymentHistroy = DB::table('paymenthistory')->where('companyid' ,'=',(int)$userID)->where('fingerprintid' ,'=',$tokenData['card']['fingerprint'])->first();
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
    //                         $statusPayment =  DB::table('paymenthistory')->insert(
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

    // get all dummy data //
    public function getDummyCompanyDataById(Request $request) {
        $rid = request('rid');
        $decryptUserid = decrypt($rid);
        $dmyid = request('dmyid');
        if(!empty($rid)) {
            $usersdata = DB::table('claimed_business')->select('name','contact','images','address','country','state','city','zipcode','websiteurl','businessemail','services','about','contactemail','contactmobile','contactname')
            ->where('status', '!=', 'deleted')
            ->where('id', '=', (int)$decryptUserid)
            ->first();
            if(!empty($usersdata)) {
                $services = $usersdata->services;
                $otherServicename = NULL;
                $servicesArray = (array)json_decode($services);
                //print_r($services);die;
                $otherService = [];
               if(array_key_exists('11', $servicesArray)) {
                    $otherService = Service::select('service')->whereIn('id',$servicesArray['11'])->get();
                }
                $usersdata->otherService = $otherService;
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                $usersdata = array();
                return response()->json(['success' => false,'data' => $usersdata], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }        
    }

   // //Get all geolocation by id 
    public function getGeolocationsById(Request $request) {
       $validate = Validator::make($request->all(), [
            'rid' => 'required'
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $rid = request('rid');
        $decryptUserid = decrypt($rid);
        $isClaim = request('isClaim');
        if(!(empty($isClaim)) && $isClaim == 'true') {
            $geolocations = Dummy_geolocation::select('id','authid','city','state','zipcode','created_at')
                ->where('authid', '=',(int)$decryptUserid)
                ->where('status','=','1')
                ->get();
        } else {
            $geolocations = Geolocation::select('id','authid','city','state','zipcode','created_at')
                        ->where('authid', '=',(int)$decryptUserid)
                        ->where('status','=','1')
                        ->get();
        }
        
        if($geolocations) {
            return response()->json(['success' => true,'data' => $geolocations], $this->successStatus);
        } else {
            return response()->json(['success' => false,'data' => []], $this->successStatus);
        }    
    }    

    // trial plan  dummy payment //
    public function trialdummypaymentplan() {
        //$user = request('user');
        //if(isset($user) && $user != '') {
            // if($user == 'user') {
        $encryptId = request('id');
        $id = decrypt($encryptId);
            // } else {
            //     $id = request('id');
            // }
        $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
        $statusStep = Claimed_business::where('id', (int)$id)->update(['stepscompleted' => '3']);
        if($statusStep) {
            $statusCompany = Claimed_business::where('id', (int)$id)->update(['subscriptiontype' => 'manual','nextpaymentdate' => $nextDate, 'paymentplan' => (int)(request('subplan')),'plansubtype' => 'free']);
            if($statusStep) {
                $statusPayment =  DB::table('claimed_paymenthistory')->insert(
                                ['companyid' => (int)$id,'transactionfor' => 'registrationfee',
                                'amount' => '0.00',
                                'status' => 'approved' ,
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
        // } else {
        //     return response()->json(['error'=>'networkerror'], 401);
        // }
    }

    //Create Customer account in stripe
    public function companydummyPayment(Request $request){
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
        // if($rtype == 'admin') {
        //    $userID = request('userID');
        // } else {
        $useridencrypt = request('userID');
        $userID = decrypt($useridencrypt);
        // }
        if(empty($userID) || $userID == '') {
            return response()->json(['error'=>'networkerror'], 401); 
        }
        /* Get user card Token and Plan*/
        $cardHolderName = request('nameoncard');
        $subplan = request('subplan');
        $card_token = request('card_token');
        //$userID = request('userID');
        $userDetail = Claimed_business::where('id', '=', (int)$userID)->where('status', '!=', 'deleted')->get()->first()->toArray();
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
                    $nextDate = date('Y-m-d H:i:s', strtotime("+".$planAccessNumber." months", strtotime(date('Y-m-d H:i:s'))));
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
                        $statusStep = true;
                        $statusCompany = Claimed_business::where('id', (int)$userID)->update(['stepscompleted' => '3','subscriptiontype' => $subType,'customer_id' => $stripe_id,'nextpaymentdate' => $nextDate, 'paymentplan' => (int)$subplan,'plansubtype' => 'paid']);
                        if($statusStep && $statusCompany) {
                          $statusPayment =  DB::table('claimed_paymenthistory')->insert(
                                    ['companyid' => (int)$userID,
                                    'transactionid' => $charge['balance_transaction'],
                                    'tokenused' => $card_token,
                                    'transactionfor' => 'registrationfee',
                                    'amount' => $planPrice,
                                    'status' => 'approved' ,
                                    'fingerprintid' => $charge['source']['fingerprint'] ,
                                    'cardid' => $tokenData['card']['id'],
                                    'expiredate' => $nextDate,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                    ]);
                           $chiperUserid = encrypt($authid);
                            if($statusPayment) {
                                return response()->json(['success' => true,'userid' => $chiperUserid], $this->successStatus);
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
                $paymentHistroy = DB::table('claimed_paymenthistory')->where('companyid' ,'=',(int)$userID)->where('fingerprintid' ,'=',$tokenData['card']['fingerprint'])->first();
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
                            $statusStep = true;
                            $statusCompany = Claimed_business::where('id', (int)$userID)->update(['stepscompleted' => '3','subscriptiontype' => $subType,'customer_id' => $userDetail['stripeid'],'nextpaymentdate' => $nextDate,'paymentplan' => (int)$subplan, 'plansubtype' => 'paid','status' => 'active']);
                            if($statusStep && $statusCompany) {
                                $statusPayment =  DB::table('claimed_paymenthistory')->insert(
                                    ['companyid' => (int)$userID,
                                    'transactionid' => $charge['balance_transaction'],
                                    'tokenused' => $card_token,
                                    'transactionfor' => 'registrationfee',
                                    'amount' => $planPrice,
                                    'status' => 'approved' ,
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

    //Edit geolocations 
    public function editGeolocation(Request $request) {
        $validate = Validator::make($request->all(), [
            'authid' => 'required',
            'locations' => 'required',
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $isUpdated = request('isUpdated');
        $notUpdated = false;
        if(!empty($isUpdated) && $isUpdated == 'false') {
            $notUpdated = true;
        }
        $geolocationsArr = json_decode(request('locations'));
        $insertIds = [];
        $countLoc = 0;
        $geoArr = [];
        $useridencrypt = request('authid');
        $authid = decrypt($useridencrypt);
        //$authid = request('authid');
        $isclaimed = request('isclaimed');
        //echo $isclaimed;die;
        // if(!empty($isclaimed) && ($isclaimed)) {
        //     $isclaimed = request('isclaimed');
        // }
        //save all location .

        foreach ($geolocationsArr as $location) {
            if($location->state != '' && $location->city != '' && $location->zipcode != '') {
                if(!empty($isclaimed) && ($isclaimed == 'true')) {
                    $totalGeoLoc = DB::table('dummy_registration')->where('id','=',$authid)->first();
                } else {
                    $totalGeoLoc = DB::table('companydetails as c')->select('ath.is_social','c.is_claimed')
                ->join('auths as ath','ath.id','=','c.authid')
                ->where('authid','=',$authid)->first();
                }
                // }
                
                if(empty($totalGeoLoc)) {
                    return response()->json(['error'=>'networkerror'], 401);
                }
                if(!empty($isclaimed) && ($isclaimed == 'true')) {
                    $totalrecords = DB::table('dummy_geolocation')->where('authid','=',$authid)->where('status','=','1')->count();
                
                // if($totalrecords <= $totalGeoLoc->geolocationaccess) {
                    if(isset($location->hidlocationid) && !empty($location->hidlocationid)) {
                        $geolocation = Dummy_geolocation::find($location->hidlocationid);   
                    } else {
                        $geolocation = new Dummy_geolocation;
                    }
                } else {
                    $totalrecords = DB::table('geolocation')->where('authid','=',$authid)->where('status','=','1')->count();
                    
                // if($totalrecords <= $totalGeoLoc->geolocationaccess) {
                    if(isset($location->hidlocationid) && !empty($location->hidlocationid)) {
                        $geolocation = Geolocation::find($location->hidlocationid);    
                        $geoArr[] =  $location->hidlocationid;
                    } else {
                        $geolocation = new Geolocation;
                    }
                }
                    $city = $location->city;
                    $state = $location->state;
                    $zipcode = $location->zipcode;
                    // $county = $location->county;
                    $geolocation->authid = $authid;
                    $geolocation->city = $city;
                    $geolocation->zipcode = $zipcode;
                    // $geolocation->county = $county;
                    $geolocation->state = $state;
                    $geolocation->status = '1';
                    if($geolocation->save()) {
                        $insertIds[] = $geolocation->id;
                        $geoArr[] =  $geolocation->id;
                    } else {
                        return response()->json(['error'=>'networkerror'], 401);
                    }
                   $countLoc++;   
           }
        }
        if(!empty($isclaimed) && ($isclaimed == 'true')) {
            $changeStatus = DB::table('dummy_geolocation')->whereNotIn('id',$geoArr)->where('authid',$authid)->update(['status' => '0']);
        } else {
            $changeStatus = DB::table('geolocation')->whereNotIn('id',$geoArr)->where('authid',$authid)->update(['status' => '0']);
        }
        if(count($insertIds)) {
            $isSocial = false;
            $claimed = false;
            if($totalGeoLoc->is_social == '1') {
                $isSocial = true;
            }
            if($totalGeoLoc->is_claimed == '1') {
                $claimed = true;
            }
            if($notUpdated) {
                if($totalGeoLoc->is_social == '1' && $totalGeoLoc->is_claimed == '0') {
                    $userdata = Auth::where('id', '=', (int)$authid)->get();
                    $user = $userdata[0];
                    $success['type']    = $userdata[0]->usertype;
                    $success['authid']  = encrypt($userdata[0]->id);
                    $success['email']   = $userdata[0]->email;
                    $success['stepscompleted'] = $userdata[0]->stepscompleted;
                    $success['token']   =  $user->createToken('MyApp')->accessToken;
                    return response()->json(['success' => true,'isSocial'=>true,$claimed => false,'userid' => request('id'),'nextdate'=> $nextDate,'steps' => '3','data' => $success], $this->successStatus);
                } else {
                    return response()->json(['success' => true,'isclaimed'=>$claimed,'isSocial' => $isSocial], $this->successStatus);
                }
            } else {
                return response()->json(['success' => true], $this->successStatus);
            }
        } else {
            return response()->json(['success' => false,'data' => []], $this->successStatus);    
        }    
    }

    //   //Delete geolocations  //
    // public function deleteGeolocation(Request $request) {
    //     $validate = Validator::make($request->all(), [
    //         'id' => 'required'
    //     ]);
    //     if ($validate->fails()) {
    //        return response()->json(['error'=>'validationError'], 401); 
    //     }
    //     $isClaim = request('isClaim');
    //     if(!(empty($isClaim)) && $isClaim == 'true') {
    //         $updated = Claimed_geolocation::where('id', '=', ((int)request('id')))->update(['status' => '0']);
    //     } else {
    //         $updated = Geolocation::where('id', '=', ((int)request('id')))->update(['status' => '0']);
    //     }
    //     if($updated) {
    //         return response()->json(['success' => true], $this->successStatus);
    //     }  else {
    //         return response()->json(['error'=>'networkerror'], 401);
    //     } 
    // }

    // check email exist //
    public function checkEmailCompany(Request $request) {
        $userEmail = strtolower(request('email'));
        $success = false;
        if(!empty($userEmail) && $userEmail != '' ) {
            $query = Auth::where(function ($query) use ($userEmail) {
                                        $query->where('email', '=', $userEmail)
                                        ->orWhere('requested_email', '=', $userEmail);
                                    });
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
    
    //Get Plan Geolocation details//
    // public function getClaimPlanGeoLocation(Request $request) {
    //     $encryptId = request('id');
    //     $id = decrypt($encryptId);
    //     $isClaim = request('isClaim');
    //     if(!empty($id) && (int)$id) {
    //         if(!(empty($isClaim)) && $isClaim == 'true') {
    //             $planData = DB::table('dummy_registration')
    //             ->select('subscriptionplans.geolocationaccess','subscriptionplans.plantype')
    //             ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'dummy_registration.paymentplan')
    //             ->where('dummy_registration.id', '=', $id)
    //             ->first();
               
    //         } else {
    //             $planData = DB::table('companydetails')
    //             ->select('subscriptionplans.geolocationaccess','subscriptionplans.plantype')
    //             ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'companydetails.paymentplan')
    //             ->where('companydetails.authid', '=', $id)
    //             ->first();
    //         }
           
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
    // public function addclaimGeolocationpayment(Request $request) {
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
    //     $isClaimed = request('isClaimed');
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
    //     if(!(empty($isClaimed)) && $isClaimed == 'true') {
    //         $isClaimedData = true;
    //         $userDetail = dummy_registration::where('id', '=', (int)$userID)->where('status', '!=', 'rejected')->get()->first()->toArray();
    //     } else {
    //         $isClaimedData = false;
    //         $userDetail = Auth::where('id', '=', (int)$userID)->where('status', '!=', 'deleted')->get()->first()->toArray();
    //     }
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
    //                     if(!(empty($isClaimed)) && $isClaimed == 'true') {
    //                         $statusPayment =  DB::table('dummy_paymenthistory')->insert(
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
    //                     } else {
    //                        $statusPayment =  DB::table('paymenthistory')->insert(
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
    //                     }
                        
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
    //             if(!(empty($isClaimed)) && $isClaimed == 'true') {
    //                 $paymentHistroy = DB::table('dummy_paymenthistory')->where('companyid' ,'=',(int)$userID)->where('fingerprintid' ,'=',$tokenData['card']['fingerprint'])->first();
    //             } else {
    //                 $paymentHistroy = DB::table('paymenthistory')->where('companyid' ,'=',(int)$userID)->where('fingerprintid' ,'=',$tokenData['card']['fingerprint'])->first();
    //             }
                
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
    //                         if(!(empty($isClaimed)) && $isClaimed == 'true') {
    //                             $statusPayment =  DB::table('dummy_paymenthistory')->insert(
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
    //                         } else {
    //                             $statusPayment =  DB::table('paymenthistory')->insert(
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
    //                         }
                            
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

    // Get all US city
    public function  getallCounty() {
        $statename = request('statename');
        $states = Usarea::select('county')->where('statename','=', $statename)->groupBy('county')->orderBy('county','ASC')->get();
        $formated = [];
        if($states) {
            return response()->json(['success' => true, 'countydata' => $states ], $this->successStatus);
        } else {
            return response()->json(['success' => false, 'countydata' => $formated ], $this->successStatus);
        }
    }

    public function activate(Request $request) {
        $token = request('token');
        if(!empty($token)) {
            $checkToken = Auth::select('id','usertype')->where('activation_hash',urldecode($token))->where('is_activated','=','0')->first();
            if(!empty($checkToken) && isset($checkToken->id)) {   
                $authid = $checkToken->id;
                $updateStatus = Auth::where('id',$authid)->update(['is_activated' =>'1','activation_hash' => NULL]);
                if ($checkToken->usertype == 'regular') {
                    $table = 'userdetails';
                } else if ($checkToken->usertype == 'professional') {
                    $table = 'talentdetails';
                } else if($checkToken->usertype == 'yacht'){
                    $table = 'yachtdetail';
                }
                $data = [];
                if(!empty($table)) {
                    $data = DB::table($table)->select('firstname')->where('authid','=',$authid)->first();
                    $data->usertype = $checkToken->usertype;
                }
                if(!empty($updateStatus)) {
                    return response()->json(['success' => true, 'data' => $data], $this->successStatus);    
                } else {
                    return response()->json(['error'=>'networkerror1'], 401);    
                }
            } else {
                return response()->json(['error'=>'networkerror2'], 401);    
            }
        } else {
            return response()->json(['error'=>'networkerror3'], 401);
        }
    }
    //Checking Otp for account activation
    public function verifyOtp(Request $request) {
        $otp = request('otp');
        if(!empty($otp)) {
            $checkToken = Auth::select('id','usertype')->where('activation_hash',$otp)->where('is_activated','=','0')->first();
            if(!empty($checkToken) && isset($checkToken->id)) {   
                $authid = $checkToken->id;
                $updateStatus = Auth::where('id',$authid)->update(['is_activated' =>'1','activation_hash' => NULL]);
                if ($checkToken->usertype == 'regular') {
                    $table = 'userdetails';
                } else if ($checkToken->usertype == 'professional') {
                    $table = 'talentdetails';
                } else if($checkToken->usertype == 'yacht'){
                    $table = 'yachtdetail';
                }
                $data = [];
                if(!empty($table)) {
                    $data = DB::table($table)->select('firstname')->where('authid','=',$authid)->first();
                    $data->usertype = $checkToken->usertype;
                }
                if(!empty($updateStatus)) {
                    return response()->json(['success' => true, 'data' => $data], $this->successStatus);    
                } else {
                    return response()->json(['error'=>'There is some error while activating you account.'], 401);    
                }
            } else {
                return response()->json(['error'=>'OTP entered is incorrect.'], 401);
            }
        } else {
            return response()->json(['error'=>'There is some error while activating you account.'], 401);
        }
    }
    
    // public function companyCurrentPlan(Request $request) {
    //     $useridencrypt = request('id');
    //     $authid = decrypt($useridencrypt);
    //     $currentTime = Carbon\Carbon::now();
    //     if(!empty($authid) && $authid > 0) {
    //         $usersdata = DB::table('companydetails')
    //         ->Join('paymenthistory', 'paymenthistory.companyid', '=','companydetails.authid')
    //         ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'companydetails.paymentplan')->select('paymenthistory.created_at','paymenthistory.expiredate', 'subscriptionplans.*')
    //         ->where('companydetails.authid','=',(int)$authid)
    //         ->where('paymenthistory.expiredate','>',$currentTime)
    //         ->first();
    //         if(!empty($usersdata)) {
    //             return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
    //         } else {
    //             return response()->json(['success' => true,'data'=>'planExpireError'], $this->successStatus);
    //         }
    //     } else {
    //         return response()->json(['error'=>'networkerror'], 401);  
    //     }
    // }

    public function resendActivationLink(Request $request) {
        $id = request('authid');
        if(!empty($id)) {
            $userid = decrypt($id);
            $userdata = Auth::select('usertype','email')->where('id','=',$userid)->first();
            if(!empty($userdata)) {
                $select = [];
                if($userdata->usertype == 'yacht') {
                    $select[] = 'firstname';
                    $select[] = 'lastname';
                    $table = 'yachtdetail';
                } else if($userdata->usertype == 'company') {
                    $select[] = 'name';
                    $table = 'companydetails';
                } else if($userdata->usertype == 'regular') {
                    $select[] = 'firstname';
                    $select[] = 'lastname';
                    $table = 'userdetails';
                } else if($userdata->usertype == 'professional') {
                    $select[] = 'firstname';
                    $select[] = 'lastname';
                    $table = 'talentdetails';
                } 
                $userData = DB::table($table)->select($select)->where('authid',$userid)->first();
                if(!empty($userData)) {
                    $random_hashed = Hash::make(md5(uniqid($userid, true)));
                    $updateHash = Auth::where('id','=',$userid)->update(['activation_hash' => $random_hashed]);
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    $link = $website_url.'/activate?token='.urlencode($random_hashed);
                    $ACTIVATION_LINK = $link;
                    $emailArr = [];       
                    $emailArr['link'] = $ACTIVATION_LINK;

                    /*
                    $emailArr = [];
                    $ACTIVATION_OTP=rand(10000,99999);
                    $emailverification = request('verifyEmail');
                    $emailArr['otp'] = $ACTIVATION_OTP;
                    */
                    $emailArr['to_email'] = $userdata->email;
                    if($userdata->usertype == 'company') {
                        $emailArr['name'] = $userData->name;
                    } else {
                        $emailArr['name'] = $userData->firstname.' '.$userData->lastname;
                    }
                    $status = $this->sendEmailNotification($emailArr,'resend_confirmation');
                    /*
                    if(!empty($emailverification)) {
                        $emailArr['to_email'] = $requested_email = request('requested_email');
                        $updateHash = Auth::where('id','=',$userid)->update(['email_hash' => $ACTIVATION_OTP,'requested_email' => $requested_email]);
                        $status = $this->sendEmailNotification($emailArr,'resend_email_otp');
                    } else {
                        $updateHash = Auth::where('id','=',$userid)->update(['activation_hash' => $ACTIVATION_OTP]);
                        $status = $this->sendEmailNotification($emailArr,'resend_confirmation');
                    }
                    */

                    if($status != 'sent') {
                        return response()->json(['error'=>'emailsentfail'], 401);
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

    public function getClaimBusinessData(Request $request) {
        $decryptUserid = request('claimId');
        if(!empty($decryptUserid)) {
            $usersdata = DB::table('auths')
            ->leftJoin('companydetails', 'auths.id', '=', 'companydetails.authid')
            ->select('companydetails.name','companydetails.contact','companydetails.images','companydetails.address','companydetails.country','companydetails.state','companydetails.city','companydetails.zipcode','companydetails.websiteurl','companydetails.businessemail','companydetails.services','companydetails.about','companydetails.contactemail','companydetails.contactmobile','companydetails.contactname')
            ->where('auths.usertype', '=', 'company')
            ->where('auths.status', '!=', 'deleted')
            ->where('auths.id', '=', (int)$decryptUserid)
            ->first();
            if(!empty($usersdata)) {
                $services = $usersdata->services;
                $otherServicename = NULL;
                $servicesArray = (array)json_decode($services);
                //print_r($services);die;
                $otherService = [];
               if(array_key_exists('11', $servicesArray)) {
                    $otherService = Service::select('service')->whereIn('id',$servicesArray['11'])->get();
                }
                $usersdata->otherService = $otherService;
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                $usersdata = array();
                return response()->json(['success' => false,'data' => $usersdata], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }  
    }

    public function forgetpassword(Request $request) {
         $validate = Validator::make($request->all(), [
            'email' => 'required',
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }

        $userdata = Auth::where('email','=', strtolower(request('email')))->where('status','!=','deleted')->first();
        if(!empty($userdata)) {
            $authid = $userdata->id;
            $random_hashed = Hash::make(md5(uniqid($authid, true)));
            $updateHash = Auth::where('id','=',(int)$authid)->update(['password_hash' => $random_hashed]);
            $website_url = env('NG_APP_URL','https://www.marinecentral.com');
            $link = $website_url.'/reset-password?token='.urlencode($random_hashed);
            $ACTIVATION_LINK = $link;
            $emailArr = [];                                        
            $emailArr['link'] = $ACTIVATION_LINK;
            $emailArr['to_email'] = $userdata->email;
                //Send activation email notification
            $status = $this->sendEmailNotification($emailArr,'forget_password');
            if($status != 'sent') {
                return response()->json(['error'=>'emailsentfail'], 401);
            } else {
                return response()->json(['success' => true], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }

    }

    public function checkValidpasswordhash(Request $request) {
        $validate = Validator::make($request->all(), [
            'token' => 'required',
        ]);
        $token = request('token');
        if(!empty($token)) {
            $checkToken = Auth::select('id')->where('password_hash',urldecode($token))->where('status','!=', 'deleted')->first();
            if(!empty($checkToken) && isset($checkToken->id)) {   
                return response()->json(['success' => true], $this->successStatus);    
            } else {
                return response()->json(['error'=>'invalidtoken'], 401);    
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    public function resetpassword(Request $request) {
        $validate = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'required',
            'confirm' => 'required|same:password'
        ]);
   
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }

        $token = request('token');
        if(!empty($token)) {
            $checkToken = Auth::select('id')->where('password_hash',urldecode($token))->where('status','!=','deleted')->first();
            if(!empty($checkToken) && isset($checkToken->id)) {   
                $authid = $checkToken->id;
                $updatepassword =Hash::make(request('password'));
                $updateStatus = Auth::where('id',$authid)->update(['password_hash' =>NULL,'password'=>$updatepassword]);
                if(!empty($updateStatus)) {
                    return response()->json(['success' => true], $this->successStatus);    
                } else {
                    return response()->json(['error'=>'networkerror1'], 401);    
                }
            } else {
                return response()->json(['error'=>'networkerror2'], 401);    
            }
        } else {
            return response()->json(['error'=>'networkerror3'], 401);
        }
    }


    public function checkandchangeEmailAddress(Request $request) {
        $validate = Validator::make($request->all(), [
            'email' => 'required',
            'id' => 'required'
        ]);
   
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $userid = request('id');
        $decryptUserid = decrypt($userid);
        if(empty($decryptUserid) || $decryptUserid == '') {
            return 'networkerror'; 
        } else {
            $checkEmailAddressExist = strtolower(request('email'));
            $checkEmailExist =  dummy_registration::where('id', '=', (int)$decryptUserid)->first();
            if(!empty($checkEmailExist)) {
                $queryChecks = Auth::where('email', '=', $checkEmailAddressExist);
                $countChecks = $queryChecks->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
                $query2Checks = dummy_registration::where('email', '=', $checkEmailAddressExist);
                $count2Checks = $query2Checks->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
                $countChecks = $countChecks + $count2Checks;
                if(!empty($countChecks) && $countChecks > 0) {
                     return response()->json(['error'=>'emailExist'], 401);
                } else {
                    $updateStatus = dummy_registration::where('id','=',(int)$decryptUserid)->update(['email' => $checkEmailAddressExist]);
                    if(!empty($updateStatus)) {
                        return response()->json(['success' => true], $this->successStatus);    
                    } else {
                        return response()->json(['error'=>'networkerror1'], 401);    
                    }
                }
            } else {
                 return response()->json(['error'=>'networkerror'], 401);
            }
        }
    }
    
    // social registration for all type of user //
    public function registrationStage1Social(Request $request) {
        $validate = Validator::make($request->all(), [
            'socialid' => 'required',
            'socialprovider' => 'required',
            'rtype' => 'required'
        ]);
        
        $newsletter = request('newsletter');
        $name = request('socialUserName');
        if($name !='' && $name != null) {
            $nameArr = explode(" ",$name);
        }
        $firstname = '';
        $lastname = '';
        $isNameExist = false;
        if(!empty($nameArr[0])) {
            $firstname = $nameArr[0];
            $isNameExist = true;
        }
        if(!empty($nameArr[1])) {
            $lastname = $nameArr[1];
        }
        $email = strtolower(request('email'));
        $socialid = (string)request('socialid');
        $provider = request('socialprovider');
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $userEmail = strtolower(request('email'));
       
        $rtype = request('rtype');
        switch ($rtype) {
            case 'ur':
                $registerType = 'regular';    
            break;
            case 'pr':
                $registerType = 'professional'; 
            break;
            case 'bn':
                $registerType = 'company'; 
            break;
            case 'yt':
                $registerType = 'yacht'; 
            break;
            default:
                return response()->json(['error'=>'networkerror'], 401);
        }
        
        if($registerType != 'company') {
            $query = Auth::where(function ($query) use ($userEmail) {
                                            $query->where('email', '=', $userEmail)
                                            ->orWhere('requested_email', '=', $userEmail);
                                        });
            $count = $query->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
            $query2 = dummy_registration::where('email', '=', strtolower(request('email')));
            $count2 = $query2->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
            $count = $count + $count2;
            if(!empty($count) && $count > 0) {
                return response()->json(['error'=>'validationError'], 401); 
            }
        }
        $isclaimed = request('isclaimed');
        $userid = 0;
        $isdummy = false;
        $dummyReg = new dummy_registration;

        if(!empty($isclaimed) && ($isclaimed == 'true')) {
           $dummyReg->is_claim_user = '1';
           $isdummy = true;  
        } else {
            $dummyReg->is_claim_user = '0';
        }
        if($registerType == 'company') {
            $dummyReg->email = NULL;
        } else {
            $dummyReg->email = strtolower(request('email'));
        }
        $dummyReg->password = NULL;
        $dummyReg->social_id = $socialid;
        $dummyReg->ipaddress = $this->getIp();
        $dummyReg->usertype = $registerType;
        $dummyReg->stepscompleted ='1'; 
        $dummyReg->provider = $provider;
        $dummyReg->is_social = '1';
        $dummyReg->newsletter = (!empty($newsletter) && ($newsletter == 'true')) ? '1':'0';
        if($isNameExist) {
            $dummyReg->name = $name;
            $dummyReg->firstname = $firstname;
            $dummyReg->lastname = $lastname;
        }
        if($dummyReg->save()) {
            $userid = $dummyReg->id;
            $chiperUserid = encrypt($userid);
            if($isdummy) {
                $getDummyData = Companydetail::where('authid','=',(int)request('claimid'))->first();
                if(!empty($getDummyData)) {
                    $dummyReg  = dummy_registration::find($userid);
                    $dummyReg->authid  = (int)request('claimid');
                    $dummyReg->name  = $getDummyData->name;
                    $dummyReg->services   = ((isset($getDummyData->services) && $getDummyData->services !='') ? $getDummyData->services: NULL);
                    $dummyReg->address    = ((isset($getDummyData->address) && $getDummyData->address !='') ? $getDummyData->address: NULL);
                    $dummyReg->city       = $getDummyData->city;
                    $dummyReg->state      = $getDummyData->state;
                    $dummyReg->about      = ((isset($getDummyData->about) && $getDummyData->about !='') ? $getDummyData->about: NULL);
                    $dummyReg->businessemail =((isset($getDummyData->businessemail) && $getDummyData->businessemail !='') ? $getDummyData->businessemail: NULL);
                    $dummyReg->primaryimage =  ((isset($getDummyData->primaryimage) && $getDummyData->primaryimage !='') ? $getDummyData->primaryimage: NULL);
                    $dummyReg->allservices = ((isset($getDummyData->allservices) && $getDummyData->allservices !='') ? $getDummyData->allservices: NULL);
                    $dummyReg->websiteurl = ((isset($getDummyData->websiteurl) && $getDummyData->websiteurl !='') ? $getDummyData->websiteurl: NULL);
                    $dummyReg->country    = $getDummyData->country;
                    // $dummyReg->county    = $getDummyData->county;
                    $dummyReg->zipcode    = $getDummyData->zipcode;
                    $dummyReg->contact    = ((isset($getDummyData->contact) && $getDummyData->contact !='') ? $getDummyData->contact: NULL);
                    $dummyReg->contactname    = ((isset($getDummyData->contactname) && $getDummyData->contactname !='') ? $getDummyData->contactname: NULL);
                    $dummyReg->contactmobile    = ((isset($getDummyData->contactmobile) && $getDummyData->contactmobile !='') ? $getDummyData->contactmobile: NULL);
                    $dummyReg->contactemail    = ((isset($getDummyData->contactemail) && $getDummyData->contactemail !='') ? $getDummyData->contactemail: NULL);
                    $dummyReg->images     = ((isset($getDummyData->images) && $getDummyData->images !='') ? $getDummyData->images: NULL);
                    $dummyReg->longitude  = $getDummyData->longitude;
                    $dummyReg->latitude   = $getDummyData->latitude;
                    if($dummyReg->save()) {
                        // $dumygeolocation  = new Claimed_geolocation; 
                        // $dumygeolocation->authid = (int)request('claimid');
                        // $dumygeolocation->city = $getDummyData->city;
                        // $dumygeolocation->zipcode = $getDummyData->zipcode;
                        // $dumygeolocation->country = $getDummyData->country;
                        // $dumygeolocation->county = $getDummyData->county;
                        // $dumygeolocation->state = $getDummyData->state;
                        // $dumygeolocation->address    = ((isset($getDummyData->address) && $getDummyData->address !='') ? $getDummyData->address: NULL);
                        // $dumygeolocation->longitude = $getDummyData->longitude;
                        // $dumygeolocation->latitude = $getDummyData->latitude;
                        // $dumygeolocation->status = '1';
                        // if($dumygeolocation->save()) {
                            return response()->json(['success' => true,'userid' => $chiperUserid,'steps' => '1'], $this->successStatus);
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
            return response()->json(['success' => true,'userid' => $chiperUserid,'steps' => '1'], $this->successStatus);
             } 
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    
    // check email exist //
    public function checkEmailCompanyForget(Request $request) {

        $userEmail = strtolower(request('email'));
        $success = false;
        $social = '';
        if(!empty($userEmail) && $userEmail != '' ) {
            $query = Auth::where('email', '=', $userEmail);
            $count = $query->where('status', '!=', 'deleted')->where('accounttype','=','real')->where('is_social','=','0')->count();
            $query2 = dummy_registration::where('email', '=', $userEmail);
            $count2 = $query2->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->where('is_social','=','0')->count();
            $count = $count + $count2;
            if(!empty($count) && $count > 0) {
                $success = 'exist';
            } else {
                $array1 = $array2 = [];
                $query = Auth::where('email', '=', $userEmail);
                $array1 = $query->where('status', '!=', 'deleted')->where('accounttype','=','real')->where('is_social','=','1')->get();
                $query2 = dummy_registration::where('email', '=', $userEmail);
                $array2 = $query2->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->where('is_social','=','1')->get();
                if(!empty($array1) && count($array1) > 0) {
                    $social = $array1[0]->provider;
                    $success = 'social';
                } else if (!empty($array2) && count($array2) > 0) {
                    $social = $array2[0]->provider;
                    $success = 'social';
                } else {
                    $success = false; 
                }
            }
        }
        return response()->json(['success' => $success,'social'=>$social], $this->successStatus);
    } 
    
     public function _http($url, $post_data = null)
    {       
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        if(isset($post_data))
        {
            
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        $response = curl_exec($ch);
        $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->last_api_call = $url;
        curl_close($ch);
        return $response;
    }

    public function _urlencode_rfc3986($input)
    {
        if (is_array($input)) {
            return $input;
        }
        else if (is_scalar($input)) {
            return str_replace('+',' ',str_replace('%7E', '~', rawurlencode($input)));
        }
        else{
            return '';
        }
    }
    
    public function getTempUserDataById(Request $request) {
        $rid = request('rid');
        $decryptUserid = decrypt($rid);
        if(!empty($decryptUserid)) {
            $usersdata = DB::table('dummy_registration as userdetail')
            ->select('userdetail.firstname','userdetail.lastname')
            ->where('userdetail.usertype', '=', 'regular')
            ->where('userdetail.id', '=', (int)$decryptUserid)
            ->first();
            if(!empty($usersdata)) {
                return response()->json(['success' => true,'data' => $usersdata], $this->successStatus);
            } else {
                $usersdata = array();
                return response()->json(['success' => false,'data' => $usersdata], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }        
    }

    public function requestToken(Request $request)
    {       
        $url = request('url');
        $headers = request('headers');
        $header = [
            'Authorization:'.$headers.'',
            'Content-type:text/html;charset=utf-8'
        ];
        $post = [];
        $ch = curl_init();    
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);    
        curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));

        $response = curl_exec($ch);
        $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->last_api_call = $url;
        curl_close($ch);
        return $response;
    }

    public function generateToken(Request $request)
    {       
        $url = request('url');
        $headers = request('headers');
        $header = [
            'Authorization:'.$headers.'',
            'Content-type:text/html;charset=utf-8'
        ];
        $url .='?oauth_token='.request('authkey').'&oauth_verifier='.request('authrequestkey').''; 
        $post = [];
        $ch = curl_init();    
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);    
        curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));

        $response = curl_exec($ch);
        $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->last_api_call = $url;
        curl_close($ch);
        return $response;
    }
    public function verify_credentials(Request $request)
    {       
        $url = request('url');
        $header = [
            'Content-type:text/html;charset=utf-8'
        ];
        $url = $url.'?oauth_consumer_key='.request('oauth_consumer_key').'&oauth_nonce='.request('oauth_nonce').'&oauth_signature_method='.request('oauth_signature_method').'&oauth_token='.request('oauth_token').'&oauth_timestamp='.request('oauth_timestamp').'&oauth_version='.request('oauth_version').'&oauth_signature='.urlencode(request('oauth_signature'));
        $post = [];
        $ch = curl_init();    
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);    
        curl_setopt($ch, CURLOPT_POST, 0);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));

        $response = curl_exec($ch);
        $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->last_api_call = $url;
        curl_close($ch);
        return $response;
    }
    
    //~ public function getBraintreeToken() {
        //~ return response()->json([
            //~ 'data' => [
                //~ 'token' => Braintree_ClientToken::generate(["customerId" => "101909146"])
            //~ ]
        //~ ]);
    //~ }
    
    //~ public function braintreeTransaction() {
        //~ $result = Braintree_Transaction::sale([
          //~ 'amount' => '10.00',
          //~ 'paymentMethodNonce' => '28d3b2b1-c36f-0169-6bdb-ba2a9eff2676',
          //~ 'customerId' => '101909146',
          //~ 'options' => [
            //~ 'storeInVaultOnSuccess' => true,
           //~ ]
        //~ ]);
        //~ echo '<pre>'; print_r($result);
        //~ if ($result->success) {
            
          //~ // See $result->transaction for details
        //~ } else {
          //~ // Handle errors
        //~ }
    //~ }
    // get countryCodes
    public function getCountryCodes() {
        $codes = CountyCode::select('phonecode')->distinct()->get();
        if(!empty($codes)) {
            return response()->json(['success' => 'success','data' => $codes], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }
    
    public function getBraintreeToken() {
        $Customer = request('customer');
        $rid = request('id');
        $decryptUserid = decrypt($rid);
        $isNotCustomerID = true;
        if(!empty($decryptUserid)) {
            $customerID = getenv('CUSTOMER_ID').'_01_'.$decryptUserid;
            try {
                $CheckCustomer = Braintree_Customer::find($customerID);
                $customerID =  $CheckCustomer->id;
                $isNotCustomerID = false;
            } catch(Exception $e) {
                $isNotCustomerID = true;
            }
        }
        $usersdata = DB::table('dummy_registration as userdetail')
            ->select('userdetail.name','userdetail.email','userdetail.contactmobile')
            ->where('userdetail.id', '=', (int)$decryptUserid)
            ->first();
        if(!empty($usersdata)) {
            if($isNotCustomerID) {
                $CustomerData = Braintree_Customer::create([ 'id' => $customerID,
                    'company' => $usersdata->name,
                    'email' => $usersdata->email,
                    'phone' => $usersdata->contactmobile
                    ]);
                if($CustomerData->success) {
                    $Customer = $CustomerData->customer->id;
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            }
            $updateArr['customer_id'] = $customerID;
            $updated =  dummy_registration::where('id', '=', (int)$decryptUserid)->update($updateArr);
            return response()->json([
                'token' => Braintree_ClientToken::generate(["customerId" => $customerID]),
            ]);
        } else {
             return response()->json(['error'=>'networkerror'], 401);
        }
        
    }
    
    public function getBraintreeTokenAdmin() {
        $Customer = request('customer');
        $rid = request('id');
        $decryptUserid = (int)$rid;
        $isNotCustomerID = true;
        $usersdata = DB::table('companydetails as cp')
            ->Join('auths as ats', 'ats.id', '=', 'cp.authid')
            ->select('cp.name','ats.email','cp.contactmobile','cp.customer_id')
            ->where('cp.authid', '=', (int)$decryptUserid)
            ->first();
        if(!empty($decryptUserid)) {
            if(!empty($decryptUserid) && $usersdata->customer_id !=null) {
                try {
                    $CheckCustomer = Braintree_Customer::find($usersdata->customer_id);
                    $customerID =  $CheckCustomer->id;
                    $isNotCustomerID = false;
                } catch(Exception $e) {
                    $isNotCustomerID = true;
                    $customerID =  getenv('CUSTOMER_ID').'_02_'.$decryptUserid;
                }
            } else {
                $customerID =  getenv('CUSTOMER_ID').'_02_'.$decryptUserid;
            }
        }
        if(!empty($usersdata)) {
            if($isNotCustomerID) {
                $CustomerData = Braintree_Customer::create([ 'id' => $customerID,
                    'company' => $usersdata->name,
                    'email' => $usersdata->email,
                    'phone' => $usersdata->contactmobile
                    ]);
                if($CustomerData->success) {
                    $Customer = $CustomerData->customer->id;
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            }
            $updateArr['customer_id'] = $customerID;
            $updated =  Companydetail::where('authid', '=', (int)$decryptUserid)->update($updateArr);
            return response()->json([
                'token' => Braintree_ClientToken::generate(["customerId" => $customerID]),
            ]);
        } else {
             return response()->json(['error'=>'networkerror'], 401);
        }
        
    }
    
    public function getBraintreeTokenUser() {
        $Customer = request('customer');
        $rid = request('id');
        $decryptUserid = decrypt($rid);
        $isNotCustomerID = true;
        $usersdata = DB::table('companydetails as cp')
            ->Join('auths as ats', 'ats.id', '=', 'cp.authid')
            ->select('cp.name','ats.email','cp.contactmobile','cp.customer_id')
            ->where('cp.authid', '=', (int)$decryptUserid)
            ->first();
       if(!empty($decryptUserid)) {
            if(!empty($decryptUserid) && $usersdata->customer_id !=null) {
                try {
                    $CheckCustomer = Braintree_Customer::find($usersdata->customer_id);
                    $customerID =  $CheckCustomer->id;
                    $isNotCustomerID = false;
                } catch(Exception $e) {
                    $isNotCustomerID = true;
                    $customerID =  getenv('CUSTOMER_ID').'_02_'.$decryptUserid;
                }
            } else {
                $customerID =  getenv('CUSTOMER_ID').'_02_'.$decryptUserid;
            }
        }
        if(!empty($usersdata)) {
            if($isNotCustomerID) {
                $CustomerData = Braintree_Customer::create([ 'id' => $customerID,
                    'company' => $usersdata->name,
                    'email' => $usersdata->email,
                    'phone' => $usersdata->contactmobile
                    ]);
                if($CustomerData->success) {
                    $Customer = $CustomerData->customer->id;
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            }
            $updateArr['customer_id'] = $customerID;
            $updated =  Companydetail::where('authid', '=', (int)$decryptUserid)->update($updateArr);
            return response()->json([
                'token' => Braintree_ClientToken::generate(["customerId" => $customerID]),
            ]);
        } else {
             return response()->json(['error'=>'networkerror'], 401);
        }
    }
    
    public function braintreeTransaction(Request $request) {
        
        $renewPlan = request('subTyp');
        $isRecur = false;
        if($renewPlan && $renewPlan != 'null' ) {
            $isRecur = true;
        } else {
            $isRecur = false;
        }
        $rid = request('id');
        $decryptUserid = (int)($rid);
        $usersdata =CompanyDetail::where('authid', '=', (int)$decryptUserid)->first();
        $DateNext = $usersdata->nextpaymentdate;
        
        $days = 0;
        $IsDayLeft = false;
        if($usersdata->remaintrial > 0) {
            if($usersdata->account_type == 'paid') {
                if($usersdata->lastpaymentdate == null ) {
                    if($usersdata->nextpaymentdate == null) {
                        $days = 30;
                        $IsDayLeft = true;
                    } else {
                        $CreatedDate = strtotime($usersdata->nextpaymentdate);
                        $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                        $differStrTime = $CreatedDate - $CurrentDates;
                        if($differStrTime > 0) {
                            $day = floor($differStrTime/(24*60*60));
                            //if($day < 30) {
                            $days = $day;
                            $IsDayLeft = true;
                            //}
                        }
                    }
                } else {
                    if($usersdata->plansubtype == 'free') {
                        $days = $usersdata->remaintrial;
                        $IsDayLeft = true;
                    } else {
                        $CreatedDate = strtotime($usersdata->nextpaymentdate);
                        $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                        $differStrTime = $CreatedDate - $CurrentDates;
                        if($differStrTime > 0) {
                            $day = floor($differStrTime/(24*60*60));
                            if($day < 30) {
                                $days = $day;
                                $IsDayLeft = true;
                            }
                        }
                    }
                }
            } else {
                $days = $usersdata->remaintrial;
                $IsDayLeft = true;
            }
        } else {
            $CreatedDate = strtotime($usersdata->nextpaymentdate);
            $CurrentDates = strtotime(date('Y-m-d H:i:s'));
            $differStrTime = $CreatedDate - $CurrentDates;
            if($differStrTime > 0) {
                $day = floor($differStrTime/(24*60*60));
                if($day < 30) {
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
            $remaindiscount = $usersdata->remaindiscount;
            $discountapply = 50;
            
            $amount = (int)request('chargeAmount');
            $amountPaidD = ceil(($amount * $discountapply)/100);
            if($remaindiscount > 0) {
                //$amountPaidD = true;
            } else {
                $amountPaidD = 0;
            }
        }
        //~ $IsDayLeft = false;
        //~ $days = 0;
        //~ if(!empty($DateNext) && $DateNext != null) {
            //~ $CreatedDate = strtotime($usersdata['created_at']);
            //~ $CurrentDates = strtotime("- 30 days", strtotime(date('Y-m-d H:i:s')));
            //~ $differStrTime = $CreatedDate - $CurrentDates;
            //~ if($differStrTime > 0) {
                //~ $days =  (int)(($differStrTime)/(24*3600));
                //~ $IsDayLeft = true;
            //~ } else {
                //~ $IsDayLeft = false;
            //~ }
            
        //~ } else {
            //~ $IsDayLeft = true;
        //~ }
       // if($isRecur) {$result = $gateway->subscription()->cancel('the_subscription_id');
            
            $customer_id = $usersdata->customer_id;
            $payment_method = Braintree_PaymentMethod::create(['paymentMethodNonce'=>request('nonce'),'customerId' => $customer_id]);
            if($payment_method-> success){
                $amount = (int)request('chargeAmount');
                $plan_id ='';
                if($amount == 199) {
                    $plan_id ='plan_basic_monthly';
                } else if($amount == 299) {
                    $plan_id ='plan_advance_monthly';
                } else if ($amount == 399) {
                    $plan_id ='plan_pro_monthly';
                }
                $isPending = true;
                if($isRecur && $IsDayLeft && $days == 0) {
                    if($usersdata->subscription_id != null) {
                        Braintree_Subscription::cancel($usersdata->subscription_id);
                    } 
                    $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id,'discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                } else if ($isRecur && $IsDayLeft && $days > 0) {
                    if($usersdata->subscription_id != null) {
                        Braintree_Subscription::cancel($usersdata->subscription_id);
                    }
                    $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id,'trialPeriod' => true,'trialDuration' => $days,'trialDurationUnit' => 'day','discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                } else if ($isRecur && !$IsDayLeft) {
                    if($usersdata->subscription_id != null) {
                        Braintree_Subscription::cancel($usersdata->subscription_id);
                    }
                    $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id, 'options' => ['startImmediately' => true],'discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                    $isPending = false;
                } else if(!$isRecur && $IsDayLeft && $days == 0) {
                    if($usersdata->subscription_id != null) {
                        Braintree_Subscription::cancel($usersdata->subscription_id);
                    }
                    $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id,'discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                } else if (!$isRecur && $IsDayLeft && $days > 0) {
                    if($usersdata->subscription_id != null) {
                        Braintree_Subscription::cancel($usersdata->subscription_id);
                    }
                    $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id,'trialPeriod' => true,'trialDuration' => $days,'trialDurationUnit' => 'day','discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                } else if (!$isRecur && !$IsDayLeft) {
                    if($usersdata->subscription_id != null) {
                        Braintree_Subscription::cancel($usersdata->subscription_id);
                    }
                    $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id, 'options' => ['startImmediately' => true],'discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                    Braintree_Subscription::cancel($result->subscription->id);
                    $isPending = false;
                }
                $subID = $result->subscription->id;
                $updateArr['subscription_id'] = $subID;
                $updated =  CompanyDetail::where('authid', '=', (int)$decryptUserid)->update($updateArr);
                if ($result->success) {
                    return response()->json(['transaction' => $result->success,'pending'=>$isPending], $this->successStatus);
                  // See $result->transaction for details
                } else {
                    return response()->json(['error'=>$result->errors], 401);
                  //print_r($result->errors);/ Handle errors
                }
                
            } else {
                return response()->json(['error'=>$payment_method], 401);
            }
            
        //~ } else {
            //~ $result = Braintree_Transaction::sale([
              //~ 'amount' => request('chargeAmount'),
              //~ 'paymentMethodNonce' => request('nonce'),
              //~ //'customerId' => 'customer_123',
              //~ 'options' => [
                //~ 'storeInVaultOnSuccess' => true,
               //~ ]
               //~ //, 'recurring' => true
            //~ ]);
            //~ if ($result->success) {
            //~ return response()->json(['transaction' => $result->transaction], $this->successStatus);
              //~ // See $result->transaction for details
            //~ } else {
                //~ return response()->json(['error'=>$result->errors], 401);
              //~ //print_r($result->errors);/ Handle errors
            //~ }
            
        //~ }
        
        
    }
    
    public function braintreeTransactionProfile(Request $request) {
        $rid = request('id');
        $decryptUserid = (int)decrypt($rid);
        $usersdata =CompanyDetail::where('authid', '=', (int)$decryptUserid)->first();
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
        if($usersdata->remaintrial > 0) {
            if($usersdata->account_type == 'paid') {
                if($usersdata->lastpaymentdate == null ) {
                    if($usersdata->nextpaymentdate == null) {
                        $days = 30;
                        $IsDayLeft = true;
                    } else {
                        $CreatedDate = strtotime($usersdata->nextpaymentdate);
                        $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                        $differStrTime = $CreatedDate - $CurrentDates;
                        if($differStrTime > 0) {
                            $day = floor($differStrTime/(24*60*60));
                            //if($day < 30) {
                            $days = $day;
                            $IsDayLeft = true;
                            //}
                        }
                    }
                } else {
                    if($usersdata->plansubtype == 'free') {
                        $days = $usersdata->remaintrial;
                        $IsDayLeft = true;
                    } else {
                        $CreatedDate = strtotime($usersdata->nextpaymentdate);
                        $CurrentDates = strtotime(date('Y-m-d H:i:s'));
                        $differStrTime = $CreatedDate - $CurrentDates;
                        if($differStrTime > 0) {
                            $day = floor($differStrTime/(24*60*60));
                            if($day < 30) {
                                $days = $day;
                                $IsDayLeft = true;
                            }
                        }
                    }
                }
            } else {
                $days = $usersdata->remaintrial;
                $IsDayLeft = true;
            }
        } else {
            $CreatedDate = strtotime($usersdata->nextpaymentdate);
            $CurrentDates = strtotime(date('Y-m-d H:i:s'));
            $differStrTime = $CreatedDate - $CurrentDates;
            if($differStrTime > 0) {
                $day = floor($differStrTime/(24*60*60));
                if($day < 30) {
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
            $remaindiscount = $usersdata->remaindiscount;
            $discountapply = 50;
            
            $amount = (int)request('chargeAmount');
            $amountPaidD = ceil(($amount * $discountapply)/100);
            if($remaindiscount > 0) {
                //$amountPaidD = true;
            } else {
                $amountPaidD = 0;
            }
            //~ $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id,'discounts' => [
                //~ 'add' => [
                    //~ [
                        //~ 'inheritedFromId' => 'default-discount',
                        //~ 'amount' => $amountPaid,
                        //~ 'numberOfBillingCycles' => $remaindiscount,
                        //~ 'quantity' => 1
                    //~ ]
                //~ ]
            //~ ]]);
        }
        
        //~ $days = 0;
        //~ if(!empty($DateNext) && $DateNext != null) {
            //~ $CreatedDate = strtotime($usersdata['created_at']);
            //~ $CurrentDates = strtotime("- 30 days", strtotime(date('Y-m-d H:i:s')));
            //~ $differStrTime = $CreatedDate - $CurrentDates;
            //~ if($differStrTime > 0) {
                //~ $days =  (int)(($differStrTime)/(24*3600));
                //~ $IsDayLeft = true;
            //~ } else {
                //~ $IsDayLeft = false;
            //~ }
            
        //~ } else {
            //~ $IsDayLeft = true;
        //~ }
        //echo $days.'   '.$CreatedDate.'    '.$isRecur;die;
       // if($isRecur) {$result = $gateway->subscription()->cancel('the_subscription_id');
            
            $customer_id = $usersdata->customer_id;
            $payment_method = Braintree_PaymentMethod::create(['paymentMethodNonce'=>request('nonce'),'customerId' => $customer_id]);
            if($payment_method-> success){
                $amount = (int)request('chargeAmount');
                $plan_id ='';
                if($amount == 199) {
                    $plan_id ='plan_basic_monthly';
                } else if($amount == 299) {
                    $plan_id ='plan_advance_monthly';
                } else if ($amount == 399) {
                    $plan_id ='plan_pro_monthly';
                }
                $isPending = true;
                
                if($usersdata->account_type == 'paid') {
                    
                    if($isRecur && $IsDayLeft && $days == 0) {

                        if($usersdata->subscription_id != null) {
                            Braintree_Subscription::cancel($usersdata->subscription_id);
                        } 
                        $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id,'discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                    } else if ($isRecur && $IsDayLeft && $days > 0) {
                        if($usersdata->subscription_id != null) {
                            Braintree_Subscription::cancel($usersdata->subscription_id);
                        } 
                        $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id,'trialPeriod' => true,'trialDuration' => $days,'trialDurationUnit' => 'day','discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                    } else if ($isRecur && !$IsDayLeft) {
                        if($usersdata->subscription_id != null) {
                            Braintree_Subscription::cancel($usersdata->subscription_id);
                        } 
                        $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id, 'options' => ['startImmediately' => true],'discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                        $isPending = false;
                    } else if(!$isRecur && $IsDayLeft && $days == 0) {
                        if($usersdata->subscription_id != null) {
                            Braintree_Subscription::cancel($usersdata->subscription_id);
                        } 
                        $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id,'discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                    } else if (!$isRecur && $IsDayLeft && $days > 0) {
                        if($usersdata->subscription_id != null) {
                            Braintree_Subscription::cancel($usersdata->subscription_id);
                        } 
                        $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id,'trialPeriod' => true,'trialDuration' => $days,'trialDurationUnit' => 'day','discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                    } else if (!$isRecur && !$IsDayLeft) {
                        if($usersdata->subscription_id != null) {
                            Braintree_Subscription::cancel($usersdata->subscription_id);
                        } 
                        $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id, 'options' => ['startImmediately' => true],'discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                        Braintree_Subscription::cancel($result->subscription->id);
                        $isPending = false;
                    }

                } else {
                    if($usersdata->subscription_id != null) {
                        Braintree_Subscription::cancel($usersdata->subscription_id);
                    } 
                    $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id,'trialPeriod' => false,'discounts' => [
                            'add' => [
                                [
                                    'inheritedFromId' => 'default-discount',
                                    'amount' => $amountPaidD,
                                    'numberOfBillingCycles' => $remaindiscount,
                                    'quantity' => 1
                                ]
                            ]
                        ]]);
                }
                $subID = $result->subscription->id;
                $updateArr['subscription_id'] = $subID;
                $updated =  CompanyDetail::where('authid', '=', (int)$decryptUserid)->update($updateArr);
                if ($result->success) {
                    return response()->json(['transaction' => $result->success,'pending'=>$isPending], $this->successStatus);
                  // See $result->transaction for details
                } else {
                    return response()->json(['error'=>$result->errors], 401);
                  //print_r($result->errors);/ Handle errors
                }
                
            } else {
                return response()->json(['error'=>$payment_method], 401);
            }
            
        //~ } else {
            //~ $result = Braintree_Transaction::sale([
              //~ 'amount' => request('chargeAmount'),
              //~ 'paymentMethodNonce' => request('nonce'),
              //~ //'customerId' => 'customer_123',
              //~ 'options' => [
                //~ 'storeInVaultOnSuccess' => true,
               //~ ]
               //~ //, 'recurring' => true
            //~ ]);
            //~ if ($result->success) {
            //~ return response()->json(['transaction' => $result->transaction], $this->successStatus);
              //~ // See $result->transaction for details
            //~ } else {
                //~ return response()->json(['error'=>$result->errors], 401);
              //~ //print_r($result->errors);/ Handle errors
            //~ }
            
        //~ }
        
        
    }
    
    public function braintreeTransactionPlan(Request $request) {
        $rid = request('id');
        $pid = request('pid');
        $decryptUserid = (int)decrypt($rid);
        if(!empty($decryptUserid)) {
            $usersdata =dummy_registration::where('id', '=', (int)$decryptUserid)->first();
            if(!empty($usersdata)) {
                $customer_id = $usersdata->customer_id;
                $payment_method = Braintree_PaymentMethod::create(['paymentMethodNonce'=>request('nonce'),'customerId' => $customer_id]);
                if($payment_method-> success){
                    $amount = (int)request('chargeAmount');
                    $plan_id ='';
                    if($amount == 199) {
                        $plan_id ='plan_basic_monthly';
                    } else if($amount == 299) {
                        $plan_id ='plan_advance_monthly';
                    } else if ($amount == 399) {
                        $plan_id ='plan_pro_monthly';
                    }
                    $PlanData = DB::table('subscriptionplans')->Join('discounts', 'discounts.paymentplan', '=', 'subscriptionplans.id')
                    ->select('subscriptionplans.*','discounts.current_discount')
                    ->where('subscriptionplans.id',(int)$pid)
                    ->where('subscriptionplans.status','=','active')
                    ->get();
                    if(!empty($PlanData) && count($PlanData) > 0) {
                        $date = date('2019-12-31 23:59:59');
                        $current = date('Y-m-d 00:00:00');
                        if($current < $date) {
                            $amountPaid = $PlanData[0]->amount;
                            $discountapply = $PlanData[0]->current_discount;
                            $amountPaid = ceil(($amountPaid * $discountapply)/100);
                            $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id,'discounts' => [
                                'add' => [
                                    [
                                        'inheritedFromId' => 'default-discount',
                                        'amount' => $amountPaid,
                                        'numberOfBillingCycles' => 12,
                                        'quantity' => 1
                                    ]
                                ]
                            ]]);
                        } else {
                            $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id]);
                        }
                        $subID = $result->subscription->id;
                        $updateArr['subscription_id'] = $subID;
                        $updateArr['remaintrial'] = 30;
                        $updated =  dummy_registration::where('id', '=', (int)$decryptUserid)->update($updateArr);
                        if ($result->success) {
                            return response()->json(['transaction' => $result->success], $this->successStatus);
                          // See $result->transaction for details
                        } else {
                            return response()->json(['error'=>$result->errors], 401);
                          //print_r($result->errors);/ Handle errors
                        }
                    } else {
                        return response()->json(['error'=>'network'], 401);
                    }
                } else {
                    return response()->json(['error'=>$payment_method], 401);
                }
            } else {
                return response()->json(['error'=>'network'], 401);
            }
        } else {
            return response()->json(['error'=>'network'], 401);
        }
    }
    
     //Create Customer account in stripe
     public function companypayment(Request $request){
       // need to set plan month
        $validate = Validator::make($request->all(), [
            'tranctionID' => 'required',
            'subplan' => 'required',
            'userID'  => 'required',
            'rtype'       => 'required',
            'card_token' => 'required',
            'nameoncard' => 'required'
        ]);
        if ($validate->fails()) {
            $success = false;
        }
        $rtype = request('rtype');
        if($rtype == 'admin') {
           $userID = request('userID');
        } else {
            $useridencrypt = request('userID');
            $userID = decrypt($useridencrypt);
        }
        $card_token = request('card_token');
        $cardHolder = request('nameoncard');
        $renewPlan = request('renewal');
        if($renewPlan && $renewPlan != 'null' && $renewPlan == 'true'  ) {
            $subType = 'automatic';
        } else {
            $subType = 'manual';
        }
        $paymentStatus = $this->stripeTransactionPlan($userID,request('subplan'),$card_token,$cardHolder,$subType);
        if($paymentStatus['status'] == 'success') {
            $isSocial = false;
            if(empty($userID) || $userID == '') {
                return response()->json(['error'=>'networkerror'], 401); 
            } else {
                if($rtype != 'admin') {
                    $checkEmailExist =  dummy_registration::where('id', '=', (int)$userID)->where('usertype', '=', 'company')->first();
                    $checkEmailAddressExist = $checkEmailExist->email;
                    if(!empty($checkEmailExist)) {
                        if($checkEmailExist->is_social == '0') {
                            $queryChecks = Auth::where('email', '=', $checkEmailAddressExist);
                            $countChecks = $queryChecks->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
                            $query2Checks = dummy_registration::where('email', '=', $checkEmailAddressExist);
                            $count2Checks = $query2Checks->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
                            $countChecks = $countChecks + $count2Checks;
                            if(!empty($countChecks) && $countChecks > 0) {
                                 return response()->json(['error'=>'emailExist'], 401);
                            }
                        }
                    } else {
                         return response()->json(['error'=>'networkerror'], 401);
                    }
                }
            }
            /* Get user card Token and Plan*/
            $subplan = request('subplan');
            $userDetail = dummy_registration::where('id', '=', (int)$userID)->get()->first()->toArray();
            if(!empty($userDetail) && $userDetail['is_claim_user'] == '1') {
                $isDummyUser = true;
            } else {
                $isDummyUser = false;
            }
            $email = $userDetail['email'];
            
            $ex_message = '';
            $plandata = DB::table('subscriptionplans')->leftJoin('discounts', 'discounts.paymentplan', '=', 'subscriptionplans.id')->select('subscriptionplans.*','discounts.current_discount')->where('subscriptionplans.id', '=', (int)$subplan)->where('subscriptionplans.status', '=', 'active')->first();
            if(!empty($plandata)) {
                $basicTrialDays = 0;
                /* Old Payment
                if (strpos('Basic', $plandata->planname) !== false) {
                    $currentDate = date('Y-m-d 00:00:00');
                    if(env('BASIC_PLAN_UNLIMITED_END') > $currentDate) {
                        $basicTrialDays = 60;
                    }
                }*/
        
                $planPrice = $plandata->amount;
                $planType = $plandata->plantype;
                $planAccessType = $plandata->planaccesstype;
                $planAccessNumber = $plandata->planaccessnumber;
                if($planType =='paid') { 
                    if($planAccessType == 'month') {
                        /* Old Payment
                        if($basicTrialDays > 0) {
                            $nextDate1 = date('Y-m-d 00:00:00', strtotime("+ ".$basicTrialDays." days", strtotime(date('Y-m-d H:i:s'))));
                            $nextDate = date('Y-m-d 00:00:00', strtotime("+ ".$basicTrialDays." days", strtotime(date('Y-m-d H:i:s'))));
                        } else {
                            $nextDate1 = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                            $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                        }
                        */
                        $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                    } else if($planAccessType == 'unlimited'){
                        $nextDate = '2099-01-01 00:00:00';
                    } else if($planAccessType == 'year'){
                        $nextDate = date('Y-m-d 00:00:00', strtotime("+ 365 days", strtotime(date('Y-m-d H:i:s'))));
                    }
                } else {
                    if($planAccessType == 'unlimited'){
                        $nextDate = '2099-01-01 00:00:00';
                    } else {
                        $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                    }
                    //Add Free Plan
                    return response()->json(['success' => true,'nextdate'=> $nextDate], $this->successStatus);
                }            
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            $statusStep = false;
            if($isDummyUser) {
                /* Old Payment
                if($basicTrialDays > 0) {
                    $trialPeriod = $basicTrialDays;
                } else {
                    $trialPeriod = 30;
                }
                */
                $trialPeriod = 0;
                $statusCompany = dummy_registration::where('id', (int)$userID)->update(['subscriptiontype' => $subType,'nextpaymentdate' => $nextDate, 'paymentplan' => (int)$subplan,'plansubtype' => 'paid','stepscompleted' => '3','remaintrial' => $trialPeriod,'status' => 'active']);
            } else {
                $statusCompany = dummy_registration::where('id', (int)$userID)->update(['subscriptiontype' => $subType,'nextpaymentdate' => $nextDate, 'paymentplan' => (int)$subplan,'plansubtype' => 'paid','stepscompleted' => '3']);

            }
            
            //insert temp record to actual tables
            $tempData = dummy_registration::where('id',(int)$userID)->first();
            if(!$isDummyUser) {
               $authData = new Auth;
                $authData->email = $tempData->email;
                $authData->password = $tempData->password;
                $authData->usertype = $tempData->usertype;
                $authData->ipaddress = $tempData->ipaddress;
                $authData->stepscompleted = $tempData->stepscompleted;
                if($tempData->is_social == '1') {
                    $authData->is_activated = '1';
                } else {
                    $authData->is_activated = '0';
                }
                $authData->is_social = $tempData->is_social;
                $authData->social_id = $tempData->social_id;
                $authData->provider  = $tempData->provider;
                $authData->newsletter  = $tempData->newsletter;
                $authData->status = 'active';
                if($authData->save()) {
                    $authid = $authData->id;
                    $companyData = new Companydetail;
                    $company_slug_new= preg_replace('/[^a-zA-Z0-9_ -]/s','',$tempData->name);     
                    $slug = implode("-",explode(" ",$company_slug_new));
                    $slug1 = '';
                    $array = explode(" ",$tempData->city);
                    if(is_array($array)) {
                        $slug1 = implode("-",$array);       
                    }
                    $slug = strtolower($slug.'-'.$slug1);
                    $realSlug = $slug;    
                    $companyData->authid = $authid;
                    $companyData->name = $tempData->name;
                    // Calculate slug
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
                    $companyData->slug = $slug;
                    $companyData->actualslug = $realSlug;
                    $companyData->services = $tempData->services;
                    $companyData->address = $tempData->address;
                    $companyData->city = $tempData->city;
                    $companyData->state = $tempData->state;
                    $companyData->country = $tempData->country;
                    $companyData->zipcode = $tempData->zipcode;
                    $companyData->contact = $tempData->contact;
                    $companyData->about = $tempData->about;
                    $companyData->businessemail = $tempData->businessemail;
                    $companyData->websiteurl = $tempData->websiteurl;
                    $companyData->images = $tempData->images;
                    $companyData->longitude = $tempData->longitude;
                    $companyData->latitude = $tempData->latitude;
                    $companyData->nextpaymentdate = $tempData->nextpaymentdate;
                    $companyData->customer_id = $tempData->customer_id;
                    $companyData->subscription_id = $tempData->subscription_id;
                    $companyData->paymentplan = $tempData->paymentplan;
                    $companyData->next_paymentplan = $tempData->paymentplan;
                    $companyData->plansubtype = $tempData->plansubtype;
                    $companyData->subscriptiontype = $tempData->subscriptiontype;
                    $companyData->advertisebusiness = '0';
                    $companyData->primaryimage = $tempData->primaryimage;
                    $companyData->allservices = $tempData->allservices;
                    $companyData->contactname = $tempData->contactname;
                    $companyData->contactmobile = $tempData->contactmobile;
                    $companyData->contactemail = $tempData->contactemail;
                    $companyData->status = 'active';
                    $companyData->coverphoto = $tempData->coverphoto;
                    $companyData->boats_yachts_worked    = $tempData->boats_yachts_worked;
                    $companyData->engines_worked    = $tempData->engines_worked;
                    /*Old Payment
                    if($basicTrialDays > 0) {
                        $companyData->remaintrial    = $basicTrialDays;
                    }
                    */
                    $companyData->remaintrial    = 0;
                    if($tempData->is_claim_user == '1') {
                        $companyData->is_admin_approve = '0';
                        $companyData->is_claimed = '1';
                        $companyData->accounttype = 'claimed';   
                    } else {
                        $companyData->is_admin_approve = '1';
                        $companyData->is_claimed = '0';
                        $companyData->accounttype = 'real';
                    }
                    /*Old Payment
                    $dateDiscountCheck = date('2019-12-31 23:59:59');
                    $currentDiscountCheck = date('Y-m-d 00:00:00');
                    if($currentDiscountCheck < $dateDiscountCheck) {
                        $companyData->is_discount = '1';
                        $companyData->remaindiscount = 12;
                        $companyData->discount = (!empty($plandata->current_discount) ? $plandata->current_discount : 0);
                    }
                    */
                    $companyData->is_discount = '0';
                    $companyData->remaindiscount = 0;
                    $companyData->discount = 0;
                     
                    if($companyData->save()) {
                        $DictionaryData = new Dictionary;
                        $DictionaryData->authid = $authid;
                        $DictionaryData->word = $tempData->name;
                        if($DictionaryData->save()) {
                        }
                        $statusStep = true;  
                        $rejectedRegistration = dummy_registration::where('id', '=', (int)$userID)->delete();
                        $geosuccess = TRUE;
                        if($tempData->is_social == '0' ) {
                            $random_hashed = Hash::make(str_random(8).$authid);
                            $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                            $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                            $link = $website_url.'/activate?token='.urlencode($random_hashed);
                            $ACTIVATION_LINK = $link;
                            $emailArr = [];                                        
                            $emailArr['link'] = $ACTIVATION_LINK;

                            $emailArr['to_email'] = $tempData->email;
                            $emailArr['name'] = $tempData->name;
                            //Send activation email notification
                            if($tempData->is_claim_user == '1') {
                                $status = $this->sendEmailNotification($emailArr,'claimed_business_notification');
                            } else {
                                $status = $this->sendEmailNotification($emailArr,'business_registration_activation'); 
                            }
                            if($status != 'sent') {
                                return response()->json(['error'=>'emailsentfail'], 401);
                            }
                            $adminEmailArr = array();
                            $adminEmailArr['userEmail'] = $tempData->email;
                            $adminEmailArr['userType'] = 'Company';
                            $adminEmailArr['userFirstname'] = $tempData->name;
                            $adminEmailArr['to_email'] = env("Admin_Email");
                            //Send activation email notification
                            SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                            $adminEmailArr['to_email'] = env("Info_Email");
                            SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                        } else {
                            $isSocial = true;
                        }
                    } else {
                        return response()->json(['error'=>'entryfail'], 401);
                    }
                } else {
                    return response()->json(['error'=>'entryfail'], 401);
                } 
            } else {
                $dummyCompanyID = 0;
                DB::beginTransaction();  
                $usersdata = dummy_registration::where('id', '=', (int)$userID) //Get Dummy Company data from Dummy registration
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
                        if(!empty($dummyCompanydata)) {                     //Save Old dummy Data to a backup Table
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
                                $authData = Auth::find($dummyCompanyID); //Update Dummy company data in Auth with dummy registration data 
                                $authData->email = $usersdata->email;
                                $authData->password = $usersdata->password;
                                $authData->usertype = $usersdata->usertype;   
                                $authData->ipaddress = $usersdata->ipaddress;             
                                $authData->stepscompleted = $usersdata->stepscompleted;
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
                                if($authData->save()) { //Updated Auth Table 
                                    $IsCompanyData = Companydetail::where('authid', '=',$dummyCompanyID)->first();
                                    if(!empty($IsCompanyData)) { //Update Dummy company data in Auth with dummy registration data
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
                                        /*Old Payment
                                        $companyData->remaintrial = $usersdata->remaintrial;
                                        */
                                        $companyData->remaintrial = 0;
                                        $companyData->is_claimed = '1';
                                        $companyData->boats_yachts_worked    = $usersdata->boats_yachts_worked;
                                        $companyData->engines_worked    = $usersdata->engines_worked;
                                        /*Old Payment
                                        $dateDiscountCheck = date('2019-12-31 23:59:59');
                                        $currentDiscountCheck = date('Y-m-d 00:00:00');
                                        if($currentDiscountCheck < $dateDiscountCheck) {
                                            $companyData->is_discount = '1';
                                            $companyData->remaindiscount = 12;
                                            $companyData->discount = 50;
                                        }*/
                                        $companyData->is_discount = '0';
                                        $companyData->remaindiscount = 0;
                                        $companyData->discount = 0;
                                        if($companyData->save()) {
                                            $statusStep = TRUE;
                                            $rejectedRegistration = dummy_registration::where('authid', '=', $dummyCompanyID)->where('is_claim_user', '=', '1')->where('status', '=', 'active')->delete();
                                            DB::commit();
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

                $geosuccess = TRUE;
                $statusCompany = TRUE;
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
                 
                //Send activation email notification 
                if($tempData->is_social == '0' ) {
                    $emailArr['to_email'] = $tempData->email;
                } else {
                    $emailArr['to_email'] = $tempData->contactemail;
                }
                if($isSocial) {
                    $status = $this->sendEmailNotification($emailArr,'approve_claimbusiness_social');
                } else {
                    $status = $this->sendEmailNotification($emailArr,'approve_claimbusiness');
                }
                if($tempData->is_social == '1') {
                    $isSocial = true;
                }
                if($status != 'sent') {
                    DB::rollBack();
                    return response()->json(['error'=>'emailsentfail'], 401);
                } 
                $adminEmailArr = array();
                $adminEmailArr['userEmail'] = $tempData->email;
                $adminEmailArr['userType'] = 'Company';
                $adminEmailArr['userFirstname'] = $claimedName;
                $adminEmailArr['to_email'] = env("Admin_Email");
                //Send activation email notification
                SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                $adminEmailArr['to_email'] = env("Info_Email");
                SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
            }

            if($statusStep && $statusCompany && $geosuccess) {
                if($isDummyUser) {
                    // $paymentTable = 'dummy_paymenthistory';
                    $paymentTable = 'paymenthistory';
                    $authid = $dummyCompanyID;
                } else {
                    $paymentTable = 'paymenthistory';
                }
                $status = 'pending';
                $transactionid = request('tranctionID');
                if($subType == 'manual') {
                    $transactionid = $paymentStatus['chargeTrs'];
                    $status = 'approved';
                    $statusPayment =  DB::table($paymentTable)->insert(
                            ['companyid' => (int)$authid,
                            'transactionid' => $transactionid,
                            'transactionfor' => 'registrationfee',
                            'amount' => $planPrice,
                            'payment_type' =>$plandata->id,
                            'status' => $status ,
                            'customer_id' => $userDetail['customer_id'],
                            'subscription_id' => $userDetail['subscription_id'],
                            'expiredate' => $nextDate,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                            ]);
                } else {
                    //Check if webhook already inserted code
                    $paymentHistoryData = DB::table('paymenthistory')->where('subscription_id','=',$userDetail['subscription_id'])->where('transactionfor','registrationfee')->orderBy('created_at','DESC')->get();
                    if(!empty($paymentHistoryData) && count($paymentHistoryData) > 0 && $paymentHistoryData[0]->status == 'approved') {
                        $statusPayment = true;
                    } else {
                        $statusPayment =  DB::table($paymentTable)->insert(
                            ['companyid' => (int)$authid,
                            'transactionid' => $transactionid,
                            'transactionfor' => 'registrationfee',
                            'amount' => $planPrice,
                            'payment_type' =>$plandata->id,
                            'status' => $status ,
                            'customer_id' => $userDetail['customer_id'],
                            'subscription_id' => $userDetail['subscription_id'],
                            'expiredate' => $nextDate,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                            ]); 
                    }
                }
                

                if($statusPayment) {
                    //Trigger Zap Business
                    $zaiperenv = env('ZAIPER_ENV','local');
                    if($zaiperenv == 'live') {
                        $zapierData = array();
                        $zapierData['type']     = 'Business';
                        $zapierData['id']   = $authid;
                        $zapierData['email']    = $tempData->email;
                        $zapierData['businessemail'] = $tempData->businessemail;
                        $zapierData['name']     = $tempData->name;
                        $zapierData['contact']  = $tempData->contact;
                        $zapierData['address']  = $tempData->address;
                        $zapierData['city']     = $tempData->city;
                        $zapierData['state']    = $tempData->state;
                        $zapierData['country']  = $tempData->country;
                        $zapierData['zipcode']  = $tempData->zipcode;
                        $zapierData['plan']  = $plandata->planname;
                        $zapierData['subscriptiontype'] = $tempData->subscriptiontype;
                        $zapierData['contactmobile'] = $tempData->country_code.$tempData->contactmobile;
                        $zapierData['contactemail'] = $tempData->contactemail;
                        $zapierData['contactname'] = $tempData->contactname;
                        $zapierData['newsletter'] = $tempData->newsletter;
                        $zapierData['website'] = $tempData->websiteurl;
                        $zapierData['about'] = $tempData->about;
                        $this->sendAccountCreateZapierBiz($zapierData);
                    }
                    if($tempData->is_social == '1'  && $tempData->is_claim_user == '0') {
                        $userdata = Auth::where('id', '=', (int)$authid)->get();
                        $user = $userdata[0];
                        $success['type']    = $userdata[0]->usertype;
                        $success['authid']  = encrypt($userdata[0]->id);
                        $success['email']   = $userdata[0]->email;
                        $success['stepscompleted'] = $userdata[0]->stepscompleted;
                        $success['token']   =  $user->createToken('MyApp')->accessToken;
                        return response()->json(['success' => true,'isSocial'=>true,'userid' => request('id'),'nextdate'=> $nextDate,'steps' => '3','data' => $success], $this->successStatus);
                    } else {
                        return response()->json(['success' => true,'userid' => encrypt($authid),'nextdate'=> $nextDate,'isSocial' => $isSocial,'authid' => encrypt($authid)], $this->successStatus);
                    }
                } else {
                    return response()->json(['error'=>'entryfail'], 401);
                }
            } else {
                return response()->json(['error'=>'entryfail'], 401);
            }
        } else {
            return response()->json(['success' => false,'error'=>$paymentStatus], $this->successStatus);
        }
    }
    
    public function braintreeTransactionLead(Request $request) {
        $result = Braintree_Transaction::sale([
          'amount' => request('chargeAmount'),
          'paymentMethodNonce' => request('nonce'),
          'options' => [
            'storeInVaultOnSuccess' => true,
           ]
           //, 'recurring' => true
        ]);
        if ($result->success) {
            //echo "<pre>";print_r($result->transaction);die;
            $rid = request('id');
            $decryptUserid = decrypt($rid);
            $CompanyDetail = Companydetail::where('authid', '=', (int)$decryptUserid)->get()->first();
            $statusCompany = Companydetail::where('authid', (int)$decryptUserid)->update(['lead_payment' => $CompanyDetail->lead_payment+1]);
            $statusPayment =  DB::table('paymenthistory')->insert(
            ['companyid' => (int)$decryptUserid,
            'transactionid' => $result->transaction->id,
            'transactionfor' => 'leadfee',
            'amount' => request('chargeAmount'),
            'status' => 'approved' ,
            'customer_id' => $result->transaction->customer['id'],
            'expiredate' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
            ]);
            return response()->json(['transaction' => $result->transaction], $this->successStatus);
        } else {
            return response()->json(['error'=>$result->errors], 401);
          //print_r($result->errors);/ Handle errors
        }
    }
    
    //Get Plan Geolocation details//
    public function getlocationcount(Request $request) {
        $encryptId = request('id');
        $id = decrypt($encryptId);
        $isClaim = request('isClaim');
        if(!empty($id) && (int)$id) {
            if(!(empty($isClaim)) && $isClaim == 'true') {
                $planData = DB::table('dummy_registration')
                ->select('subscriptionplans.geolocationaccess','subscriptionplans.plantype')
                ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'dummy_registration.paymentplan')
                ->where('dummy_registration.id', '=', $id)
                ->first();
               
            } else {
                $planData = DB::table('companydetails')
                ->select('subscriptionplans.planname','subscriptionplans.geolocationaccess','subscriptionplans.plantype','companydetails.is_discount','paymenthistory.created_at')
                ->Join('subscriptionplans', 'subscriptionplans.id', '=', 'companydetails.paymentplan')
                ->Join('paymenthistory', 'paymenthistory.companyid', '=', 'companydetails.authid')
                ->where('companydetails.authid', '=', $id)
                ->where('paymenthistory.transactionfor','registrationfee')
                ->orderBy('paymenthistory.id','DESC')
                ->first();
            }
           
            if($planData) {
                if(!(empty($isClaim)) && $isClaim == 'true') {
                } else {
                    $currentDate = date('Y-m-d 00:00:00');
                    if(env('BASIC_UNLIMITED_GEO_LOC') == 'YES') {
                        if ((strpos('Basic', $planData->planname) !== false) && (($planData->created_at < env('BASIC_UNLIMITED_ACCESS_END')) || ($planData->is_discount == '1'))) {
                            $planData->geolocationaccess = 9999;
                        } 
                    }
                }
                return response()->json(['success' => true,'data' => $planData], $this->successStatus);
            } else {
                return response()->json(['success' => false,'data' => []], $this->successStatus);    
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    } 
    
    //Get Plan Geolocation details//
    public function skipandComplete(Request $request) {
        $encryptId = request('rid');
        $id = decrypt($encryptId);
        $isClaim = request('isClaim');
        if(!empty($id) && (int)$id) {
            if(!(empty($isClaim)) && $isClaim == 'true') {
                $tempData = DB::table('dummy_registration')
                ->where('id', '=', $id)
                ->first();
               
            } else {
                $tempData = DB::table('companydetails')
                ->select('companydetails.is_claimed','auths.is_social')
                ->Join('auths', 'auths.id', '=', 'companydetails.authid')
                ->where('companydetails.authid', '=', $id)
                ->first();
            }
            $isSocial = false;
            $claimed = false;
            if($tempData) {
                 if($tempData->is_social == '1') {
                     $isSocial = true;
                 }
                 if($tempData->is_claimed == '1') {
                     $claimed = true;
                 }
                 if($tempData->is_social == '1' && $tempData->is_claimed == '0') {
                    $userdata = Auth::where('id', '=', (int)$authid)->get();
                    $user = $userdata[0];
                    $success['type']    = $userdata[0]->usertype;
                    $success['authid']  = encrypt($userdata[0]->id);
                    $success['email']   = $userdata[0]->email;
                    $success['stepscompleted'] = $userdata[0]->stepscompleted;
                    $success['token']   =  $user->createToken('MyApp')->accessToken;
                    return response()->json(['success' => true,'isSocial'=>true,$claimed => false,'userid' => request('id'),'nextdate'=> $nextDate,'steps' => '3','data' => $success], $this->successStatus);
                } else {
                    return response()->json(['success' => true,'isclaimed'=>$claimed,'isSocial' => $isSocial], $this->successStatus);
                }
            } else {
                return response()->json(['success' => false,'data' => []], $this->successStatus);    
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    } 
    
    //Get Plan Geolocation details//
    public function getLocationInfo(Request $request) {
        $encryptId = request('id');
        $id = decrypt($encryptId);
        if(!empty($id) && (int)$id) {
            $planDataData = Geolocation::where('authid',$id)->where('status','1')->get();
            return response()->json(['success' => true,'data' => $planDataData], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }
    
    // check email exist //
    public function checkemailexisteditemail(Request $request) {
        $userEmail = strtolower(request('email'));
        $encryptId = request('id');
        $id = decrypt($encryptId);
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
    /* OTP CODE
    public function activateEmailOtp(Request $request) {
        $otp = request('otp');
        if(!empty($otp)) {
            $checkToken = Auth::where('email_hash',$otp)->where('requested_email','!=',NULL)->first();
            if(!empty($checkToken) && isset($checkToken->id)) { 
                $authid = $checkToken->id;
                $emailChange = strtolower($checkToken->requested_email);
                $updateStatus = Auth::where('id',$authid)->update(['requested_email' =>NULL,'email' => $emailChange,'email_hash' => NULL]);
                if(!empty($updateStatus)) {
                    $zaiperenv = env('ZAIPER_ENV','local');
                    if($zaiperenv == 'live') {
                        $this->sendAccountCreateZapierbyID($authid);
                    }
                    return response()->json(['success' => true], $this->successStatus);    
                } else {
                    return response()->json(['error'=>'There is some error while verifing OTP.'], 401);    
                }
            } else {
                return response()->json(['error'=>'OTP entered is incorrect.'], 401);    
            }
        } else {
            return response()->json(['error'=>'There is some error while verifing OTP.'], 401);
        }
    }
    */

    public function activateEmail(Request $request) {
        $token = request('token');
        if(!empty($token)) {
            $checkToken = Auth::where('email_hash',urldecode($token))->where('requested_email','!=',NULL)->first();
            if(!empty($checkToken) && isset($checkToken->id)) { 
                $authid = $checkToken->id;
                $emailChange = strtolower($checkToken->requested_email);
                $updateStatus = Auth::where('id',$authid)->update(['requested_email' =>NULL,'email' => $emailChange,'email_hash' => NULL]);
                if(!empty($updateStatus)) {
                    $zaiperenv = env('ZAIPER_ENV','local');
                    if($zaiperenv == 'live') {
                        if(isset($checkToken->usertype) && $checkToken->usertype == 'company') {
                            $this->companyCreateZapierbyID($authid);
                        } else {
                            $this->sendAccountCreateZapierbyID($authid);
                        }
                    }
                    return response()->json(['success' => true], $this->successStatus);    
                } else {
                    return response()->json(['error'=>'networkerror1'], 401);    
                }
            } else {
                return response()->json(['error'=>'networkerror2'], 401);    
            }
        } else {
            return response()->json(['error'=>'networkerror3'], 401);
        }
    }
    
    function country_to_continent( $country ){
        $continent = 'Other';
        if( $country == 'AX' ) $continent = 'Europe';
        if( $country == 'AL' ) $continent = 'Europe';
        if( $country == 'AD' ) $continent = 'Europe';
        if( $country == 'AT' ) $continent = 'Europe';
        if( $country == 'BY' ) $continent = 'Europe';
        if( $country == 'BE' ) $continent = 'Europe';
        if( $country == 'BA' ) $continent = 'Europe';
        if( $country == 'BG' ) $continent = 'Europe';
        if( $country == 'HR' ) $continent = 'Europe';
        if( $country == 'CZ' ) $continent = 'Europe';
        if( $country == 'DK' ) $continent = 'Europe';
        if( $country == 'EE' ) $continent = 'Europe';
        if( $country == 'FO' ) $continent = 'Europe';
        if( $country == 'FI' ) $continent = 'Europe';
        if( $country == 'FR' ) $continent = 'Europe';
        if( $country == 'DE' ) $continent = 'Europe';
        if( $country == 'GI' ) $continent = 'Europe';
        if( $country == 'GR' ) $continent = 'Europe';
        if( $country == 'GG' ) $continent = 'Europe';
        if( $country == 'VA' ) $continent = 'Europe';
        if( $country == 'HU' ) $continent = 'Europe';
        if( $country == 'IS' ) $continent = 'Europe';
        if( $country == 'IE' ) $continent = 'Europe';
        if( $country == 'IM' ) $continent = 'Europe';
        if( $country == 'IT' ) $continent = 'Europe';
        if( $country == 'JE' ) $continent = 'Europe';
        if( $country == 'LV' ) $continent = 'Europe';
        if( $country == 'LI' ) $continent = 'Europe';
        if( $country == 'LT' ) $continent = 'Europe';
        if( $country == 'LU' ) $continent = 'Europe';
        if( $country == 'MK' ) $continent = 'Europe';
        if( $country == 'MT' ) $continent = 'Europe';
        if( $country == 'MD' ) $continent = 'Europe';
        if( $country == 'MC' ) $continent = 'Europe';
        if( $country == 'ME' ) $continent = 'Europe';
        if( $country == 'NL' ) $continent = 'Europe';
        if( $country == 'NO' ) $continent = 'Europe';
        if( $country == 'PL' ) $continent = 'Europe';
        if( $country == 'PT' ) $continent = 'Europe';
        if( $country == 'RO' ) $continent = 'Europe';
        if( $country == 'RU' ) $continent = 'Europe';
        if( $country == 'SM' ) $continent = 'Europe';
        if( $country == 'RS' ) $continent = 'Europe';
        if( $country == 'SK' ) $continent = 'Europe';
        if( $country == 'SI' ) $continent = 'Europe';
        if( $country == 'ES' ) $continent = 'Europe';
        if( $country == 'SJ' ) $continent = 'Europe';
        if( $country == 'SE' ) $continent = 'Europe';
        if( $country == 'CH' ) $continent = 'Europe';
        if( $country == 'UA' ) $continent = 'Europe';
        if( $country == 'GB' ) $continent = 'Europe';
        return $continent;
    }
    
    public function getIpInfo($ip){
        $output =  app('geocoder')->geocode($ip)->dump('geojson');
        if(!empty($output)) {
            if(count($output->toArray()) > 0) {
                $arrayCord = json_decode($output->toArray()[0]);
                if(!empty($arrayCord)) {
                    if(isset($arrayCord->properties->countryCode)) {
                        $countryCode = $arrayCord->properties->countryCode; 
                        if($this->country_to_continent($countryCode) == 'Europe') {
                            return 'EU';
                        } else {
                            return 'not_europe';
                        }
                    } else {
                        return 'not_europe';
                    }
                } else {
                    return 'not_europe';
                }
            } else {
                return 'not_europe';
            }
        } else {
            return 'not_europe';
        }
    }
    
    public function geocontinent(Request $request) {
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $getIpAddress = explode(",",$_SERVER['HTTP_X_FORWARDED_FOR']);
            $clientIp_address = $getIpAddress[0];
        } else {
            $clientIp_address = $_SERVER['REMOTE_ADDR'];
        }
        //~ $ip = $request->ip();
        $continent = $this->getIpInfo($clientIp_address);
        return response()->json(['success' => true,'data' => $continent ], $this->successStatus);
        //~ $geoip = new GeoIPLocation();
        //~ echo $_SERVER['REMOTE_ADDR'];
        //$geoip->setIP(request->ip());
        // return response()->json(['data' => $geoip->getContinentCode()], $this->successStatus); 
    }
    
    public function sendSubscriptionEndAlert() {
            $currentDate = date('Y-m-d H:i:s',strtotime("+ 8 days", strtotime(date('Y-m-d H:i:s'))));
        $CurrentDatestr = strtotime("- 1 days",strtotime(date('Y-m-d H:i:s')));
        //~ $getCompanyData = Companydetail::select('authid','name','nextpaymentdate','contactemail','contactmobile','subscription_reminder')->where('status','active')->where('subscriptiontype','manual')->where('accounttype','real')->where('nextpaymentdate','<',$currentDate)->get();
        $getCompanyData = DB::table('companydetails as cmp')
            ->leftJoin('subscriptionplans as subp', 'subp.id', '=', 'cmp.paymentplan')
            ->select('cmp.authid','cmp.name','cmp.nextpaymentdate','cmp.contactemail','cmp.contactmobile','cmp.subscription_reminder','cmp.account_type','subp.amount as planamount','cmp.paymentplan','cmp.remaintrial','cmp.next_paymentplan','cmp.free_subscription_end')
            ->where('cmp.status','active')->where('cmp.subscriptiontype','manual')->where('cmp.accounttype','real')->
            where(function ($query) use ($currentDate) {
                $query->where(function ($query1) use ($currentDate) {
                    $query1->where('cmp.account_type','paid')->where('cmp.nextpaymentdate','<',$currentDate);
                })
                ->orwhere(function ($query2) use ($currentDate) {
                    $query2->where('cmp.account_type','free')->where('cmp.free_subscription_end','<',$currentDate);
                });
            })->get();
        
        
        
        if(!empty($getCompanyData) && count($getCompanyData) > 0) {
            foreach($getCompanyData as $getCompanyDatas) {
                if(($getCompanyDatas->account_type == 'free') ||($getCompanyDatas->account_type == 'paid' && ($getCompanyDatas->paymentplan == $getCompanyDatas->next_paymentplan && (($getCompanyDatas->planamount > 0 && $getCompanyDatas->remaintrial == 0 ) ||($getCompanyDatas->planamount < 1) )) )) {
                    if($getCompanyDatas->account_type == 'free') {
                        $companyExpireDate = strtotime($getCompanyDatas->free_subscription_end);
                    } else {
                        $companyExpireDate = strtotime($getCompanyDatas->nextpaymentdate);
                    }
                    $difference = $companyExpireDate - $CurrentDatestr;
                    $days = $difference / (24*60*60);
                    $updateReminder = false;
                    $updateArr = [];
                    if($days >= 7 && $days < 8) {
                        $updateArr['subscription_reminder'] = '1';
                        if($getCompanyDatas->subscription_reminder == '0') {
                            $dayRem = '7 days';
                            $status = $this->sendreminderEmail($getCompanyDatas,$dayRem);
                            if($status == 'sent') {
                                $updated =  Companydetail::where('authid', '=', (int)$getCompanyDatas->authid)->update($updateArr);
                            }
                        }
                    } else if($days >= 3 && $days < 4) {
                        $updateArr['subscription_reminder'] = '1';
                        if($getCompanyDatas->subscription_reminder == '0') {
                            $dayRem = '3 days';
                            $status = $this->sendreminderEmail($getCompanyDatas,$dayRem);
                            if($status == 'sent') {
                                $updated =  Companydetail::where('authid', '=', (int)$getCompanyDatas->authid)->update($updateArr);
                            }
                        }
                    } else if($days >= 1 && $days < 2) {
                        $updateArr['subscription_reminder'] = '1';
                        if($getCompanyDatas->subscription_reminder == '0') {
                            $dayRem = '1 day';
                            $status = $this->sendreminderEmail($getCompanyDatas,$dayRem);
                            if($status == 'sent') {
                                $updated =  Companydetail::where('authid', '=', (int)$getCompanyDatas->authid)->update($updateArr);
                            }
                        }
                    } else {
                        $updateArr['subscription_reminder'] = '0';
                        $updated =  Companydetail::where('authid', '=', (int)$getCompanyDatas->authid)->update($updateArr);
                    }
                    
                }
            }
        }
        $updateOtherArr['subscription_reminder'] = '0';
        $updatedOther =  Companydetail::where('status','active')->where('nextpaymentdate','>=',$currentDate)->update($updateOtherArr);
    }
    
    public function sendreminderEmail($userInfo,$dayRem) {
        $emailArr['paymentdate'] = date('m-d-Y',strtotime($userInfo->nextpaymentdate));
        $emailArr['name'] = $userInfo->name;
        $emailArr['remain'] = $dayRem;
        $emailArr['to_email'] = $userInfo->contactemail;
        $website_url = env('NG_APP_URL','https://www.marinecentral.com');
        $link = $website_url.'/login';
        $emailArr['link'] = $link;
        $status = $this->sendEmailNotification($emailArr,'subscription_reminder');
        return $status;
    } 
    
    public function sendUnreadMessageAlert() {
        $currentDate6hour = date('Y-m-d H:i:s',strtotime("- 6 hours", strtotime(date('Y-m-d H:i:s'))));
        $currentDate24hour = date('Y-m-d H:i:s',strtotime("- 24 hours", strtotime(date('Y-m-d H:i:s'))));
        $currentDate = strtotime(date('Y-m-d H:i:s'));
        $messageData = DB::select("SELECT DISTINCT ON (message_id) * FROM (select msgmain.id,msgmain.parent_id, msgmain.message_to,msgmain.message_type,msgmain.message_from,unionSub2.firstname as to_firstname, unionSub2.lastname as to_lastname ,COALESCE(unionSub2.contactemail,authsdata.authemail,NULL) as email,COALESCE(unionSub1.firstname,msgmain.quote_name,NULL) as from_firstname, unionSub1.lastname as from_lastname,msgmain.request_id,msgmain.message_type,msgmain.first_alert,msgmain.second_alert,msgmain.created_at,msgmain.message_id,msgmain.is_read,msgmain.to_usertype,msgmain.from_usertype
            from messages as msgmain
            left join (
                (select authid, firstname, lastname from userdetails)
                union (select authid, firstname, lastname from yachtdetail)
                union (select authid, firstname, lastname from talentdetails)
                union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname from companydetails)
            ) unionSub1 on unionSub1.authid = msgmain.message_from
            left join (
                (select authid as rauthid, firstname, lastname,COALESCE(NULL,NULL) as contactemail from userdetails)
                union (select authid as rauthid, firstname, lastname,COALESCE(NULL,NULL) as contactemail from yachtdetail)
                union (select authid as rauthid, firstname, lastname,COALESCE(NULL,NULL) as contactemail from talentdetails)
                union (select authid as rauthid, name as firstname, COALESCE(NULL,NULL) as lastname,contactemail from companydetails)
            ) unionSub2 on unionSub2.rauthid = msgmain.message_to 
            
           left join (
                (select id as authid,email as authemail from auths)
            ) authsdata on authsdata.authid = msgmain.message_to where  msgmain.is_deleted = '0' AND msgmain.is_read = '0' AND  msgmain.created_at < '".$currentDate6hour."' ORDER BY msgmain.created_at DESC) temp ORDER BY message_id,created_at DESC");
        $UsersData = [];
        if(!empty($messageData) && count($messageData) > 0) {
            $website_url = env('NG_APP_URL','https://www.marinecentral.com');
            foreach($messageData as $messageDatas) {
                $CreatedDate = strtotime($messageDatas->created_at);
                $difference = $currentDate - $CreatedDate;
                $hours = $difference / (60*60);
                $userArr = [];
                if($messageDatas->first_alert == '0' && ($hours >= 6 && $hours < 24)) {
                    $userArr['from_name'] = $messageDatas->from_firstname.' '.$messageDatas->from_lastname;
                    $userArr['to_name'] = $messageDatas->to_firstname.' '.$messageDatas->to_lastname;
                    $userArr['to_email'] = $messageDatas->email;
                    $link = '';
                    if($messageDatas->to_usertype == 'company') {
                        $link = $website_url.'/business/messages?id='.$messageDatas->message_id.'&type='.$messageDatas->message_type.'&cf=marine';
                    } else if($messageDatas->to_usertype == 'yacht') {
                        $link = $website_url.'/yacht/messages?id='.$messageDatas->message_id.'&type='.$messageDatas->message_type.'&cf=marine';
                    } else if($messageDatas->to_usertype == 'regular') {
                        $link = $website_url.'/boat-owner/messages?id='.$messageDatas->message_id.'&type='.$messageDatas->message_type.'&cf=marine';
                    } else if($messageDatas->to_usertype == 'professional') {
                        $link = $website_url.'/job-seeker/messages?id='.$messageDatas->message_id.'&type='.$messageDatas->message_type.'&cf=marine';
                    }
                    if($link != '' && !empty($messageDatas->email)) {
                        $userArr['link'] = $link;
                        $status = $this->sendEmailNotification($userArr,'unreadMessage_reminder');
                        if($status == 'sent') {
                            $updateReminder = Messages::where('id','=',$messageDatas->id)->update(['first_alert' => '1']);
                        }
                    }
                } else if ($messageDatas->second_alert == '0' && ($hours >= 24 && $hours < 30)) {
                    $userArr['from_name'] = $messageDatas->from_firstname.' '.$messageDatas->from_lastname;
                    $userArr['to_name'] = $messageDatas->to_firstname.' '.$messageDatas->to_lastname;
                    $userArr['to_email'] = $messageDatas->email;
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    if($messageDatas->to_usertype == 'company') {
                        $link = $website_url.'/business/messages?id='.$messageDatas->message_id.'&type='.$messageDatas->message_type.'&cf=marine';
                    } else if($messageDatas->to_usertype == 'yacht') {
                        $link = $website_url.'/yacht/messages?id='.$messageDatas->message_id.'&type='.$messageDatas->message_type.'&cf=marine';
                    } else if($messageDatas->to_usertype == 'regular') {
                        $link = $website_url.'/boat-owner/messages?id='.$messageDatas->message_id.'&type='.$messageDatas->message_type.'&cf=marine';
                    } else if($messageDatas->to_usertype == 'professional') {
                        $link = $website_url.'/job-seeker/messages?id='.$messageDatas->message_id.'&type='.$messageDatas->message_type.'&cf=marine';
                    }
                    if($link != '' && !empty($messageDatas->email)) {
                        $userArr['link'] = $link;
                        $status = $this->sendEmailNotification($userArr,'unreadMessage_reminder');
                        if($status == 'sent') {
                            $updateReminder = Messages::where('id','=',$messageDatas->id)->update(['second_alert' => '1']);
                        }
                    }
                }
            }
        }
    }
    
    public function getDummyDataScript2(Request $request) {
        $file = fopen("dusdmmy1.csv","r");
        echo "<pre>";
        $DummyBussinessData = [];
        while(! feof($file)) {
            $DummyBussinessData[] = fgetcsv($file);
        }
        print_r($DummyBussinessData);
        $i = 0;
        $country = 'United States';
        $plandata = DB::table('subscriptionplans')->where('isadminplan', '=', '1')->where('status', '=', 'active')->first();
        $subplan = $plandata->id;
        $nextDate = date('Y-m-d 00:00:00', strtotime("+20 years", strtotime(date('Y-m-d H:i:s'))));
                
        foreach($DummyBussinessData as $DummyBussinessDatas) {
            if($i > 0) {
                DB::beginTransaction();
                $userData = [];
                $stateData = Usarea::where('state',$DummyBussinessDatas[3])->first();
                $company_name = $userData['name'] = $DummyBussinessDatas[0];
                $address = $userData['address'] = $DummyBussinessDatas[1];
                $city = $userData['city'] = $DummyBussinessDatas[2];
                //$state = $userData[''] = $stateData->statename;
                $zip = $userData['zipcode'] = $DummyBussinessDatas[4];
                $state = $userData['state'] = $DummyBussinessDatas[5];
                $phone = $userData['contact'] = str_replace(array('(', ')', '-', ' '), '', $DummyBussinessDatas[6]);
                $dummyEmail = $userData['businessemail'] = $DummyBussinessDatas[7];
                $website = $userData['websiteurl'] = $DummyBussinessDatas[8];
                $businessType = $userData['about'] = $DummyBussinessDatas[9];
                if(!empty($stateData)) {
                    //~ echo $DummyBussinessDatas[3];
                    //~ echo $stateData->state.'   '.$stateData->statename.'<br>';
                    
                
                
                    $state = $stateData->statename;
                    $longitude = 0;
                    $latitude = 0;
                    $auth   = new Auth;
                    $authid = 0;
                    $countemail = Companydetail::where('accounttype','=','dummy')->count();
                    if(empty($countemail)) {
                        $countemail = 0;
                    }
                    $countemail = $countemail+1;
                    $requestEmail = 'marine_business'.$countemail.'@marinecentral.com';
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
                    } else {
                        DB::rollBack();
                        DB::table('faildummy')->insert(
                            $userData
                        );
                    }
                    if($authid) {
                        $locAddress = (isset($address) && $address !='') ? $address.' ' : '';
                        $location = $locAddress.$city.' '.$zip.' '.$state.' ,'.$country;
                        $output = $this->getGeoLocation($location); //Get Location from location Trait
                        $longitude = $output['longitude'];
                        $latitude = $output['latitude'];
                        $company_name_new  = preg_replace('/[^a-zA-Z0-9_ -]/s','',$company_name);
                        $array = explode(" ",$company_name_new);
                        if(is_array($array)) {
                            $slug = implode("-",$array);       
                        }
                        $slug1 = '';
                        $array = explode(" ",$city);
                        if(is_array($array)) {
                            $slug1 = implode("-",$array);       
                        }
                        $slug = strtolower($slug.'-'.$slug1);
                        $checkSlug = Companydetail::where('slug','=',strtolower($slug))->count();
                        if($checkSlug) {
                            $slug = $slug.'-'.($checkSlug+1);
                        }
                        
                        $companydetail  = new Companydetail;
                        $companydetail->authid  = $authid;
                        $companydetail->name  = $company_name;
                        $companydetail->slug = strtolower($slug);
                        $companydetail->services   = '{}';
                        $companydetail->businessemail = ((isset($dummyEmail) && $dummyEmail !='') ? $dummyEmail: NULL);
                        $companydetail->address = ((isset($address) && $address !='') ? $address: NULL);
                        $companydetail->websiteurl    = ((isset($website) && $website !='') ? $website: NULL);
                        $companydetail->allservices =  NULL;
                        $companydetail->city       = $city;
                        $companydetail->state      = $state;
                        $companydetail->country    = $country;
                        $companydetail->about    = ((isset($businessType) && $businessType !='') ? $businessType: NULL);
                        $companydetail->zipcode    = $zip;
                        $companydetail->contact    = $phone;
                        $companydetail->longitude  = $longitude;
                        $companydetail->latitude   = $latitude;
                        $companydetail->contactname    = NULL;
                        $companydetail->contactmobile    = $phone;
                        $companydetail->contactemail    = ((isset($dummyEmail) && $dummyEmail !='') ? $dummyEmail: NULL);
                        $companydetail->accounttype   = 'dummy';
                        $companydetail->country_code = '+1';
                        $companydetail->subscriptiontype = 'manual';
                        $companydetail->nextpaymentdate = $nextDate;
                        $companydetail->paymentplan = (int)($subplan);
                        $companydetail->plansubtype = 'free';
                        $companydetail->status = 'active';
                        if($companydetail->save()) {
                            DB::commit();
                            //$usersdata['authid'] = $authid;
                        } else {
                            DB::rollBack();
                            DB::table('faildummy')->insert(
                                $userData
                            );
                        }
                    }
                } else {
                }
            }
            $i++;
        }
    }
    
    // registor discount user //
    public function discountRegistration(Request $request) {
        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'required',
            'rstep' => 'required',
            'services' => 'required',
            'city' => 'required',
            'state' => 'required',
            'about' => 'required',
            'businessemail' => 'required',
            'country' => 'required',
            // 'county' => 'required',
            'zipcode' => 'required',
            'contact' => 'required',
            'contactname' => 'required',
            'contactemail' => 'required',
            'contactmobile' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401);
        }
        
        $auth   = array(); 
        $updated = 0;
        
        $query = Auth::where('email', '=', strtolower(request('contactemail')));
        $count = $query->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
        $query2 = dummy_registration::where('email', '=', strtolower(request('contactemail')));
        $count2 = $query2->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
        $count = $count + $count2;
        if(!empty($count) && $count > 0) {
            return response()->json(['error'=>'validationError'], 401); 
        } 
        $address = request('address');
        $locAddress = (isset($address) && $address !='') ? $address.' ' : '';
        $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');
        $output = $this->getGeoLocation($location); //Get Location from location Trait
        $longitude = $output['longitude'];
        $latitude = $output['latitude'];

        $CompanyImage = request('images');
        $CompanyImages = json_decode(request('companyimages'));
        $imagesArr = [];
        if(!empty($CompanyImages)) {
            for($i=0;$i< count($CompanyImages);$i++){
                $imagesArr[$i]['image'] = $CompanyImages[$i];
                $imagesArr[$i]['primary'] = 0;
            }
            if(isset($CompanyImage) && $CompanyImage != '') {
                $imagesArr[count($CompanyImages)]['image'] = $CompanyImage;
                $imagesArr[count($CompanyImages)]['primary'] = 1;
            }
            $imagesObj =  json_encode($imagesArr,JSON_UNESCAPED_SLASHES);
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
                    $otherserviceArr[$ii] = $id;
                } else {
                    return 'networkerror';
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
        $company_name = request('name');
        
        $company_name_new  = preg_replace('/[^a-zA-Z0-9_ -]/s','',$company_name);
        $slug = implode("-",explode(" ",$company_name_new));
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
            $checkSlug = dummy_registration::where('actualslug','=',strtolower($slug))->count();
            $checkSlugEdit = dummy_registration::where('slug','=',strtolower($slug))->count();
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
        
        $boatYachtJson = request('boatYachtworked');
        $emptyboatYachtworked = true;
        $boatYachtworkedArray  = array();
        $i = 0;
        $j = 0;
        if(!empty($boatYachtJson)) {
            $boatYachtworked = json_decode(request('boatYachtworked'));
            $checkBoat = [];
            foreach ($boatYachtworked as $val) {
                if($val && !in_array($val, $checkBoat)) {
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
                if($val && !in_array($val,$checkEngine)) {
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
        
        //$email =  strtolower(request('contactemail'));
        $registerType = 'company'; 
        $userid = 0;
        $password = 'errtt';
        $companydetail  = new dummy_registration;   
        $companydetail->is_claim_user = '0';
        $companydetail->email = strtolower(request('contactemail'));
        $companydetail->password = Hash::make($password);
        $companydetail->ipaddress = $this->getIp();
        $companydetail->usertype = $registerType;
        $companydetail->stepscompleted ='2'; 
        $companydetail->actualslug   = strtolower($realSlug);
        $companydetail->slug   = strtolower($slug);
        $companydetail->name  = request('name');
        $companydetail->services   = $services;
        $companydetail->address    = ((isset($address) && $address !='') ? request('address'): NULL);
        $companydetail->city       = request('city');
        $companydetail->state      = request('state');
        $companydetail->about      = request('about');
        $companydetail->businessemail = request('businessemail');
        $companydetail->primaryimage =   ((isset($CompanyImage) && $CompanyImage !='') ? $CompanyImage: NULL);
        $companydetail->allservices =  ((isset($allservices) && $allservices !='') ? json_encode($allservices,JSON_UNESCAPED_SLASHES): NULL);
        $companydetail->websiteurl = request('websiteurl');
        $companydetail->country    = request('country');
        // $companydetail->county    = request('county');
        $companydetail->zipcode    = request('zipcode');
        $companydetail->contact    = request('contact');
        $companydetail->contactname    = request('contactname');
        $companydetail->contactmobile    = request('contactmobile');
        $companydetail->contactemail    = request('contactemail');
        
        $companydetail->images     = $imagesObj;
        $companydetail->longitude  = $longitude;
        $companydetail->latitude   = $latitude;
        $companydetail->country_code   = request('country_code');
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
            $userid = $companydetail->id;
            $chiperUserid = encrypt($userid);
            $zaiperenv = env('ZAIPER_ENV','local');
            if($zaiperenv == 'live') {
                $zapierData = array();
                $zapierData['type']     = 'Business';
                $zapierData['email']    = strtolower(request('contactemail'));
                $zapierData['businessemail'] = request('businessemail');
                $zapierData['name']     = request('name');
                $zapierData['contact']  = request('contact');
                $zapierData['address']  = request('address');
                $zapierData['city']     = request('city');
                $zapierData['state']    = request('state');
                $zapierData['country']  = request('country');
                $zapierData['zipcode']  = request('zipcode');
                $zapierData['contactmobile'] = request('contactmobile');
                $zapierData['contactemail'] = request('contactemail');
                $zapierData['contactname'] = request('contactname');
                $zapierData['newsletter'] = '0';
                $zapierData['website'] = request('websiteurl');
                $zapierData['about'] = request('about');
                $this->stepCompleteBiz($zapierData);
            }
            return response()->json(['success' => true,'userid' => $chiperUserid,'steps' => '2'], $this->successStatus); 
        
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }
    
    public function getClientTokenDiscount() {
        //$Customer = request('customer');
        $rid = request('id');
        $decryptUserid = decrypt($rid);
        $isNotCustomerID = true;
        if(!empty($decryptUserid)) {
            $customerID = getenv('CUSTOMER_ID').'_01_'.$decryptUserid;
            try {
                $CheckCustomer = Braintree_Customer::find($customerID);
                $customerID =  $CheckCustomer->id;
                $isNotCustomerID = false;
            } catch(Exception $e) {
                $isNotCustomerID = true;
            }
        }
        $usersdata = DB::table('dummy_registration as userdetail')
            ->select('userdetail.name','userdetail.email','userdetail.contactmobile')
            ->where('userdetail.id', '=', (int)$decryptUserid)
            ->first();
        if(!empty($usersdata)) {
            if($isNotCustomerID) {
                $CustomerData = Braintree_Customer::create([ 'id' => $customerID,
                    'company' => $usersdata->name,
                    'email' => $usersdata->email,
                    'phone' => $usersdata->contactmobile
                    ]);
                if($CustomerData->success) {
                    $Customer = $CustomerData->customer->id;
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            }
            $updateArr['customer_id'] = $customerID;
            $updated =  dummy_registration::where('id', '=', (int)$decryptUserid)->update($updateArr);
            return response()->json([
                'token' => Braintree_ClientToken::generate(["customerId" => $customerID]),
            ]);
        } else {
             return response()->json(['error'=>'networkerror'], 401);
        }
    }
    
    public function braintreeTransactionPlanDiscount(Request $request) {
        $rid = request('id');
        $plan = request('plan');
        if(!empty($plan) && $plan == 'marinepro') {
            $plan = 'pro';
        } else if(!empty($plan) && $plan == 'advanced') {
            $plan = 'advance';
        }
        $planArr = array('pro','advance','basic');
        $decryptUserid = (int)decrypt($rid);
        if(!empty($decryptUserid)) {
            $usersdata =dummy_registration::where('id', '=', (int)$decryptUserid)->first();
            if(!empty($usersdata)) {
                if(in_array($plan,$planArr)) {
                    $PlanData = DB::table('subscriptionplans')->Join('discounts', 'discounts.paymentplan', '=', 'subscriptionplans.id')
                    ->select('subscriptionplans.*','discounts.current_discount')
                    ->where('subscriptionplans.planname','ILIKE','%'.$plan.'%')
                    ->where('subscriptionplans.status','=','active')
                    ->get();
                    //echo "<pre>";print_r($PlanData);die;
                    $customer_id = $usersdata->customer_id;
                    $payment_method = Braintree_PaymentMethod::create(['paymentMethodNonce'=>request('nonce'),'customerId' => $customer_id]);
                    if($payment_method-> success){
                        $amount = $PlanData[0]->amount;
                        $plan_id ='';
                        if($amount == 199) {
                            $plan_id ='plan_basic_monthly';
                        } else if($amount == 299) {
                            $plan_id ='plan_advance_monthly';
                        } else if ($amount == 399) {
                            $plan_id ='plan_pro_monthly';
                        }
                        $date = date('2019-12-31 23:59:59');
                        $current = date('Y-m-d 00:00:00');
                        if($current < $date) {
                            $IsapplyDiscount = true;
                            $amountPaid = $PlanData[0]->amount;
                            $discountapply = $PlanData[0]->current_discount;
                            $amountPaid = ceil(($amountPaid * $discountapply)/100);
                            $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id,'discounts' => [
                                'add' => [
                                    [
                                        'inheritedFromId' => 'default-discount',
                                        'amount' => $amountPaid,
                                        'numberOfBillingCycles' => 12,
                                        'quantity' => 1
                                    ]
                                ]
                            ]]);
                        } else {
                            $IsapplyDiscount = false;
                            $result = Braintree_Subscription::create(['paymentMethodToken'=> $payment_method->paymentMethod->token,'planId'=>$plan_id]);
                        }
                        //if()
                         //echo "<pre>";print_r($result);
                        $subID = $result->subscription->id;
                        $updateArr['subscription_id'] = $subID;
                        $updateArr['remaintrial'] = 30;
                        $updated =  dummy_registration::where('id', '=', (int)$decryptUserid)->update($updateArr);
                        if ($result->success) {
                            return response()->json(['transaction' => $result->success,'data' => $result], $this->successStatus);
                          // See $result->transaction for details
                        } else {
                            return response()->json(['error'=>$result->errors], 200);
                          //print_r($result->errors);/ Handle errors
                        }
                        
                    } else {
                        return response()->json(['error'=>$payment_method], 200);
                    }
                } else {
                    return response()->json(['error'=>'networkerror'], 200);
                }
                
                
            } else {
                return response()->json(['error'=>'networkerror'], 200);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 200);
        }
    }
    
        //Create Customer account in stripe
     public function companypaymentDiscount(Request $request){
       // need to set plan month
        $validate = Validator::make($request->all(), [
            'tranctionID' => 'required',
            'subplan' => 'required',
            'userID'  => 'required',
            'card_token' => 'required',
            'nameoncard' => 'required'
        ]);
        if ($validate->fails()) {
            $success = false;
        }
        
        $renewPlan = request('renewal');
        if($renewPlan == 'true' && $renewPlan != 'null' ) {
            $subType = 'automatic';
        } else {
            $subType = 'manual';
        }
        $useridencrypt = request('userID');
        $userID = decrypt($useridencrypt);
       
        if(empty($userID) || $userID == '') {
            return response()->json(['error'=>'networkerror'], 401); 
        } else {
            $card_token = request('card_token');
            $cardHolder = request('nameoncard');
            $paymentStatus = $this->stripeTransactionPlanDiscount($userID,request('subplan'),$card_token,$cardHolder,$subType);
            if(isset($paymentStatus['status']) && $paymentStatus['status'] == 'success') {
                $basicTrialDays = 0;
                $checkplan = request('subplan');
                /*Old Payment
                if(!empty($checkplan) && $checkplan == 'basic') {
                        $currentDate = date('Y-m-d 00:00:00');
                        if(env('BASIC_PLAN_UNLIMITED_END') > $currentDate) {
                            $basicTrialDays = 60;
                        }
                }
                */

                $checkEmailExist =  dummy_registration::where('id', '=', (int)$userID)->where('usertype', '=', 'company')->first();
                $checkEmailAddressExist = $checkEmailExist->email;
                if(!empty($checkEmailExist)) {
                    $queryChecks = Auth::where('email', '=', $checkEmailAddressExist);
                    $countChecks = $queryChecks->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
                    $query2Checks = dummy_registration::where('email', '=', $checkEmailAddressExist);
                    $count2Checks = $query2Checks->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
                    $countChecks = $countChecks + $count2Checks;
                    if(!empty($countChecks) && $countChecks > 0) {
                        return response()->json(['error'=>'emailExist'], 401);
                    }
                } else {
                    return response()->json(['error'=>'networkerror'], 401);
                }
            } else {
                return response()->json(['success' => false,'error'=>$paymentStatus], $this->successStatus);
            }
        }
         /* Get user card Token and Plan*/
        $subplan = request('subplan');
        if(!empty($subplan) && $subplan == 'marinepro') {
            $subplan = 'pro';
        } else if(!empty($subplan) && $subplan == 'advanced') {
            $subplan = 'advance';
        }
        $userDetail = dummy_registration::where('id', '=', (int)$userID)->get()->first()->toArray();
        $isDummyUser = false;
        $email = $userDetail['email'];
       
        $ex_message = '';
        $plandata = DB::table('subscriptionplans')->where('planname','ILIKE','%'.$subplan.'%')
                    ->where('status','=','active')
                    ->where('active_status','=','active')
                    ->first();
        // $plandata = DB::table('subscriptionplans')->where('id', '=', (int)$subplan)->where('status', '=', 'active')->first();
        if(!empty($plandata)) {
            $planPrice = $plandata->amount;
            $planType = $plandata->plantype;
            $planAccessType = $plandata->planaccesstype;
            $planAccessNumber = $plandata->planaccessnumber;
            if($planType =='paid') { 
                if($planAccessType == 'month') {
                    /*
                    if($basicTrialDays > 0) {
                        $nextDate = date('Y-m-d 00:00:00', strtotime("+ ".$basicTrialDays." days", strtotime(date('Y-m-d H:i:s'))));
                    } else {
                        $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                    }
                    */
                    $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                } else if($planAccessType == 'unlimited'){
                    $nextDate = '2099-01-01 00:00:00';
                } else if($planAccessType == 'year'){
                    $nextDate = date('Y-m-d 00:00:00', strtotime("+ 365 days", strtotime(date('Y-m-d H:i:s'))));
                }
            } else {
                if($planAccessType == 'unlimited'){
                    $nextDate = '2099-01-01 00:00:00';
                } else {
                    $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                }
                //Add Free Plan
                return response()->json(['success' => true,'nextdate'=> $nextDate], $this->successStatus);
            }            
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
        $statusStep = false;
        
        $statusCompany = dummy_registration::where('id', (int)$userID)->update(['subscriptiontype' => $subType,'nextpaymentdate' => $nextDate, 'paymentplan' => $plandata->id,'plansubtype' => 'paid','stepscompleted' => '3']);

        //insert temp record to actual tables
        $tempData = dummy_registration::where('id',(int)$userID)->first();
        //if(!$isDummyUser) {
        $getRNDpassword = $this->randomString();
        $authData = new Auth;
        $authData->email = $tempData->email;
        $authData->password =  Hash::make($getRNDpassword);
        $authData->usertype = $tempData->usertype;
        $authData->ipaddress = $tempData->ipaddress;
        $authData->stepscompleted = $tempData->stepscompleted;
        //~ if($tempData->is_social == '1') {
            //~ $authData->is_activated = '1';
        //~ } else {
        $authData->is_activated = '0';
        //~ }
        $authData->is_social = $tempData->is_social;
        $authData->social_id = $tempData->social_id;
        $authData->provider  = $tempData->provider;
        $authData->status = 'active';
        if($authData->save()) {
            $authid = $authData->id;
            $companyData = new Companydetail;
            $company_slug_new= preg_replace('/[^a-zA-Z0-9_ -]/s','',$tempData->name);     
            $slug = implode("-",explode(" ",$company_slug_new));
            $slug1 = '';
            $array = explode(" ",$tempData->city);
            if(is_array($array)) {
                $slug1 = implode("-",$array);       
            }
            $slug = strtolower($slug.'-'.$slug1);
            $realSlug = $slug;    
            $companyData->authid = $authid;
            $companyData->name = $tempData->name;
            // Calculate slug
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
            $discountData = DB::table('discounts')->where('paymentplan',(int)$plandata->id)
                    ->first();
            $companyData->slug = $slug;
            $companyData->actualslug = $realSlug;
            $companyData->services = $tempData->services;
            $companyData->address = $tempData->address;
            $companyData->city = $tempData->city;
            $companyData->state = $tempData->state;
            $companyData->country = $tempData->country;
            $companyData->zipcode = $tempData->zipcode;
            $companyData->contact = $tempData->contact;
            $companyData->about = $tempData->about;
            $companyData->businessemail = $tempData->businessemail;
            $companyData->websiteurl = $tempData->websiteurl;
            $companyData->images = $tempData->images;
            $companyData->longitude = $tempData->longitude;
            $companyData->latitude = $tempData->latitude;
            $companyData->nextpaymentdate = $tempData->nextpaymentdate;
            $companyData->customer_id = $tempData->customer_id;
            $companyData->subscription_id = $tempData->subscription_id;
            $companyData->paymentplan = $tempData->paymentplan;
            $companyData->next_paymentplan = $tempData->paymentplan;
            $companyData->plansubtype = $tempData->plansubtype;
            $companyData->subscriptiontype = $tempData->subscriptiontype;
            $companyData->advertisebusiness = '0';
            $companyData->primaryimage = $tempData->primaryimage;
            $companyData->allservices = $tempData->allservices;
            $companyData->contactname = $tempData->contactname;
            $companyData->contactmobile = $tempData->contactmobile;
            $companyData->contactemail = $tempData->contactemail;
            /*Old Payment
            if($basicTrialDays > 0 ) {
                $companyData->remaintrial = $basicTrialDays;
            } else {
                $companyData->remaintrial = 30;
            }
            */
            $companyData->remaintrial = 0;
            $companyData->status = 'active';
            $companyData->coverphoto = $tempData->coverphoto;
            $companyData->boats_yachts_worked    = $tempData->boats_yachts_worked;
            $companyData->engines_worked    = $tempData->engines_worked;
            $companyData->is_admin_approve = '1';
            $companyData->is_claimed = '0';
            $companyData->accounttype = 'real';
            /*Old payment
            $dateDiscountCheck = date('2019-12-31 23:59:59');
            $currentDiscountCheck = date('Y-m-d 00:00:00');
            if($currentDiscountCheck < $dateDiscountCheck) {
                $companyData->is_discount = '1';
                $companyData->remaindiscount = 12;
                $companyData->discount = (!empty($discountData->current_discount) ? $discountData->current_discount : 0);
            } else {
                $companyData->remaindiscount = 0;
            }
            */
            $companyData->remaindiscount = 0;
            $companyData->discount = 0;
            $companyData->is_discount = '0';
            // $companyData->county = $tempData->county;
            if($companyData->save()) {
                $DictionaryData = new Dictionary;
                $DictionaryData->authid = $authid;
                $DictionaryData->word = $tempData->name;
                if($DictionaryData->save()) {
                }
                $statusStep = true;
                
                $rejectedRegistration = dummy_registration::where('id', '=', (int)$userID)->delete();
                $geosuccess = TRUE;
                if($tempData->is_social == '0' ) {
                    $random_hashed = Hash::make(str_random(8).$authid);
                    $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    $link = $website_url.'/activate?token='.urlencode($random_hashed);
                    $ACTIVATION_LINK = $link;
                    $emailArr = [];                                        
                    $emailArr['link'] = $ACTIVATION_LINK;
                    /*
                     $ACTIVATION_OTP=rand(10000,99999);
                     $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $ACTIVATION_OTP]);
                     $emailArr['otp'] = $ACTIVATION_OTP;
                     //
                     */
                    $emailArr['to_email'] = $tempData->email;
                    $emailArr['name'] = $tempData->name;
                    $emailArr['password'] = $getRNDpassword;
                    $emailArr['logEmail'] = $tempData->email;
                        //Send activation email notification
                        //~ if($tempData->is_claim_user == '1') {
                            //~ $status = $this->sendEmailNotification($emailArr,'claimed_business_notification');
                        //~ } else {
                    //Trigger Zap Business
                    $zaiperenv = env('ZAIPER_ENV','local');
                    if($zaiperenv == 'live') {
                        $zapierData = array();
                        $zapierData['type']     = 'Business';
                        $zapierData['id']   = $authid;
                        $zapierData['email']    = $tempData->email;
                        $zapierData['businessemail'] = $tempData->businessemail;
                        $zapierData['name']     = $tempData->name;
                        $zapierData['contact']  = $tempData->contact;
                        $zapierData['address']  = $tempData->address;
                        $zapierData['city']     = $tempData->city;
                        $zapierData['state']    = $tempData->state;
                        $zapierData['country']  = $tempData->country;
                        $zapierData['zipcode']  = $tempData->zipcode;
                        $zapierData['plan']     = $plandata->planname;
                        $zapierData['subscriptiontype'] = $tempData->subscriptiontype;
                        $zapierData['contactmobile'] = $tempData->country_code.$tempData->contactmobile;
                        $zapierData['contactemail'] = $tempData->contactemail;
                        $zapierData['contactname'] = $tempData->contactname;
                        $zapierData['newsletter'] = $tempData->newsletter;
                        $zapierData['website'] = $tempData->websiteurl;
                        $zapierData['about'] = $tempData->about;
                        $this->sendAccountCreateZapierBiz($zapierData);
                    }
                    $status = $this->sendEmailNotification($emailArr,'business_registration_activation_discount'); 
                    $adminEmailArr = array();
                    $adminEmailArr['userEmail'] = $emailArr['to_email'];
                    $adminEmailArr['userType'] = 'Company';
                    $adminEmailArr['userFirstname'] = $emailArr['name'];
                    $adminEmailArr['to_email'] = env("Admin_Email");
                    //Send activation email notification
                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                    $adminEmailArr['to_email'] = env("Info_Email");
                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                    //~ }
                    if($status != 'sent') {
                        return response()->json(['error'=>'emailsentfail'], 401);
                    }
                } else {
                    $isSocial = true;
                }
                // } else {
                //     $geosuccess = FALSE;    
                // }
            } else {
                return response()->json(['error'=>'entryfail'], 401);
            }
        } else {
            return response()->json(['error'=>'entryfail'], 401);
        } 
        //} 

        if($statusStep && $statusCompany && $geosuccess) {
            $paymentTable = 'paymenthistory';
            $transactionid = NULL;
            $status = 'pending';
            if($subType == 'manual') {
                $transactionid = $paymentStatus['chargeTrs'];
                $status = 'approved';
                $statusPayment =  DB::table($paymentTable)->insert(
                    ['companyid' => (int)$authid,
                    'transactionid' => $transactionid,
                    'transactionfor' => 'registrationfee',
                    'amount' => $planPrice,
                    'payment_type' =>$plandata->id,
                    'status' => $status,
                    'customer_id' => $userDetail['customer_id'],
                    'subscription_id' => $userDetail['subscription_id'],
                    'expiredate' => $nextDate,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                    ]);  
            } else {
                $paymentHistoryData = DB::table('paymenthistory')->where('subscription_id','=',$userDetail['subscription_id'])->where('transactionfor','registrationfee')->orderBy('created_at','DESC')->get();
                if(!empty($paymentHistoryData) && count($paymentHistoryData) > 0 && $paymentHistoryData[0]->status == 'approved') {
                    $statusPayment = true;   
                } else {
                    $statusPayment =  DB::table($paymentTable)->insert(
                    ['companyid' => (int)$authid,
                    'transactionid' => $transactionid,
                    'transactionfor' => 'registrationfee',
                    'amount' => $planPrice,
                    'payment_type' =>$plandata->id,
                    'status' => $status,
                    'customer_id' => $userDetail['customer_id'],
                    'subscription_id' => $userDetail['subscription_id'],
                    'expiredate' => $nextDate,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                    ]); 
                }
            }
            
            if($statusPayment) {
                //~ if($tempData->is_social == '1'  && $tempData->is_claim_user == '0') {
                    //~ $userdata = Auth::where('id', '=', (int)$authid)->get();
                    //~ $user = $userdata[0];
                    //~ $success['type']    = $userdata[0]->usertype;
                    //~ $success['authid']  = encrypt($userdata[0]->id);
                    //~ $success['email']   = $userdata[0]->email;
                    //~ $success['stepscompleted'] = $userdata[0]->stepscompleted;
                    //~ $success['token']   =  $user->createToken('MyApp')->accessToken;
                    //~ return response()->json(['success' => true,'isSocial'=>true,'userid' => request('id'),'nextdate'=> $nextDate,'steps' => '3','data' => $success], $this->successStatus);
                //~ } else {
                $isSocial = false;
                return response()->json(['success' => true,'userid' => encrypt($authid),'nextdate'=> $nextDate,'isSocial' => $isSocial], $this->successStatus);
                //~ }
            } else {
                return response()->json(['error'=>'entryfail'], 401);
            }
        } else {
            return response()->json(['error'=>'entryfail'], 401);
        }
    }
    
    public function changeEmailAddressDiscount(Request $request) {
        $validate = Validator::make($request->all(), [
            'email' => 'required',
            'id' => 'required'
        ]);
   
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $userid = request('id');
        $decryptUserid = decrypt($userid);
        if(empty($decryptUserid) || $decryptUserid == '') {
            return 'networkerror'; 
        } else {
            $checkEmailAddressExist = strtolower(request('email'));
            $checkEmailExist =  dummy_registration::where('id', '=', (int)$decryptUserid)->first();
            if(!empty($checkEmailExist)) {
                $queryChecks = Auth::where('email', '=', $checkEmailAddressExist);
                $countChecks = $queryChecks->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
                $query2Checks = dummy_registration::where('email', '=', $checkEmailAddressExist);
                $count2Checks = $query2Checks->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
                $countChecks = $countChecks + $count2Checks;
                if(!empty($countChecks) && $countChecks > 0) {
                     return response()->json(['error'=>'emailExist'], 401);
                } else {
                    $updateStatus = dummy_registration::where('id','=',(int)$decryptUserid)->update(['email' => $checkEmailAddressExist]);
                    if(!empty($updateStatus)) {
                        return response()->json(['success' => true], $this->successStatus);    
                    } else {
                        return response()->json(['error'=>'networkerror1'], 401);    
                    }
                }
            } else {
                 return response()->json(['error'=>'networkerror'], 401);
            }
        }
    }
    
    public function getsubscriptionInfo() {
        $subname = request('subname');
        if(!empty($subname) && $subname == 'marinepro') {
            $subname = 'pro';
        } else if(!empty($subname) && $subname == 'advanced') {
            $subname = 'advance';
        }
        $allPlans = DB::table('subscriptionplans as subscriptionplans')->leftJoin('discounts', 'discounts.paymentplan', '=', 'subscriptionplans.id')->select('subscriptionplans.planname','subscriptionplans.plandescription','subscriptionplans.amount','subscriptionplans.id','discounts.current_discount')->where('subscriptionplans.status','=','active')->where('subscriptionplans.active_status','=','active')->where('subscriptionplans.planname','ILIKE','%'.$subname.'%')->get();
        if($allPlans && count($allPlans) > 0) {
            $discountAmount = 0;
            $amountwithoutdiscount = $allPlans[0]->amount;
            $discountapply = $allPlans[0]->current_discount;
            if($discountapply > 0) {
                $discountAmount = ceil(($amountwithoutdiscount * $discountapply)/100);
            }
            $discountedAmount = $amountwithoutdiscount - $discountAmount;
            $allPlans[0]->discountedAmount = $discountedAmount;
            return response()->json(['success' => true,'data' => $allPlans], $this->successStatus);
        } else {
            return response()->json(['success' => false,'data' => $allPlans], $this->successStatus);
        }
    }
    
    //~ public function checkPass() {
        //~ $rndNum = $this->randomString();
        //~ echo $rndNum;
    //~ }
    
    function randomString() {
        $length = 8;
        $specialChPos = mt_rand(0,7);
        $str = "";
        $speChArr = array('!','@','#','$','%','^','&','*','(',')');
        $maxSp = count($speChArr)-1;
        $characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            if($specialChPos == $i) {
                $rand = mt_rand(0, $maxSp);
                $str .= $speChArr[$rand];
            } else {
                $rand = mt_rand(0, $max);
                $str .= $characters[$rand];
            }
        }
        $numberRnd = mt_rand(10,99);
        $str .= $numberRnd;
        return $str;
    }
    public function getFreePlan() {
        $freePlan = Subscriptionplans::select('id')->where('subscriptionplans.planname','ILIKE','%Free%')->where('isadminplan','0')->first();
        if(!empty($freePlan)) {
            $id = $freePlan->id;
            return response()->json(['success' => true,'data' => $id], $this->successStatus);
        } else {
            return response()->json(['success' => false,'data' => []], $this->successStatus);
        }
    }

    public function payPerLeadPayment(Request $request){
        $userid = request('id');
        $planid = request('planid');
        $id = decrypt($userid);
        
        if((int)$id) {
            $nextDate = date('Y-m-d 00:00:00', strtotime("+ 90 days", strtotime(date('Y-m-d H:i:s'))));
            $statusCompany = dummy_registration::where('id', (int)$id)->update(['subscriptiontype' => 'manual','nextpaymentdate' => $nextDate, 'paymentplan' => $planid,'plansubtype' => 'free','stepscompleted' => '3','lastpaymentdate' =>date('Y-m-d H:i:s')]);
            if($statusCompany) {
                $tempData = dummy_registration::where('id',(int)$id)->first();
                $email = $tempData->email;
                $checkIfUserAlreadyExist = Auth::where('email',$email)->where('status','!=','deleted')->count();
                if($checkIfUserAlreadyExist) {
                    return response()->json(['error'=>'networkerror'], 401);
                }
                $getRNDpassword = $this->randomString();
                $authData = new Auth;
                $authData->email = $tempData->email;
                $authData->password =  Hash::make($getRNDpassword);
                $authData->usertype = $tempData->usertype;
                $authData->ipaddress = $tempData->ipaddress;
                $authData->stepscompleted = $tempData->stepscompleted;
                $authData->newsletter = $tempData->newsletter;
                $authData->is_activated = '0';
                $authData->is_social = $tempData->is_social;
                $authData->social_id = $tempData->social_id;
                $authData->provider  = $tempData->provider;
                $authData->status = 'active';
                $company_name = $tempData->name;
                $company_name_new  = preg_replace('/[^a-zA-Z0-9_ -]/s','',$company_name);
                $slug = implode("-",explode(" ",$company_name_new));
                $slug1 = '';
                $array = explode(" ",$tempData->city);
                if(is_array($array)) {
                    $slug1 = implode("-",$array);       
                }
                $slug = strtolower($slug.'-'.$slug1);
                $realSlug = $slug;
                if($authData->save()) {
                    $authid = $authData->id;
                    $companyData = new Companydetail;
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
                    $companyData->slug = $slug;
                    $companyData->authid = $authid;
                    $companyData->name = $tempData->name;
                    $companyData->actualslug = $realSlug;
                    $companyData->services = $tempData->services;
                    $companyData->address = $tempData->address;
                    $companyData->city = $tempData->city;
                    $companyData->state = $tempData->state;
                    $companyData->country = $tempData->country;
                    $companyData->zipcode = $tempData->zipcode;
                    $companyData->contact = $tempData->contact;
                    $companyData->about = $tempData->about;
                    $companyData->businessemail = $tempData->businessemail;
                    $companyData->websiteurl = $tempData->websiteurl;
                    $companyData->images = $tempData->images;
                    $companyData->longitude = $tempData->longitude;
                    $companyData->latitude = $tempData->latitude;
                    $companyData->nextpaymentdate = $tempData->nextpaymentdate;
                    $companyData->customer_id = $tempData->customer_id;
                    $companyData->subscription_id = $tempData->customer_id;
                    $companyData->paymentplan = $tempData->paymentplan;
                    $companyData->plansubtype = $tempData->plansubtype;
                    $companyData->subscriptiontype = $tempData->subscriptiontype;
                    $companyData->advertisebusiness = '0';
                    $companyData->primaryimage = $tempData->primaryimage;
                    $companyData->allservices = $tempData->allservices;
                    $companyData->contactname = $tempData->contactname;
                    $companyData->contactmobile = $tempData->contactmobile;
                    $companyData->contactemail = $tempData->contactemail;
                    $companyData->status = 'active';
                    $companyData->coverphoto = $tempData->coverphoto;
                    $companyData->accounttype = 'real';
                    $companyData->next_paymentplan = $tempData->paymentplan;
                    $companyData->lastpaymentdate = $tempData->lastpaymentdate;
                    $companyData->boats_yachts_worked    = $tempData->boats_yachts_worked;
                    $companyData->engines_worked    = $tempData->engines_worked;
                    $companyData->is_admin_approve = '1';
                    $companyData->is_claimed = '0';
                    $companyData->accounttype = 'real';
                    $companyData->remaintrial = 60;
                    
                    if($companyData->save()) {
                        $DictionaryData = new Dictionary;
                        $DictionaryData->authid = $authid;
                        $DictionaryData->word = $tempData->name;
                        if($DictionaryData->save()) {
                        }
                        $statusStep = true;
                        $geosuccess = true;
                        //Deleted Records from Dummy
                        $rejectedRegistration = dummy_registration::where('id', '=', (int)$id)->delete();
                        //Send Account Created Email 
                        $random_hashed = Hash::make(str_random(8).$authid);
                        $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                        $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                        $link = $website_url.'/activate?token='.urlencode($random_hashed);
                        $ACTIVATION_LINK = $link;
                        $emailArr = [];                                        
                        $emailArr['link'] = $ACTIVATION_LINK;
                        /* 
                         //temp otp
                         $ACTIVATION_OTP=rand(10000,99999);
                         $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $ACTIVATION_OTP]);
                          $emailArr['otp'] = $ACTIVATION_OTP;
                        //*/
                        $emailArr['to_email'] = $tempData->email;
                        $emailArr['name'] = $tempData->name;
                        $emailArr['password'] = $getRNDpassword;
                        $emailArr['logEmail'] = $tempData->email;
                        //Trigger Zap Business
                        $zaiperenv = env('ZAIPER_ENV','local');
                        if($zaiperenv == 'live') {
                            $zapierData = array();
                            $zapierData['type']     = 'Business';
                            $zapierData['id']   = $authid;
                            $zapierData['email']    = $tempData->email;
                            $zapierData['businessemail'] = $tempData->businessemail;
                            $zapierData['name']     = $tempData->name;
                            $zapierData['contact']  = $tempData->contact;
                            $zapierData['address']  = $tempData->address;
                            $zapierData['city']     = $tempData->city;
                            $zapierData['state']    = $tempData->state;
                            $zapierData['country']  = $tempData->country;
                            $zapierData['zipcode']  = $tempData->zipcode;
                            $zapierData['plan']  = 'Free';
                            $zapierData['subscriptiontype'] = $tempData->subscriptiontype;
                            $zapierData['contactmobile'] = $tempData->country_code.$tempData->contactmobile;
                            $zapierData['contactemail'] = $tempData->contactemail;
                            $zapierData['contactname'] = $tempData->contactname;
                            $zapierData['newsletter'] = $tempData->newsletter;
                            $zapierData['website'] = $tempData->websiteurl;
                            $zapierData['about'] = $tempData->about;
                            $this->sendAccountCreateZapierBiz($zapierData);
                        }
                        //Send activation email notification
                        $status = $this->sendEmailNotification($emailArr,'business_registration_activation_discount');
                        $adminEmailArr = array();
                        $adminEmailArr['userEmail'] = $emailArr['to_email'];
                        $adminEmailArr['userType'] = 'Company';
                        $adminEmailArr['userFirstname'] = $emailArr['name'];
                        $adminEmailArr['to_email'] = env("Admin_Email");
                        //Send activation email notification
                        SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                        $adminEmailArr['to_email'] = env("Info_Email");
                        SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_notification');
                        if($status == 'sent') {
                            $statusPayment =  DB::table('paymenthistory')->insert(
                                            ['companyid' => (int)$authid,'transactionfor' => 'registrationfee',
                                            'amount' => '0.00',
                                            'status' => 'approved' ,
                                            'payment_type'=>(int)(request('subplan')),
                                            'expiredate' => $nextDate,
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'updated_at' => date('Y-m-d H:i:s')
                                            ]);
                            if($statusPayment) {
                                return response()->json(['success' => true,'authid' => encrypt($authid)], $this->successStatus);
                            } else {
                                return response()->json(['error'=>'networkerror'], 401);
                            }
                        } else {
                            return response()->json(['error'=>'emailsentfail'], 401);
                        }
                    } else {
                        return response()->json(['error'=>'entryfail'], 401);
                    }
                } else {
                    return response()->json(['error'=>'entryfail'], 401);
                }

            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }
    
    public function getAllBoatandYacht() {
        $BoatandYachtData = Boat_Engine_Companies::select('name as itemName',DB::raw('COALESCE(\'btyt\') as itemtype'))->where(function($query) {
            $query->where('category', '=', 'boats')
            ->orWhere('category', '=', 'yachts');
        })->where('status','1')->orderBy('itemName','ASC')->get();
        if(!empty($BoatandYachtData)) {
            return response()->json(['success' => true,'data' =>$BoatandYachtData ], $this->successStatus);
        } else {
            return response()->json(['success' => false], $this->successStatus);
        }
    }
    public function getAllEngines() {
        $EnginesData = Boat_Engine_Companies::select('name as itemName',DB::raw('COALESCE(\'engine\') as itemtype'))->where('category','=','engines')->where('status','1')->orderBy('itemName','ASC')->get();
        if(!empty($EnginesData)) {
            return response()->json(['success' => true,'data' =>$EnginesData ], $this->successStatus);
        } else {
            return response()->json(['success' => false], $this->successStatus);
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
    
    public function addFishCharterReq(Request $request){
       $validate = Validator::make($request->all(), [
              'userdata'=>'required',
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
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $userdata = request('userdata');
        if(empty($userdata)) {
            return response()->json(['error'=>'networkerror'], 401); 
        }
        $userdatajson = json_decode($userdata,true);
        $userType = $userdatajson['usertypefield'];
        $useremail = $userdatajson['email'];
        $response = $this->addRequestedUser($userdatajson);
        if($response){
            // $category_Id = env("Fish_Charter_Category_id");
            // $service_Id = env("Fish_Charter_Service_id");
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
            $authid =  $this->regAuthId;
            if(!empty($authid) && (int)($authid)) {
                $servreq = new User_request_services;
                $servreq->authid = $authid;
                $servreq->services = $servicesObj;
                $servreq->request_type = 'fish_charter_request';
               $servreq->country = request('country');
                $servreq->city = request('city');
                $servreq->state = request('state');
                $servreq->zipcode = request('zipcode');
                $servreq->address = request('address');
                $servreq->title = request('title');
                $servreq->description = request('description');
                $servreq->addspecialrequirement = request('optionalinfo');
                $servreq->charterDays = request('charterDays');
                $servreq->totalPeople = request('totalPeople');
                $servreq->charterType = ucfirst(request('charterType'));
                $servreq->status = 'posted';
                $servreq->longitude = $longitude;
                $servreq->latitude = $latitude;
                if($servreq->save()) {
                    $serviceRequestId = $servreq->id;
                    $tablename = $this->regTableName;
                    $boatOwnerDetail =  DB::table($tablename)
                        ->where('authid', '=', (int)$authid)
                        ->select('firstname','lastname')
                        ->first();
                    $miles = 50;
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
                    
                    $random_hashed = Hash::make(str_random(8).$authid);
                    $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    $link = $website_url.'/activate?token='.urlencode($random_hashed);
                    $ACTIVATION_LINK = $link;
                    $emailArr = [];                                        
                    $emailArr['link'] = $ACTIVATION_LINK;
                    /*
                    $ACTIVATION_OTP=rand(10000,99999);
                    $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $ACTIVATION_OTP]);
                    $emailArr['otp'] = $ACTIVATION_OTP;                                        
                     */
                    $emailArr['to_email'] = $userdatajson['email'];
                    $emailArr['name'] = $userdatajson['firstname'].' '.$userdatajson['lastname'];
                    $emailArr['password'] = $this->regRanPas;
                    $emailArr['logEmail'] = $userdatajson['email'];
                    //Send activation email notification
                    
                    $statusReq = $this->sendEmailNotification($emailArr,'user_registration_and_service_request'); 
                    $adminEmailArr = [];
                    if($userType == 'yt') {
                        $adminEmailArr['userType'] = 'Yacht Owner/Captain';
                    } else {
                        $adminEmailArr['userType'] = 'Boat Owner';
                    }
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    $link = $website_url.'/service-request/'.$serviceRequestId.'?cf=marine';
                    $adminEmailArr['userEmail'] = $emailArr['to_email'];
                    $adminEmailArr['link'] = $link;
                    $adminEmailArr['userFirstname'] = $emailArr['name'];
                    $adminEmailArr['to_email'] = env("Admin_Email");
                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_new_service_notification');
                    $adminEmailArr['to_email'] = env("Info_Email");
                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_new_service_notification');
                    if (!empty($listOfBusinessInMiles) && count($listOfBusinessInMiles) > 0) {
                        $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                        $link = $website_url.'/service-request/'.$serviceRequestId.'?cf=marine';
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
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }
   

    public function addBoatSlipReq(Request $request){
       $validate = Validator::make($request->all(), [
              'userdata'=>'required',
              'boatCountry'=> 'required',
              'boatState'=> 'required',
               'title'=> 'required',
              'description'=> 'required',
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
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $userdata = request('userdata');
        if(empty($userdata)) {
            return response()->json(['error'=>'networkerror'], 401); 
        }
        $userdatajson = json_decode($userdata,true);
        $userType = $userdatajson['usertypefield'];
        $useremail = $userdatajson['email'];
        $response = $this->addRequestedUser($userdatajson);
        if($response){
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
             $authid =  $this->regAuthId;
            if(!empty($authid) && (int)($authid)) {
                $servreq = new User_request_services;
                $servreq->authid = $authid;
                $servreq->services =  $servicesObj;
                 $servreq->title = request('title');
                $servreq->description = request('description');
                $servreq->addspecialrequirement = request('optionalinfo');
                $servreq->request_type = 'boat_slip_request';
                $servreq->country = request('boatCountry');
                $servreq->city = request('boatCity');
                 $servreq->state = request('boatState');
                $servreq->zipcode = request('boatZipcode');
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
                $servreq->address = request('boatAddress');
                $servreq->longitude = $longitude;
                $servreq->latitude = $latitude;
                $servreq->status = 'posted';
                if($servreq->save()) {
                    $serviceRequestId = $servreq->id;
                    $tablename = $this->regTableName;
                    $boatOwnerDetail =  DB::table($tablename)
                        ->where('authid', '=', (int)$authid)
                        ->select('firstname','lastname')
                        ->first();
                    $miles = 50;
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
                    
                    $random_hashed = Hash::make(str_random(8).$authid);
                    $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    $link = $website_url.'/activate?token='.urlencode($random_hashed);
                    $ACTIVATION_LINK = $link;
                    $emailArr = [];                                        
                    $emailArr['link'] = $ACTIVATION_LINK;
                    /*
                    $ACTIVATION_OTP=rand(10000,99999);
                    $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $ACTIVATION_OTP]);
                    $emailArr['otp'] = $ACTIVATION_OTP;                                        
                     */
                    $emailArr['to_email'] = $userdatajson['email'];
                    $emailArr['name'] = $userdatajson['firstname'].' '.$userdatajson['lastname'];
                    $emailArr['password'] = $this->regRanPas;
                    $emailArr['logEmail'] = $userdatajson['email'];
                    //Send activation email notification
                    
                    $statusReq = $this->sendEmailNotification($emailArr,'user_registration_and_service_request'); 
                    $adminEmailArr = [];
                    if($userType == 'yt') {
                        $adminEmailArr['userType'] = 'Yacht Owner/Captain';
                    } else {
                        $adminEmailArr['userType'] = 'Boat Owner';
                    }
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    $link = $website_url.'/service-request/'.$serviceRequestId.'?cf=marine';
                    $adminEmailArr['userEmail'] = $emailArr['to_email'];
                    $adminEmailArr['link'] = $link;
                    $adminEmailArr['userFirstname'] = $emailArr['name'];
                    $adminEmailArr['to_email'] = env("Admin_Email");
                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_new_service_notification');
                    $adminEmailArr['to_email'] = env("Info_Email");
                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_new_service_notification');
                    if (!empty($listOfBusinessInMiles) && count($listOfBusinessInMiles) > 0) {
                        $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                        $link = $website_url.'/service-request/'.$serviceRequestId.'?cf=marine';
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
                        return response()->json(['success' => true,'userid' => encrypt($authid)], $this->successStatus);
                    } else {
                        return response()->json(['success' => true, 'userid' => encrypt($authid)], $this->successStatus); 
                    }    
                    return response()->json(['success' => true, 'userid' => encrypt($authid)], $this->successStatus); 
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

    // add boat owner service request
    public function addBoatOwnerServiceRequest(Request $request) {
        $validate = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'country' => 'required',
            'state' => 'required',
            // 'county' => 'required',
            'city' => 'required',
            'zipcode' => 'required',
            'numberofleads' => 'required',
            'userdata' => 'required',
            'services' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $userdata = request('userdata');
        if(empty($userdata)) {
            return response()->json(['error'=>'networkerror'], 401); 
        }
        $userdatajson = json_decode($userdata,true);
        $userType = $userdatajson['usertypefield'];
        $useremail = $userdatajson['email'];
        $response = $this->addRequestedUser($userdatajson);
        if($response){
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
            $authid =  $this->regAuthId;
            if(!empty($authid) && (int)($authid)) {
                $servreq = new User_request_services;
                $servreq->title = request('title');
                $servreq->request_type = 'normal_request';
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
                    $tablename = $this->regTableName;
                    $boatOwnerDetail =  DB::table($tablename)
                        ->where('authid', '=', (int)$authid)
                        ->select('firstname','lastname')
                        ->first();
                    $miles = 50;
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
                    $random_hashed = Hash::make(str_random(8).$authid);
                    $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    $link = $website_url.'/activate?token='.urlencode($random_hashed);
                    $ACTIVATION_LINK = $link;
                    $emailArr = [];                                        
                    $emailArr['link'] = $ACTIVATION_LINK;
                    /*
                    $ACTIVATION_OTP=rand(10000,99999);
                    $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $ACTIVATION_OTP]);
                    $emailArr['otp'] = $ACTIVATION_OTP;                                        
                     */
                    $emailArr['to_email'] = $userdatajson['email'];
                    $emailArr['name'] = $userdatajson['firstname'].' '.$userdatajson['lastname'];
                    $emailArr['password'] = $this->regRanPas;
                    $emailArr['logEmail'] = $userdatajson['email'];
                    //Send activation email notification
                    
                    $statusReq = $this->sendEmailNotification($emailArr,'user_registration_and_service_request'); 
                    $adminEmailArr = [];
                    if($userType == 'yt') {
                        $adminEmailArr['userType'] = 'Yacht Owner/Captain';
                    } else {
                        $adminEmailArr['userType'] = 'Boat Owner';
                    }
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    $link = $website_url.'/service-request/'.$serviceRequestId.'?cf=marine';
                    $adminEmailArr['userEmail'] = $emailArr['to_email'];
                    $adminEmailArr['link'] = $link;
                    $adminEmailArr['userFirstname'] = $emailArr['name'];
                    $adminEmailArr['to_email'] = env("Admin_Email");
                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_new_service_notification');
                    $adminEmailArr['to_email'] = env("Info_Email");
                    SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_user_new_service_notification');
                    if (!empty($listOfBusinessInMiles) && count($listOfBusinessInMiles) > 0) {
                        $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                        $link = $website_url.'/service-request/'.$serviceRequestId.'?cf=marine';
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
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }    
    }
    
    public function addRequestedUser($userdatajson){
        $userType = $userdatajson['usertypefield'];
        $useremail = $userdatajson['email'];
        $query = Auth::where('email', '=', strtolower($useremail));
        $count = $query->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
        $query2 = dummy_registration::where('email', '=', strtolower($useremail));
        $count2 = $query2->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
        $count = $count + $count2;
        if(!empty($count) && $count > 0) {
            return response()->json(['error'=>'validationError'], 401); 
        } 
        $getRNDpassword = $this->regRanPas = $this->randomString();
        $authData = new Auth;
        $authData->email = strtolower($userdatajson['email']);
        $authData->password =  Hash::make($getRNDpassword);
        $tablename =  $this->regTableName  = 'userdetails';
        if($userType == 'yt') {
            $authData->usertype = 'yacht';
            $this->regTableName = $tablename = 'yachtdetail';
        } else {
            $authData->usertype = 'regular';    
            $this->regTableName = $tablename = 'userdetails';
        }
        $authData->ipaddress = $this->getIp();
        $authData->stepscompleted = '2';
        $authData->newsletter = '0';
        $authData->is_activated = '0';
        $authData->status = 'active';
        $authData->is_social = '0';
        if($authData->save()) {
            $locAddress = ((isset($userdatajson['address']) && $userdatajson['address'] !='') ? $userdatajson['address'].' ': '');
            $location = $locAddress.$userdatajson['city'].' '.$userdatajson['state'].' '.$userdatajson['country'].' ,'.$userdatajson['zipcode'];
            $output = $this->getGeoLocation($location); //Get Location from location Trait
            $longitude = $output['longitude'];
            $latitude = $output['latitude'];
            $this->regAuthId = $authid = $authData->id;
            if($userType == 'yt') {
                $userdetails = new Yachtdetail;  
                $userdetails->primaryimage = NULL;
                $userdetails->contact = $userdatajson['mobile'];
            } else  {
                $userdetails = new Userdetail;  
                $userdetails->profile_image = NULL;
                $userdetails->mobile = $userdatajson['mobile'];
            }
            $userdetails->authid = $authid;
            $userdetails->firstname = $userdatajson['firstname'];
            $userdetails->lastname = $userdatajson['lastname'];
            $userdetails->city = $userdatajson['city'];
            $userdetails->state = $userdatajson['state'];
            $userdetails->country = $userdatajson['country'];
            $userdetails->zipcode = $userdatajson['zipcode'];
            $userdetails->address = ((isset($userdatajson['address']) && $userdatajson['address'] !='') ? $userdatajson['address']: NULL);
            $userdetails->longitude = $longitude;
            $userdetails->latitude = $latitude;
            $userdetails->status = 'active';
            $country_code = $userdatajson['countrycode'];
            if($country_code != '') {
                $pos = strpos($country_code, '+');
                if(!$pos){
                    $country_code ='+'.$country_code;
                }
            }   
            $userdetails->country_code   = $country_code;
                            // $userdetails->county = $tempData->county;
            if($userdetails->save()) {
                $zaiperenv = env('ZAIPER_ENV','local');
                if($zaiperenv == 'live') {
                    $zapierData = array();
                    if($userType == 'yt') {
                        $zapierData['type']     = 'Yacht Owner';  
                        $zapierData['tag']      = 'Signed Up - Captain';
                    } else  {
                        $zapierData['tag']      = 'Signed Up - Boat Owner'; 
                        $zapierData['type']     = 'Boat Owner';
                    }
                    $zapierData['id']   = $authid;
                    $zapierData['email']    = $userdatajson['email'];
                    $zapierData['firstname']= $userdatajson['firstname'];
                    $zapierData['lastname'] = $userdatajson['lastname'];
                    $zapierData['contact']  = $country_code.$userdatajson['mobile'];
                    $zapierData['address']  = $userdatajson['address'];
                    $zapierData['city']     = $userdatajson['city'];
                    $zapierData['state']    = $userdatajson['state'];
                    $zapierData['country']  = $userdatajson['country'];
                    $zapierData['zipcode']  = $userdatajson['zipcode'];
                    
                    $this->sendAccountCreateZapier($zapierData);
                }
            } else {
                return false;;  
            }
        } else {
            return false;  
        }
        return true;
    }
    public function checkisCompany() {
        $userid = request('userid');
        if(empty($userid) || $userid == '') {
            return response()->json(['error'=>'networkerror'], 401);
        }
        $getCompanyData = Companydetail::select('authid','slug')->where('authid','=',(int)$userid)->where('accounttype','real')->where('status','!=','deleted')->get();
        if(!empty($getCompanyData) && count($getCompanyData) > 0) {
            return response()->json(['success' => true,'slug' => $getCompanyData[0]->slug], $this->successStatus);
        } else {
            return response()->json(['success' => false], $this->successStatus);
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
                'userdata'=> 'required'
            ]);
            if ($validate->fails()) {
                return response()->json(['error'=>'validationError'], 401); 
            }
            
            $userdata = request('userdata');
            if(empty($userdata)) {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            $userdatajson = json_decode($userdata,true);
            $useremail = $userdatajson['email'];
            $query = Auth::where('email', '=', strtolower($useremail));
            $count = $query->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
            $query2 = dummy_registration::where('email', '=', strtolower($useremail));
            $count2 = $query2->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
            $count = $count + $count2;
            if(!empty($count) && $count > 0) {
                return response()->json(['error'=>'validationError'], 401); 
            } 
            $getRNDpassword = $this->randomString();
            $authData = new Auth;
            $authData->email = strtolower($userdatajson['email']);
            $authData->password = Hash::make($getRNDpassword);
            $authData->usertype = 'regular';
            $authData->ipaddress = $this->getIp();
            $authData->stepscompleted = '2';
            $authData->newsletter = '0';
            $authData->is_activated = '0';
            $authData->status = 'active';
            $authData->is_social = '0';
            if($authData->save()) {
                $locAddress = ((isset($userdatajson['address']) && $userdatajson['address'] !='') ? $userdatajson['address'].' ': '');
                $location = $locAddress.$userdatajson['city'].' '.$userdatajson['state'].' '.$userdatajson['country'].' ,'.$userdatajson['zipcode'];
                $output = $this->getGeoLocation($location); //Get Location from location Trait
                $longitude = $output['longitude'];
                $latitude = $output['latitude'];
                
                $authid = $authData->id;
                $userdetails = new Userdetail;
                $userdetails->authid = $authid;
                $userdetails->firstname = $userdatajson['firstname'];
                $userdetails->lastname = $userdatajson['lastname'];
                $userdetails->city = $userdatajson['city'];
                $userdetails->state = $userdatajson['state'];
                $userdetails->country = $userdatajson['country'];
                $userdetails->zipcode = $userdatajson['zipcode'];
                $userdetails->address = ((isset($userdatajson['address']) && $userdatajson['address'] !='') ? $userdatajson['address']: NULL);
                $userdetails->mobile = $userdatajson['mobile'];
                $userdetails->profile_image = NULL;
                $userdetails->longitude = $longitude;
                $userdetails->latitude = $latitude;
                $userdetails->status = 'active';
                $country_code = $userdatajson['countrycode'];
                if($country_code != '') {
                    $pos = strpos($country_code, '+');
                    if(!$pos){
                        $country_code ='+'.$country_code;
                    }
                }   
                $userdetails->country_code   = $country_code;
                                // $userdetails->county = $tempData->county;
                if($userdetails->save()) {
                    $zaiperenv = env('ZAIPER_ENV','local');
                    if($zaiperenv == 'live') {
                        $zapierData = array();
                        $zapierData['type']     = 'Boat Owner';
                        $zapierData['id']   = $authid;
                        $zapierData['email']    = $userdatajson['email'];
                        $zapierData['firstname']= $userdatajson['firstname'];
                        $zapierData['lastname'] = $userdatajson['lastname'];
                        $zapierData['contact']  = $country_code.$userdatajson['mobile'];
                        $zapierData['address']  = $userdatajson['address'];
                        $zapierData['city']     = $userdatajson['city'];
                        $zapierData['state']    = $userdatajson['state'];
                        $zapierData['country']  = $userdatajson['country'];
                        $zapierData['zipcode']  = $userdatajson['zipcode'];
                        $zapierData['tag']      = 'Signed Up - Boat Owner';
                        $this->sendAccountCreateZapier($zapierData);
                    }
                } else {
                    return response()->json(['error'=>'networkerror'], 401);  
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);  
            }
            
            $contactUsArr = array();
            $userid = $authid;
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
            // echo "<pre>";print_r($listOfBusinessInMiles);die;
            $random_hashed = Hash::make(str_random(8).$authid);
            $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
            $website_url = env('NG_APP_URL','https://www.marinecentral.com');
            $link = $website_url.'/activate?token='.urlencode($random_hashed);
            $ACTIVATION_LINK = $link;
            $emailArr = [];                                        
            $emailArr['link'] = $ACTIVATION_LINK;
            /*
            $ACTIVATION_OTP=rand(10000,99999);
            $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $ACTIVATION_OTP]);
            $emailArr = [];
            $emailArr['otp'] = $ACTIVATION_OTP; 
            */
                                                 
            $emailArr['to_email'] = $userdatajson['email'];
            $emailArr['name'] = $userdatajson['firstname'].' '.$userdatajson['lastname'];
            $emailArr['password'] = $getRNDpassword;
            $emailArr['logEmail'] = $userdatajson['email'];
            //Send activation email notification
            
            $statusReq = $this->sendEmailNotification($emailArr,'user_registration_and_request_quote');
            $adminEmailArr = [];
            $adminEmailArr['userEmail'] = $emailArr['to_email'];
            $adminEmailArr['userType'] = 'Boat Owner';
            $adminEmailArr['userFirstname'] =  $emailArr['name'];
            $adminEmailArr['to_email'] = env("Admin_Email");
                           
            SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_service_notification');
            $adminEmailArr['to_email'] = env("Info_Email");
            SendNewLeadNotificationEmails::dispatch($adminEmailArr,'admin_new_service_notification');
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
               
                    return response()->json(['success' => true,'userid' => encrypt($authid)], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            } else {
                    return response()->json(['error'=>'networkerror'], 401); 
            }
        }

    //Stripe Company payment
        public function companypaymentStripe(Request $request){
       // need to set plan month
        $validate = Validator::make($request->all(), [
            'tranctionID' => 'required',
            'subplan' => 'required',
            'userID'  => 'required',
            'rtype'       => 'required'
        ]);
        if ($validate->fails()) {
            $success = false;
        }
        $isSocial = false;
        $renewPlan = request('renewal');
        if($renewPlan && $renewPlan != 'null' && $renewPlan == 'true'  ) {
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
        } else {
            if($rtype != 'admin') {
                $checkEmailExist =  dummy_registration::where('id', '=', (int)$userID)->where('usertype', '=', 'company')->first();
                $checkEmailAddressExist = $checkEmailExist->email;
                if(!empty($checkEmailExist)) {
                    if($checkEmailExist->is_social == '0') {
                        $queryChecks = Auth::where('email', '=', $checkEmailAddressExist);
                        $countChecks = $queryChecks->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
                        $query2Checks = dummy_registration::where('email', '=', $checkEmailAddressExist);
                        $count2Checks = $query2Checks->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
                        $countChecks = $countChecks + $count2Checks;
                        if(!empty($countChecks) && $countChecks > 0) {
                             return response()->json(['error'=>'emailExist'], 401);
                        }
                    }
                } else {
                     return response()->json(['error'=>'networkerror'], 401);
                }
            }
        }
        /* Get user card Token and Plan*/
        $subplan = request('subplan');
        $userDetail = dummy_registration::where('id', '=', (int)$userID)->get()->first()->toArray();
        if(!empty($userDetail) && $userDetail['is_claim_user'] == '1') {
            $isDummyUser = true;
        } else {
            $isDummyUser = false;
        }
        $email = $userDetail['email'];
       
        $ex_message = '';
        $plandata = DB::table('subscriptionplans')->leftJoin('discounts', 'discounts.paymentplan', '=', 'subscriptionplans.id')->select('subscriptionplans.*','discounts.current_discount')->where('subscriptionplans.id', '=', (int)$subplan)->where('subscriptionplans.status', '=', 'active')->first();
        if(!empty($plandata)) {
            $planPrice = $plandata->amount;
            $planType = $plandata->plantype;
            $planAccessType = $plandata->planaccesstype;
            $planAccessNumber = $plandata->planaccessnumber;
            if($planType =='paid') { 
                if($planAccessType == 'month') {
                    $nextDate1 = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                    $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                } else if($planAccessType == 'unlimited'){
                    $nextDate = '2099-01-01 00:00:00';
                }
            } else {
                if($planAccessType == 'unlimited'){
                    $nextDate = '2099-01-01 00:00:00';
                } else {
                    $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                }
                //Add Free Plan
                return response()->json(['success' => true,'nextdate'=> $nextDate], $this->successStatus);
            }            
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
        $statusStep = false;
        if($isDummyUser) {
            $statusCompany = dummy_registration::where('id', (int)$userID)->update(['subscriptiontype' => $subType,'nextpaymentdate' => $nextDate, 'paymentplan' => (int)$subplan,'plansubtype' => 'paid','stepscompleted' => '3','status' => 'active']);
        } else {
            $statusCompany = dummy_registration::where('id', (int)$userID)->update(['subscriptiontype' => $subType,'nextpaymentdate' => $nextDate, 'paymentplan' => (int)$subplan,'plansubtype' => 'paid','stepscompleted' => '3']);

        }
        
        //insert temp record to actual tables
        $tempData = dummy_registration::where('id',(int)$userID)->first();
        if(!$isDummyUser) {
           $authData = new Auth;
            $authData->email = $tempData->email;
            $authData->password = $tempData->password;
            $authData->usertype = $tempData->usertype;
            $authData->ipaddress = $tempData->ipaddress;
            $authData->stepscompleted = $tempData->stepscompleted;
            if($tempData->is_social == '1') {
                $authData->is_activated = '1';
            } else {
                $authData->is_activated = '0';
            }
            $authData->is_social = $tempData->is_social;
            $authData->social_id = $tempData->social_id;
            $authData->provider  = $tempData->provider;
            $authData->newsletter  = $tempData->newsletter;
            $authData->status = 'active';
            if($authData->save()) {
                $authid = $authData->id;
                $companyData = new Companydetail;
                $company_slug_new= preg_replace('/[^a-zA-Z0-9_ -]/s','',$tempData->name);     
                $slug = implode("-",explode(" ",$company_slug_new));
                $slug1 = '';
                $array = explode(" ",$tempData->city);
                if(is_array($array)) {
                    $slug1 = implode("-",$array);       
                }
                $slug = strtolower($slug.'-'.$slug1);
                $realSlug = $slug;    
                $companyData->authid = $authid;
                $companyData->name = $tempData->name;
                // Calculate slug
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
                $companyData->slug = $slug;
                $companyData->actualslug = $realSlug;
                $companyData->services = $tempData->services;
                $companyData->address = $tempData->address;
                $companyData->city = $tempData->city;
                $companyData->state = $tempData->state;
                $companyData->country = $tempData->country;
                $companyData->zipcode = $tempData->zipcode;
                $companyData->contact = $tempData->contact;
                $companyData->about = $tempData->about;
                $companyData->businessemail = $tempData->businessemail;
                $companyData->websiteurl = $tempData->websiteurl;
                $companyData->images = $tempData->images;
                $companyData->longitude = $tempData->longitude;
                $companyData->latitude = $tempData->latitude;
                $companyData->nextpaymentdate = $tempData->nextpaymentdate;
                $companyData->customer_id = $tempData->customer_id;
                $companyData->subscription_id = $tempData->subscription_id;
                $companyData->paymentplan = $tempData->paymentplan;
                $companyData->next_paymentplan = $tempData->paymentplan;
                $companyData->plansubtype = $tempData->plansubtype;
                $companyData->subscriptiontype = $tempData->subscriptiontype;
                $companyData->advertisebusiness = '0';
                $companyData->primaryimage = $tempData->primaryimage;
                $companyData->allservices = $tempData->allservices;
                $companyData->contactname = $tempData->contactname;
                $companyData->contactmobile = $tempData->contactmobile;
                $companyData->contactemail = $tempData->contactemail;
                $companyData->status = 'active';
                $companyData->coverphoto = $tempData->coverphoto;
                $companyData->boats_yachts_worked    = $tempData->boats_yachts_worked;
                $companyData->engines_worked    = $tempData->engines_worked;
                if($tempData->is_claim_user == '1') {
                    $companyData->is_admin_approve = '0';
                    $companyData->is_claimed = '1';
                    $companyData->accounttype = 'claimed';   
                } else {
                    $companyData->is_admin_approve = '1';
                    $companyData->is_claimed = '0';
                    $companyData->accounttype = 'real';
                }
                $dateDiscountCheck = date('2019-12-31 23:59:59');
                $currentDiscountCheck = date('Y-m-d 00:00:00');
                if($currentDiscountCheck < $dateDiscountCheck) {
                    $companyData->is_discount = '1';
                    $companyData->remaindiscount = 12;
                    $companyData->discount = (!empty($plandata->current_discount) ? $plandata->current_discount : 0);
                }
                // $companyData->county = $tempData->county;
                if($companyData->save()) {
                    $DictionaryData = new Dictionary;
                    $DictionaryData->authid = $authid;
                    $DictionaryData->word = $tempData->name;
                    if($DictionaryData->save()) {
                    }
                    $statusStep = true;                    
                    $rejectedRegistration = dummy_registration::where('id', '=', (int)$userID)->delete();
                    $geosuccess = TRUE;
                    if($tempData->is_social == '0' ) {
                        $random_hashed = Hash::make(str_random(8).$authid);
                        $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                        $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                        $link = $website_url.'/activate?token='.urlencode($random_hashed);
                        $ACTIVATION_LINK = $link;
                        $emailArr = [];                                        
                        $emailArr['link'] = $ACTIVATION_LINK;
                        /*
                        $ACTIVATION_OTP=rand(10000,99999);
                        $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $ACTIVATION_OTP]);
                        $emailArr['otp'] = $ACTIVATION_OTP;
                        */
                        $emailArr['to_email'] = $tempData->email;
                        $emailArr['name'] = $tempData->name;
                        //Send activation email notification
                        if($tempData->is_claim_user == '1') {
                            $status = $this->sendEmailNotification($emailArr,'claimed_business_notification');
                        } else {
                            $status = $this->sendEmailNotification($emailArr,'business_registration_activation'); 
                        }
                        if($status != 'sent') {
                            return response()->json(['error'=>'emailsentfail'], 401);
                        }
                    } else {
                        $isSocial = true;
                    }
                    // } else {
                    //     $geosuccess = FALSE;    
                    // }
                } else {
                    return response()->json(['error'=>'entryfail'], 401);
                }
            } else {
                return response()->json(['error'=>'entryfail'], 401);
            } 
        } else {
            $geosuccess = TRUE;
            $statusStep = TRUE;
            $statusCompany = TRUE;
            $authid = (int)$userID;
            $random_hashed = Hash::make(str_random(8).$authid);
            $website_url = env('NG_APP_URL','https://www.marinecentral.com');
            $link = $website_url.'/activate?token='.urlencode($random_hashed);
            $ACTIVATION_LINK = $link;
            $emailArr = [];                                        
            $emailArr['link'] = $ACTIVATION_LINK;
            if($tempData->is_social == '0' ) {
                $emailArr['to_email'] = $tempData->email;
            } else {
                $emailArr['to_email'] = $tempData->contactemail;
            }
            $emailArr['name'] = $tempData->name;
            //Send activation email notification
            $status = $this->sendEmailNotification($emailArr,'claimed_business_notification');
            if($status != 'sent') {
                return response()->json(['error'=>'emailsentfail'], 401);
            }
            if($tempData->is_social == '1') {
                $isSocial = true;
            }
        }

        if($statusStep && $statusCompany && $geosuccess) {
            if($isDummyUser) {
                $paymentTable = 'dummy_paymenthistory';
            } else {
                $paymentTable = 'paymenthistory';
            }

            $statusPayment =  DB::table($paymentTable)->insert(
                    ['companyid' => (int)$authid,
                    'transactionid' => request('tranctionID'),
                    'transactionfor' => 'registrationfee',
                    'amount' => $planPrice,
                    'payment_type' =>$plandata->id,
                    'status' => 'pending' ,
                    'customer_id' => $userDetail['customer_id'],
                    'subscription_id' => $userDetail['subscription_id'],
                    'expiredate' => $nextDate,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                    ]);
            if($statusPayment) {
                if($tempData->is_social == '1'  && $tempData->is_claim_user == '0') {
                    $userdata = Auth::where('id', '=', (int)$authid)->get();
                    $user = $userdata[0];
                    $success['type']    = $userdata[0]->usertype;
                    $success['authid']  = encrypt($userdata[0]->id);
                    $success['email']   = $userdata[0]->email;
                    $success['stepscompleted'] = $userdata[0]->stepscompleted;
                    $success['token']   =  $user->createToken('MyApp')->accessToken;
                    return response()->json(['success' => true,'isSocial'=>true,'userid' => request('id'),'nextdate'=> $nextDate,'steps' => '3','data' => $success], $this->successStatus);
                } else {
                    return response()->json(['success' => true,'userid' => encrypt($authid),'nextdate'=> $nextDate,'isSocial' => $isSocial], $this->successStatus);
                }
            } else {
                return response()->json(['error'=>'entryfail'], 401);
            }
        } else {
            return response()->json(['error'=>'entryfail'], 401);
        }
    }

     public function stripeTransactionPlan($decryptUserid, $pid, $card_token, $cardHolder,$subType) {
        $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
        if($decryptUserid) {
            $usersdata =dummy_registration::where('id', '=', (int)$decryptUserid)->first();
            if(!empty($usersdata)) {
                try {
                    $customer = $stripe->customers()->create([      //Create a customer account 
                        'source' => $card_token,
                        'email' => $usersdata->email,
                        'metadata' => ['name' => $usersdata->name,'email' => $usersdata->email]
                    ]);
                    if(isset($customer['id'])) {
                        $stripe_id = $customer['id'];
                        $PlanData = DB::table('subscriptionplans')
                        ->select('subscriptionplans.*')
                        ->where('subscriptionplans.id',(int)$pid)
                        ->where('subscriptionplans.status','=','active')
                        ->get();

                        if(!empty($PlanData) && count($PlanData) > 0) {
                            $current = date('Y-m-d 00:00:00');
                            $amount = (int)$PlanData[0]->amount;
                            $plan_id =$PlanData[0]->stripe_plan_id;
                            /*Old Payment
                            $basicTrialDays = 0;
                            if($amount == 199) {
                                $plan_id ='plan_basic_monthly';
                                if(env('BASIC_PLAN_UNLIMITED_END') > $current) {
                                    $basicTrialDays = 60;
                                }
                            } else if($amount == 299) {
                                $plan_id ='plan_advance_monthly';
                            } else if ($amount == 399) {
                                $plan_id ='plan_pro_monthly';
                            }
                            */
                            /*Old payment
                            $date = date('2019-12-31 23:59:59');
                            if($current < $date) {
                                $amountPaid = $PlanData[0]->amount;
                                $discountapply = $PlanData[0]->current_discount;
                                $amountPaid = ceil(($amountPaid * $discountapply)/100);
                                $amountPaid = $amountPaid * 100;
                                $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
                                $coupon = $stripe->coupons()->create([
                                    'duration'    => 'repeating',
                                    'amount_off' => $amountPaid,
                                    'currency'  => 'USD',
                                    'duration_in_months' => 12
                                ]);
                                
                                if(isset($coupon['id'])) {
                                    if($basicTrialDays > 0) {
                                        $subscription = $stripe->subscriptions()->create($stripe_id, [
                                            'plan' => $plan_id,
                                            'coupon' => $coupon['id'],
                                            'trial_end' => strtotime( '+'.$basicTrialDays.' day' ),
                                            'metadata' => ['name' => $cardHolder]
                                        ]);
                                    } else {
                                        $subscription = $stripe->subscriptions()->create($stripe_id, [
                                            'plan' => $plan_id,
                                            'coupon' => $coupon['id'],
                                            'metadata' => ['name' => $cardHolder]
                                        ]);
                                    }
                                } else {
                                    return 'network';
                                }
                                
                            } else {
                                if($basicTrialDays > 0) {
                                    $subscription = $stripe->subscriptions()->create($stripe_id, [
                                        'plan' => $plan_id,
                                        'trial_end' => strtotime( '+'.$basicTrialDays.' day' ),
                                        'metadata' => ['name' => $cardHolder]
                                    ]);
                                } else {
                                    $subscription = $stripe->subscriptions()->create($stripe_id, [
                                        'plan' => $plan_id,
                                        'metadata' => ['name' => $cardHolder]
                                    ]);
                                }
                            }
                            */
                            $chargeTrs = '';
                            if($subType == 'manual') {
                                $charge = $stripe->charges()->create([ 
                                    'customer' => $stripe_id,
                                    'currency' => 'USD',
                                    'amount' => $amount
                                    ]);
                                if($charge['status'] == 'succeeded') {
                                    $chargeTrs = $charge['balance_transaction'];
                                } else {
                                    return response()->json(['error'=>'paymenterror'], 401);
                                }
                            } else {
                                $subscription = $stripe->subscriptions()->create($stripe_id, [
                                    'plan' => $plan_id,
                                    'metadata' => ['name' => $cardHolder]
                                ]); 
                            }

                            if((isset($subscription['id']) && $subType== 'automatic') || $subType == 'manual') {
                                if($subType == 'manual') {
                                    $updateArr['subscription_id'] = NULL;    
                                } else {
                                    $updateArr['subscription_id'] = $subscription['id'];
                                }
                                $updateArr['remaintrial'] = 0;
                                $updateArr['remaindiscount'] = 0;
                                /* Old Payment
                                if($basicTrialDays > 0) {
                                    $updateArr['remaintrial'] = $basicTrialDays;
                                } else {
                                    $updateArr['remaintrial'] = 30;
                                }
                                */
                                $updateArr['customer_id'] = $customer['id'];
                                $updated =  dummy_registration::where('id', '=', (int)$decryptUserid)->update($updateArr);
                                if ($updated) {
                                    if($chargeTrs != '') {
                                        return ['status' =>'success','chargeTrs' => $chargeTrs];
                                    } else {
                                        return ['status' =>'success'];
                                    }
                                } else {
                                    return 'network';
                                }
                            } else {
                                return 'subscription Id not found.';
                            }
                        } else {
                            return 'Plan not found.';
                        }
                    } else {
                        return 'Customer Id not found.';
                    }
                } catch(Exception $e) {
                    return $e->getMessage();
                } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
                    return $e->getMessage();
                } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
                    return $e->getMessage();
                }
            } else {
                return 'network';
            }
        } else {
            return 'network';
        }
    }
    //Create Customer account in stripe
    public function companywihtoutpayment(Request $request){
       // need to set plan month
        $validate = Validator::make($request->all(), [
            'subplan' => 'required',
            'userID'  => 'required',
            'rtype'       => 'required',
        ]);
        if ($validate->fails()) {
            $success = false;
        }
        $rtype = request('rtype');
        if($rtype == 'admin') {
           $userID = request('userID');
        } else {
            $useridencrypt = request('userID');
            $userID = decrypt($useridencrypt);
        }
        $card_token = request('card_token');
        $cardHolder = request('nameoncard');
        $isSocial = false;
        $renewPlan = request('renewal');

        if($renewPlan && $renewPlan != 'null' && $renewPlan == 'true'  ) {
            $subType = 'automatic';
        } else {
            $subType = 'manual';
        }
        if(empty($userID) || $userID == '') {
            return response()->json(['error'=>'networkerror'], 401); 
        } else {
            if($rtype != 'admin') {
                $checkEmailExist =  dummy_registration::where('id', '=', (int)$userID)->where('usertype', '=', 'company')->first();
                $checkEmailAddressExist = $checkEmailExist->email;
                if(!empty($checkEmailExist)) {
                    if($checkEmailExist->is_social == '0') {
                        $queryChecks = Auth::where('email', '=', $checkEmailAddressExist);
                        $countChecks = $queryChecks->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
                        $query2Checks = dummy_registration::where('email', '=', $checkEmailAddressExist);
                        $count2Checks = $query2Checks->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
                        $countChecks = $countChecks + $count2Checks;
                        if(!empty($countChecks) && $countChecks > 0) {
                             return response()->json(['error'=>'emailExist'], 401);
                        }
                    }
                } else {
                     return response()->json(['error'=>'networkerror'], 401);
                }
            }
        }
        /* Get user card Token and Plan*/
        $subplan = request('subplan');
        $userDetail = dummy_registration::where('id', '=', (int)$userID)->get()->first()->toArray();
        if(!empty($userDetail) && $userDetail['is_claim_user'] == '1') {
            $isDummyUser = true;
        } else {
            $isDummyUser = false;
        }
        $email = $userDetail['email'];
       
        $ex_message = '';
        $plandata = DB::table('subscriptionplans')->leftJoin('discounts', 'discounts.paymentplan', '=', 'subscriptionplans.id')->select('subscriptionplans.*','discounts.current_discount')->where('subscriptionplans.id', '=', (int)$subplan)->where('subscriptionplans.status', '=', 'active')->first();
        if(!empty($plandata)) {
            $basicTrialDays = 0;
            if (strpos('Basic', $plandata->planname) !== false) {
                $currentDate = date('Y-m-d 00:00:00');
                if(env('BASIC_PLAN_UNLIMITED_END') > $currentDate) {
                    $basicTrialDays = 60;
                }
            }
            $planPrice = $plandata->amount;
            $planType = $plandata->plantype;
            $planAccessType = $plandata->planaccesstype;
            $planAccessNumber = $plandata->planaccessnumber;
            if($planType =='paid') { 
                if($planAccessType == 'month') {
                    if($basicTrialDays > 0) {
                        $nextDate1 = date('Y-m-d 00:00:00', strtotime("+ ".$basicTrialDays." days", strtotime(date('Y-m-d H:i:s'))));
                        $nextDate = date('Y-m-d 00:00:00', strtotime("+ ".$basicTrialDays." days", strtotime(date('Y-m-d H:i:s'))));
                    } else {
                        $nextDate1 = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                        $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                    }
                } else if($planAccessType == 'unlimited'){
                    $nextDate = '2099-01-01 00:00:00';
                }
            } else {
                if($planAccessType == 'unlimited'){
                    $nextDate = '2099-01-01 00:00:00';
                } else {
                    $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                }
                //Add Free Plan
                return response()->json(['success' => true,'nextdate'=> $nextDate], $this->successStatus);
            }            
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
        $statusStep = false;
        if($isDummyUser) {
            if($basicTrialDays > 0) {
                $trialPeriod = $basicTrialDays;
            } else {
                $trialPeriod = 30;
            }
            $statusCompany = dummy_registration::where('id', (int)$userID)->update(['subscriptiontype' => $subType,'nextpaymentdate' => $nextDate, 'paymentplan' => (int)$subplan,'plansubtype' => 'paid','stepscompleted' => '3','remaintrial' => $trialPeriod,'status' => 'active']);
        } else {
            $statusCompany = dummy_registration::where('id', (int)$userID)->update(['subscriptiontype' => $subType,'nextpaymentdate' => $nextDate, 'paymentplan' => (int)$subplan,'plansubtype' => 'paid','stepscompleted' => '3']);

        }
        
        //insert temp record to actual tables
        $tempData = dummy_registration::where('id',(int)$userID)->first();
        if(!$isDummyUser) {
           $authData = new Auth;
            $authData->email = $tempData->email;
            $authData->password = $tempData->password;
            $authData->usertype = $tempData->usertype;
            $authData->ipaddress = $tempData->ipaddress;
            $authData->stepscompleted = $tempData->stepscompleted;
            if($tempData->is_social == '1') {
                $authData->is_activated = '1';
            } else {
                $authData->is_activated = '0';
            }
            $authData->is_social = $tempData->is_social;
            $authData->social_id = $tempData->social_id;
            $authData->provider  = $tempData->provider;
            $authData->newsletter  = $tempData->newsletter;
            $authData->status = 'active';
            if($authData->save()) {
                $authid = $authData->id;
                $companyData = new Companydetail;
                $company_slug_new= preg_replace('/[^a-zA-Z0-9_ -]/s','',$tempData->name);     
                $slug = implode("-",explode(" ",$company_slug_new));
                $slug1 = '';
                $array = explode(" ",$tempData->city);
                if(is_array($array)) {
                    $slug1 = implode("-",$array);       
                }
                $slug = strtolower($slug.'-'.$slug1);
                $realSlug = $slug;    
                $companyData->authid = $authid;
                $companyData->name = $tempData->name;
                // Calculate slug
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
                $companyData->slug = $slug;
                $companyData->actualslug = $realSlug;
                $companyData->services = $tempData->services;
                $companyData->address = $tempData->address;
                $companyData->city = $tempData->city;
                $companyData->state = $tempData->state;
                $companyData->country = $tempData->country;
                $companyData->zipcode = $tempData->zipcode;
                $companyData->contact = $tempData->contact;
                $companyData->about = $tempData->about;
                $companyData->businessemail = $tempData->businessemail;
                $companyData->websiteurl = $tempData->websiteurl;
                $companyData->images = $tempData->images;
                $companyData->longitude = $tempData->longitude;
                $companyData->latitude = $tempData->latitude;
                $companyData->nextpaymentdate = $tempData->nextpaymentdate;
                $companyData->customer_id = $tempData->customer_id;
                $companyData->subscription_id = $tempData->subscription_id;
                $companyData->paymentplan = $tempData->paymentplan;
                $companyData->next_paymentplan = $tempData->paymentplan;
                $companyData->plansubtype = $tempData->plansubtype;
                $companyData->subscriptiontype = $tempData->subscriptiontype;
                $companyData->advertisebusiness = '0';
                $companyData->primaryimage = $tempData->primaryimage;
                $companyData->allservices = $tempData->allservices;
                $companyData->contactname = $tempData->contactname;
                $companyData->contactmobile = $tempData->contactmobile;
                $companyData->contactemail = $tempData->contactemail;
                $companyData->status = 'active';
                $companyData->coverphoto = $tempData->coverphoto;
                $companyData->boats_yachts_worked    = $tempData->boats_yachts_worked;
                $companyData->engines_worked    = $tempData->engines_worked;
                if($basicTrialDays > 0) {
                    $companyData->remaintrial    = $basicTrialDays;
                }
                if($tempData->is_claim_user == '1') {
                    $companyData->is_admin_approve = '0';
                    $companyData->is_claimed = '1';
                    $companyData->accounttype = 'claimed';   
                } else {
                    $companyData->is_admin_approve = '1';
                    $companyData->is_claimed = '0';
                    $companyData->accounttype = 'real';
                }
                $dateDiscountCheck = date('2019-12-31 23:59:59');
                $currentDiscountCheck = date('Y-m-d 00:00:00');
                if($currentDiscountCheck < $dateDiscountCheck) {
                    $companyData->is_discount = '1';
                    $companyData->remaindiscount = 12;
                    $companyData->discount = (!empty($plandata->current_discount) ? $plandata->current_discount : 0);
                }
                // $companyData->county = $tempData->county;
                if($companyData->save()) {
                    $DictionaryData = new Dictionary;
                    $DictionaryData->authid = $authid;
                    $DictionaryData->word = $tempData->name;
                    if($DictionaryData->save()) {
                    }
                    $statusStep = true;
                    $rejectedRegistration = dummy_registration::where('id', '=', (int)$userID)->delete();
                    $geosuccess = TRUE;
                    if($tempData->is_social == '0' ) {
                        $random_hashed = Hash::make(str_random(8).$authid);
                        $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                        $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                        $link = $website_url.'/activate?token='.urlencode($random_hashed);
                        $ACTIVATION_LINK = $link;
                        $emailArr = [];                                        
                        $emailArr['link'] = $ACTIVATION_LINK;
                        $emailArr['to_email'] = $tempData->email;
                        $emailArr['name'] = $tempData->name;
                        //Send activation email notification
                        if($tempData->is_claim_user == '1') {
                            $status = $this->sendEmailNotification($emailArr,'claimed_business_notification');
                        } else {
                            $status = $this->sendEmailNotification($emailArr,'business_registration_activation'); 
                        }
                        if($status != 'sent') {
                            return response()->json(['error'=>'emailsentfail'], 401);
                        }
                    } else {
                        $isSocial = true;
                    }
                    // } else {
                    //     $geosuccess = FALSE;    
                    // }
                } else {
                    return response()->json(['error'=>'entryfail'], 401);
                }
            } else {
                return response()->json(['error'=>'entryfail'], 401);
            } 
        } else {
            $geosuccess = TRUE;
            $statusStep = TRUE;
            $statusCompany = TRUE;
            $authid = (int)$userID;
            $random_hashed = Hash::make(str_random(8).$authid);
            $website_url = env('NG_APP_URL','https://www.marinecentral.com');
            $link = $website_url.'/activate?token='.urlencode($random_hashed);
            $ACTIVATION_LINK = $link;
            $emailArr = [];                                        
            $emailArr['link'] = $ACTIVATION_LINK;
            if($tempData->is_social == '0' ) {
                $emailArr['to_email'] = $tempData->email;
            } else {
                $emailArr['to_email'] = $tempData->contactemail;
            }
            $emailArr['name'] = $tempData->name;
            //Send activation email notification
            $status = $this->sendEmailNotification($emailArr,'claimed_business_notification');
            if($status != 'sent') {
                return response()->json(['error'=>'emailsentfail'], 401);
            }
            if($tempData->is_social == '1') {
                $isSocial = true;
            }
        }

        if($statusStep && $statusCompany && $geosuccess) {
            if($isDummyUser) {
                $paymentTable = 'dummy_paymenthistory';
            } else {
                $paymentTable = 'paymenthistory';
            }

            $statusPayment =  DB::table($paymentTable)->insert(
                    ['companyid' => (int)$authid,
                    'transactionid' => request('tranctionID'),
                    'transactionfor' => 'registrationfee',
                    'amount' => $planPrice,
                    'payment_type' =>$plandata->id,
                    'status' => 'pending' ,
                    'customer_id' => $userDetail['customer_id'],
                    'subscription_id' => $userDetail['subscription_id'],
                    'expiredate' => $nextDate,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                    ]);
            if($statusPayment) {
                if($tempData->is_social == '1'  && $tempData->is_claim_user == '0') {
                    $userdata = Auth::where('id', '=', (int)$authid)->get();
                    $user = $userdata[0];
                    $success['type']    = $userdata[0]->usertype;
                    $success['authid']  = encrypt($userdata[0]->id);
                    $success['email']   = $userdata[0]->email;
                    $success['stepscompleted'] = $userdata[0]->stepscompleted;
                    $success['token']   =  $user->createToken('MyApp')->accessToken;
                    return response()->json(['success' => true,'isSocial'=>true,'userid' => request('id'),'nextdate'=> $nextDate,'steps' => '3','data' => $success], $this->successStatus);
                } else {
                    return response()->json(['success' => true,'userid' => encrypt($authid),'nextdate'=> $nextDate,'isSocial' => $isSocial], $this->successStatus);
                }
            } else {
                return response()->json(['error'=>'entryfail'], 401);
            }
        } else {
            return response()->json(['error'=>'entryfail'], 401);
        }
    }


    public function stripeTransactionLead(Request $request) {
        $validate = Validator::make($request->all(), [
            'card_token' => 'required',
            'userID'  => 'required',
            'cardtype' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validation'], 401);
        }
        $paymentFor = 'leadfee';
        $paymenttype = request('qoutepayment');
        if(!empty($paymenttype)) {
            $paymentFor = 'qoutefee';    
        }
        $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
        $rid = request('userID');
        $chargeAmount = env('LEAD_CHARGE',10);
        $decryptUserid = decrypt($rid);
        $isNotCustomerID = true;
        $type = request('cardtype');
        $usersdata = DB::table('companydetails as cp')
            ->Join('auths as ats', 'ats.id', '=', 'cp.authid')
            ->select('cp.name','ats.email','cp.contactmobile','cp.customer_id')
            ->where('cp.authid', '=', (int)$decryptUserid)
            ->first();
        $card_token = request('card_token');
        if($type == 'new') {
            $tokenData = $stripe->tokens()->find($card_token);
            /* Check If user stripe account is already created*/
            if(!isset($tokenData['id']) || $tokenData == '') {
                return response()->json(['error'=>'wrongToken'], 401);
            }    
        } else {
            $card_token = decrypt($card_token);
        }
        
        if(!empty($decryptUserid)) {
            if(!empty($decryptUserid) && $usersdata->customer_id !=null) {
                try {
                    //find customer ID
                    $CheckCustomer = $stripe->customers()->find($usersdata->customer_id);
                    $customerID =  $usersdata->customer_id;
                    $isNotCustomerID = false;
                    if($type == 'old') {
                        $customer = $stripe->customers()->update($usersdata->customer_id, [
                                'default_source' => $card_token
                            ]);
                    } else {
                        $card = $stripe->cards()->create($customerID, $card_token);
                        $customer = $stripe->customers()->update($usersdata->customer_id, [
                                'default_source' => $card['id']
                            ]);
                    }
                } catch(Exception $e) {
                    $isNotCustomerID = true;
                    $customer = $stripe->customers()->create([      //Create a customer account 
                        'source' => $card_token,
                        'email' => $usersdata->email,
                        'metadata' => ['name' => $usersdata->name,'email' => $usersdata->email]
                    ]);
                    $customerID = $customer['id'];
                }
            } else {
               $customer = $stripe->customers()->create([      //Create a customer account 
                        'source' => $card_token,
                        'email' => $usersdata->email,
                        'metadata' => ['name' => $usersdata->name,'email' => $usersdata->email]
                    ]);
                $customerID = $customer['id'];
            }
        }
        if(!empty($usersdata)) {
            try {
            $charge = $stripe->charges()->create([ 
                'customer' => $customerID,
                'currency' => 'USD',
                'amount' => $chargeAmount
                ]);
            if($charge['status'] == 'succeeded') {
                $CompanyDetail = Companydetail::where('authid', '=', (int)$decryptUserid)->get()->first();
                $updateArr = array();
                if(!empty($paymenttype)) {
                    $updateArr['quotes_payment'] = $CompanyDetail->quotes_payment+1;
                } else {
                    $updateArr['lead_payment'] = $CompanyDetail->lead_payment+1;    
                }
                if($isNotCustomerID) {
                    $updateArr['customer_id'] = $customerID;
                }
                $statusCompany = Companydetail::where('authid', (int)$decryptUserid)->update($updateArr);
                    $statusPayment =  DB::table('paymenthistory')->insert(
                            ['companyid' => (int)$decryptUserid,
                            'transactionid' => $charge['balance_transaction'],
                            'transactionfor' => $paymentFor,
                            'amount' => $chargeAmount,
                            'status' => 'approved' ,
                            'customer_id' => $customerID,
                            'expiredate' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                            ]);
                    if($statusPayment) {
                        return response()->json(['success' => true], $this->successStatus);
                    } else {
                        return response()->json(['error'=>'entryfail'], 401);
                    }
                } else {
                    // generate exception
                    return response()->json(['error'=>'paymenterror'], 401);
                }
            } catch (Exception $e) {
                return response()->json(['success' => false,'error'=>$e->getMessage()], $this->successStatus);
            }
        } else {
             return response()->json(['error'=>'networkerror'], 401);
        }  
    }

     public function stripeTransactionPlanDiscount($decryptUserid, $plan, $card_token, $cardHolder,$subType) {
        $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
        $chargeTrs = '';
        if($decryptUserid) {
            if(!empty($plan) && $plan == 'marinepro') {
                $plan = 'pro';
            } else if(!empty($plan) && $plan == 'advanced') {
                $plan = 'advance';
            }
            /*
            $basicTrialDays = 0;
            if(!empty($plan) && $plan == 'basic') {
                    $currentDate = date('Y-m-d 00:00:00');
                    if(env('BASIC_PLAN_UNLIMITED_END') > $currentDate) {
                        $basicTrialDays = 60;
                    }
            }
            */
            $usersdata =dummy_registration::where('id', '=', (int)$decryptUserid)->first();
            if(!empty($usersdata)) {
                try {
                    $customer = $stripe->customers()->create([      //Create a customer account 
                        'source' => $card_token,
                        'email' => $usersdata->email,
                        'metadata' => ['name' => $usersdata->name,'email' => $usersdata->email]
                    ]);
                    if(isset($customer['id'])) {
                        $stripe_id = $customer['id'];
                        $PlanData = DB::table('subscriptionplans')
                        ->select('subscriptionplans.*')
                        ->where('subscriptionplans.planname','ILIKE','%'.$plan.'%')
                        ->where('subscriptionplans.status','=','active')
                        ->where('subscriptionplans.active_status','=','active')
                        ->get();

                        if(!empty($PlanData) && count($PlanData) > 0) {
                            $amount = (int)$PlanData[0]->amount;;
                            $plan_id =$PlanData[0]->stripe_plan_id;
                            /*Old Payment
                            if($amount == 199) {
                                $plan_id ='plan_basic_monthly';
                            } else if($amount == 299) {
                                $plan_id ='plan_advance_monthly';
                            } else if ($amount == 399) {
                                $plan_id ='plan_pro_monthly';
                            }*/

                            /*
                            $date = date('2019-12-31 23:59:59');
                            $current = date('Y-m-d 00:00:00');
                            if($current < $date) {
                                $amountPaid = $PlanData[0]->amount;
                                $discountapply = $PlanData[0]->current_discount;
                                $amountPaid = ceil(($amountPaid * $discountapply)/100);
                                $amountPaid = $amountPaid * 100;
                                $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
                                $coupon = $stripe->coupons()->create([
                                    'duration'    => 'repeating',
                                    'amount_off' => $amountPaid,
                                    'currency'  => 'USD',
                                    'duration_in_months' => 12
                                ]);
                                if(isset($coupon['id'])) {
                                    if($basicTrialDays > 0) {
                                        $subscription = $stripe->subscriptions()->create($stripe_id, [
                                            'plan' => $plan_id,
                                            'coupon' => $coupon['id'],
                                            'trial_end' => strtotime( '+'.$basicTrialDays.' day' ),
                                            'metadata' => ['name' => $cardHolder]
                                        ]);
                                    } else {
                                        $subscription = $stripe->subscriptions()->create($stripe_id, [
                                            'plan' => $plan_id,
                                            'coupon' => $coupon['id'],
                                            'metadata' => ['name' => $cardHolder]
                                        ]);
                                    }
                                } else {
                                    return 'network';
                                }
                            } else {
                                if($basicTrialDays > 0) {
                                    $subscription = $stripe->subscriptions()->create($stripe_id, [
                                        'plan' => $plan_id,
                                        'trial_end' => strtotime( '+'.$basicTrialDays.' day' ),
                                        'metadata' => ['name' => $cardHolder]
                                    ]);
                                } else {
                                    $subscription = $stripe->subscriptions()->create($stripe_id, [
                                        'plan' => $plan_id,
                                        'metadata' => ['name' => $cardHolder]
                                    ]);
                                }
                                
                            }
                            */
                            
                            if($subType == 'manual') {
                                $charge = $stripe->charges()->create([ 
                                    'customer' => $stripe_id,
                                    'currency' => 'USD',
                                    'amount' => $amount
                                    ]);
                                if($charge['status'] == 'succeeded') {
                                    $chargeTrs = $charge['balance_transaction'];
                                } else {
                                    return response()->json(['error'=>'paymenterror'], 401);
                                }
                            } else {
                                $subscription = $stripe->subscriptions()->create($stripe_id, [
                                    'plan' => $plan_id,
                                    'metadata' => ['name' => $cardHolder]
                                ]);    
                            }
                            
                             if((isset($subscription['id']) && $subType== 'automatic') || $subType == 'manual') {
                                if($subType == 'manual') {
                                    $updateArr['subscription_id'] = NULL;    
                                } else {
                                    $updateArr['subscription_id'] = $subscription['id'];
                                }
                                $updateArr['remaintrial'] = 0;
                                $updateArr['remaindiscount'] = 0;
                                /*Old payment
                                if($basicTrialDays > 0) {
                                    $updateArr['remaintrial'] = $basicTrialDays;
                                } else {
                                    $updateArr['remaintrial'] = 30;
                                }
                                */
                                $updateArr['customer_id'] = $customer['id'];
                                $updated =  dummy_registration::where('id', '=', (int)$decryptUserid)->update($updateArr);
                                if ($updated) {
                                    if($chargeTrs != '') {
                                        return ['status' =>'success','chargeTrs' => $chargeTrs];
                                    } else {
                                        return ['status' =>'success'];
                                    }
                                } else {
                                    return 'network';
                                }
                            } else {
                                return 'network';
                            }
                        } else {
                            return 'network';
                        }
                    } else {
                        return 'network';
                    }
                } catch(Exception $e) {
                    return $e->getMessage();
                } catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
                    return $e->getMessage();
                } catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
                    return $e->getMessage();
                }
            } else {
                return 'network';
            }
        } else {
            return 'network';
        }
    }

         //Create Customer account in stripe
     public function companywithoutpaymentDiscount(Request $request){
       // need to set plan month
        $validate = Validator::make($request->all(), [
            'tranctionID' => 'required',
            'subplan' => 'required',
            'userID'  => 'required'
        ]);
        if ($validate->fails()) {
            $success = false;
        }
        
        $renewPlan = request('renewal');
        if($renewPlan == 'true' && $renewPlan != 'null' ) {
            $subType = 'automatic';
        } else {
            $subType = 'manual';
        }
        $useridencrypt = request('userID');
        $userID = decrypt($useridencrypt);
       
        if(empty($userID) || $userID == '') {
            return response()->json(['error'=>'networkerror'], 401); 
        } else {
            $card_token = request('card_token');
            $cardHolder = request('nameoncard');
            $checkEmailExist =  dummy_registration::where('id', '=', (int)$userID)->where('usertype', '=', 'company')->first();
            $checkEmailAddressExist = $checkEmailExist->email;
            if(!empty($checkEmailExist)) {
                $queryChecks = Auth::where('email', '=', $checkEmailAddressExist);
                $countChecks = $queryChecks->where('status', '!=', 'deleted')->where('accounttype','=','real')->count();
                $query2Checks = dummy_registration::where('email', '=', $checkEmailAddressExist);
                $count2Checks = $query2Checks->where('status', '=', 'active')->where('is_claim_user','=','1')->where('usertype', '=','company')->count();
                $countChecks = $countChecks + $count2Checks;
                if(!empty($countChecks) && $countChecks > 0) {
                    return response()->json(['error'=>'emailExist'], 401);
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        }
         /* Get user card Token and Plan*/
        $subplan = request('subplan');
        if(!empty($subplan) && $subplan == 'marinepro') {
            $subplan = 'pro';
        } else if(!empty($subplan) && $subplan == 'advanced') {
            $subplan = 'advance';
        }
        $userDetail = dummy_registration::where('id', '=', (int)$userID)->get()->first()->toArray();
        $isDummyUser = false;
        $email = $userDetail['email'];
       
        $ex_message = '';
        $plandata = DB::table('subscriptionplans')->where('planname','ILIKE','%'.$subplan.'%')
                    ->where('status','=','active')
                    ->first();
        // $plandata = DB::table('subscriptionplans')->where('id', '=', (int)$subplan)->where('status', '=', 'active')->first();
        if(!empty($plandata)) {
            $basicTrialDays = 0;
            if (strpos('Basic', $plandata->planname) !== false) {
                $currentDate = date('Y-m-d 00:00:00');
                if(env('BASIC_PLAN_UNLIMITED_END') > $currentDate) {
                    $basicTrialDays = 60;
                }
            }
            $planPrice = $plandata->amount;
            $planType = $plandata->plantype;
            $planAccessType = $plandata->planaccesstype;
            $planAccessNumber = $plandata->planaccessnumber;
            if($planType =='paid') { 
                if($planAccessType == 'month') {
                    if($basicTrialDays > 0) {
                        $nextDate = date('Y-m-d 00:00:00', strtotime("+ ".$basicTrialDays." days", strtotime(date('Y-m-d H:i:s'))));
                    } else {
                        $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                    }
                   // $nextDate = date('Y-m-d 00:00:00', strtotime("+".$planAccessNumber." months", strtotime($nextDate1)));
                } else if($planAccessType == 'unlimited'){
                    $nextDate = '2099-01-01 00:00:00';
                }
            } else {
                if($planAccessType == 'unlimited'){
                    $nextDate = '2099-01-01 00:00:00';
                } else {
                    $nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
                }
                //Add Free Plan
                return response()->json(['success' => true,'nextdate'=> $nextDate], $this->successStatus);
            }            
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
        $statusStep = false;
        $statusCompany = dummy_registration::where('id', (int)$userID)->update(['subscriptiontype' => $subType,'nextpaymentdate' => $nextDate, 'paymentplan' => $plandata->id,'plansubtype' => 'paid','stepscompleted' => '3']);

        //insert temp record to actual tables
        $tempData = dummy_registration::where('id',(int)$userID)->first();
        //if(!$isDummyUser) {
        $getRNDpassword = $this->randomString();
        $authData = new Auth;
        $authData->email = $tempData->email;
        $authData->password =  Hash::make($getRNDpassword);
        $authData->usertype = $tempData->usertype;
        $authData->ipaddress = $tempData->ipaddress;
        $authData->stepscompleted = $tempData->stepscompleted;
        //~ if($tempData->is_social == '1') {
            //~ $authData->is_activated = '1';
        //~ } else {
        $authData->is_activated = '0';
        //~ }
        $authData->is_social = $tempData->is_social;
        $authData->social_id = $tempData->social_id;
        $authData->provider  = $tempData->provider;
        $authData->status = 'active';
        if($authData->save()) {
            $authid = $authData->id;
            $companyData = new Companydetail;
            $company_slug_new= preg_replace('/[^a-zA-Z0-9_ -]/s','',$tempData->name);     
            $slug = implode("-",explode(" ",$company_slug_new));
            $slug1 = '';
            $array = explode(" ",$tempData->city);
            if(is_array($array)) {
                $slug1 = implode("-",$array);       
            }
            $slug = strtolower($slug.'-'.$slug1);
            $realSlug = $slug;    
            $companyData->authid = $authid;
            $companyData->name = $tempData->name;
            // Calculate slug
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
            $discountData = DB::table('discounts')->where('paymentplan',(int)$plandata->id)
                    ->first();
            $companyData->slug = $slug;
            $companyData->actualslug = $realSlug;
            $companyData->services = $tempData->services;
            $companyData->address = $tempData->address;
            $companyData->city = $tempData->city;
            $companyData->state = $tempData->state;
            $companyData->country = $tempData->country;
            $companyData->zipcode = $tempData->zipcode;
            $companyData->contact = $tempData->contact;
            $companyData->about = $tempData->about;
            $companyData->businessemail = $tempData->businessemail;
            $companyData->websiteurl = $tempData->websiteurl;
            $companyData->images = $tempData->images;
            $companyData->longitude = $tempData->longitude;
            $companyData->latitude = $tempData->latitude;
            $companyData->nextpaymentdate = $tempData->nextpaymentdate;
            $companyData->customer_id = $tempData->customer_id;
            $companyData->subscription_id = $tempData->subscription_id;
            $companyData->paymentplan = $tempData->paymentplan;
            $companyData->next_paymentplan = $tempData->paymentplan;
            $companyData->plansubtype = $tempData->plansubtype;
            $companyData->subscriptiontype = $tempData->subscriptiontype;
            $companyData->advertisebusiness = '0';
            $companyData->primaryimage = $tempData->primaryimage;
            $companyData->allservices = $tempData->allservices;
            $companyData->contactname = $tempData->contactname;
            $companyData->contactmobile = $tempData->contactmobile;
            $companyData->contactemail = $tempData->contactemail;
            if($basicTrialDays > 0) {
                $companyData->remaintrial = $basicTrialDays;
            } else {
                $companyData->remaintrial = 30;
            }
            $companyData->status = 'active';
            $companyData->coverphoto = $tempData->coverphoto;
            $companyData->boats_yachts_worked    = $tempData->boats_yachts_worked;
            $companyData->engines_worked    = $tempData->engines_worked;
            $companyData->is_admin_approve = '1';
            $companyData->is_claimed = '0';
            $companyData->accounttype = 'real';
            $dateDiscountCheck = date('2019-12-31 23:59:59');
            $currentDiscountCheck = date('Y-m-d 00:00:00');
            if($currentDiscountCheck < $dateDiscountCheck) {
                $companyData->is_discount = '1';
                $companyData->remaindiscount = 12;
                $companyData->discount = (!empty($discountData->current_discount) ? $discountData->current_discount : 0);
            } else {
                $companyData->remaindiscount = 0;
            }
            // $companyData->county = $tempData->county;
            if($companyData->save()) {
                $DictionaryData = new Dictionary;
                $DictionaryData->authid = $authid;
                $DictionaryData->word = $tempData->name;
                if($DictionaryData->save()) {
                }
                $statusStep = true;
                
                $rejectedRegistration = dummy_registration::where('id', '=', (int)$userID)->delete();
                $geosuccess = TRUE;
                if($tempData->is_social == '0' ) {
                    $random_hashed = Hash::make(str_random(8).$authid);
                    $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $random_hashed]);
                    $website_url = env('NG_APP_URL','https://www.marinecentral.com');
                    $link = $website_url.'/activate?token='.urlencode($random_hashed);
                    $ACTIVATION_LINK = $link;
                    $emailArr = [];                                        
                    $emailArr['link'] = $ACTIVATION_LINK;
                    /*temp otp
                     $ACTIVATION_OTP=rand(10000,99999);
                     $updateHash = Auth::where('id','=',$authid)->update(['activation_hash' => $ACTIVATION_OTP]);
                     $emailArr['otp'] = $ACTIVATION_OTP;
                     */
                    $emailArr['to_email'] = $tempData->email;
                    $emailArr['name'] = $tempData->name;
                    $emailArr['password'] = $getRNDpassword;
                    $emailArr['logEmail'] = $tempData->email;
                        //Send activation email notification
                        //~ if($tempData->is_claim_user == '1') {
                            //~ $status = $this->sendEmailNotification($emailArr,'claimed_business_notification');
                        //~ } else {
                    $status = $this->sendEmailNotification($emailArr,'business_registration_activation_discount'); 
                    //~ }
                    if($status != 'sent') {
                        return response()->json(['error'=>'emailsentfail'], 401);
                    }
                } else {
                    $isSocial = true;
                }
                // } else {
                //     $geosuccess = FALSE;    
                // }
            } else {
                return response()->json(['error'=>'entryfail'], 401);
            }
        } else {
            return response()->json(['error'=>'entryfail'], 401);
        } 
        //} 

        if($statusStep && $statusCompany && $geosuccess) {
            $paymentTable = 'paymenthistory';
            
            $statusPayment =  DB::table($paymentTable)->insert(
                    ['companyid' => (int)$authid,
                    'transactionid' => '',
                    'transactionfor' => 'registrationfee',
                    'amount' => $planPrice,
                    'payment_type' =>$plandata->id,
                    'status' => 'pending' ,
                    'customer_id' => $userDetail['customer_id'],
                    'subscription_id' => $userDetail['subscription_id'],
                    'expiredate' => $nextDate,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                    ]);
            if($statusPayment) {
                //~ if($tempData->is_social == '1'  && $tempData->is_claim_user == '0') {
                    //~ $userdata = Auth::where('id', '=', (int)$authid)->get();
                    //~ $user = $userdata[0];
                    //~ $success['type']    = $userdata[0]->usertype;
                    //~ $success['authid']  = encrypt($userdata[0]->id);
                    //~ $success['email']   = $userdata[0]->email;
                    //~ $success['stepscompleted'] = $userdata[0]->stepscompleted;
                    //~ $success['token']   =  $user->createToken('MyApp')->accessToken;
                    //~ return response()->json(['success' => true,'isSocial'=>true,'userid' => request('id'),'nextdate'=> $nextDate,'steps' => '3','data' => $success], $this->successStatus);
                //~ } else {
                $isSocial = false;
                return response()->json(['success' => true,'userid' => encrypt($authid),'nextdate'=> $nextDate,'isSocial' => $isSocial], $this->successStatus);
                //~ }
            } else {
                return response()->json(['error'=>'entryfail'], 401);
            }
        } else {
            return response()->json(['error'=>'entryfail'], 401);
        }
    }
}
