<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ExchangeStatsService;
use Illuminate\Http\JsonResponse;

/**
 * Tableau de bord d'administration : une vue d'ensemble de l'activité de
 * la plateforme (comptes, annonces, réservations, catégories les plus
 * actives). Toute la logique de calcul vit dans ExchangeStatsService,
 * le contrôleur ne fait que l'exposer.
 */
class DashboardController extends Controller
{
    public function index(ExchangeStatsService $stats): JsonResponse
    {
        return response()->json(['data' => $stats->summary()]);
    }
}
