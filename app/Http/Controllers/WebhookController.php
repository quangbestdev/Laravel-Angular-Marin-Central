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

class WebhookController extends Controller
{
    public $successStatus = 200;
    //public $customer_id_env = '65237887';
   
    public function __construct(Request $request) {
    }
    public function subcription_webhook(Request $request) {
		//$stripe = Stripe::make(config()->get('services')['stripe']['secret']);
		$payload = json_decode($request->getContent(), true);
		$eventId = $payload['id'];
		$chargeData = $payload;
		
		$currentDate = date('Y-m-d H:i:s');
		if(!empty($chargeData)) {
			$subscription_id = $chargeData['data']['object']['subscription'];
			$type = str_replace('.', '_', $payload['type']);
			
			$webhook  = new Webhook;     
			$webhook->content = $request->getContent();
			$webhook->subscription_id = $subscription_id;
			$webhook->kind = $type;
			$authid = 0;
			if($webhook->save()) {
				$paymentHistoryData = DB::table('paymenthistory')->where('subscription_id','=',$subscription_id)->where('transactionfor','registrationfee')->orderBy('created_at','DESC')->get();
				$companyDetail = DB::table('companydetails')->where('subscription_id', '=', $subscription_id)->first();
				if( $type == 'invoice_payment_succeeded'){
					$transaction =  $chargeData['data']['object']['id'];
					$amountPaid =  (int)(($chargeData['data']['object']['amount_paid'])/100);
					if($amountPaid > 0) {
					//$companyDetail = Companydetail::where('subscription_id',$subscription_id)->first();
						$webhook->transaction = $transaction;
						$webhook->amount = $amountPaid;
						$isInsert = false;
						if($webhook->save()) {
							if(!empty($paymentHistoryData) && count($paymentHistoryData) > 0 && $paymentHistoryData[0]->status == 'pending') {
								$planaccessType = DB::table('companydetails')->Join('subscriptionplans','companydetails.paymentplan','subscriptionplans.id')->select('planaccesstype')->where('authid',(int)$paymentHistoryData[0]->companyid)->first();
								if($planaccessType->planaccesstype == 'month') {
									$nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
								} else {
									$nextDate = date('Y-m-d 00:00:00', strtotime("+ 365 days", strtotime(date('Y-m-d H:i:s'))));
								}
								$dataUpdate['status'] = 'approved';
								$dataUpdate['expiredate'] = $nextDate;
								$dataUpdate['transactionid'] = $transaction;
								$dataUpdate['amount'] = $amountPaid;
								$id =  $paymentHistoryData[0]->id;
								$update = DB::table('paymenthistory')->where('id','=',(int)$id)->update($dataUpdate);
								$authid = (int)$paymentHistoryData[0]->companyid;
								//~ $dataUpdateComp['nextpaymentdate'] = $nextDate;
								//~ $updateComp = Companydetail::where('authid','=',(int)$paymentHistoryData[0]->companyid)->update($dataUpdateComp);
							} else {

								//$companyDetails = Companydetail::where('subscription_id',$subscription_id)->first();
								if(!empty($companyDetail)) {
									$isInsert = true;
									$authid = (int)$companyDetail->authid;
								}
							}
							if($authid > 0) {
								$dataUpdateComp = [];
								$isDiscount = false;
								/*Old Payment
								$dateDiscountCheck = date('2019-12-31 23:59:59');
								$currentDiscountCheck = date('Y-m-d 00:00:00');
								$isDiscount = false;
								if($currentDiscountCheck < $dateDiscountCheck) {
									$isDiscount = true;
								} else {
									if(!empty($companyDetail->next_paymentplan) && $companyDetail->next_paymentplan != null && ((int)$companyDetail->next_paymentplan == (int)$companyDetail->paymentplan) && $companyDetail->remaindiscount > 0) {
										$isDiscount = true;
									} else {
										$isDiscount = false;
									}
								}
								*/
								if($isDiscount) {
									$dataUpdateComp['remaindiscount'] = (int)$companyDetail->remaindiscount - 1;
									$dataUpdateComp['discount'] = 50;
									$dataUpdateComp['is_discount'] = '1';
								} else {
									$dataUpdateComp['remaindiscount'] = 0;
									$dataUpdateComp['discount'] = 0;
									$dataUpdateComp['is_discount'] = '0';
								}
								if($isDiscount) {
									$checkAmount = (int)(($amountPaid*2)+1);
								} else {
									$checkAmount = (int)$amountPaid;
								}
								if($isInsert) {
									$amountPlanType = 0;
									$paymentplanData = DB::table('subscriptionplans')->where('amount',$checkAmount)->get();
									if(!empty($paymentplanData) && count($paymentplanData) > 0) {
										$amountPlanType = (int)$paymentplanData[0]->id;
									}
									$planaccessType = DB::table('companydetails')->Join('subscriptionplans','companydetails.paymentplan','subscriptionplans.id')->select('planaccesstype')->where('authid',(int)$companyDetail->authid)->first();
									if($planaccessType->planaccesstype == 'month') {
										$nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
									} else {
										$nextDate = date('Y-m-d 00:00:00', strtotime("+ 365 days", strtotime(date('Y-m-d H:i:s'))));
									}
									$statusPayment =  DB::table('paymenthistory')->insert(
										['companyid' => (int)$companyDetail->authid,
										'transactionid' => $transaction,
										'transactionfor' => 'registrationfee',
										'amount' => $amountPaid,
										'payment_type' =>$amountPlanType,
										'status' => 'approved' ,
										'customer_id' => $companyDetail->customer_id,
										'subscription_id' => $subscription_id,
										'expiredate' => $nextDate,
										'created_at' => date('Y-m-d H:i:s'),
										'updated_at' => date('Y-m-d H:i:s')
										]);
								}
								$dataUpdateComp['lastpaymentdate'] = date('Y-m-d H:i:s');
								$dataUpdateComp['paymentplan'] = $companyDetail->next_paymentplan;
								$dataUpdateComp['remaintrial'] = 0;
								$dataUpdateComp['nextpaymentdate'] = $nextDate;
								$updateComp = Companydetail::where('authid','=',(int)$companyDetail->authid)->update($dataUpdateComp);
							}
							$paymentStatus = '';
							if(!empty($companyDetail)) {
								$paymentStatus = $companyDetail->subscriptiontype;
							}
							if($paymentStatus == 'manual') {
								 $subscription = $stripe->subscriptions()->cancel($chargeData['data']['object']['customer'],$subscription_id);
							}
						}
					}
				} else if( $type == 'invoice_payment_failed') {
					if($webhook->save()) {
						if(!empty($companyDetail)) {
							$dataUpdateComp['nextpaymentdate'] = $currentDate;
							$dataUpdateComphis = [];
							$dataUpdateComphis['expiredate'] = $currentDate;
							$updateComp = Companydetail::where('subscription_id','=',$companyDetail->subscription_id)->update($dataUpdateComp);
							$updateCompHis = DB::table('paymenthistory')->where('subscription_id','=',$companyDetail->subscription_id)->update($dataUpdateComphis);
						}
					}
				}
			}
		}   

    }
	// public function subcription_webhook(Request $request) {
	// 	$nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
	// 	$currentDate = date('Y-m-d H:i:s');
	// 	$signature = request('bt_signature');
	// 	$payload = request('bt_payload');
	// 	$dataWebHook = Braintree_WebhookNotification::parse($signature,$payload);
	// 	$subscription_id = $dataWebHook->subject['subscription']['id'];
	// 	$putContentData = $dataWebHook->kind.'/////'.$subscription_id;
		
