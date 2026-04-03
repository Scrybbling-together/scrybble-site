<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\ResponseInterface;

class GumroadApi
{
    const string API_ROOT = 'https://api.gumroad.com/v2/';
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::API_ROOT,
            'query' => [
                'access_token' => config('services.gumroad.token')
            ]
        ]);
    }

    public function saleById(string $sale_id)
    {
        $contents = Cache::remember("gumroad:sales:$sale_id", Carbon::now()->addHour(),
            fn() => $this->client->get("sales/$sale_id")->getBody()->getContents()
        );
        $decoded = json_decode($contents, true);

        if ($decoded['success']) {
            return collect($decoded['sale']);
        }

        return null;
    }

    /**
     *
     * @link https://app.gumroad.com/api#licenses
     * @param string $license
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function verifyLicense(string $license): ResponseInterface
    {
        return $this->client->post('licenses/verify', [
            'json' => [
                'product_permalink' => 'remarkable-to-obsidian',
                'license_key' => $license
            ]
        ])->withHeader('Content-Type', 'application/json');
    }

    public function getSubscribers(string $productId): array
    {
        $response = $this->client->get("products/$productId/subscribers");

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getSales(?string $pageKey = null): array
    {
        $query = ['access_token' => config('services.gumroad.token')];
        if ($pageKey) {
            $query['page_key'] = $pageKey;
        }

        $response = $this->client->get('sales', ['query' => $query]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
