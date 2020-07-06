<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Traits\NotificationTrait;
class SaveNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use NotificationTrait;
    protected $userId;
    protected $usertype; 
    protected $service_requests;
    protected $reviews;
    protected $jobs;
    protected $request_id;
    protected $notificationDate;
    protected $reviewType;
    protected $is_read;
    protected $service_action;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId,$usertype,$service_requests,$reviews,$jobs,$request_id,$notificationDate,$reviewType=null,$is_read=null,$service_action=null)
    {
        $this->userId = $userId;
        $this->usertype = $usertype;
        $this->service_requests = $service_requests;
        $this->reviews = $reviews;
        $this->jobs = $jobs;
        $this->request_id = $request_id;
        $this->notificationDate = $notificationDate;
        $this->reviewType = $reviewType;
        $this->service_action = $service_action;
        $this->is_read = $is_read;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->addNotification($this->userId,$this->usertype,$this->service_requests,$this->reviews,$this->jobs,$this->request_id,$this->notificationDate,$this->reviewType,$this->is_read,$this->service_action);
    }
}
