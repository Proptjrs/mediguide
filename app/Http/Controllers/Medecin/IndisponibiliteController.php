<?php

namespace App\Http\Controllers\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Disponibilite;
use Illuminate\Http\Request;

/**
 * Déclaration d'indisponibilité ponctuelle par le médecin (mémoire, chap. 2 et 3) :
 * le médecin ne définit pas son planning de base, il déclare uniquement des exceptions
 * (congé, mission, urgence, formation) au planning fixé par l'admin.
 */
class IndisponibiliteController extends Controller
{
    private const MOTIFS = ['conge', 'mission', 'urgence', 'formation'];

    public function store(Request $request)
    {
        $medecin = $request->user()->medecin;
        abort_unless($medecin, 403);

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

        return back()->with('ok', 'Indisponibilité déclarée.');
    }

    public function destroy(Request $request, Disponibilite $disponibilite)
    {
        abort_unless($disponibilite->type === 'INDISPONIBILITE', 404);
        abort_unless($disponibilite->medecin_id === $request->user()->medecin?->id, 403);

        $disponibilite->delete();

        return back()->with('ok', 'Indisponibilité annulée.');
    }
}
