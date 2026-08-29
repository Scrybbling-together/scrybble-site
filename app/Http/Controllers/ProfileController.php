<?php

namespace App\Http\Controllers;

use App\Services\GumroadService;

class ProfileController extends Controller
{
    public function __invoke(GumroadService $gumroadService)
    {
        $licenseInfo = $gumroadService->licenseInfo();

        return view('userProfile', ['licenseData' => $licenseInfo]);
    }
}
