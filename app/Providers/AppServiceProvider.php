<?php

namespace App\Providers;

use App\Events\ExchangeablePublished;
use App\Events\ReservationRequested;
use App\Events\ReservationStatusUpdated;
use App\Listeners\NotifyMatchingMembers;
use App\Listeners\NotifyReservationRequested;
use App\Listeners\NotifyReservationStatusUpdated;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\Skill;
use App\Models\User;
use App\Observers\ItemObserver;
use App\Observers\SkillObserver;
use App\Policies\ItemPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\SkillPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerObservers();
        $this->registerPolicies();
        $this->registerEventListeners();
        $this->configureRateLimiting();
    }

    private function registerEventListeners(): void
    {
        // Enregistrement explicite plutôt que la découverte automatique de
        // Laravel : on voit d'un coup d'œil, ici, quel événement déclenche
        // quel comportement.
        Event::listen(ExchangeablePublished::class, NotifyMatchingMembers::class);
        Event::listen(ReservationRequested::class, NotifyReservationRequested::class);
        Event::listen(ReservationStatusUpdated::class, NotifyReservationStatusUpdated::class);
    }

    private function registerObservers(): void
    {
        Item::observe(ItemObserver::class);
        Skill::observe(SkillObserver::class);
    }

    private function registerPolicies(): void
    {
        // Pas de `Gate::before` accordant un blanc-seing aux administrateurs :
        // certaines règles doivent s'appliquer même à eux (ex: un admin ne
        // peut pas modifier son propre compte via UserPolicy::update). Le
        // contournement admin est donc explicite, au cas par cas, dans
        // chaque Policy qui le justifie.
        Gate::policy(Item::class, ItemPolicy::class);
        Gate::policy(Skill::class, SkillPolicy::class);
        Gate::policy(Reservation::class, ReservationPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }

    private function configureRateLimiting(): void
    {
        // Un membre authentifié a plus de marge qu'un visiteur anonyme,
        // identifié par IP.
        RateLimiter::for('api', function ($request) {
            return $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip());
        });
    }
}
