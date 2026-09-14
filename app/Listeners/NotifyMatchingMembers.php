<?php

namespace App\Listeners;

use App\Events\ExchangeablePublished;
use App\Notifications\NewMatchFound;
use App\Services\MatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Traité en file d'attente : la recherche de correspondances et l'envoi de
 * notifications ne doivent jamais ralentir la réponse HTTP qui a publié
 * l'annonce (voir config QUEUE_CONNECTION=database).
 */
class NotifyMatchingMembers implements ShouldQueue
{
    public function __construct(
        private readonly MatchingService $matchingService,
    ) {}

    public function handle(ExchangeablePublished $event): void
    {
        $owners = $this->matchingService->findOwnersToNotify($event->exchangeable);

        Notification::send($owners, new NewMatchFound($event->exchangeable));
    }
}