	// 	$webhook  = new Webhook;     
	// 	$webhook->content = $putContentData;
	// 	$webhook->subscription_id = $subscription_id;
	// 	$webhook->kind = $dataWebHook->kind;
	// 	$authid = 0;
	// 	//if($webhook->save()) {
	// 		$paymentHistoryData = DB::table('paymenthistory')->where('subscription_id','=',$subscription_id)->where('transactionfor','registrationfee')->orderBy('created_at','DESC')->get();
	// 		$companyDetail = DB::table('companydetails')->where('subscription_id', '=', $subscription_id)->first();
	// 		if(strtoupper($dataWebHook->kind) == 'SUBSCRIPTION_CHARGED_SUCCESSFULLY'){
	// 			$transaction =  $dataWebHook->subject['subscription']['transactions'][0]['id'];
	// 			$amountPaid =  $dataWebHook->subject['subscription']['transactions'][0]['amount'];
	// 			//$companyDetail = Companydetail::where('subscription_id',$subscription_id)->first();
	// 			$webhook->transaction = $transaction;
	// 			$webhook->amount = $amountPaid;
	// 			$isInsert = false;
	// 			if($webhook->save()) {
	// 				if(!empty($paymentHistoryData) && count($paymentHistoryData) > 0 && $paymentHistoryData[0]->status == 'pending') {
						
