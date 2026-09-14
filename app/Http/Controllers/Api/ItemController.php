<?php

namespace App\Http\Controllers\Api;

use App\Actions\ArchiveExchangeableAction;
use App\Actions\PublishExchangeableAction;
use App\Enums\ExchangeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Items\StoreItemRequest;
use App\Http\Requests\Items\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ItemController extends Controller
{
    /**
     * Catalogue public des objets publiés. Un membre connecté peut filtrer
     * ses propres annonces (y compris non publiées) via `?mine=1`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Item::query()->with(['category', 'owner']);

        if ($request->boolean('mine') && $request->user()) {
            $query->ownedBy($request->user()->id);
        } else {
            $query->published();
        }

        if ($type = $request->query('type')) {
            $query->ofType(ExchangeType::from($type));
        }

        if ($categoryId = $request->query('category_id')) {
            $query->byCategory((int) $categoryId);
        }

        $items = $query->search($request->query('q'))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ItemResource::collection($items);
    }

    public function store(StoreItemRequest $request): ItemResource
    {
        $item = $request->user()->items()->create($request->validated());

        return new ItemResource($item->load(['category', 'owner']));
    }

    public function show(Item $item): ItemResource
    {
        return new ItemResource($item->load(['category', 'owner']));
    }

    public function update(UpdateItemRequest $request, Item $item): ItemResource
    {
        $item->update($request->validated());

        return new ItemResource($item->load(['category', 'owner']));
    }

    public function destroy(Request $request, Item $item): Response
    {
        $this->authorize('delete', $item);

        $item->delete();

        return response()->noContent();
    }

    public function publish(Request $request, Item $item, PublishExchangeableAction $action): ItemResource
    {
        $this->authorize('manageLifecycle', $item);

        return new ItemResource($action($item));
    }

    public function archive(Request $request, Item $item, ArchiveExchangeableAction $action): ItemResource
    {
        $this->authorize('manageLifecycle', $item);

        return new ItemResource($action($item));
    }
}
