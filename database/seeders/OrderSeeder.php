<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Driver;
use App\Models\Order;
use App\Models\DriverDailyStat;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $driver = Driver::where('driver_id', 'DRV0001')->first();
        if (!$driver) return;

        // ---- Completed earlier today ----
        $delivered = Order::updateOrCreate(
            ['order_number' => 'KYA-70001'],
            [
                'driver_id' => $driver->id,
                'customer_name' => 'Amaka Obi',
                'customer_phone' => '+2348023456789',
                'customer_email' => 'amaka.obi@gmail.com',
                'delivery_address' => '12 Sapele Road, Benin City',
                'nearest_landmark' => 'Opposite Guinness Nigeria',
                'latitude' => 6.339, 'longitude' => 5.6216,
                'amount' => 2000, 'status' => 'Delivered',
                'payment_method' => 'Cash', 'payment_status' => 'Paid',
                'delivery_type' => 'Instant', 'priority' => 'Normal',
                'started_at' => now()->subHours(3), 'completed_at' => now()->subHours(2),
            ]
        );
        $delivered->items()->delete();
        $delivered->items()->create(['bottle_name' => 'Kayora Premium Water', 'size' => '75cl', 'quantity' => 4, 'price' => 500, 'subtotal' => 2000]);

        // ---- Currently active (Out For Delivery) ----
        $active = Order::updateOrCreate(
            ['order_number' => 'KYA-70002'],
            [
                'driver_id' => $driver->id,
                'customer_name' => 'Tunde Bakare',
                'customer_phone' => '+2348034567890',
                'customer_email' => 'tunde.bakare@yahoo.com',
                'delivery_address' => '45 Airport Road, Benin City',
                'nearest_landmark' => 'Near Airport Road Roundabout',
                'latitude' => 6.3423, 'longitude' => 5.6109,
                'amount' => 4800, 'status' => 'Out For Delivery',
                'payment_method' => 'Transfer', 'payment_status' => 'Paid',
                'delivery_type' => 'Instant', 'priority' => 'High',
                'eta' => '25 min', 'distance_km' => 3.1,
                'assigned_at' => now()->subMinutes(40), 'started_at' => now()->subMinutes(15),
            ]
        );
        $active->items()->delete();
        $active->items()->create(['bottle_name' => 'Kayora Premium Water', 'size' => '1.5L', 'quantity' => 6, 'price' => 800, 'subtotal' => 4800]);

        // ---- Assigned, not yet started ----
        $assigned = Order::updateOrCreate(
            ['order_number' => 'KYA-70003'],
            [
                'driver_id' => $driver->id,
                'customer_name' => 'Grace Idahosa',
                'customer_phone' => '+2348045678901',
                'customer_email' => 'grace.idahosa@outlook.com',
                'delivery_address' => '7 Ring Road, Benin City',
                'nearest_landmark' => 'Behind New Benin Market',
                'latitude' => 6.3355, 'longitude' => 5.6037,
                'amount' => 4300, 'status' => 'Assigned',
                'payment_method' => 'Card', 'payment_status' => 'Paid',
                'delivery_type' => 'Instant', 'priority' => 'Normal',
                'eta' => '35 min', 'distance_km' => 1.8,
                'assigned_at' => now()->subMinutes(10),
            ]
        );
        $assigned->items()->delete();
        $assigned->items()->create(['bottle_name' => 'Kayora Premium Water', 'size' => '50cl', 'quantity' => 10, 'price' => 300, 'subtotal' => 3000]);
        $assigned->items()->create(['bottle_name' => 'Kayora Premium Water', 'size' => '1L', 'quantity' => 2, 'price' => 650, 'subtotal' => 1300]);

        // ---- Seed today's distance so the stat isn't zero on first load ----
        DriverDailyStat::updateOrCreate(
            ['driver_id' => $driver->id, 'date' => now()->toDateString()],
            ['distance_km' => 12.4, 'last_latitude' => 6.335, 'last_longitude' => 5.6037]
        );
    }
}