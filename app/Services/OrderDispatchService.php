<?php

// app/Services/OrderDispatchService.php
namespace App\Services;

use App\Models\Driver;
use App\Models\Order;

class OrderDispatchService
{
    /**
     * Finds the nearest on-duty driver who hasn't already declined this
     * order, offers it to them (sets offered_driver_id), and returns that
     * driver — or null if nobody is available.
     */
    public function offerToNearestDriver(Order $order): ?Driver
    {
        $declinedDriverIds = $order->declines()->pluck('driver_id');

        $candidates = Driver::where('duty_status', 'on_duty')
            ->whereNotIn('id', $declinedDriverIds)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->get();

        if ($candidates->isEmpty()) {
            $order->update(['offered_driver_id' => null]);
            return null;
        }

        $nearest = $candidates
            ->map(fn ($driver) => [
                'driver' => $driver,
                'distance' => HaversineService::distanceKm(
                    (float) $driver->current_latitude,
                    (float) $driver->current_longitude,
                    (float) $order->latitude,
                    (float) $order->longitude
                ),
            ])
            ->sortBy('distance')
            ->first();

        $order->update(['offered_driver_id' => $nearest['driver']->id]);

        return $nearest['driver'];
    }
}
