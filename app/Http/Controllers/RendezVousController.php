<?php

namespace App\Http\Controllers;

use App\Models\{Medecin, RendezVous};
use App\Http\Requests\ReserverRdvRequest;
use App\Services\RdvService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RendezVousController extends Controller
{
    /** F4 — calendrier hebdomadaire des créneaux d'un médecin. */
    public function calendrier(Medecin $medecin, Request $request, RdvService $rdv)
    {
        // Le calendrier est consultable sans compte. Si un visiteur clique un
        // créneau, il passe par la connexion puis revient ici automatiquement.
        if (! $request->user()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        $lundi = Carbon::parse($request->query('semaine', 'now'))->startOfWeek();

        return view('calendrier', [
            'medecin' => $medecin->load('utilisateur', 'specialite', 'structure'),
            'lundi' => $lundi,
            'semaine' => $rdv->creneauxSemaine($medecin, $lundi),
        ]);
    }

    /** F4 — réservation (transaction + lockForUpdate dans le service). */
    public function reserver(Medecin $medecin, ReserverRdvRequest $request, RdvService $rdv)
    {

        try {
            $rdv->creerRendezVous(
                $request->user()->patient,
                $medecin,
                Carbon::parse($request->input('date_heure')),
                $request->input('motif')
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['creneau' => $e->getMessage()]);
        }

        return redirect()->route('dashboard')
            ->with('ok', 'Rendez-vous confirmé — une confirmation vous a été envoyée par e-mail.');
    }

    /** Annulation par le patient (chap. 2 — "prendre, modifier ou annuler un rendez-vous"). */
    public function annuler(RendezVous $rendezVous, Request $request, RdvService $rdv)
    {
        abort_unless($request->user()->patient?->id === $rendezVous->patient_id, 403);

        $rdv->annuler($rendezVous);

        return back()->with('ok', 'Rendez-vous annulé.');
    }
}
