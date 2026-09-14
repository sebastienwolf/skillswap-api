<?php

use Illuminate\Support\Facades\Route;

// Projet 100% API : la racine web ne sert qu'à confirmer que l'application
// tourne. Toute la logique métier est exposée sous /api (voir routes/api.php).
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'status' => 'ok',
    'docs' => '/api',
]));
