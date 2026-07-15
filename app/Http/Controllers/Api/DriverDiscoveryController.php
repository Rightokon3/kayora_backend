<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverDiscoveryController extends Controller
{
    /**
     * GET /api/drivers/nearby?latitude=..&longitude=..
     *
     * Returns on-duty, not-currently-busy drivers within range, sorted by
     * distance, so the customer can pick one directly for ASAP delivery
     * instead of the order being broadcast to every online driver.
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $lat = (float) $request->latitude;
        $lng = (float) $request->longitude;
        $radiusKm = 10; // adjust to taste

        $drivers = Driver::select('*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(current_latitude)) *
                cos(radians(current_longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(current_latitude)))) AS distance_km',
                [$lat, $lng, $lat]
            )
            ->where('duty_status', 'on_duty')
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            // Exclude drivers currently mid-delivery. This checks active
            // orders rather than duty_status, since duty_status is the
            // driver's own shift toggle — being on an active delivery is a
            // different thing from having ended your shift.
            ->whereNotIn('id', function ($query) {
                $query->select('driver_id')
                    ->from('orders')
                    ->whereNotNull('driver_id')
                    ->whereNotIn('status', ['Delivered', 'Cancelled']);
            })
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km')
            ->limit(15)
            ->get();

        $result = $drivers->map(function ($driver) {
            $distanceKm = round($driver->distance_km, 1);
            return [
                'id'           => $driver->id,
                'fullName'     => $driver->name,
                'vehicle'      => $driver->vehicle,
                'plateNumber'  => $driver->plate_number,
                'distanceKm'   => $distanceKm,
                // rough ETA estimate at an average 25km/h city delivery speed
                'etaMinutes'   => max(2, (int) round(($distanceKm / 25) * 60)),
            ];
        });

        return response()->json(['success' => true, 'drivers' => $result]);
    }
}