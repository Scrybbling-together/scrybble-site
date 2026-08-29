<?php

namespace App\Http\Controllers;

use App\Services\GumroadApi;

class GumroadSaleController extends Controller
{
    public function show(string $sale_id, GumroadApi $gumroadApi)
    {
        $sale_info = $gumroadApi->saleById($sale_id);

        return response()->json([
            'email' => $sale_info['sale']['email'],
            'license_key' => $sale_info['sale']['license_key'],
        ]);
    }
}
