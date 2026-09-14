<?php

namespace App\Actions;

use App\Contracts\Exchangeable;
use Illuminate\Database\Eloquent\Model;

/**
 * Publie une annonce (Item ou Skill). Passer par une Action plutôt que
 * d'appeler directement `$item->markAsPublished()` dans le contrôleur
 * donne un point d'extension unique si une règle supplémentaire doit un
 * jour s'ajouter (ex: exiger une modération avant publication).
 */
class PublishExchangeableAction
{
    public function __invoke(Exchangeable&Model $exchangeable): Exchangeable&Model
    {
        $exchangeable->markAsPublished();

        return $exchangeable;
    }
}
