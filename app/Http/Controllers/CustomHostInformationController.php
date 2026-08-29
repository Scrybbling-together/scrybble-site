<?php

namespace App\Http\Controllers;

use Laravel\Passport\Passport;

/**
 * Provides all the information required to self-host a Scrybble instance
 */
class CustomHostInformationController extends Controller
{
    public function show()
    {
        $obsidianClient = Passport::clientModel()::query()
            ->where('name', 'scrybble-device-flow')
            ->first(['secret', 'id']);

        return view('selfHostInformation', [
            'secret' => $obsidianClient->secret,
            'id' => $obsidianClient->id,
        ]);
    }
}
