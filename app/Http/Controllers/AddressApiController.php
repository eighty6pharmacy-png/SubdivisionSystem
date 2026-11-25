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
        $url = self::BASE_URL . $endpoint;

        // Remember if local PHP cURL needs withoutVerifying so we don't waste a timeout on every request
        $skipVerify = app()->isLocal() || Cache::get('psgc_skip_ssl_verify', false);

        try {
            $client = Http::connectTimeout(3)->timeout(5);
            if ($skipVerify) {
                $client = $client->withoutVerifying();
            }
            $resp = $client->get($url);
            if ($resp->successful() && is_array($resp->json())) {
                return $resp->json();
            }
        } catch (\Throwable $e) {
            if (! $skipVerify) {
                try {
                    $resp = Http::withoutVerifying()->connectTimeout(3)->timeout(5)->get($url);
                    if ($resp->successful() && is_array($resp->json())) {
                        Cache::put('psgc_skip_ssl_verify', true, 86400 * 7);
                        return $resp->json();
                    }
                } catch (\Throwable $ex) {
                    // Return empty array
                }
            }
        }
        return [];
    }

    /**
     * Strip unused PSGC metadata fields to keep JSON payloads small and fast.
     */
    private function slimList(array $items): array
    {
        return array_values(array_map(fn (array $item) => [
            'code' => $item['code'] ?? '',
            'name' => $item['name'] ?? '',
            ...(! empty($item['isNcr']) ? ['isNcr' => true] : []),
        ], $items));
    }

    private function cachedJsonResponse(array $data): JsonResponse
    {
        return response()->json($data)->header('Cache-Control', 'public, max-age=86400');
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
        $cacheKey = 'psgc_all_provinces_v2';
        $cached = Cache::get($cacheKey);
        if (!empty($cached) && is_array($cached)) {
            return $this->cachedJsonResponse($cached);
        }

        $data = $this->fetchJson('/provinces.json');
        if (!empty($data)) {
            $data = $this->slimList($data);
            usort($data, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
            // Include Metro Manila at top
            array_unshift($data, [
                'code' => '130000000',
                'name' => 'Metro Manila',
                'isNcr' => true,
            ]);
            Cache::put($cacheKey, $data, 86400 * 30);
        }

        return $this->cachedJsonResponse($data);
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
        $cacheKey = "psgc_cities_v2_{$code}";
        $cached = Cache::get($cacheKey);
        if (!empty($cached) && is_array($cached)) {
            return $this->cachedJsonResponse($cached);
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
            $data = $this->slimList($data);
            Cache::put($cacheKey, $data, 86400 * 30);
        }

        return $this->cachedJsonResponse($data);
    }

    public function barangays(string $cityCode): JsonResponse
    {
        $cacheKey = "psgc_barangays_v2_{$cityCode}";
        $cached = Cache::get($cacheKey);
        if (!empty($cached) && is_array($cached)) {
            return $this->cachedJsonResponse($cached);
        }

        $data = $this->fetchJson("/cities-municipalities/{$cityCode}/barangays.json");
        if (!empty($data)) {
            $data = $this->slimList($data);
            Cache::put($cacheKey, $data, 86400 * 30);
        }

        return $this->cachedJsonResponse($data);
    }
}
