<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $query = User::query();

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        return UserResource::collection($query->latest()->paginate($request->integer('per_page', 20)));
    }

    public function show(User $user): UserResource
    {
        $this->authorize('viewAny', User::class);

        return new UserResource($user);
    }

    /**
     * Change le rôle d'un membre ou active/désactive son compte.
     * Un administrateur ne peut pas se modifier lui-même par ce biais
     * (voir UserPolicy::update), pour éviter de se retirer ses propres droits
     * par erreur.
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user->update($request->validated());

        return new UserResource($user);
    }

    public function destroy(User $user): Response
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->noContent();
    }
}