	// 					$dataUpdate['status'] = 'approved';
	// 					$dataUpdate['expiredate'] = $nextDate;
	// 					$dataUpdate['transactionid'] = $transaction;
	// 					$dataUpdate['amount'] = $amountPaid;
	// 					$id =  $paymentHistoryData[0]->id;
	// 					$update = DB::table('paymenthistory')->where('id','=',(int)$id)->update($dataUpdate);
	// 					$authid = (int)$paymentHistoryData[0]->companyid;
	// 					//~ $dataUpdateComp['nextpaymentdate'] = $nextDate;
	// 					//~ $updateComp = Companydetail::where('authid','=',(int)$paymentHistoryData[0]->companyid)->update($dataUpdateComp);
	// 				} else {

	// 					//$companyDetails = Companydetail::where('subscription_id',$subscription_id)->first();
	// 					if(!empty($companyDetail)) {
	// 						$isInsert = true;
	// 						$authid = (int)$companyDetail->authid;
	// 					}
	// 				}
	// 				if($authid > 0) {
	// 					$dataUpdateComp = [];
	// 					$dateDiscountCheck = date('2019-12-31 23:59:59');
	// 					$currentDiscountCheck = date('Y-m-d 00:00:00');
	// 					$isDiscount = false;
	// 					if($currentDiscountCheck < $dateDiscountCheck) {
	// 						$isDiscount = true;
	// 					} else {
	// 						if(!empty($companyDetail->next_paymentplan) && $companyDetail->next_paymentplan != null && ((int)$companyDetail->next_paymentplan == (int)$companyDetail->paymentplan) && $companyDetail->remaindiscount > 0) {
	// 							$isDiscount = true;
	// 						} else {
	// 							$isDiscount = false;
	// 						}
	// 					}
	// 					if($isDiscount) {
	// 						$dataUpdateComp['remaindiscount'] = (int)$companyDetail->remaindiscount - 1;
	// 					} else {
	// 						$dataUpdateComp['remaindiscount'] = 0;
	// 						$dataUpdateComp['discount'] = 0;
	// 						$dataUpdateComp['is_discount'] = '0';
	// 					}
	// 					if($isDiscount) {
	// 						$checkAmount = (int)(($amountPaid*2)+1);
	// 					} else {
	// 						$checkAmount = (int)$amountPaid;
	// 					}
	// 					if($isInsert) {
	// 						$amountPlanType = 0;
	// 						$paymentplanData = DB::table('subscriptionplans')->where('amount',$checkAmount)->get();
	// 						if(!empty($paymentplanData) && count($paymentplanData) > 0) {
	// 							$amountPlanType = (int)$paymentplanData[0]->id;
	// 						}
	// 						$statusPayment =  DB::table('paymenthistory')->insert(
	// 							['companyid' => (int)$companyDetail->authid,
	// 							'transactionid' => $transaction,
	// 							'transactionfor' => 'registrationfee',
	// 							'amount' => $amountPaid,
	// 							'payment_type' =>$amountPlanType,
	// 							'status' => 'approved' ,
	// 							'customer_id' => $companyDetail->customer_id,
	// 							'subscription_id' => $subscription_id,
	// 							'expiredate' => $nextDate,
	// 							'created_at' => date('Y-m-d H:i:s'),
	// 							'updated_at' => date('Y-m-d H:i:s')
	// 							]);
	// 					}
	// 					$dataUpdateComp['lastpaymentdate'] = date('Y-m-d H:i:s');
	// 					$dataUpdateComp['paymentplan'] = $companyDetail->next_paymentplan;
	// 					$dataUpdateComp['remaintrial'] = 0;
	// 					$dataUpdateComp['nextpaymentdate'] = $nextDate;
	// 					$updateComp = Companydetail::where('authid','=',(int)$companyDetail->authid)->update($dataUpdateComp);
	// 				}
	// 				$paymentStatus = '';
	// 				if(!empty($companyDetail)) {
	// 					$paymentStatus = $companyDetail->subscriptiontype;
	// 				}
	// 				if($paymentStatus == 'manual') {
	// 					Braintree_Subscription::cancel($subscription_id);
	// 				}
	// 			}
	// 		} else if(strtoupper($dataWebHook->kind) == 'SUBSCRIPTION_CHARGED_UNSUCCESSFULLY') {
	// 			if($webhook->save()) {
	// 				if(!empty($companyDetail)) {
	// 					$dataUpdateComp['nextpaymentdate'] = $currentDate;
	// 					$dataUpdateComphis = [];
	// 					$dataUpdateComphis['expiredate'] = $currentDate;
	// 					$updateComp = Companydetail::where('subscription_id','=',$companyDetail->subscription_id)->update($dataUpdateComp);
	// 					$updateCompHis = DB::table('paymenthistory')->where('subscription_id','=',$companyDetail->subscription_id)->update($dataUpdateComphis);
	// 				}
	// 			}
	// 		}
	// 	//}   
	// }
	public function subcription_webhook123(Request $request) {
		$sampleNotification = Braintree_WebhookTesting::sampleNotification('subscription_charged_successfully', 'cx3pf6');
		//$notification = Braintree_WebhookTesting::sampleNotification($sampleNotification['bt_signature'], $sampleNotification['bt_payload']);
		
		$nextDate = date('Y-m-d 00:00:00', strtotime("+ 30 days", strtotime(date('Y-m-d H:i:s'))));
		$currentDate = date('Y-m-d H:i:s');
		$signature = $sampleNotification['bt_signature'];
		$payload = $sampleNotification['bt_payload'];
		//$dataWebHook = 'testt_///'.$signature.'_/////'.$payload;
		
		$dataWebHook = Braintree_WebhookNotification::parse($signature,$payload);
		$transaction =  $dataWebHook->subject['subscription']['transactions'][0]['id'];
		echo "<pre>";print_r($dataWebHook);
		$subscription_id = $dataWebHook->subject['subscription']['id'];
		$putContentData = $dataWebHook->kind.'/////'.$subscription_id;
		$test = json_encode(json_decode(json_encode($dataWebHook), True));
		$webhook  = new Webhook;     
		$webhook->content = $test;
		if($webhook->save()) {
			$paymentHistoryData = DB::table('paymenthistory')->where('subscription_id','=',$subscription_id)->where('transactionfor','registrationfee')->orderBy('created_at','DESC')->get();
			$companyDetail = DB::table('companydetails as cmp')
				->Join('subscriptionplans as sub','sub.id','=','cmp.paymentplan')
                ->where('cmp.subscription_id', '=', $subscription_id)
                ->select('cmp.*','sub.amount as planamount')
                ->first();
			if(strtoupper($dataWebHook->kind) == 'SUBSCRIPTION_CHARGED_SUCCESSFULLY'){
				$transaction =  $dataWebHook->subject['subscription']['transactions'][0]['id'];
				//$companyDetail = Companydetail::where('subscription_id',$subscription_id)->first();
				
				if(!empty($paymentHistoryData) && count($paymentHistoryData) > 0 && $paymentHistoryData[0]->status == 'pending') {
					$dataUpdate['status'] = 'approved';
					$dataUpdate['expiredate'] = $nextDate;
					$dataUpdate['transactionid'] = $transaction;
					$id =  $paymentHistoryData[0]->id;
					$update = DB::table('paymenthistory')->where('id','=',(int)$id)->update($dataUpdate);
					$dataUpdateComp['nextpaymentdate'] = $nextDate;
					$updateComp = Companydetail::where('authid','=',(int)$paymentHistoryData[0]->companyid)->update($dataUpdateComp);
				} else {
					//$companyDetails = Companydetail::where('subscription_id',$subscription_id)->first();
					if(!empty($companyDetail)) {
						$statusPayment =  DB::table('paymenthistory')->insert(
							['companyid' => (int)$companyDetail->authid,
							'transactionid' => $transaction,
							'transactionfor' => 'registrationfee',
							'amount' => $companyDetail->planamount,
							'payment_type' =>$companyDetail->paymentplan,
							'status' => 'approved' ,
							'customer_id' => $companyDetail->customer_id,
							'subscription_id' => $subscription_id,
							'expiredate' => $nextDate,
							'created_at' => date('Y-m-d H:i:s'),
							'updated_at' => date('Y-m-d H:i:s')
							]);
						$dataUpdateComp['nextpaymentdate'] = $nextDate;
						$updateComp = Companydetail::where('authid','=',(int)$companyDetail->authid)->update($dataUpdateComp);
					}
				}
				$paymentStatus = '';
				if(!empty($companyDetail)) {
					$paymentStatus = $companyDetail->subscriptiontype;
				}
				if($paymentStatus == 'manual') {
					Braintree_Subscription::cancel($subscription_id);
				}
			} else if(strtoupper($dataWebHook->kind) == 'SUBSCRIPTION_CHARGED_UNSUCCESSFULLY') {
				if(!empty($companyDetail)) {
					$dataUpdateComp['nextpaymentdate'] = $currentDate;
					$updateComp = Companydetail::where('subscription_id','=',$companyDetail->subscription_id)->update($dataUpdateComp);
				}
			}
		}   
		//~ $result = Braintree_Customer::find('65237886_02_34');
		//~ //echo "<pre>";print_r($result);die;
		//~ $token = '';
		//~ if(!empty($result)) {
			//~ for($i = 0 ; $i < count($result->paymentMethods) ;$i++) {
				//~ if($result->paymentMethods[$i]->default) {
					//~ $token = $result->paymentMethods[$i]->token;
				//~ }
			//~ }
		//~ }
		//~ $result1 = Braintree_Subscription::create(['paymentMethodToken'=> $token,'planId'=>'plan_99_monthly']);
		//~ Braintree_Subscription::cancel($result->subscription->id);
		//~ echo $token;die;
		
		//~ echo $result->paymentMethods[0]->token;
		//~ //$paymentMethod =Braintree_PaymentMethodNonce::create($result->paymentMethods[0]->token);
		//~ //$nounce = $paymentMethod->paymentMethodNonce->nonce;
		//~ //echo $nounce;
		//~ //payment_method = gateway.payment_method.find("token")
		//~ //$payment_method = Braintree_PaymentMethod::create(['paymentMethodNonce'=>$nounce,'customerId' => '65237886_02_34']);
				
		
		//~ $result1 = Braintree_Subscription::create(['paymentMethodToken'=> $result->paymentMethods[0]->token,'planId'=>'plan_99_monthly']);
					
		//~ echo "<pre>";print_r($result1);die;
		//default
		//~ $clientToken = $gateway->clientToken()->generate([
			//~ "customerId" => $aCustomerId
		//~ ]);
		
	}
}
