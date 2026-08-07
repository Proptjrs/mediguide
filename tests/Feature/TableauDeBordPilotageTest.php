<?php

namespace Tests\Feature;

use App\Models\{Medecin, Patient, Questionnaire, RendezVous, Specialite, StructureMedicale, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tableau de bord décisionnel de l'administrateur : au-delà du décompte des
 * enregistrements, il doit livrer les indicateurs qui servent à décider —
 * rendez-vous manqués, urgences détectées, patients à rappeler.
 */
class TableauDeBordPilotageTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(): User
    {
        $spec = Specialite::create(['nom' => 'Cardiologie']);
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056,
            'type' => 'hopital']);

        $um = User::create(['nom' => 'DIALLO', 'prenom' => 'Moussa', 'role' => 'medecin',
            'email' => 'medecin@test.sn', 'telephone' => '770000001', 'password' => 'motdepasse',
            'email_verified_at' => now()]);
        $medecin = Medecin::create(['utilisateur_id' => $um->id, 'specialite_id' => $spec->id,
            'structure_id' => $structure->id, 'num_ordre' => 'SN-1234', 'valide' => true]);

        $up = User::create(['nom' => 'NDIAYE', 'prenom' => 'Awa', 'role' => 'patient',
            'email' => 'patient@test.sn', 'telephone' => '770000002', 'password' => 'motdepasse',
            'email_verified_at' => now()]);
        $patient = Patient::create(['utilisateur_id' => $up->id, 'date_naissance' => '1998-04-02',
            'sexe' => 'F']);

        // Deux rendez-vous passés : l'un honoré, l'autre manqué.
        RendezVous::create(['patient_id' => $patient->id, 'medecin_id' => $medecin->id,
            'date_heure' => now()->subDays(5), 'statut' => 'HONORE']);
        RendezVous::create(['patient_id' => $patient->id, 'medecin_id' => $medecin->id,
            'date_heure' => now()->subDays(3), 'statut' => 'NO_SHOW']);

        // Deux questionnaires, dont un ayant déclenché l'alerte d'urgence.
        Questionnaire::create(['patient_id' => $patient->id, 'specialite_resultat' => 'Cardiologie',
            'niveau_urgence' => 3, 'urgence_detectee' => false, 'etapes' => []]);
        Questionnaire::create(['patient_id' => $patient->id, 'specialite_resultat' => 'Cardiologie',
            'niveau_urgence' => 9, 'urgence_detectee' => true, 'etapes' => []]);

        return User::create(['nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'admin',
            'email' => 'admin@test.sn', 'telephone' => '770000003', 'password' => 'motdepasse',
            'email_verified_at' => now()]);
    }

    public function test_les_indicateurs_de_pilotage_sont_affiches(): void
    {
        $admin = $this->contexte();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Pilotage')
            ->assertSee('Rendez-vous manqués')
            ->assertSee('Urgences détectées')
            ->assertSee('Activité des six derniers mois')
            ->assertSee('Patients à rappeler');
    }

    public function test_le_taux_de_rendez_vous_manques_est_calcule(): void
    {
        $admin = $this->contexte();

        // Un manqué sur deux rendez-vous passés, soit cinquante pour cent.
        $this->actingAs($admin)->get('/dashboard')->assertSee('50 %');
    }

    public function test_un_patient_en_urgence_est_signale_pour_rappel(): void
    {
        $admin = $this->contexte();

        $this->actingAs($admin)->get('/dashboard')->assertSee('Awa NDIAYE');
    }
}
