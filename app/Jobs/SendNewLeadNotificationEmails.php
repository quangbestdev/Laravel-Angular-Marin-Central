<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Traits\NotificationTrait;
use App\notifications_error_logs;

class SendNewLeadNotificationEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use NotificationTrait;
    protected $emailArr;
    protected $templateName;
    public $tries = 2;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($emailInfo,$template)
    {
        $this->emailArr = $emailInfo;
        $this->templateName = $template;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {   
         $emailstatus = $this->sendEmailNotification($this->emailArr,$this->templateName); 
    }
    public function failed(Exception $exception)
    {
        $error_logs = new notifications_error_logs;
        $error_logs->notification_to = $this->emailArr['to_email'];
        $error_logs->errortype = 'email';
        $error_logs->messages = $exception->messages();
        $error_logs->save();    
    }
}
