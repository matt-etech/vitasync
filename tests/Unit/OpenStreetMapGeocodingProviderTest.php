<?php

namespace Tests\Unit;

use App\Services\Geocoding\OpenStreetMapGeocodingProvider;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenStreetMapGeocodingProviderTest extends TestCase
{
    public function test_it_geocodes_addresses_with_openstreetmap_nominatim(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'place_id' => 12345,
                    'lat' => '51.501364',
                    'lon' => '-0.14189',
                    'display_name' => 'Buckingham Palace, London, United Kingdom',
                    'type' => 'attraction',
                ],
            ]),
        ]);

        $provider = new OpenStreetMapGeocodingProvider(
            http: app(HttpFactory::class),
            endpoint: 'https://nominatim.openstreetmap.org/search',
            userAgent: 'VitaSyncTest/1.0',
            countryCodes: 'gb',
        );

        $result = $provider->geocode('Buckingham Palace');

        $this->assertNotNull($result);
        $this->assertSame(51.501364, $result->latitude);
        $this->assertSame(-0.14189, $result->longitude);
        $this->assertSame('Buckingham Palace, London, United Kingdom', $result->formattedAddress);
        $this->assertSame('12345', $result->placeId);
        $this->assertSame('attraction', $result->locationType);
        $this->assertSame('openstreetmap', $result->provider);

        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return str_starts_with($request->url(), 'https://nominatim.openstreetmap.org/search?')
                && $query === [
                    'q' => 'Buckingham Palace',
                    'format' => 'jsonv2',
                    'limit' => '1',
                    'addressdetails' => '0',
                    'countrycodes' => 'gb',
                ]
                && $request->hasHeader('User-Agent', 'VitaSyncTest/1.0');
        });
    }

    public function test_it_returns_null_when_openstreetmap_has_no_results(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([]),
        ]);

        $provider = new OpenStreetMapGeocodingProvider(
            http: app(HttpFactory::class),
            endpoint: 'https://nominatim.openstreetmap.org/search',
            userAgent: 'VitaSyncTest/1.0',
        );

        $this->assertNull($provider->geocode('Unknown address'));
    }
}
