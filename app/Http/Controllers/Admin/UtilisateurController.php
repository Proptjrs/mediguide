<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{DossierPatient, Patient, User};
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Gestion des comptes utilisateurs par l'administrateur
 * (mémoire, chap. 2 : « Créer, modifier, suspendre ou supprimer un compte »).
 *
 * Rappel de la logique d'accès aux trois rôles :
 *  - patient : libre-service, une seule barrière (confirmer son adresse) ;
 *  - médecin : libre-service + validation du n° d'Ordre par l'admin ;
 *  - admin   : jamais en libre-service — créé par le seeder puis par un autre
 *              admin depuis cet écran. C'est pourquoi il n'existe aucune page
 *              publique d'inscription administrateur.
 */
class UtilisateurController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->query('q');

        return view('admin.utilisateurs.index', [
            'utilisateurs' => User::query()
                ->when($recherche, fn ($q) => $q->where(fn ($s) => $s
                    ->where('nom', 'like', "%{$recherche}%")
                    ->orWhere('prenom', 'like', "%{$recherche}%")
                    ->orWhere('email', 'like', "%{$recherche}%")))
                ->orderBy('role')->orderBy('nom')
                ->paginate(20)->withQueryString(),
            'recherche' => $recherche,
        ]);
    }

    public function create()
    {
        return view('admin.utilisateurs.form', ['utilisateur' => new User()]);
    }

    /**
     * Un compte créé ici est considéré comme vérifié : c'est l'administration
     * qui le crée et répond de l'adresse. Le titulaire reçoit tout de même ses
     * identifiants par les voies internes de la structure.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => 'required|email:rfc,dns|unique:users,email',
            'telephone' => 'nullable|string|max:30',
            'role' => 'required|in:patient,admin',
            'password' => 'required|min:8',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([...$data, 'email_verified_at' => now()]);

            if ($user->role === 'patient') {
                $patient = Patient::create(['utilisateur_id' => $user->id]);
                DossierPatient::create(['patient_id' => $patient->id]);
            }

            return $user;
        });

        return redirect()->route('admin.utilisateurs.index')
            ->with('ok', 'Compte créé pour ' . $user->fullName() . ' (' . $user->role . ').');
    }

    public function edit(User $utilisateur)
    {
        return view('admin.utilisateurs.form', ['utilisateur' => $utilisateur]);
    }

    public function update(Request $request, User $utilisateur)
    {
        $data = $request->validate([
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => ['required', 'email:rfc,dns', Rule::unique('users', 'email')->ignore($utilisateur->id)],
            'telephone' => 'nullable|string|max:30',
        ]);

        // Changer l'adresse annule la confirmation : la nouvelle boîte doit être prouvée.
        if ($data['email'] !== $utilisateur->email) {
            $data['email_verified_at'] = null;
            $utilisateur->forceFill($data)->save();
            $utilisateur->sendEmailVerificationNotification();

            return redirect()->route('admin.utilisateurs.index')
                ->with('ok', 'Compte mis à jour — un lien de confirmation a été envoyé à ' . $utilisateur->email . '.');
        }

        $utilisateur->update($data);

        return redirect()->route('admin.utilisateurs.index')->with('ok', 'Compte mis à jour.');
    }

    /** Suspension / réactivation : le compte reste en base mais ne peut plus se connecter. */
    public function basculerActivation(Request $request, User $utilisateur)
    {
        if ($utilisateur->id === $request->user()->id) {
            return back()->with('erreur', 'Vous ne pouvez pas suspendre votre propre compte.');
        }

        $utilisateur->update(['actif' => ! $utilisateur->actif]);

        return back()->with('ok', $utilisateur->actif
            ? 'Compte réactivé : ' . $utilisateur->fullName() . '.'
            : 'Compte suspendu : ' . $utilisateur->fullName() . '.');
    }

    public function destroy(Request $request, User $utilisateur)
    {
        if ($utilisateur->id === $request->user()->id) {
            return back()->with('erreur', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        // Un médecin ayant des rendez-vous ne doit pas disparaître : on le suspend.
        if ($utilisateur->medecin?->rendezVous()->exists()) {
            return back()->with('erreur',
                'Ce médecin a des rendez-vous enregistrés : suspendez son compte plutôt que de le supprimer.');
        }

        $nom = $utilisateur->fullName();

        DB::transaction(function () use ($utilisateur) {
            $utilisateur->patient?->dossier?->delete();
            $utilisateur->patient?->delete();
            $utilisateur->medecin?->delete();
            $utilisateur->delete();
        });

        return redirect()->route('admin.utilisateurs.index')->with('ok', 'Compte supprimé : ' . $nom . '.');
    }
}
