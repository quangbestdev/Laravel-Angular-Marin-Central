<?php

namespace App\Console\Commands;
use DB;
use Illuminate\Http\Request;
use App\Companydetail;
use App\Http\Traits\NotificationTrait;
use Illuminate\Console\Command;


class sendSubscriptionEndAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    use NotificationTrait;
    protected $signature = 'sendSubscriptionEndAlert:smsalert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
     
    public function handle()
    {
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
		$website_url = 'https://www.marinecentral.com';
		$link = $website_url.'/login';
		$emailArr['link'] = $link;
		$status = $this->sendEmailNotification($emailArr,'subscription_reminder');
		return $status;
	} 
}
