<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\SitemapGenerator;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        SitemapGenerator::create(config('app.url'))
            ->hasCrawled(function (Url $url) {
                if ($url->segment(0) === 'dashboard') {
                    return null;
                }

                return $url;
            })
            ->writeToFile(public_path('sitemap.xml'));
    }
}
