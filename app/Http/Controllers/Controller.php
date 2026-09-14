<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Laravel 11+ ne fournit plus ce trait par défaut : on le rajoute ici
    // une seule fois pour bénéficier de `$this->authorize(...)` dans tous
    // les contrôleurs, plutôt que d'appeler le Gate façade partout.
    use AuthorizesRequests;
}
