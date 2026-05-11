<?php

namespace App\Services\Geocoding;

readonly class GeocodeResult
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public string $formattedAddress,
        public ?string $placeId,
        public ?string $locationType,
        public string $provider,
    ) {
    }
}
