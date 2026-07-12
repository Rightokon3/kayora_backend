<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Driver;
use App\Models\Order;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        /* ============================================================
           SECOND DEMO DRIVER — based in Uyo
           ------------------------------------------------------------
           Without this, every seeded driver sits near Benin City, so
           there's nothing for the nearest-driver dispatch logic to
           actually choose between. current_latitude/longitude are set
           directly here (normally only written by the app's real GPS
           location ping) so dispatch tests work immediately without
           needing the app to send a location first.
        ============================================================ */
        $uyoDriver = Driver::updateOrCreate(
            ['driver_id' => 'DRV0002'],
            [
                'name' => 'Ekaette Udo',
                'email' => 'ekaette.udo@kayora.com',
                'password' => Hash::make('driver123'),
                'phone' => '+2348098765432',
                'vehicle' => 'Kayora Delivery Van',
                'plate_number' => 'AKS-118-KY',
                'duty_status' => 'on_duty',
                'current_latitude' => 5.0377,
                'current_longitude' => 7.9128,
                'last_seen_at' => now(),
            ]
        );

        // Make sure the original Benin City driver also has a live location,
        // so BOTH candidates are eligible for dispatch comparisons.
        $beninDriver = Driver::where('driver_id', 'DRV0001')->first();
        if ($beninDriver) {
            $beninDriver->update([
                'duty_status' => 'on_duty',
                'current_latitude' => 6.335,
                'current_longitude' => 5.6037,
                'last_seen_at' => now(),
            ]);
        }

        /* ============================================================
           BENIN CITY ORDERS (assigned to DRV0001)
        ============================================================ */
        $this->makeOrder([
            'order_number' => 'KYA-90001',
            'driver_id' => $beninDriver?->id,
            'customer_name' => 'Amaka Obi',
            'customer_phone' => '+2348023456789',
            'customer_email' => 'amaka.obi@gmail.com',
            'delivery_address' => '12 Sapele Road, Benin City',
            'nearest_landmark' => 'Opposite Guinness Nigeria',
            'latitude' => 6.339, 'longitude' => 5.6216,
            'amount' => 2000, 'status' => 'Assigned',
            'payment_method' => 'Cash', 'payment_status' => 'Paid',
            'priority' => 'Normal', 'delivery_type' => 'Instant',
            'eta' => '20 min', 'distance_km' => 2.1,
            'assigned_at' => now()->subMinutes(15),
        ], [
            ['bottle_name' => 'Kayora Premium Water', 'size' => '75cl', 'quantity' => 4, 'price' => 500, 'subtotal' => 2000],
        ]);

        $this->makeOrder([
            'order_number' => 'KYA-90002',
            'driver_id' => $beninDriver?->id,
            'customer_name' => 'Grace Idahosa',
            'customer_phone' => '+2348045678901',
            'customer_email' => 'grace.idahosa@outlook.com',
            'delivery_address' => '7 Ring Road, Benin City',
            'nearest_landmark' => 'Behind New Benin Market',
            'latitude' => 6.3355, 'longitude' => 5.6037,
            'amount' => 4300, 'status' => 'Out For Delivery',
            'payment_method' => 'Card', 'payment_status' => 'Paid',
            'priority' => 'Normal', 'delivery_type' => 'Instant',
            'eta' => '10 min', 'distance_km' => 1.2,
            'assigned_at' => now()->subMinutes(40),
            'started_at' => now()->subMinutes(10),
        ], [
            ['bottle_name' => 'Kayora Premium Water', 'size' => '50cl', 'quantity' => 10, 'price' => 300, 'subtotal' => 3000],
            ['bottle_name' => 'Kayora Premium Water', 'size' => '1L', 'quantity' => 2, 'price' => 650, 'subtotal' => 1300],
        ]);

        $this->makeOrder([
            'order_number' => 'KYA-90003',
            'driver_id' => $beninDriver?->id,
            'customer_name' => 'Ifeoma Chukwu',
            'customer_phone' => '+2348034567890',
            'customer_email' => 'ifeoma.chukwu@gmail.com',
            'delivery_address' => '3 Reservation Road, Benin City',
            'nearest_landmark' => 'Near GRA Gate 2',
            'latitude' => 6.3401, 'longitude' => 5.6288,
            'amount' => 4500, 'status' => 'Delivered',
            'payment_method' => 'Transfer', 'payment_status' => 'Paid',
            'priority' => 'Normal', 'delivery_type' => 'Instant',
            'assigned_at' => now()->subHours(4),
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHours(2),
        ], [
            ['bottle_name' => 'Kayora Premium Water', 'size' => '5L', 'quantity' => 3, 'price' => 1500, 'subtotal' => 4500],
        ]);

        /* ============================================================
           UYO ORDERS (assigned to DRV0002)
        ============================================================ */
        $this->makeOrder([
            'order_number' => 'KYA-90004',
            'driver_id' => $uyoDriver->id,
            'customer_name' => 'Emmanuel Akpan',
            'customer_phone' => '+2348056789012',
            'customer_email' => 'emmanuel.akpan@gmail.com',
            'delivery_address' => '24 Aka Road, Uyo',
            'nearest_landmark' => 'Near Uyo Township Stadium',
            'latitude' => 5.0450, 'longitude' => 7.9200,
            'amount' => 3600, 'status' => 'Assigned',
            'payment_method' => 'Transfer', 'payment_status' => 'Paid',
            'priority' => 'Normal', 'delivery_type' => 'Instant',
            'eta' => '25 min', 'distance_km' => 3.4,
            'assigned_at' => now()->subMinutes(20),
        ], [
            ['bottle_name' => 'Kayora Premium Water', 'size' => '1.5L', 'quantity' => 6, 'price' => 600, 'subtotal' => 3600],
        ]);

        $this->makeOrder([
            'order_number' => 'KYA-90005',
            'driver_id' => $uyoDriver->id,
            'customer_name' => 'Mfon Etim',
            'customer_phone' => '+2348067890123',
            'customer_email' => 'mfon.etim@gmail.com',
            'delivery_address' => '10 Wellington Bassey Way, Uyo',
            'nearest_landmark' => 'Close to Ibom Plaza',
            'latitude' => 5.0333, 'longitude' => 7.9089,
            'amount' => 2500, 'status' => 'Delivered',
            'payment_method' => 'Cash', 'payment_status' => 'Paid',
            'priority' => 'Normal', 'delivery_type' => 'Instant',
            'assigned_at' => now()->subHours(5),
            'started_at' => now()->subHours(4),
            'completed_at' => now()->subHours(3),
        ], [
            ['bottle_name' => 'Kayora Premium Water', 'size' => '18.9L', 'quantity' => 1, 'price' => 2500, 'subtotal' => 2500],
        ]);

        /* ============================================================
           ASAP ORDERS — Pending, unassigned, ready for dispatch testing.
           Neither has a driver_id or offered_driver_id set yet, so you
           can manually trigger:

             $order = Order::where('order_number', 'KYA-90006')->first();
             app(\App\Services\OrderDispatchService::class)->offerToNearestDriver($order);

           via `php artisan tinker` and confirm it picks the UYO driver
           for the Uyo order (not the Benin City one), and vice versa.
        ============================================================ */
        $this->makeOrder([
            'order_number' => 'KYA-90006',
            'driver_id' => null,
            'offered_driver_id' => null,
            'customer_name' => 'Blessing Umoh',
            'customer_phone' => '+2348078901234',
            'customer_email' => 'blessing.umoh@gmail.com',
            'delivery_address' => '5 Ikot Ekpene Road, Uyo',
            'nearest_landmark' => 'Opposite Uyo City Mall',
            'latitude' => 5.0289, 'longitude' => 7.9310,
            'amount' => 5200, 'status' => 'Pending',
            'payment_method' => 'Card', 'payment_status' => 'Paid',
            'priority' => 'Urgent', 'delivery_type' => 'Instant',
        ], [
            ['bottle_name' => 'Kayora Premium Water', 'size' => '75cl', 'quantity' => 8, 'price' => 500, 'subtotal' => 4000],
            ['bottle_name' => 'Kayora Premium Water', 'size' => '50cl', 'quantity' => 4, 'price' => 300, 'subtotal' => 1200],
        ]);

        $this->makeOrder([
            'order_number' => 'KYA-90007',
            'driver_id' => null,
            'offered_driver_id' => null,
            'customer_name' => 'Tunde Bakare',
            'customer_phone' => '+2348034567890',
            'customer_email' => 'tunde.bakare@yahoo.com',
            'delivery_address' => '45 Airport Road, Benin City',
            'nearest_landmark' => 'Near Airport Road Roundabout',
            'latitude' => 6.3423, 'longitude' => 5.6109,
            'amount' => 4800, 'status' => 'Pending',
            'payment_method' => 'Transfer', 'payment_status' => 'Paid',
            'priority' => 'Urgent', 'delivery_type' => 'Instant',
        ], [
            ['bottle_name' => 'Kayora Premium Water', 'size' => '1.5L', 'quantity' => 6, 'price' => 800, 'subtotal' => 4800],
        ]);
    }

    private function makeOrder(array $orderData, array $items): void
    {
        $order = Order::updateOrCreate(
            ['order_number' => $orderData['order_number']],
            $orderData
        );

        $order->items()->delete();
        foreach ($items as $item) {
            $order->items()->create($item);
        }
    }
}