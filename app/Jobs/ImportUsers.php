<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Traits\ImportTrait;
class ImportUsers implements ShouldQueue
{
   
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,ImportTrait;
    protected $csvData;
    protected $adminId;
    protected $type;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($csvData,$adminId,$type)
    {   
        $this->type = $type;
        $this->csvData = $csvData;
        $this->adminId = $adminId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {   if($this->type == 'regular') {
            $this->Importdata($this->csvData,$this->adminId); 
        } else if($this->type == 'company') {
            $this->importDataBusiness($this->csvData,$this->adminId);            
        } else if($this->type == 'dummy_company') {
            $this->importDataDummyBusiness($this->csvData,$this->adminId);            
        } else if($this->type == 'yacht') {
            $this->importYacht($this->csvData,$this->adminId);            
        } else if($this->type == 'professional'){
            $this->importProfessional($this->csvData,$this->adminId);
        }
    }
}
