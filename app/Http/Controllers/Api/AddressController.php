<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address; // Ensure you have generated the Address model via `php artisan make:model Address`

class AddressController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // Automatically associates the address with the authenticated Sanctum user
        $address = $request->user()->addresses()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Address saved successfully',
            'address' => $address
        ], 201);
    }
    public function index(Request $request)
{
    $addresses = $request->user()
        ->addresses() // assumes a hasMany relationship on the User model — see step 3
        ->orderByDesc('created_at')
        ->get();

    return response()->json(['success' => true, 'addresses' => $addresses]);
}
}