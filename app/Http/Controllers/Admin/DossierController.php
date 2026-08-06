<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{DossierPatient, Patient};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Consultation des dossiers patients par l'administrateur (mémoire, chap. 2 et 4.2.7) :
 * accès superviseur, en lecture seule, via DossierPolicy — pas l'espace personnel du patient.
 */
class DossierController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->query('q');

        $patients = Patient::with('utilisateur', 'dossier')
            ->whereHas('utilisateur', function ($q) use ($recherche) {
                if ($recherche) {
                    $q->where('nom', 'like', "%{$recherche}%")
                        ->orWhere('prenom', 'like', "%{$recherche}%")
                        ->orWhere('email', 'like', "%{$recherche}%");
                }
            })
            ->paginate(15)->withQueryString();

        return view('admin.dossiers.index', ['patients' => $patients, 'recherche' => $recherche]);
    }

    public function show(DossierPatient $dossier)
    {
        Gate::authorize('view', $dossier);

        return view('admin.dossiers.show', [
            'dossier' => $dossier->load('patient.utilisateur'),
            'consultations' => $dossier->patient->consultations()
                ->with('medecin.utilisateur', 'medecin.specialite')->latest()->get(),
            'rdvs' => $dossier->patient->rendezVous()
                ->with('medecin.utilisateur', 'medecin.specialite')->latest('date_heure')->get(),
        ]);
    }
}
