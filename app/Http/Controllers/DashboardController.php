<?php

namespace App\Http\Controllers;

use App\Models\{Medecin, Questionnaire, RendezVous, StructureMedicale, User};
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
                'indicateurs' => $this->indicateurs(),
                'activiteMensuelle' => $this->activiteMensuelle(),
                'patientsARisque' => $this->patientsARisque(),
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

    /**
     * Indicateurs de pilotage sur les trente derniers jours : ils répondent aux
     * questions que se pose la direction, et non au simple décompte d'objets.
     */
    private function indicateurs(): array
    {
        $depuis = now()->subDays(30);
        $clos = RendezVous::where('date_heure', '>=', $depuis)
            ->whereIn('statut', ['HONORE', 'NO_SHOW'])->count();
        $manques = RendezVous::where('date_heure', '>=', $depuis)
            ->where('statut', 'NO_SHOW')->count();
        $questionnaires = Questionnaire::where('created_at', '>=', $depuis)->count();
        $urgences = Questionnaire::where('created_at', '>=', $depuis)
            ->where('urgence_detectee', true)->count();

        return [
            'rdvClos' => $clos,
            'manques' => $manques,
            'tauxManques' => $clos > 0 ? round($manques / $clos * 100) : 0,
            'annulations' => RendezVous::where('date_heure', '>=', $depuis)
                ->where('statut', 'ANNULE')->count(),
            'questionnaires' => $questionnaires,
            'urgences' => $urgences,
            'tauxUrgence' => $questionnaires > 0 ? round($urgences / $questionnaires * 100) : 0,
            'urgenceMoyenne' => round((float) Questionnaire::where('created_at', '>=', $depuis)
                ->avg('niveau_urgence'), 1),
        ];
    }

    /** Rendez-vous des six derniers mois, pour lire la tendance d'activité. */
    private function activiteMensuelle(): array
    {
        $mois = collect(range(5, 0))->map(fn ($n) => now()->subMonths($n)->startOfMonth());

        $comptes = RendezVous::query()
            ->where('date_heure', '>=', $mois->first())
            ->where('statut', '!=', 'ANNULE')
            ->get(['date_heure'])
            ->countBy(fn ($r) => $r->date_heure->format('Y-m'));

        return $mois->map(fn ($m) => [
            'libelle' => ucfirst($m->translatedFormat('M')),
            'valeur' => (int) ($comptes[$m->format('Y-m')] ?? 0),
        ])->all();
    }

    /**
     * Patients dont le dernier questionnaire a déclenché une alerte d'urgence
     * sans qu'un rendez-vous n'ait suivi : ce sont eux qu'il faut rappeler.
     */
    private function patientsARisque()
    {
        return Questionnaire::query()
            ->with('patient.utilisateur')
            ->where('urgence_detectee', true)
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('patient_id')
            ->latest()
            ->get()
            ->unique('patient_id')
            ->take(8);
    }

    /** UC-A2 — validation d'un médecin par l'administrateur. */
    public function validerMedecin(Medecin $medecin)
    {
        $medecin->update(['valide' => true]);

        return back()->with('ok', 'Médecin validé : ' . $medecin->utilisateur->fullName());
    }
}
