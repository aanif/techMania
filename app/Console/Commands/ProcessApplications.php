<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessNbnApplications;

class ProcessApplications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-applications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will process the application according to requirements in task 2';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ProcessNbnApplications::dispatch();
    }
}
