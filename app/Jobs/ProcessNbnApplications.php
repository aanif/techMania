<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Application;
use Illuminate\Support\Facades\Http;

class ProcessNbnApplications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Retrieve NBN applications with the status 'order'
        $nbnApplications = Application::where('status', 'order')
        ->whereHas('plan', function ($query) {
            $query->where('type', 'nbn');
        })
        ->get();
        foreach ($nbnApplications as $application) {
            // Make the HTTP POST request to the B2B endpoint
            $response = Http::post(env('NBN_B2B_ENDPOINT'), [
                'address_1' => $application->address_1,
                'address_2' => $application->address_2,
                'city' => $application->city,
                'state' => $application->state,
                'postcode' => $application->postcode,
                'plan_name' => $application->plan->name,
            ]);
            if ($response->successful()) {
                // Order successful, store the Order Id and update status
                $application->order_id = $response['id'];
                $application->status = 'complete';
            } else {
                // Order failed or error occurred
                $application->status = 'order_failed';
            }
            $application->save();
        }
    }
}
