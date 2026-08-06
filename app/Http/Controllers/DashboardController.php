<?php

namespace App\Http\Controllers;

use App\Models\{Medecin, RendezVous, StructureMedicale, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /** F6-F12 — tableau de bord selon le rôle. */
    public function index(Request $request)
    {
        $user = $request->user();

        return match ($user->role) {
            'medecin' => view('dashboard.medecin', [
                'rdvsJour' => $user->medecin->rendezVous()
                    ->with('patient.utilisateur')->whereDate('date_heure', today())
                    ->orderBy('date_heure')->get(),
                'planningBase' => $user->medecin->disponibilites()->base()
                    ->orderBy('jour_semaine')->orderBy('heure_debut')->get()->groupBy('jour_semaine'),
                'indisponibilites' => $user->medecin->disponibilites()->indisponibilite()
                    ->where('date', '>=', today())->orderBy('date')->get(),
            ]),
            'admin' => view('dashboard.admin', [
                'nbPatients' => User::where('role', 'patient')->count(),
                'nbMedecins' => Medecin::where('valide', true)->count(),
                'enAttente' => Medecin::with('utilisateur', 'specialite')->where('valide', false)->get(),
                'medecins' => Medecin::with('utilisateur', 'specialite')->where('valide', true)->get(),
                'nbStructures' => StructureMedicale::count(),
                'nbComptes' => User::count(),
                'rdvSemaine' => RendezVous::whereBetween('date_heure',
                    [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'rdvParSpecialite' => $this->rdvParSpecialite(),
            ]),
            default => view('dashboard.patient', [
                'rdvs' => $user->patient?->rendezVous()
                    ->with('medecin.utilisateur', 'medecin.structure', 'medecin.specialite')
                    ->where('date_heure', '>=', now())->where('statut', 'CONFIRME')
                    ->orderBy('date_heure')->get() ?? collect(),
                'dossier' => $user->patient?->dossier,
                'consultations' => $user->patient?->consultations()
                    ->with('medecin.utilisateur', 'medecin.specialite')
                    ->whereNotNull('prescription')->latest()->get() ?? collect(),
                'notifications' => $user->notifications()->latest()->take(5)->get(),
            ]),
        };
    }

    /**
     * Répartition des rendez-vous du mois par spécialité (tableau de bord
     * statistique de l'administrateur, chap. 2). Alimente le graphique en anneau.
     */
    private function rdvParSpecialite(): array
    {
        $couleurs = ['#0284C7', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444', '#0EA5E9'];

        return RendezVous::query()
            ->join('medecins', 'medecins.id', '=', 'rendez_vous.medecin_id')
            ->join('specialites', 'specialites.id', '=', 'medecins.specialite_id')
            ->whereBetween('rendez_vous.date_heure', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('rendez_vous.statut', '!=', 'ANNULE')
            ->groupBy('specialites.nom')
            ->orderByDesc('total')
            ->limit(count($couleurs))
            ->get([DB::raw('specialites.nom AS libelle'), DB::raw('COUNT(*) AS total')])
            ->values()
            ->map(fn ($ligne, $i) => [
                'libelle' => $ligne->libelle,
                'valeur' => (int) $ligne->total,
                'couleur' => $couleurs[$i],
            ])
            ->all();
    }

    /** UC-A2 — validation d'un médecin par l'administrateur. */
    public function validerMedecin(Medecin $medecin)
    {
        $medecin->update(['valide' => true]);

        return back()->with('ok', 'Médecin validé : ' . $medecin->utilisateur->fullName());
    }
}
