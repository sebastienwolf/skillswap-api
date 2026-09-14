<?php

namespace App\Http\Controllers\Api;

use App\Actions\ArchiveExchangeableAction;
use App\Actions\PublishExchangeableAction;
use App\Filters\ExchangeableFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Skills\StoreSkillRequest;
use App\Http\Requests\Skills\UpdateSkillRequest;
use App\Http\Resources\SkillResource;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SkillController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Skill::query()->with(['category', 'owner']);

        ExchangeableFilters::fromRequest($request)->apply($query);

        $skills = $query->search($request->query('q'))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return SkillResource::collection($skills);
    }

    public function store(StoreSkillRequest $request): SkillResource
    {
        $skill = $request->user()->skills()->create($request->validated());

        return new SkillResource($skill->load(['category', 'owner']));
    }

    public function show(Skill $skill): SkillResource
    {
        return new SkillResource($skill->load(['category', 'owner']));
    }

    public function update(UpdateSkillRequest $request, Skill $skill): SkillResource
    {
        $skill->update($request->validated());

        return new SkillResource($skill->load(['category', 'owner']));
    }

    public function destroy(Request $request, Skill $skill): Response
    {
        $this->authorize('delete', $skill);

        $skill->delete();

        return response()->noContent();
    }

    public function publish(Request $request, Skill $skill, PublishExchangeableAction $action): SkillResource
    {
        $this->authorize('manageLifecycle', $skill);

        return new SkillResource($action($skill));
    }

    public function archive(Request $request, Skill $skill, ArchiveExchangeableAction $action): SkillResource
    {
        $this->authorize('manageLifecycle', $skill);

        return new SkillResource($action($skill));
    }
}
