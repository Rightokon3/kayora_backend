<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'id' => 1,
                'name' => 'Sharp-Sharp',
                'size' => '30cl',
                'tagline' => 'The Event Bottle',
                'price' => 300,
                'heroDesc' => 'The event standard. Compact, easy to chill in volume, and purpose-built for any occasion where water is served to guests.',
                'aboutTitle' => 'The Sharp-sharp — In Detail',
                'aboutBody' => "The 30cl Sharp-sharp was designed for occasions where water is served, not just consumed. Weddings, naming ceremonies, corporate functions, conferences, church events — these are the settings where a bottle that is small enough to handle comfortably but substantial enough to be satisfying matters most.\n\nProduced at our Eket facility under the same eight-stage purification process as every other Kayora size, the 30cl is sold in cases of 24. It chills quickly, is easy to transport in large quantities, and the tamper-evident cap gives your guests confidence that what they are drinking is sealed, safe and traceable.\n\nFor events and caterers who need a reliable, premium water with visible certification, the Sharp-sharp is the specification.",
                'bestUsedTitle' => 'Who Reaches for the Sharp-sharp',
                'usedFor' => [
                    ['title' => 'Weddings & Naming Ceremonies', 'desc' => 'The right size for service at tables and for guests to hold during proceedings.'],
                    ['title' => 'Corporate Events & Conferences', 'desc' => 'Professional presentation, easy logistics, and a brand that signals quality.'],
                    ['title' => 'Catering & Food Service', 'desc' => 'Standard pack size for catering packs, event coolers and bulk chill.'],
                    ['title' => 'Church Events & Programmes', 'desc' => 'Compact, affordable per-unit cost for large congregations.'],
                ],
                'specs' => [
                    ['label' => 'VOLUME', 'value' => '30cl (300ml)'],
                    ['label' => 'MATERIAL', 'value' => 'PET — food-grade, BPA-free'],
                    ['label' => 'PACKAGING', 'value' => '24 bottles per case'],
                    ['label' => 'CAP', 'value' => 'Steam-cleaned, ozone-sterilised, tamper-evident'],
                    ['label' => 'PURIFICATION', 'value' => 'Eight-stage purification process'],
                    ['label' => 'STORAGE', 'value' => 'Keep away from direct sunlight and heat'],
                ],
                'regulatory' => [
                    ['label' => 'NAFDAC REGISTRATION', 'value' => 'A1-111026', 'sub' => 'National Agency for Food and Drug Administration and Control'],
                    ['label' => 'SON MANCAP REGISTRATION', 'value' => 'FT-29179', 'sub' => 'Standards Organisation of Nigeria'],
                ],
                'imageColor' => '#1E5FAF',
                'orderTitle' => 'Order the Kayora 30cl Sharp-sharp',
                'orderDesc' => 'Direct delivery across Akwa Ibom State. Distributor network across the South-South and South-East.',
            ],
            [
                'id' => 2,
                'name' => 'Original',
                'size' => '50cl',
                'tagline' => 'The Everyday Bottle',
                'price' => 400,
                'heroDesc' => 'The backbone of the range. For homes, offices, schools and anywhere clean water is an everyday requirement.',
                'aboutTitle' => 'The Original — In Detail',
                'aboutBody' => "The 50cl Original is the Kayora bottle most Nigerians encounter first — on office desks, in school bags, on restaurant tables and in supermarket fridges across Akwa Ibom.\n\nCases of 12 make it straightforward for household orders, office deliveries and retail stocking. The same eight-stage purified water, the same NAFDAC-registered process, the same tamper-evident seal as every other size in the range.",
                'bestUsedTitle' => 'Who Reaches for the Original',
                'usedFor' => [
                    ['title' => 'Homes & Households', 'desc' => 'Everyday hydration for families — fits fridges, school bags and hands of all sizes.'],
                    ['title' => 'Offices & Workplaces', 'desc' => 'The standard desk bottle. Clean presentation, no spillage risk, sealed fresh.'],
                ],
                'specs' => [
                    ['label' => 'VOLUME', 'value' => '50cl (500ml)'],
                    ['label' => 'MATERIAL', 'value' => 'PET — food-grade, BPA-free'],
                    ['label' => 'PACKAGING', 'value' => '12 bottles per case'],
                ],
                'regulatory' => [
                    ['label' => 'NAFDAC REGISTRATION', 'value' => 'A1-111026', 'sub' => 'National Agency for Food and Drug Administration and Control'],
                ],
                'imageColor' => '#0D4A8C',
                'orderTitle' => 'Order the Kayora 50cl Original',
                'orderDesc' => 'Direct delivery across Akwa Ibom State. Distributor network across the South-South and South-East.',
            ]
        ];

        foreach ($products as $prodData) {
            // updateOrCreate keeps your existing row data intact while appending the new rich text columns safely!
            Product::updateOrCreate(['id' => $prodData['id']], $prodData);
        }
    }
}