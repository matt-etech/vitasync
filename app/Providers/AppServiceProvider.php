<?php

namespace App\Providers;

use App\Services\Geocoding\GeocodingProvider;
use App\Services\Geocoding\OpenStreetMapGeocodingProvider;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GeocodingProvider::class, function ($app): GeocodingProvider {
            return new OpenStreetMapGeocodingProvider(
                http: $app->make(HttpFactory::class),
                endpoint: config('services.openstreetmap.geocoding_endpoint'),
                userAgent: config('services.openstreetmap.geocoding_user_agent'),
                countryCodes: config('services.openstreetmap.geocoding_country_codes'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
