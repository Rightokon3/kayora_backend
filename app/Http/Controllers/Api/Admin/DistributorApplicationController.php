<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistributorApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DistributorApplicationController extends Controller
{
    /**
     * GET /api/admin/distributor-applications/{id}
     * Full column set for the modal. The /admin/customers list only
     * embeds a summary (id/status/businessName) per row, so the modal
     * fetches the rest here when the admin taps the distributor icon.
     */
public function show($id)
{
    $application = DistributorApplication::findOrFail($id);

    // Cast to camelCase for the frontend — the model itself stores
    // Laravel's native snake_case columns (full_name, business_name,
    // etc.), but DistributorApplication.tsx expects fullName,
    // businessName, and so on.
    return response()->json([
        'id' => $application->id,
        'status' => $application->status,
        'fullName' => $application->full_name,
        'businessName' => $application->business_name,
        'businessType' => $application->business_type,
        'city' => $application->city,
        'lga' => $application->lga,
        'state' => $application->state,
        'phone' => $application->phone,
        'whatsapp' => $application->whatsapp,
        'email' => $application->email,
        'estimatedMonthlyVolume' => $application->estimated_monthly_volume,
        'yearsInBusiness' => $application->years_in_business,
        'additionalInfo' => $application->additional_info,
        'submittedAt' => optional($application->created_at)->toIso8601String(),
    ]);
}

    /**
     * POST /api/admin/distributor-applications/{id}/approve
     * Flips the underlying user to a distributor: users.is_distributor
     * becomes true and users.distributor_application_id points back at
     * this row. This is the "no longer a normal user" step.
     */
    public function approve($id)
    {
        $application = DistributorApplication::findOrFail($id);

        DB::transaction(function () use ($application) {
            $application->update(['status' => 'approved']);

            User::where('id', $application->user_id)->update([
                'is_distributor' => true,
                'distributor_application_id' => $application->id,
            ]);
        });

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/admin/distributor-applications/{id}/deny
     * Rejects the application; the user stays a normal customer.
     */
    public function deny($id)
    {
        $application = DistributorApplication::findOrFail($id);
        $application->update(['status' => 'rejected']);

        return response()->json(['success' => true]);
    }
}