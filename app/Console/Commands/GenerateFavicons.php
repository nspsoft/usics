<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\FaviconService;
use Illuminate\Console\Command;

class GenerateFavicons extends Command
{
    protected $signature = 'app:generate-favicons';

    protected $description = 'Generate public/favicon.png and public/favicon.ico from Company logo.';

    public function handle(FaviconService $faviconService): int
    {
        $faviconService->generateForCompany(Company::query()->first());
        $this->info('Favicons generated.');

        return self::SUCCESS;
    }
}

