<?php

namespace App\Http\Controllers\Secretaire;

use App\Http\Controllers\Controller;
use App\Models\Disponibilite;
use App\Services\RdvService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * L'agenda tenu par la secrétaire médicale.
 *
 * Dans un service hospitalier, ce n'est pas le médecin qui note ses absences
 * entre deux consultations : sa secrétaire tient l'agenda. Elle dispose donc des
 * mêmes gestes que lui sur les disponibilités, et d'aucun autre : ni comptes, ni
 * structures, ni plannings de base — ceux-ci restent à l'administration.
 */
class AgendaController extends Controller
{
    private const MOTIFS = ['conge', 'mission', 'urgence', 'formation'];

    public function index(Request $request, RdvService $rdv)
    {
        $medecin = $this->medecinAssiste($request);
        $lundi = Carbon::now()->startOfWeek();

        return view('secretaire.agenda', [
            'medecin' => $medecin->load('utilisateur', 'specialite', 'structure'),
            'semaine' => $rdv->creneauxSemaine($medecin, $lundi),
            'lundi' => $lundi,
            'absences' => $medecin->disponibilites()
                ->where('type', 'INDISPONIBILITE')
                ->where('date', '>=', today())->orderBy('date')->get(),
            'rendezVous' => $medecin->rendezVous()
                ->where('date_heure', '>=', now())
                ->orderBy('date_heure')->with('patient.utilisateur')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $medecin = $this->medecinAssiste($request);

        $data = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'heure_debut' => 'nullable|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i|after:heure_debut|required_with:heure_debut',
            'motif' => 'required|in:' . implode(',', self::MOTIFS),
        ]);

        $medecin->disponibilites()->create([
            'type' => 'INDISPONIBILITE',
            'date' => $data['date'],
            'heure_debut' => $data['heure_debut'] ?? null,
            'heure_fin' => $data['heure_fin'] ?? null,
            'motif' => $data['motif'],
        ]);

        return back()->with('ok', "Absence enregistrée pour le médecin.");
    }

    public function destroy(Request $request, Disponibilite $disponibilite)
    {
        $medecin = $this->medecinAssiste($request);
        abort_unless($disponibilite->type === 'INDISPONIBILITE', 404);
        // Une secrétaire n'agit que sur l'agenda du médecin qu'elle assiste.
        abort_unless($disponibilite->medecin_id === $medecin->id, 403);

        $disponibilite->delete();

        return back()->with('ok', 'Absence annulée.');
    }

    /** Le médecin assisté, ou 403 si le compte n'est rattaché à personne. */
    private function medecinAssiste(Request $request)
    {
        $medecin = $request->user()->secretaire?->medecin;
        abort_unless($medecin, 403, "Votre compte n'est rattaché à aucun médecin.");

        return $medecin;
    }
}
