<?php

namespace App\Http\Controllers;

use App\Support\Courriel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Validation\Rule;

/**
 * Profil de l'utilisateur connecté.
 *
 * Répond à l'exigence du chap. 2 pour le médecin (« gérer son profil
 * professionnel ») et sert les trois rôles : identité, contact, mot de passe.
 * Le numéro d'Ordre et la validation restent en lecture seule — ils relèvent de
 * l'administrateur (chap. 3, UC-A2).
 */
class ProfilController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return view('profil', [
            'utilisateur' => $user,
            'medecin' => $user->medecin?->load('specialite', 'structure'),
            'patient' => $user->patient,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => ['required', 'email:rfc', Rule::unique('users', 'email')->ignore($user->id)],
            'telephone' => 'nullable|string|max:30',
        ]);

        // Changer d'adresse annule la confirmation : la nouvelle boîte doit être
        // prouvée à son tour, sinon on pourrait rediriger ses notifications
        // vers une adresse qui ne nous appartient pas.
        $adresseChangee = $data['email'] !== $user->email;
        if ($adresseChangee) {
            $data['email_verified_at'] = null;
        }

        $user->forceFill($data)->save();

        if ($adresseChangee) {
            Courriel::tenter(
                fn () => $user->sendEmailVerificationNotification(),
                "lien de confirmation d'adresse"
            );

            return redirect()->route('verification.notice')
                ->with('ok', 'Adresse modifiée — un lien de confirmation vient d\'être envoyé à ' . $user->email . '.');
        }

        return back()->with('ok', 'Profil mis à jour.');
    }

    /** Renseignements de santé du profil : groupe sanguin et allergies. */
    public function updatePatient(Request $request)
    {
        $patient = $request->user()->patient;
        abort_unless($patient, 403);

        $data = $request->validate([
            'groupe_sanguin' => 'nullable|string|max:5',
            'allergies' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($patient, $data) {
            $patient->update([
                'groupe_sanguin' => $data['groupe_sanguin'] ?? null,
                'allergies' => $data['allergies'] ?? null,
            ]);
        });

        return back()->with('ok', 'Renseignements de santé mis à jour.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'mot_de_passe_actuel' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (! Hash::check($data['mot_de_passe_actuel'], $request->user()->password)) {
            return back()->withErrors(['mot_de_passe_actuel' => 'Mot de passe actuel incorrect.']);
        }

        $request->user()->update(['password' => $data['password']]);

        return back()->with('ok', 'Mot de passe modifié.');
    }
}
