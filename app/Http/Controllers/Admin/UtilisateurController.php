<?php

namespace App\Http\Controllers\Admin;

use App\Support\Courriel;
use App\Http\Controllers\Controller;
use App\Models\{Medecin, Patient, Secretaire, User};
use App\Notifications\AdresseModifiee;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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
 *              publique d'inscription administrateur ;
 *  - secrétaire : créée ici également, et rattachée au médecin qu'elle assiste.
 *              Sans ce rattachement son espace n'aurait aucun agenda à tenir.
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
        return view('admin.utilisateurs.form', [
            'utilisateur' => new User(),
            'medecins' => $this->medecinsValides(),
        ]);
    }

    /** Les médecins auxquels une secrétaire peut être rattachée. */
    private function medecinsValides()
    {
        return Medecin::with('utilisateur', 'specialite')
            ->where('valide', true)->get()
            ->sortBy(fn ($m) => $m->utilisateur->nom);
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
            'email' => 'required|email:rfc|unique:users,email',
            'telephone' => 'nullable|string|max:30',
            'role' => 'required|in:patient,admin,secretaire',
            // Une secrétaire sans médecin n'aurait pas d'agenda : le champ est
            // donc exigé, mais seulement pour ce rôle.
            'medecin_id' => 'required_if:role,secretaire|nullable|exists:medecins,id',
            'password' => 'required|min:8',
        ], [
            'medecin_id.required_if' => 'Indiquez le médecin que cette secrétaire assiste.',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                ...collect($data)->except('medecin_id')->all(),
                'email_verified_at' => now(),
            ]);

            if ($user->role === 'patient') {
                Patient::create(['utilisateur_id' => $user->id]);
            }
            if ($user->role === 'secretaire') {
                Secretaire::create([
                    'utilisateur_id' => $user->id,
                    'medecin_id' => $data['medecin_id'],
                ]);
            }

            return $user;
        });

        return redirect()->route('admin.utilisateurs.index')
            ->with('ok', 'Compte créé pour ' . $user->fullName() . ' (' . $user->role . ').');
    }

    public function edit(User $utilisateur)
    {
        return view('admin.utilisateurs.form', [
            'utilisateur' => $utilisateur,
            'medecins' => $this->medecinsValides(),
        ]);
    }

    public function update(Request $request, User $utilisateur)
    {
        $data = $request->validate([
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => ['required', 'email:rfc', Rule::unique('users', 'email')->ignore($utilisateur->id)],
            'telephone' => 'nullable|string|max:30',
        ]);

        // Changer l'adresse annule la confirmation : la nouvelle boîte doit être prouvée.
        if ($data['email'] !== $utilisateur->email) {
            $ancienne = $utilisateur->email;
            $data['email_verified_at'] = null;
            $utilisateur->forceFill($data)->save();
            Courriel::tenter(
                fn () => $utilisateur->sendEmailVerificationNotification(),
                "lien de confirmation d'adresse"
            );
            $this->avertirAncienneAdresse($ancienne, $utilisateur, $request->user());

            return redirect()->route('admin.utilisateurs.index')
                ->with('ok', 'Compte mis à jour — un lien de confirmation a été envoyé à '
                    . $utilisateur->email . ', et l\'ancienne adresse a été prévenue.');
        }

        $utilisateur->update($data);

        return redirect()->route('admin.utilisateurs.index')->with('ok', 'Compte mis à jour.');
    }

    /**
     * Prévient l'ancienne adresse qu'elle ne pilote plus le compte.
     *
     * Sans cet avertissement, un administrateur — ou quelqu'un qui aurait pris
     * la main sur un compte d'administration — pourrait rediriger le compte
     * d'un patient vers sa propre boîte, confirmer la nouvelle adresse et en
     * prendre le contrôle sans que le titulaire n'en sache jamais rien. Le
     * message ne l'empêche pas, mais il le rend visible : c'est le principe
     * d'une opération sensible qui laisse une trace chez la personne concernée.
     *
     * L'envoi ne doit pas faire échouer la modification : elle est déjà
     * enregistrée, et une panne de messagerie ne doit pas la défaire.
     */
    private function avertirAncienneAdresse(string $ancienne, User $compte, User $auteur): void
    {
        try {
            // Notification « à la volée » : l'ancienne adresse n'appartient
            // plus à aucun compte, on lui écrit donc directement.
            Courriel::tenter(
                fn () => Notification::route('mail', $ancienne)
                    ->notify(new AdresseModifiee($ancienne, $compte, $auteur)),
                "avertissement à l'ancienne adresse"
            );
        } catch (\Throwable $e) {
            Log::warning("Avertissement non envoyé à {$ancienne} : " . $e->getMessage());
        }
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
            $utilisateur->patient?->delete();
            $utilisateur->medecin?->delete();
            $utilisateur->delete();
        });

        return redirect()->route('admin.utilisateurs.index')->with('ok', 'Compte supprimé : ' . $nom . '.');
    }
}
