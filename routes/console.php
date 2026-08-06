<?php

use App\Models\RendezVous;
use App\Notifications\RappelRdv;
use Illuminate\Support\Facades\Schedule;

/** F5 — job planifié : rappels J-1, toutes les heures (chap. 4.2.5). */
Schedule::call(function () {
    RendezVous::with('patient.utilisateur')
        ->where('statut', 'CONFIRME')
        ->where('rappel_envoye', false)
        ->whereBetween('date_heure', [now()->addDay()->startOfHour(), now()->addDay()->endOfHour()])
        ->each(function (RendezVous $rdv) {
            $rdv->patient->utilisateur->notify(new RappelRdv($rdv));
            $rdv->update(['rappel_envoye' => true]);
        });
})->hourly()->name('rappels-rdv');

/** Gestion des no-show : RDV passés jamais honorés (chap. 4.2.5). */
Schedule::call(function () {
    RendezVous::where('statut', 'CONFIRME')
        ->where('date_heure', '<', now()->subHours(2))
        ->update(['statut' => 'NO_SHOW']);
})->hourly()->name('no-show');
