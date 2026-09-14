<?php

namespace App\Actions;

use App\Contracts\Exchangeable;
use Illuminate\Database\Eloquent\Model;

class ArchiveExchangeableAction
{
    public function __invoke(Exchangeable&Model $exchangeable): Exchangeable&Model
    {
        $exchangeable->markAsArchived();

        return $exchangeable;
    }
}
