<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\ArchiveExchangeableAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Vue d'administration des objets : contrairement à Api\ItemController,
 * elle n'est pas limitée aux annonces publiées ni au périmètre d'un seul
 * membre — un administrateur voit et modère tout.
 */
class ItemController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Item::query()->with(['category', 'owner']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $items = $query->latest()->paginate($request->integer('per_page', 20));

        return ItemResource::collection($items);
    }

    /**
     * Retrait modéré d'une annonce ne respectant pas les règles de la
     * plateforme, sans attendre l'action de son propriétaire.
     */
    public function archive(Item $item, ArchiveExchangeableAction $action): ItemResource
    {
        return new ItemResource($action($item));
    }

    public function destroy(Item $item): Response
    {
        $item->delete();

        return response()->noContent();
    }
}
