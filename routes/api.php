<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ItemController as AdminItemController;
use App\Http\Controllers\Api\Admin\SkillController as AdminSkillController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SkillController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/items', [ItemController::class, 'index']);
Route::get('/items/{item}', [ItemController::class, 'show']);

Route::get('/skills', [SkillController::class, 'index']);
Route::get('/skills/{skill}', [SkillController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Routes authentifiées (membres)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Mêmes méthodes `index()` que les routes publiques ci-dessus (le filtre
    // `mine` géré par ExchangeableFilters ne change pas), mais protégées par
    // `auth:sanctum` : contrairement à `/items?mine=1`, l'authentification
    // est ici garantie avant d'exécuter le contrôleur (401 propre si le
    // token est absent/invalide), plutôt que de dépendre silencieusement
    // d'une résolution d'utilisateur optionnelle sur une route publique.
    Route::get('/me/items', [ItemController::class, 'index']);
    Route::get('/me/skills', [SkillController::class, 'index']);

    Route::post('/items', [ItemController::class, 'store']);
    Route::patch('/items/{item}', [ItemController::class, 'update']);
    Route::delete('/items/{item}', [ItemController::class, 'destroy']);
    Route::post('/items/{item}/publish', [ItemController::class, 'publish']);
    Route::post('/items/{item}/archive', [ItemController::class, 'archive']);

    Route::post('/skills', [SkillController::class, 'store']);
    Route::patch('/skills/{skill}', [SkillController::class, 'update']);
    Route::delete('/skills/{skill}', [SkillController::class, 'destroy']);
    Route::post('/skills/{skill}/publish', [SkillController::class, 'publish']);
    Route::post('/skills/{skill}/archive', [SkillController::class, 'archive']);

    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::post('/reservations/{reservation}/accept', [ReservationController::class, 'accept']);
    Route::post('/reservations/{reservation}/decline', [ReservationController::class, 'decline']);
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
    Route::post('/reservations/{reservation}/complete', [ReservationController::class, 'complete']);
});

/*
|--------------------------------------------------------------------------
| Routes d'administration
|--------------------------------------------------------------------------
|
| Le middleware `admin` (voir bootstrap/app.php et EnsureUserIsAdmin) ne
| fait qu'une vérification de rôle ; l'autorisation fine reste portée par
| les Policies lorsqu'elle existe (ex: UserPolicy::update).
|
*/

Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'account.active', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/items', [AdminItemController::class, 'index'])->name('items.index');
    Route::post('/items/{item}/archive', [AdminItemController::class, 'archive'])->name('items.archive');
    Route::delete('/items/{item}', [AdminItemController::class, 'destroy'])->name('items.destroy');

    Route::get('/skills', [AdminSkillController::class, 'index'])->name('skills.index');
    Route::post('/skills/{skill}/archive', [AdminSkillController::class, 'archive'])->name('skills.archive');
    Route::delete('/skills/{skill}', [AdminSkillController::class, 'destroy'])->name('skills.destroy');

    Route::apiResource('categories', AdminCategoryController::class)->except(['show']);

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
});
