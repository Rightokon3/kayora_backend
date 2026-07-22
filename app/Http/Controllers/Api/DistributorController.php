<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DistributorController extends Controller
{
    // POST: Store incoming Application Forms


    // GET: Fetch Dynamic Contact metadata fields
    public function getShopInfo()
    {
        $settings = DB::table('shop_settings')->pluck('value', 'key');

        return response()->json([
            'phone' => $settings['phone'] ?? '+2348012345678',
            'email' => $settings['email'] ?? 'info@kayorawater.com',
            'address' => $settings['address'] ?? "173 Eket Oron Road, Eket\nAkwa Ibom State",
            'working_hours' => $settings['working_hours'] ?? "Mon - Fri: 8:00 AM - 5:00 PM\nSat: 9:00 AM - 2:00 PM",
        ]);
    }
    public function __construct(private \App\Services\ExpoPushService $expoPush) {}

public function submitApplication(Request $request)
{
    $data = $request->validate([
        'fullName' => 'required|string|max:255',
        'businessName' => 'required|string|max:255',
        'businessType' => 'required|string|max:255',
        'city' => 'required|string|max:255',
        'lga' => 'required|string|max:255',
        'state' => 'required|string|max:255',
        'phone' => 'required|string|max:255',
        'whatsapp' => 'nullable|string|max:255',
        'email' => 'required|email|max:255',
        'estimatedMonthlyVolume' => 'required|string|max:255',
        'yearsInBusiness' => 'nullable|string|max:255',
        'additionalInfo' => 'nullable|string',
    ]);

    $application = \App\Models\DistributorApplication::create([
        'user_id' => $request->user()->id,
        'full_name' => $data['fullName'],
        'business_name' => $data['businessName'],
        'business_type' => $data['businessType'],
        'city' => $data['city'], 'lga' => $data['lga'], 'state' => $data['state'],
        'phone' => $data['phone'], 'whatsapp' => $data['whatsapp'] ?? null,
        'email' => $data['email'],
        'estimated_monthly_volume' => $data['estimatedMonthlyVolume'],
        'years_in_business' => $data['yearsInBusiness'] ?? null,
        'additional_info' => $data['additionalInfo'] ?? null,
        'status' => 'pending',
    ]);

    $this->expoPush->notifyAdmins('New distributor application',
        "{$data['fullName']} applied to become a distributor.",
        ['type' => 'distributor_application', 'applicationId' => $application->id]);

    return response()->json(['success' => true]);
}
}