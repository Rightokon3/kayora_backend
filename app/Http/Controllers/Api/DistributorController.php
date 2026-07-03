<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DistributorController extends Controller
{
    // POST: Store incoming Application Forms
    public function submitApplication(Request $request)
    {
        $validated = $request->validate([
            'fullName' => 'required|string|max:255',
            'businessName' => 'required|string|max:255',
            'businessType' => 'required|string',
            'city' => 'required|string',
            'lga' => 'required|string',
            'state' => 'required|string',
            'phone' => 'required|string',
            'whatsapp' => 'nullable|string',
            'email' => 'required|email',
            'estimatedMonthlyVolume' => 'required|string',
            'yearsInBusiness' => 'nullable|string',
            'additionalInfo' => 'nullable|string',
        ]);

        DB::table('distributor_applications')->insert([
            'user_id' => Auth::id(),
            'full_name' => $validated['fullName'],
            'business_name' => $validated['businessName'],
            'business_type' => $validated['businessType'],
            'city' => $validated['city'],
            'lga' => $validated['lga'],
            'state' => $validated['state'],
            'phone' => $validated['phone'],
            'whatsapp' => $request->whatsapp,
            'email' => $validated['email'],
            'estimated_monthly_volume' => $validated['estimatedMonthlyVolume'],
            'years_in_business' => $request->yearsInBusiness,
            'additional_info' => $request->additionalInfo,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your form has been successfully submitted and will be reviewed.'
        ]);
    }

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
}