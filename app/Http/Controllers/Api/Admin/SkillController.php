<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\ArchiveExchangeableAction;
use App\Http\Controllers\Controller;
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

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $skills = $query->latest()->paginate($request->integer('per_page', 20));

        return SkillResource::collection($skills);
    }

    public function archive(Skill $skill, ArchiveExchangeableAction $action): SkillResource
    {
        return new SkillResource($action($skill));
    }

    public function destroy(Skill $skill): Response
    {
        $skill->delete();

        return response()->noContent();
    }
}
