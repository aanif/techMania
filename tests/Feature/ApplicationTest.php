<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Application;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function testListApplicationsWithPlanTypeFilter(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Create application records
        $applications = Application::factory(25)->create();
        $nbnApplications = Application::factory(8)->create(['plan_id' => function () {
            return Plan::factory()->create(['type' => 'nbn'])->id;
        }]);
        $opticommApplications = Application::factory(8)->create(['plan_id' => function () {
            return Plan::factory()->create(['type' => 'opticomm'])->id;
        }]);

        // Make API request with plan type filter and authentication
        $response = $this->actingAs($user)->get('/api/applications?plan_type=nbn');
        
        // Assert response status is 200 (OK)
        $response->assertStatus(200);

        // Assert the returned applications have the correct plan type
        $responseData = $response->json('data.data');
        foreach ($responseData as $application) {
            $this->assertEquals('nbn', $application['plan_type']);
        }
    }

    public function testOrderNbnApplications(): void
    {
        // Create NBN applications with 'order' status
        $nbnPlan = Plan::where('type', 'nbn')->first();

        if (!$nbnPlan) {
            $nbnPlan = Plan::factory()->create([
                'type' => 'nbn',
            ]);
        }
        
        $nbnApplications = Application::factory()
            ->count(5)
            ->state(function (array $attributes) use ($nbnPlan) {
                return [
                    'status' => 'order',
                    'plan_id' => $nbnPlan->id,
                ];
            })
            ->create();

        // Mock the HTTP POST request to the B2B endpoint
        Http::fake([
            env('NBN_B2B_ENDPOINT') => Http::response(['order_id' => 'ORD123456'], 200)
        ]);

        // Dispatch the artisan command to process NBN applications
        $this->artisan('app:process-applications');

        // Assert that each NBN application has been updated with order_id and status 'complete'
        foreach ($nbnApplications as $application) {
            $application->refresh();
            $this->assertEquals('complete', $application->status->value);
        }
    }
}
