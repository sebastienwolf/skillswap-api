<?php

namespace App\Observers;

use App\Enums\ExchangeStatus;
use App\Events\ExchangeablePublished;
use App\Models\Item;

/**
 * Fait le lien entre les changements d'état d'un Item et les événements
 * applicatifs : le contrôleur ne s'occupe que de valider et persister, il
 * ignore tout des notifications déclenchées en aval (séparation des
 * responsabilités).
 */
class ItemObserver
{
    public function created(Item $item): void
    {
        if ($item->status === ExchangeStatus::Published) {
            ExchangeablePublished::dispatch($item);
        }
    }

    public function updated(Item $item): void
    {
        if ($item->wasChanged('status') && $item->status === ExchangeStatus::Published) {
            ExchangeablePublished::dispatch($item);
        }
    }
}
