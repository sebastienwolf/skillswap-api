<?php

use App\Console\Commands\ArchiveStaleExchangeables;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Archive chaque nuit les annonces (items/skills) publiées depuis plus de
// 60 jours et jamais réservées, pour garder un catalogue pertinent.
Schedule::command(ArchiveStaleExchangeables::class)->daily();
