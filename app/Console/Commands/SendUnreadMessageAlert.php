<?php

namespace App\Console\Commands;
use DB;

use Illuminate\Console\Command;
use App\Http\Traits\NotificationTrait;
use App\Messages;

class SendUnreadMessageAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    use NotificationTrait;
    protected $signature = 'SendUnreadMessageAlert:messagealert';

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
        //
    }
}
