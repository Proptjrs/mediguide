<?php

namespace Database\Seeders;

use App\Models\{Disponibilite, Medecin, Specialite, StructureMedicale, User};
use Illuminate\Database\Seeder;

/**
 * Le réseau de soins du district : structures, spécialités et praticiens.
 *
 * Ce semis est le seul endroit où le réseau est décrit, et il est réécrit sans
 * dommage : chaque élément est créé s'il manque, mis à jour s'il a changé, laissé
 * tel quel sinon. C'est ce qui permet de le rejouer à chaque démarrage sur une
 * base déjà peuplée — la base en ligne, notamment, où le semis principal
 * s'interrompt de lui-même dès que le référentiel existe.
 */
class ReseauMedicalSeeder extends Seeder
{
    /** Les dix-huit spécialités du moteur d'orientation (chap. 4.2.3). */
    public const SPECIALITES = [
        'Cardiologie', 'Pneumologie', 'Gastro-entérologie', 'Neurologie', 'Dermatologie',
        'Ophtalmologie', 'ORL', 'Orthopédie', 'Gynécologie', 'Pédiatrie', 'Urologie',
        'Psychiatrie', 'Endocrinologie', 'Dentisterie', 'Infectiologie', 'Rhumatologie',
        'Médecine Générale', 'Chirurgie',
    ];

    /** Les structures du district, du premier niveau au recours spécialisé. */
    public const STRUCTURES = [
        ['Hôpital Roi Baudouin', 'Guédiawaye, Dakar', 14.7758, -17.4056, 'hopital', true],
        ['Centre de Santé Wakhinane', 'Wakhinane Nimzatt, Guédiawaye', 14.7825, -17.4010, 'centre_sante', false],
        ['Poste de Santé Golf Sud', 'Golf Sud, Guédiawaye', 14.7712, -17.4098, 'poste_sante', false],
        ['Poste de Santé Darouminame', 'Darouminame, Guédiawaye', 14.7699, -17.4021, 'poste_sante', false],
    ];

    /**
     * Les praticiens, répartis selon la pyramide sanitaire décrite au chapitre 1 :
     * le poste de santé assure le premier niveau, le centre de santé le deuxième,
     * l'hôpital le recours spécialisé. C'est cette répartition qui permet à
     * l'orientation de proposer « le niveau adapté, et non systématiquement
     * l'hôpital ».
     *
     * Chacune des dix-huit spécialités compte au moins un praticien : sans cela,
     * une orientation vers une spécialité non pourvue ne renverrait aucune
     * structure au patient, et l'écran de résultats resterait vide.
     *
     * [prénom, nom, spécialité, structure, n° d'Ordre, adresse]
     */
    public const MEDECINS = [
        // ── Hôpital Roi Baudouin : le recours spécialisé ─────────────────────
        ['Moussa', 'DIALLO', 'Cardiologie', 'Hôpital Roi Baudouin', 'SN-1201', null],
        ['Aminata', 'FALL', 'Ophtalmologie', 'Hôpital Roi Baudouin', 'SN-1305', 'aminata.fall@mediguide.sn'],
        ['Khadim', 'MBAYE', 'Pédiatrie', 'Hôpital Roi Baudouin', 'SN-1422', 'khadim.mbaye@mediguide.sn'],
        ['Sokhna', 'DIENG', 'Gynécologie', 'Hôpital Roi Baudouin', 'SN-1518', 'sokhna.dieng@mediguide.sn'],
        ['Ibrahima', 'SOW', 'Pneumologie', 'Hôpital Roi Baudouin', 'SN-1741', 'ibrahima.sow@mediguide.sn'],
        ['Mariama', 'BA', 'Gastro-entérologie', 'Hôpital Roi Baudouin', 'SN-1802', 'mariama.ba@mediguide.sn'],
        ['Cheikh', 'NDOYE', 'Neurologie', 'Hôpital Roi Baudouin', 'SN-1866', 'cheikh.ndoye@mediguide.sn'],
        ['Adama', 'SECK', 'Orthopédie', 'Hôpital Roi Baudouin', 'SN-1912', 'adama.seck@mediguide.sn'],
        ['Ousmane', 'KANE', 'Urologie', 'Hôpital Roi Baudouin', 'SN-2003', 'ousmane.kane@mediguide.sn'],
        ['Ndèye', 'THIAM', 'Psychiatrie', 'Hôpital Roi Baudouin', 'SN-2077', 'ndeye.thiam@mediguide.sn'],
        ['Fatou', 'GAYE', 'Endocrinologie', 'Hôpital Roi Baudouin', 'SN-2134', 'fatou.gaye@mediguide.sn'],
        ['Modou', 'SARR', 'Infectiologie', 'Hôpital Roi Baudouin', 'SN-2198', 'modou.sarr@mediguide.sn'],
        ['Bineta', 'DIOP', 'Rhumatologie', 'Hôpital Roi Baudouin', 'SN-2251', 'bineta.diop@mediguide.sn'],
        ['Alioune', 'BADJI', 'Chirurgie', 'Hôpital Roi Baudouin', 'SN-2310', 'alioune.badji@mediguide.sn'],

        // ── Centre de Santé Wakhinane : le deuxième niveau ───────────────────
        ['Awa', 'CISSÉ', 'Médecine Générale', 'Centre de Santé Wakhinane', 'SN-1707', 'awa.cisse@mediguide.sn'],
        ['Rokhaya', 'SYLLA', 'Dermatologie', 'Centre de Santé Wakhinane', 'SN-2388', 'rokhaya.sylla@mediguide.sn'],
        ['Malick', 'TOURÉ', 'ORL', 'Centre de Santé Wakhinane', 'SN-2415', 'malick.toure@mediguide.sn'],
        ['Astou', 'FAYE', 'Dentisterie', 'Centre de Santé Wakhinane', 'SN-2469', 'astou.faye@mediguide.sn'],
        ['Seynabou', 'LÔ', 'Pédiatrie', 'Centre de Santé Wakhinane', 'SN-2503', 'seynabou.lo@mediguide.sn'],
        ['Coumba', 'DIAGNE', 'Gynécologie', 'Centre de Santé Wakhinane', 'SN-2554', 'coumba.diagne@mediguide.sn'],

        // ── Postes de santé : le premier niveau, médecine générale ───────────
        ['Pape', 'NIANG', 'Médecine Générale', 'Poste de Santé Golf Sud', 'SN-1633', 'pape.niang@mediguide.sn'],
        ['Mame Diarra', 'WADE', 'Médecine Générale', 'Poste de Santé Darouminame', 'SN-2601', 'mame.wade@mediguide.sn'],
    ];

