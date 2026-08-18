<?php

namespace Database\Seeders;

use App\Models\{Medecin, Patient, Questionnaire, RendezVous, Secretaire, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Un jeu de données pour la démonstration.
 *
 * Le tableau de bord de pilotage n'a d'intérêt que s'il a quelque chose à
 * montrer : sur une base fraîche, tous les indicateurs valent zéro et la
 * démonstration tombe à plat. Ce semis crée un historique plausible —
 * des rendez-vous honorés, des patients qui ne sont pas venus, des
 * questionnaires dont certains ont franchi le seuil d'urgence.
 *
 * Il ne s'exécute jamais tout seul : il se lance à la main, avant une
 * présentation, par
 *     php artisan db:seed --class=DemonstrationSeeder
 */
class DemonstrationSeeder extends Seeder
{
    public function run(): void
    {
        $medecins = Medecin::with('utilisateur')->where('valide', true)->get();
        if ($medecins->isEmpty()) {
            $this->command->warn('Aucun médecin validé : lancez d\'abord le semis principal.');

            return;
        }

        // Le secrétariat ne dépend pas de l'historique : il est créé d'abord,
        // faute de quoi une base déjà peuplée le priverait de compte — c'est
        // exactement ce qui s'est produit en production.
        $this->secretaire($medecins->first());

        // Relancer ce semis doublerait l'historique : on s'arrête si des
        // rendez-vous passés existent déjà.
        if (RendezVous::where('date_heure', '<', now())->count() >= 20) {
            $this->command->info('  Historique déjà en place : rendez-vous et questionnaires ignorés.');

            return;
        }

        $patients = $this->patients();
        $this->command->info(sprintf('  %d patients de démonstration', $patients->count()));

        // ── Un historique sur trente jours ──────────────────────────────────
        // Les proportions sont celles observées à l'hôpital : une consultation
        // sur cinq environ n'est pas honorée.
        // Un médecin ne peut avoir deux rendez-vous à la même heure : les
        // créneaux sont donc distribués, non tirés au hasard.
        $honores = $absents = $annules = 0;
        $i = 0;
        foreach (range(1, 64) as $rang) {
            $i = $rang;
            $patient = $patients->random();
            $medecin = $medecins[$rang % $medecins->count()];
            $quand = Carbon::now()->subDays(1 + intdiv($rang, 3))
                ->setTime(8 + ($rang % 9), $rang % 2 ? 30 : 0);

            $statut = match (true) {
                $i % 7 === 0 => 'NO_SHOW',
                $i % 11 === 0 => 'ANNULE',
                default => 'HONORE',
            };
            $statut === 'HONORE' ? $honores++ : ($statut === 'NO_SHOW' ? $absents++ : $annules++);

            RendezVous::create([
                'patient_id' => $patient->id, 'medecin_id' => $medecin->id,
                'date_heure' => $quand, 'statut' => $statut,
                'motif' => 'Consultation', 'created_at' => $quand->copy()->subDays(2),
            ]);
        }

        // ── Des rendez-vous à venir ─────────────────────────────────────────
        $motifs = ['Consultation', 'Suivi de traitement', 'Contrôle', 'Première visite',
                   'Douleurs persistantes', "Résultats d'analyses"];
        $aVenir = 0;
        // Le premier médecin reçoit des patients aujourd'hui même : son agenda
        // du jour doit montrer quelque chose pendant la démonstration.
        foreach ([[0, 0, 15], [0, 0, 16], [0, 0, 17]] as $k => [$jours, $_, $heure]) {
            RendezVous::create([
                'patient_id' => $patients->random()->id, 'medecin_id' => $medecins[0]->id,
                'date_heure' => Carbon::now()->addDays($jours)->setTime($heure, 0),
                'statut' => 'CONFIRME', 'motif' => $motifs[$k % count($motifs)],
            ]);
            $aVenir++;
        }
        foreach (range(1, 12) as $rang) {
            RendezVous::create([
                'patient_id' => $patients->random()->id,
                'medecin_id' => $medecins[$rang % $medecins->count()]->id,
                'date_heure' => Carbon::now()->addDays(1 + intdiv($rang, 3))->setTime(8 + ($rang % 8), 0),
                'statut' => 'CONFIRME', 'motif' => $motifs[$rang % count($motifs)],
            ]);
            $aVenir++;
        }

        // ── Des questionnaires, dont deux urgents ───────────────────────────
        $specialites = ['Cardiologie', 'Pédiatrie', 'Médecine Générale',
                        'Gastro-entérologie', 'Ophtalmologie'];
        $urgents = 0;
        foreach (range(1, 47) as $i) {
            $urgent = $i % 8 === 0;
            if ($urgent) {
                $urgents++;
            }
            Questionnaire::create([
                'patient_id' => $patients->random()->id,
                'etapes' => ['difficulte' => 'douleur', 'zone' => 'ventre'],
                'specialite_resultat' => $specialites[array_rand($specialites)],
                'niveau_urgence' => $urgent ? random_int(8, 10) : random_int(1, 6),
                'urgence_detectee' => $urgent,
                'created_at' => Carbon::now()->subDays(random_int(0, 28)),
            ]);
        }

        $this->command->info(sprintf(
            '  %d honorés · %d non honorés · %d annulés · %d à venir · 47 questionnaires dont %d urgents',
            $honores, $absents, $annules, $aVenir, $urgents));
    }

    /** La secrétaire du premier médecin, si elle n'existe pas déjà. */
    private function secretaire(Medecin $medecin): void
    {
        // Le journal doit trancher : « déjà présent » et « rien fait » se
        // ressemblaient trop, et l'on ne savait pas si le compte existait.
        if (User::where('email', 'secretaire@mediguide.sn')->exists()) {
            $this->command->info('  secrétariat déjà présent : secretaire@mediguide.sn');

            return;
        }
        $u = User::create([
            'nom' => 'SARR', 'prenom' => 'Fatou', 'role' => 'secretaire',
            'email' => 'secretaire@mediguide.sn',
            'password' => config('mediguide.demo.mot_de_passe'), 'email_verified_at' => now(),
        ]);
        Secretaire::create(['utilisateur_id' => $u->id, 'medecin_id' => $medecin->id]);
        $this->command->info('  secrétariat créé : secretaire@mediguide.sn');
    }

    /** Quelques patients, créés une seule fois. */
    private function patients()
    {
        $noms = [['Aïssatou', 'BA'], ['Ousmane', 'SOW'], ['Mariama', 'DIOP'],
                 ['Ibrahima', 'FAYE'], ['Ndèye', 'SECK'], ['Cheikh', 'GUEYE'],
                 ['Fatoumata', 'CISSÉ'], ['Modou', 'NDOYE'], ['Khady', 'SYLLA'],
                 ['Alioune', 'BADJI'], ['Rokhaya', 'THIAM'], ['Babacar', 'SAMB'],
                 ['Adama', 'KANE'], ['Seynabou', 'DIAGNE']];

        foreach ($noms as $i => [$prenom, $nom]) {
            $email = 'patient' . ($i + 1) . '@mediguide.sn';
            if (User::where('email', $email)->exists()) {
                continue;
            }
            $u = User::create([
                'nom' => $nom, 'prenom' => $prenom, 'role' => 'patient', 'email' => $email,
                'password' => config('mediguide.demo.mot_de_passe'), 'email_verified_at' => now(),
            ]);
            Patient::create([
                'utilisateur_id' => $u->id,
                'sexe' => $i % 2 ? 'M' : 'F',
                'date_naissance' => now()->subYears(random_int(19, 64))->toDateString(),
            ]);
        }

        return Patient::all();
    }
}
