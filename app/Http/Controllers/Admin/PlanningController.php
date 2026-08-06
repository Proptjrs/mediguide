<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Disponibilite, Medecin};
use Illuminate\Http\Request;

/**
 * Gestion des plannings de consultation par l'administrateur (mémoire, chap. 3) :
 * c'est l'admin, et non le médecin, qui fixe les plages horaires de base.
 */
class PlanningController extends Controller
{
    public function edit(Medecin $medecin)
    {
        return view('admin.planning', [
            'medecin' => $medecin->load('utilisateur', 'specialite', 'structure'),
            'creneaux' => $medecin->disponibilites()->base()->orderBy('jour_semaine')
                ->orderBy('heure_debut')->get()->groupBy('jour_semaine'),
        ]);
    }

    public function store(Medecin $medecin, Request $request)
    {
        $data = $request->validate([
            'jour_semaine' => 'required|integer|min:1|max:5',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
        ]);

        $medecin->disponibilites()->create([
            'type' => 'BASE',
            'jour_semaine' => $data['jour_semaine'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
        ]);

        return back()->with('ok', 'Plage horaire ajoutée au planning.');
    }

    public function destroy(Disponibilite $disponibilite)
    {
        abort_unless($disponibilite->type === 'BASE', 404);
        $medecin = $disponibilite->medecin;
        $disponibilite->delete();

        return redirect()->route('admin.planning.edit', $medecin)->with('ok', 'Plage horaire supprimée.');
    }
}
