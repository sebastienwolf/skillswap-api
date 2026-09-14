<?php

namespace App\Observers;

use App\Enums\ExchangeStatus;
use App\Events\ExchangeablePublished;
use App\Models\Skill;

/**
 * Symétrique de ItemObserver pour les compétences (voir son commentaire).
 */
class SkillObserver
{
    public function created(Skill $skill): void
    {
        if ($skill->status === ExchangeStatus::Published) {
            ExchangeablePublished::dispatch($skill);
        }
    }

    public function updated(Skill $skill): void
    {
        if ($skill->wasChanged('status') && $skill->status === ExchangeStatus::Published) {
            ExchangeablePublished::dispatch($skill);
        }
    }
}