    public function run(): void
    {
        $motDePasse = config('mediguide.demo.mot_de_passe');

        $specs = collect(self::SPECIALITES)
            ->mapWithKeys(fn ($n) => [$n => Specialite::firstOrCreate(['nom' => $n])]);

        $structures = collect(self::STRUCTURES)->mapWithKeys(function ($s) {
            [$nom, $adresse, $lat, $lng, $type, $urgences] = $s;

            return [$nom => StructureMedicale::firstOrCreate(['nom' => $nom], [
                'adresse' => $adresse, 'latitude' => $lat, 'longitude' => $lng,
                'type' => $type, 'urgences_24h' => $urgences,
            ])];
        });

        $poses = 0;
        foreach (self::MEDECINS as [$prenom, $nom, $specialite, $structure, $ordre, $email]) {
            // Le premier de la liste porte l'adresse de démonstration : il ne faut
            // pas lui en créer une seconde, sinon il apparaîtrait en double dans
            // la liste des plannings côté administrateur.
            $adresse = $email ?: config('mediguide.demo.medecin');

            $u = User::firstOrCreate(['email' => $adresse], [
                'nom' => $nom, 'prenom' => $prenom, 'role' => 'medecin',
                'password' => $motDePasse, 'email_verified_at' => now(),
            ]);

            $m = Medecin::updateOrCreate(['utilisateur_id' => $u->id], [
                'structure_id' => $structures[$structure]->id,
                'specialite_id' => $specs[$specialite]->id,
                'num_ordre' => $ordre, 'valide' => true,
            ]);

            // Le planning de base : lundi à vendredi, 8 h-12 h et 14 h-17 h.
            // Il est fixé par l'administration, non par le médecin (chap. 3).
            foreach (range(1, 5) as $jour) {
                foreach ([['08:00', '12:00'], ['14:00', '17:00']] as [$debut, $fin]) {
                    Disponibilite::firstOrCreate([
                        'medecin_id' => $m->id, 'type' => 'BASE', 'jour_semaine' => $jour,
                        'heure_debut' => $debut, 'heure_fin' => $fin,
                    ]);
                }
            }
            $poses += $m->wasRecentlyCreated ? 1 : 0;
        }

        $this->command?->info(sprintf(
            '  réseau : %d structures, %d spécialités, %d praticiens (%d ajoutés)',
            $structures->count(), $specs->count(), count(self::MEDECINS), $poses
        ));
    }
}
