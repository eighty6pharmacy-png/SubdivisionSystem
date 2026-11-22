<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HighwayNavigationController extends Controller
{
    /**
     * Highway Navigation Proxy & Route Provider (100% Free)
     * Fetches and caches high-precision highway route coordinates from any visitor origin
     * directly to the Althesa Residences entrance gate without requiring third-party paid keys.
     */
    public function getRoute(Request $request)
    {
        $startLat = $request->query('start_lat') ?? $request->query('origin_lat');
        $startLng = $request->query('start_lng') ?? $request->query('origin_lng');
        $endLat   = $request->query('end_lat')   ?? $request->query('dest_lat');
        $endLng   = $request->query('end_lng')   ?? $request->query('dest_lng');

        if (!$startLat || !$startLng || !$endLat || !$endLng) {
            return response()->json([
                'code' => 'Error',
                'message' => 'Missing coordinates: origin_lat, origin_lng, dest_lat, and dest_lng are required.'
            ], 400);
        }

        $sLat = (float) $startLat;
        $sLng = (float) $startLng;
        $eLat = (float) $endLat;
        $eLng = (float) $endLng;

        // Cache route responses by ~15m grid resolution for 2 hours for instant load times
        $cacheKey = sprintf('hwy_nav_route_v4_%.4f_%.4f_%.4f_%.4f', $sLat, $sLng, $eLat, $eLng);

        $cachedData = Cache::remember($cacheKey, 7200, function () use ($sLat, $sLng, $eLat, $eLng) {
            try {
                $url = sprintf(
                    'https://router.project-osrm.org/route/v1/driving/%.6f,%.6f;%.6f,%.6f?overview=full&geometries=geojson&alternatives=3&steps=true',
                    $sLng,
                    $sLat,
                    $eLng,
                    $eLat
                );

                $response = Http::withHeaders([
                    'User-Agent' => 'SubdivisionSystem-Althesa/1.0 (contact: admin@althesa.local)',
                    'Accept' => 'application/json',
                ])->timeout(8)->get($url);

                if ($response->successful()) {
                    $json = $response->json();
                    if (isset($json['code']) && $json['code'] === 'Ok' && !empty($json['routes'])) {
                        $routes = $json['routes'];

                        // If OSRM returned only 1 route on a trip > 500m, probe a perpendicular midpoint via-waypoint
                        // to surface any parallel alternative road (Google Maps style).
                        if (count($routes) === 1 && ($routes[0]['distance'] ?? 0) > 500) {
                            $altRoute = $this->fetchViaAlternative($sLat, $sLng, $eLat, $eLng, $routes[0]);
                            if ($altRoute !== null) {
                                $routes[] = $altRoute;
                            }
                        }

                        return [
                            'code' => 'Ok',
                            'routes' => $routes,
                            'source' => 'server_proxy'
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('HighwayNavigationController proxy notice: ' . $e->getMessage());
            }

            // Fallback direct straight line calculation if external server is momentarily busy
            $directDist = $this->calculateDistance($sLat, $sLng, $eLat, $eLng);
            return [
                'code' => 'Ok',
                'routes' => [
                    [
                        'geometry' => [
                            'type' => 'LineString',
                            'coordinates' => [
                                [$sLng, $sLat],
                                [$eLng, $eLat]
                            ]
                        ],
                        'distance' => $directDist,
                        'duration' => $directDist / 8.33
                    ]
                ],
                'source' => 'direct_fallback'
            ];
        });

        return response()->json($cachedData);
    }

    private function fetchViaAlternative(float $sLat, float $sLng, float $eLat, float $eLng, array $primaryRoute): ?array
    {
        $primaryDist = (float) ($primaryRoute['distance'] ?? 0);
        if ($primaryDist <= 500) {
            return null;
        }

        $midLat = ($sLat + $eLat) / 2.0;
        $midLng = ($sLng + $eLng) / 2.0;
        $dLat = $eLat - $sLat;
        $dLng = $eLng - $sLng;
        $norm = hypot($dLat, $dLng);
        if ($norm <= 1e-6) {
            return null;
        }

        // Offset ~350m perpendicular to the origin-destination line (both sides)
        $offsetDeg = 0.0032;
        $perpLat = (-$dLng / $norm) * $offsetDeg;
        $perpLng = ($dLat / $norm) * $offsetDeg;

        foreach ([[$midLat + $perpLat, $midLng + $perpLng], [$midLat - $perpLat, $midLng - $perpLng]] as [$vLat, $vLng]) {
            try {
                $viaUrl = sprintf(
                    'https://router.project-osrm.org/route/v1/driving/%.6f,%.6f;%.6f,%.6f;%.6f,%.6f?overview=full&geometries=geojson&alternatives=false&steps=true',
                    $sLng,
                    $sLat,
                    $vLng,
                    $vLat,
                    $eLng,
                    $eLat
                );
                $res = Http::withHeaders([
                    'User-Agent' => 'SubdivisionSystem-Althesa/1.0 (contact: admin@althesa.local)',
                    'Accept' => 'application/json',
                ])->timeout(5)->get($viaUrl);

                if ($res->successful()) {
                    $json = $res->json();
                    if (isset($json['code']) && $json['code'] === 'Ok' && !empty($json['routes'][0])) {
                        $cand = $json['routes'][0];
                        $candDist = (float) ($cand['distance'] ?? 0);
                        // Accept only if it's a reasonable detour (not a huge loop) and meaningfully different from primary
                        if ($candDist > 0 && $candDist <= $primaryDist * 1.45 && abs($candDist - $primaryDist) > 40) {
                            return $cand;
                        }
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
