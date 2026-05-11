<?php

namespace App\Services\Geocoding;

use Illuminate\Http\Client\Factory as HttpFactory;

class OpenStreetMapGeocodingProvider implements GeocodingProvider
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $endpoint,
        private readonly string $userAgent,
        private readonly ?string $countryCodes = null,
    ) {
    }

    public function geocode(string $address): ?GeocodeResult
    {
        $address = trim($address);

        if ($address === '') {
            return null;
        }

        if (blank($this->endpoint) || blank($this->userAgent)) {
            throw new GeocodingException('Maps geocoding is not configured. Add OPENSTREETMAP_GEOCODING_ENDPOINT and OPENSTREETMAP_GEOCODING_USER_AGENT.');
        }

        $parameters = [
            'q' => $address,
            'format' => 'jsonv2',
            'limit' => 1,
            'addressdetails' => 0,
        ];

        if (filled($this->countryCodes)) {
            $parameters['countrycodes'] = $this->countryCodes;
        }

        $response = $this->http
            ->timeout(8)
            ->acceptJson()
            ->withUserAgent($this->userAgent)
            ->get($this->endpoint, $parameters);

        if (! $response->ok()) {
            throw new GeocodingException('Maps geocoding request failed.');
        }

        $payload = $response->json();

        if (isset($payload['error'])) {
            throw new GeocodingException((string) $payload['error']);
        }

        if (! is_array($payload) || $payload === []) {
            return null;
        }

        $result = $payload[0] ?? null;

        if (! is_array($result) || ! isset($result['lat'], $result['lon'])) {
            return null;
        }

        return new GeocodeResult(
            latitude: (float) $result['lat'],
            longitude: (float) $result['lon'],
            formattedAddress: (string) ($result['display_name'] ?? $address),
            placeId: isset($result['place_id']) ? (string) $result['place_id'] : null,
            locationType: isset($result['type']) ? (string) $result['type'] : null,
            provider: 'openstreetmap',
        );
    }
}
