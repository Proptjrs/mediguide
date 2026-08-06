<?php

namespace App\Http\Controllers;

use App\Models\{Consultation, RendezVous};
use Illuminate\Http\Request;

/** F9 — rédaction du compte-rendu de consultation (UC-M5). */
class ConsultationController extends Controller
{
    public function store(RendezVous $rendezVous, Request $request)
    {
        abort_unless($request->user()->medecin?->id === $rendezVous->medecin_id, 403);

        $data = $request->validate([
            'observations' => 'required|string|min:10',
            'prescription' => 'nullable|string',
        ]);

        Consultation::create([
            'rendez_vous_id' => $rendezVous->id,
            'medecin_id' => $rendezVous->medecin_id,
            'patient_id' => $rendezVous->patient_id,
            ...$data,
        ]);
        $rendezVous->update(['statut' => 'HONORE']);            // cycle du RDV

        return back()->with('ok', 'Compte-rendu enregistré au dossier patient.');
    }

    /** Ordonnance imprimable — le patient concerné, le médecin rédacteur ou l'admin (superviseur). */
    public function ordonnance(Consultation $consultation, Request $request)
    {
        $user = $request->user();
        $autorise = match ($user->role) {
            'patient' => $user->patient?->id === $consultation->patient_id,
            'medecin' => $user->medecin?->id === $consultation->medecin_id,
            'admin' => true,
            default => false,
        };
        abort_unless($autorise, 403);

        return view('ordonnance', [
            'consultation' => $consultation->load('medecin.utilisateur', 'medecin.specialite', 'medecin.structure', 'patient.utilisateur'),
        ]);
    }
}
