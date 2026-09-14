<?php

namespace App\Console\Commands;

use App\Actions\ArchiveExchangeableAction;
use App\Enums\ExchangeStatus;
use App\Models\Item;
use App\Models\Skill;
use Illuminate\Console\Command;

/**
 * Archive automatiquement les annonces publiées depuis longtemps et
 * jamais réservées, pour garder un catalogue pertinent (voir la
 * planification dans routes/console.php).
 */
class ArchiveStaleExchangeables extends Command
{
    protected $signature = 'app:archive-stale-exchangeables {--days=60 : Ancienneté en jours à partir de laquelle une annonce publiée est archivée}';

    protected $description = 'Archive les objets et compétences publiés depuis trop longtemps';

    public function handle(ArchiveExchangeableAction $action): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays($days);
        $archived = 0;

        foreach ([Item::query(), Skill::query()] as $query) {
            $stale = $query->where('status', ExchangeStatus::Published)
                ->where('created_at', '<=', $threshold)
                ->get();

            foreach ($stale as $exchangeable) {
                $action($exchangeable);
                $archived++;
            }
        }

        $this->info("{$archived} annonce(s) archivée(s) (plus de {$days} jours).");

        return self::SUCCESS;
    }
}
