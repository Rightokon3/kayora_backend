<?php

// app/Http/Controllers/Api/DriverStatusController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DriverStatusController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'status' => 'required|in:on_duty,off_duty',
        ]);

        $request->user()->update([
            'duty_status' => $request->status,
            'last_seen_at' => now(),
        ]);

        return response()->json(['status' => $request->status]);
    }
}