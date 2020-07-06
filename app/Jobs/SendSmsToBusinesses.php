<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\notifications_error_logs;
// use App\Http\Traits\NotificationTrait;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
class SendSmsToBusinesses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    // use NotificationTrait;
    protected $sms;
    protected $tonumber;
    protected $notification_type;
    protected $notification_from;
    protected $notification_to;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($sms,$tonumber,$notification_type,$notification_from,$notification_to)
    {
        $this->sms = $sms;
        $this->tonumber = $tonumber;
        $this->notification_type = $notification_type;
        $this->notification_from = $notification_from;
        $this->notification_to = $notification_to;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
         // $this->sendSms($this->sms,$this->tonumber,$this->notification_type,$this->notification_from,$this->notification_to);
        $account_sid = getenv('TWILIO_ACCOUNT_SID');
        $auth_token = getenv('TWILIO_AUTH_TOKEN');
        $twilio_number = getenv('TWILO_PHONE_NUMBER');
        $client = new Client($account_sid, $auth_token);
        try {
           $message = $client->messages->create(
                $this->tonumber,
                array(
                    'from' => $twilio_number,
                    'body' => $this->sms
                )
            );
            return $message->sid;
        } catch (TwilioException $e) {
          $msg = $e->getMessage();
          $error_logs = new notifications_error_logs;
          $error_logs->notification_to = $this->notification_to;
          $error_logs->notification_from = $this->notification_from;
          $error_logs->errortype = 'sms';
          $error_logs->notification_type = $this->notification_type;
          $error_logs->messages = $msg.' Phone Number'.$this->tonumber;
          $error_logs->save();
        }
    }
}
