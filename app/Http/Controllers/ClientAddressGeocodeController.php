<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeocodeClientAddressRequest;
use App\Services\Geocoding\GeocodingException;
use App\Services\Geocoding\GeocodingProvider;
use Illuminate\Http\JsonResponse;

class ClientAddressGeocodeController extends Controller
{
    public function __invoke(GeocodeClientAddressRequest $request, GeocodingProvider $geocoding): JsonResponse
    {
        try {
            $result = $geocoding->geocode($request->string('address')->toString());
        } catch (GeocodingException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if ($result === null) {
            return response()->json(['message' => 'Maps could not resolve this address.'], 404);
        }

        return response()->json([
            'latitude' => $result->latitude,
            'longitude' => $result->longitude,
            'formatted_address' => $result->formattedAddress,
            'place_id' => $result->placeId,
            'location_type' => $result->locationType,
            'provider' => $result->provider,
        ]);
    }
}
