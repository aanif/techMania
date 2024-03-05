<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class ApplicationController extends Controller
{
    public function getApplications(Request $request)
    {
        $query = Application::query();

        // Apply plan type filter if provided
        $planType = $request->query('plan_type');
        if ($planType && in_array($planType, ['null', 'nbn', 'opticomm', 'mobile'])) {
            $query->whereHas('plan', function ($query) use ($planType) {
                $query->where('type', $planType);
            });
        }

        // Sort applications by creation date (oldest first)
        $query->orderBy('created_at', 'asc');

        // Retrieve paginated results
        $perPage = $request->query('limit', 10);
        $applications = $query->paginate($perPage);

        // Format the data and include additional fields
        $applications->transform(function ($application, $count) {
            //Human readable $ format cost
            $planMonthlyCost = number_format($application->plan->monthly_cost / 100, 2);
            $formattedPlanMonthlyCost = '$' . $planMonthlyCost;

            $application->application_id = $application->id;
            $application->customer_full_name = $application->customer->first_name.' '.$application->customer->last_name;
            $application->address = $application->address_1.' '.$application->address_2;
            $application->plan_monthly_cost = $formattedPlanMonthlyCost;
            $application->plan_name = $application->plan->name;
            $application->plan_type = $application->plan->type;
            
            //Remove all the order_ids where status is completed
            if ($application->status->value !== 'complete') {
                unset($application->order_id);
            }
            unset($application->customer, $application->plan);
            return $application;
        });
        // Return the paginated results
        return response()->json([
            'total' => $applications->total(),
            'page' => $applications->currentPage(),
            'per_page' => $applications->perPage(),
            'data' => $applications->toArray(),
        ]);
        
    }
}
