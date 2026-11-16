<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AddressApiController extends Controller
{
    private const BASE_URL = 'https://psgc.gitlab.io/api';

    private function fetchJson(string $endpoint): array
    {
        try {
            $resp = Http::timeout(8)->get(self::BASE_URL . $endpoint);
            if ($resp->successful() && is_array($resp->json())) {
                return $resp->json();
            }
        } catch (\Throwable $e) {
            // Retry with verify => false in case local PHP environment has cURL SSL bundle issue
            try {
                $resp = Http::withoutVerifying()->timeout(8)->get(self::BASE_URL . $endpoint);
                if ($resp->successful() && is_array($resp->json())) {
                    return $resp->json();
                }
            } catch (\Throwable $ex) {
                // Return empty array
            }
        }
        return [];
    }

    public function regions(): JsonResponse
    {
        $cacheKey = 'psgc_regions';
        $cached = Cache::get($cacheKey);
        if (!empty($cached) && is_array($cached)) {
            return response()->json($cached);
        }

        $data = $this->fetchJson('/regions.json');
        if (!empty($data)) {
            Cache::put($cacheKey, $data, 86400 * 7);
        }

        return response()->json($data);
    }

    public function allProvinces(): JsonResponse
    {
        $cacheKey = 'psgc_all_provinces';
        $cached = Cache::get($cacheKey);
        if (!empty($cached) && is_array($cached)) {
            return response()->json($cached);
        }

        $data = $this->fetchJson('/provinces.json');
        if (!empty($data)) {
            usort($data, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
            // Include Metro Manila at top
            array_unshift($data, [
                'code' => '130000000',
                'name' => 'Metro Manila',
                'regionCode' => '130000000',
                'isNcr' => true,
            ]);
            Cache::put($cacheKey, $data, 86400 * 7);
        }

        return response()->json($data);
    }

    public function provinces(string $regionCode): JsonResponse
    {
        $cacheKey = "psgc_provinces_{$regionCode}";
        $cached = Cache::get($cacheKey);
        if (!empty($cached) && is_array($cached)) {
            return response()->json($cached);
        }

        $data = $this->fetchJson("/regions/{$regionCode}/provinces.json");
        if (!empty($data)) {
            Cache::put($cacheKey, $data, 86400 * 7);
        }

        return response()->json($data);
    }

    public function cities(string $code): JsonResponse
    {
        $cacheKey = "psgc_cities_{$code}";
        $cached = Cache::get($cacheKey);
        if (!empty($cached) && is_array($cached)) {
            return response()->json($cached);
        }

        // Region codes in PSGC end with '0000000' (or NCR 130000000)
        $isRegion = str_ends_with($code, '0000000') || request()->boolean('is_region');

        if ($isRegion) {
            $data = $this->fetchJson("/regions/{$code}/cities-municipalities.json");
        } else {
            $data = $this->fetchJson("/provinces/{$code}/cities-municipalities.json");
            if (empty($data)) {
                $data = $this->fetchJson("/regions/{$code}/cities-municipalities.json");
            }
        }

        if (!empty($data)) {
            Cache::put($cacheKey, $data, 86400 * 7);
        }

        return response()->json($data);
    }

    public function barangays(string $cityCode): JsonResponse
    {
        $cacheKey = "psgc_barangays_{$cityCode}";
        $cached = Cache::get($cacheKey);
        if (!empty($cached) && is_array($cached)) {
            return response()->json($cached);
        }

        $data = $this->fetchJson("/cities-municipalities/{$cityCode}/barangays.json");
        if (!empty($data)) {
            Cache::put($cacheKey, $data, 86400 * 7);
        }

        return response()->json($data);
    }
}
