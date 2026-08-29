<?php

namespace App\Services;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Auth;

class GumroadService
{
    public function __construct(private GumroadApi $gumroadApi) {}

    public function licenseInfo(): ?array
    {
        $licenseObj = Auth::user()->gumroadLicense;
        if ($licenseObj === null) {
            return null;
        }
        $license = $licenseObj->license;
        $response = ['license' => $license];
        $isLifetime = boolval($licenseObj->lifetime);

        try {
            $res = $this->gumroadApi->verifyLicense($license);
            $info = json_decode($res->getBody()->getContents(), JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
            $purchase = $info['purchase'];

            $cancelled = $purchase['subscription_cancelled_at'];
            $ended = $purchase['subscription_ended_at'];
            $failed = $purchase['subscription_failed_at'];

            $response['exists'] = true;
            $response['licenseInformation'] = [
                'uses' => $info['uses'],
                'order_number' => $purchase['order_number'],
                'sale_id' => $purchase['sale_id'],
                'subscription_id' => $purchase['subscription_id'],
                'active' => $cancelled === null && $ended === null && $failed === null,
            ];
            $response['lifetime'] = $isLifetime;
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                $response['exists'] = false;
                $response['lifetime'] = $isLifetime;
            } else {
                throw $e;
            }
        }

        return $response;
    }
}
