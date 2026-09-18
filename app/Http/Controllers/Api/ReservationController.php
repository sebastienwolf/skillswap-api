<?php

namespace App\Http\Controllers\Api;

use App\Actions\Reservations\AcceptReservationAction;
use App\Actions\Reservations\CancelReservationAction;
use App\Actions\Reservations\CompleteReservationAction;
use App\Actions\Reservations\DeclineReservationAction;
use App\Actions\Reservations\RequestReservationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    /**
     * Associe le nom exposé publiquement au modèle concret. Garder cette
     * table à un seul endroit évite de faire fuiter les noms de classes
     * PHP dans le contrat de l'API (voir StoreReservationRequest).
     *
     * @return array<string, class-string<Item|Skill>>
     */
    private function reservableTypes(): array
    {
        return [
            'item' => Item::class,
            'skill' => Skill::class,
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        // `reservable.owner` (et pas seulement `reservable`) : ItemResource/
        // SkillResource exposent `owner` via `whenLoaded('owner')` — sans ce
        // chargement imbriqué, la clé `owner` disparaît purement et
        // simplement du JSON (elle n'est pas `null`, elle est absente), ce
        // qui fait planter le frontend là où `reservable.owner.id` est lu.
        $query = Reservation::query()->with(['requester', 'reservable.owner']);

        if ($request->query('scope') === 'received') {
            // Réservations reçues sur mes propres annonces : impossible à
            // filtrer par une simple colonne (relation polymorphique), on
            // restreint donc aux identifiants de mes Items/Skills.
            $itemIds = Item::query()->ownedBy($request->user()->id)->pluck('id');
            $skillIds = Skill::query()->ownedBy($request->user()->id)->pluck('id');

            $query->where(function ($q) use ($itemIds, $skillIds) {
                $q->where(fn ($q2) => $q2->where('reservable_type', Item::class)->whereIn('reservable_id', $itemIds))
                    ->orWhere(fn ($q2) => $q2->where('reservable_type', Skill::class)->whereIn('reservable_id', $skillIds));
            });
        } else {
            $query->where('requester_id', $request->user()->id);
        }

        return ReservationResource::collection($query->latest()->paginate($request->integer('per_page', 15)));
    }

    public function store(StoreReservationRequest $request, RequestReservationAction $action): ReservationResource
    {
        $modelClass = $this->reservableTypes()[$request->validated('reservable_type')] ?? null;

        if ($modelClass === null) {
            throw ValidationException::withMessages(['reservable_type' => 'Type de ressource inconnu.']);
        }

        $exchangeable = $modelClass::query()->findOrFail($request->validated('reservable_id'));

        $reservation = $action($exchangeable, $request->user(), $request->validated('message'));

        return new ReservationResource($reservation->load(['requester', 'reservable.owner']));
    }

    public function show(Reservation $reservation): ReservationResource
    {
        $this->authorize('view', $reservation);

        return new ReservationResource($reservation->load(['requester', 'reservable.owner']));
    }

    public function accept(Reservation $reservation, AcceptReservationAction $action): ReservationResource
    {
        $this->authorize('respond', $reservation);

        return new ReservationResource($action($reservation)->load(['requester', 'reservable.owner']));
    }

    public function decline(Reservation $reservation, DeclineReservationAction $action): ReservationResource
    {
        $this->authorize('respond', $reservation);

        return new ReservationResource($action($reservation)->load(['requester', 'reservable.owner']));
    }

    public function cancel(Reservation $reservation, CancelReservationAction $action): ReservationResource
    {
        $this->authorize('cancel', $reservation);

        return new ReservationResource($action($reservation)->load(['requester', 'reservable.owner']));
    }

    public function complete(Reservation $reservation, CompleteReservationAction $action): ReservationResource
    {
        $this->authorize('complete', $reservation);

        return new ReservationResource($action($reservation)->load(['requester', 'reservable.owner']));
    }
}
