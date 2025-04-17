<?php

namespace Doxa\Libraries\Cloudflare;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CloudFlareService
{

    private string $api_token;
    private string $zone_id;
    private string $api_url;
    private string $resource_name;

    // right now, the api key has only this one permission.
    private string $action_url = '/purge_cache';

    public function __construct($resource_name = null)
    {
        $this->api_token = config('services.cloudflare.utils.token');
        $this->resource_name = $resource_name ? strtolower($resource_name) : strtolower(config('app.project_name'));
        $this->zone_id = $this->getZoneID();
        $this->api_url = 'https://api.cloudflare.com/client/v4/zones/' . $this->zone_id . $this->action_url;
    }



    private function getZoneID()
    {
        return Cache::remember("cloudflare_zone_id_{$this->resource_name}", 3600, function () {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->api_token}",
                'Content-Type' => 'application/json',
            ])->get("https://api.cloudflare.com/client/v4/zones");

            if ($response->successful()) {
                $zones = $response->json('result');

                foreach ($zones as $zone) {
                    $zone_name = strtolower(explode('.', $zone['name'])[0]);
                    if ($zone_name === $this->resource_name) {
                        return $zone['id'];
                    }
                }
            }

            throw new \Exception("Zone ID for '{$this->resource_name}' not found.");
        });
    }

    /**
     * Purge the cache for the given files.
     * If no files are provided, all cache will be purged.
     * If that is the intention, use purgeAllCache() instead.
     * //TODO change Logs to Clogs
     * @param array $files
     * @return bool
     */
    public function purgeCache(array $files = []): bool
    {
        $payload = empty($files) ? ['purge_everything' => true] : ['files' => $files];

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->api_token}",
                'Content-Type' => 'application/json',
            ])->post($this->api_url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success']) {
                    // dd($data);
                    return true;
                } else {
                    $this->handleCloudFlareError($data);
                    return false;
                }
            } else {
                $this->handleGeneralError($response);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception during Cloudflare purge request', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function handleCloudflareError($data)
    {
        $errors = $data['errors'] ?? [];
        $messages = $data['messages'] ?? [];

        foreach ($errors as $error) {
            Log::error('Cloudflare Error', [
                'code' => $error['code'],
                'message' => $error['message'],
            ]);
        }

        foreach ($messages as $message) {
            Log::warning('Cloudflare Message', [
                'code' => $message['code'],
                'message' => $message['message'],
            ]);
        }
    }

    private function handleGeneralError($response)
    {
        $statusCode = $response->status();
        $errorMessage = $response->body();

        Log::error('Cloudflare purge request failed', [
            'status_code' => $statusCode,
            'error_message' => $errorMessage
        ]);
    }



    public function purgeAllCache(): bool
    {
        return $this->purgeCache();
    }
}
