<?php

namespace App\Http\Controllers\Medecin;

use App\Http\Controllers\Controller;
use App\Models\DossierPatient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Consultation du dossier d'un patient par son médecin (mémoire, chap. 2 :
 * « Accéder au dossier des patients ayant un rendez-vous confirmé ou honoré
 * avec lui »).
 *
 * La règle d'accès n'est pas réécrite ici : elle est portée par DossierPolicy
 * (chap. 4.2.7), qui n'autorise le médecin que si un rendez-vous CONFIRME ou
 * HONORE le lie effectivement à ce patient.
 */
class DossierController extends Controller
{
    public function show(Request $request, DossierPatient $dossier)
    {
        Gate::authorize('view', $dossier);

        $medecin = $request->user()->medecin;

        return view('medecin.dossier', [
            'dossier' => $dossier->load('patient.utilisateur'),
            // On ne montre que ce qui concerne ce médecin : ses propres
            // rendez-vous et ses propres comptes rendus avec ce patient.
            'rdvs' => $dossier->patient->rendezVous()
                ->where('medecin_id', $medecin->id)
                ->latest('date_heure')->get(),
            'consultations' => $dossier->patient->consultations()
                ->where('medecin_id', $medecin->id)
                ->with('medecin.specialite')->latest()->get(),
        ]);
    }
}
