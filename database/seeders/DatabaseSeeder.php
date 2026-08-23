<?php

namespace Database\Seeders;

use App\Models\{Disponibilite, Medecin, Patient, Secretaire, Specialite, User};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Jeu de données du site pilote : district sanitaire de Guédiawaye.
     *
     * Le semis est rejoué à chaque démarrage du serveur : il s'interrompt donc
     * de lui-même si les données sont déjà en place, au lieu de les dupliquer.
     */
    public function run(): void
    {
        if (Specialite::exists()) {
            $this->command?->info('Référentiel déjà en place : semis ignoré.');

            return;
        }

        if (! $this->motDePasseAcceptable()) {
            return;
        }

        // Le réseau de soins — structures, spécialités, praticiens et plannings —
        // est décrit dans un seul semis, rejouable sans dommage.
        $this->call(ReseauMedicalSeeder::class);

        // Le médecin de démonstration : c'est lui que la secrétaire assiste et
        // celui dont l'agenda est montré pendant la soutenance.
        $premierMedecin = Medecin::whereHas(
            'utilisateur', fn ($q) => $q->where('email', config('mediguide.demo.medecin'))
        )->firstOrFail();

        // --- Exemple d'indisponibilité ponctuelle déclarée par un médecin (chap. 3) ---
        Disponibilite::create([
            'medecin_id' => $premierMedecin->id, 'type' => 'INDISPONIBILITE',
            'date' => now()->addWeek()->toDateString(), 'motif' => 'formation',
        ]);

        // --- Comptes de démonstration ---
        $up = User::create(['nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => config('mediguide.demo.patient'), 'password' => config('mediguide.demo.mot_de_passe'),
            'email_verified_at' => now()]);
        Patient::create(['utilisateur_id' => $up->id, 'sexe' => 'M',
            'date_naissance' => '2002-12-26', 'groupe_sanguin' => 'O+']);

        User::create(['nom' => 'ADMIN', 'prenom' => 'ISI', 'role' => 'admin',
            'email' => config('mediguide.demo.admin'), 'password' => config('mediguide.demo.mot_de_passe'),
            'email_verified_at' => now()]);

        // La secrétaire du premier médecin : c'est elle qui tient son agenda.
        $us = User::create(['nom' => 'SARR', 'prenom' => 'Fatou', 'role' => 'secretaire',
            'email' => config('mediguide.demo.secretaire'), 'password' => config('mediguide.demo.mot_de_passe'),
            'email_verified_at' => now()]);
        Secretaire::create(['utilisateur_id' => $us->id, 'medecin_id' => $premierMedecin->id]);

        // Le compte medecin@demo.sn est déjà créé plus haut (Dr Moussa DIALLO,
        // premier de la liste des médecins), avec son planning et sa validation.
    }

    /**
     * Sur une adresse publique, le mot de passe de démonstration par défaut
     * ouvrirait l'administration de la plateforme à qui
     * connaît la valeur, c'est-à-dire à quiconque lit le dépôt. Hors
     * développement, le semis exige donc un mot de passe choisi.
     */
    private function motDePasseAcceptable(): bool
    {
        $motDePasse = (string) config('mediguide.demo.mot_de_passe');

        if (app()->environment('local', 'testing') || $motDePasse !== 'password') {
            return true;
        }

        $this->command?->error('Semis interrompu : DEMO_PASSWORD n\'est pas défini.');
        $this->command?->line('  Les comptes de démonstration donnent accès à l\'administration.');
        $this->command?->line('  Définissez DEMO_PASSWORD dans la configuration du service,');
        $this->command?->line('  avec un mot de passe long, puis relancez le déploiement.');

        return false;
    }
}
