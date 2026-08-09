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

    /**
     * Clôture d'un rendez-vous par le médecin qui l'a reçu.
     *
     * Le rendez-vous a deux fins possibles : le patient s'est présenté, ou il
     * ne s'est pas présenté. La distinction alimente l'indicateur des
     * rendez-vous manqués du tableau de bord de l'administration.
     */
    public function clore(RendezVous $rendezVous, Request $request, string $issue)
    {
        abort_unless($request->user()->medecin?->id === $rendezVous->medecin_id, 403);
        abort_unless($rendezVous->statut === 'CONFIRME', 409);

        $rendezVous->update(['statut' => $issue]);

        return back()->with('ok', $issue === 'HONORE'
            ? 'Rendez-vous marqué comme honoré.'
            : 'Rendez-vous marqué comme non honoré.');
    }

    public function honorer(RendezVous $rendezVous, Request $request)
    {
        return $this->clore($rendezVous, $request, 'HONORE');
    }

    public function absent(RendezVous $rendezVous, Request $request)
    {
        return $this->clore($rendezVous, $request, 'NO_SHOW');
    }
}
