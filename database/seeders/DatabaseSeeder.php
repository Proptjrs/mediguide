<?php

namespace Database\Seeders;

use App\Models\{Disponibilite, Medecin, Patient, Secretaire, Specialite, StructureMedicale, User};
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

        // --- Les 18 spécialités du moteur d'orientation (chap. 4.2.3) ---
        $specs = collect([
            'Cardiologie', 'Pneumologie', 'Gastro-entérologie', 'Neurologie', 'Dermatologie',
            'Ophtalmologie', 'ORL', 'Orthopédie', 'Gynécologie', 'Pédiatrie', 'Urologie',
            'Psychiatrie', 'Endocrinologie', 'Dentisterie', 'Infectiologie', 'Rhumatologie',
            'Médecine Générale', 'Chirurgie',
        ])->mapWithKeys(fn ($n) => [$n => Specialite::create(['nom' => $n])]);

        // --- Structures du district (coordonnées à affiner via Nominatim) ---
        $chrb = StructureMedicale::create([
            'nom' => 'Hôpital Roi Baudouin', 'adresse' => 'Guédiawaye, Dakar',
            'latitude' => 14.7758, 'longitude' => -17.4056,
            'type' => 'hopital', 'urgences_24h' => true,
        ]);
        $wakhinane = StructureMedicale::create([
            'nom' => 'Centre de Santé Wakhinane', 'adresse' => 'Wakhinane Nimzatt, Guédiawaye',
            'latitude' => 14.7825, 'longitude' => -17.4010, 'type' => 'centre_sante',
        ]);
        StructureMedicale::create([
            'nom' => 'Poste de Santé Golf Sud', 'adresse' => 'Golf Sud, Guédiawaye',
            'latitude' => 14.7712, 'longitude' => -17.4098, 'type' => 'poste_sante',
        ]);
        StructureMedicale::create([
            'nom' => 'Poste de Santé Darouminame', 'adresse' => 'Darouminame, Guédiawaye',
            'latitude' => 14.7699, 'longitude' => -17.4021, 'type' => 'poste_sante',
        ]);

        // --- Médecins de démo (validés) + plannings lun-ven fixés par l'admin ---
        // Le premier de la liste porte l'e-mail de démonstration medecin@demo.sn :
        // il ne faut PAS créer un second compte pour lui, sinon il apparaît en
        // doublon dans la liste des plannings côté administrateur.
        // Les e-mails sont explicites (et non dérivés du nom) pour éviter les
        // caractères accentués, invalides dans une adresse.
        $medecins = [
            ['Moussa', 'DIALLO', 'Cardiologie', $chrb, 'SN-1201', config('mediguide.demo.medecin')],
            ['Aminata', 'FALL', 'Ophtalmologie', $chrb, 'SN-1305', 'aminata.fall@mediguide.sn'],
            ['Khadim', 'MBAYE', 'Pédiatrie', $chrb, 'SN-1422', 'khadim.mbaye@mediguide.sn'],
            ['Sokhna', 'DIENG', 'Gynécologie', $chrb, 'SN-1518', 'sokhna.dieng@mediguide.sn'],
            ['Pape', 'NIANG', 'Médecine Générale', $chrb, 'SN-1633', 'pape.niang@mediguide.sn'],
            ['Awa', 'CISSÉ', 'Médecine Générale', $wakhinane, 'SN-1707', 'awa.cisse@mediguide.sn'],
        ];
        $premierMedecin = null;
        foreach ($medecins as [$prenom, $nom, $spec, $structure, $ordre, $email]) {
            $u = User::create([
                'nom' => $nom, 'prenom' => $prenom, 'role' => 'medecin',
                'email' => $email, 'password' => config('mediguide.demo.mot_de_passe'),
                'email_verified_at' => now(),      // comptes de démonstration : déjà confirmés
            ]);
            $m = Medecin::create([
                'utilisateur_id' => $u->id, 'structure_id' => $structure->id,
                'specialite_id' => $specs[$spec]->id, 'num_ordre' => $ordre, 'valide' => true,
            ]);
            $premierMedecin ??= $m;
            foreach (range(1, 5) as $jour) {                     // 08h-12h / 14h-17h, fixé par l'admin
                Disponibilite::create(['medecin_id' => $m->id, 'type' => 'BASE', 'jour_semaine' => $jour,
                    'heure_debut' => '08:00', 'heure_fin' => '12:00']);
                Disponibilite::create(['medecin_id' => $m->id, 'type' => 'BASE', 'jour_semaine' => $jour,
                    'heure_debut' => '14:00', 'heure_fin' => '17:00']);
            }
        }

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
            'email' => 'secretaire@mediguide.sn', 'password' => config('mediguide.demo.mot_de_passe'),
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
