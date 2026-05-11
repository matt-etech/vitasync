<?php

namespace App\Services\Geocoding;

interface GeocodingProvider
{
    public function geocode(string $address): ?GeocodeResult;
}
