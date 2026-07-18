<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Disables Laravel's default { "data": {...} } wrapping on
        // JsonResource responses (e.g. DriverAccountResource). Without
        // this, GET /driver/me returns { "data": { "name": ..., ... } }
        // instead of { "name": ..., ... } — which is what caused
        // `data.name` to be undefined in account.tsx (data was really
        // { data: {...} }, not the flat object).
        JsonResource::withoutWrapping();
    }
}