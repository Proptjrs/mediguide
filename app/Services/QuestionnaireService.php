<?php

namespace App\Services;

use App\Models\Questionnaire;

/**
 * Cœur fonctionnel de MediGuide (mémoire, chap. 4.2.3).
 * - calculateUrgencyScore() : niveau déclaré pondéré + bonus signes d'alarme.
 *   Score >= 7  => mode URGENCE, redirection SAMU 15.
 * - determineSpecialty()   : arbre de décision difficulté + zone + profil.
 */
class QuestionnaireService
{
    public const SEUIL_URGENCE = 7;

    /** Points ajoutés par signe d'alarme coché (étape 5). */
    private const ALARMES = [
        'douleur_thoracique' => 3,
        'difficulte_respiratoire' => 3,
        'saignement_important' => 2,
        'perte_connaissance' => 2,
        'fievre_40' => 2,
    ];

    /** Arbre de décision : difficulté principale -> zone anatomique -> spécialité. */
    private const ARBRE = [
        'douleur' => [
            'poitrine' => 'Cardiologie',  'tete' => 'Neurologie',
            'ventre' => 'Gastro-entérologie', 'bassin' => 'Urologie',
            'bras' => 'Orthopédie', 'jambes' => 'Orthopédie',
            'gorge' => 'ORL', '_' => 'Médecine Générale',
        ],
        'fievre'      => ['_' => 'Infectiologie'],
        'respiration' => ['_' => 'Pneumologie'],
        'digestif'    => ['_' => 'Gastro-entérologie'],
        'peau'        => ['_' => 'Dermatologie'],
        'vision'      => ['_' => 'Ophtalmologie'],
        'orl'         => ['_' => 'ORL'],
        'grossesse'   => ['_' => 'Gynécologie'],
        'enfant'      => ['_' => 'Pédiatrie'],
        'mental'      => ['_' => 'Psychiatrie'],
        'dents'       => ['_' => 'Dentisterie'],
        'articulations' => ['_' => 'Rhumatologie'],
        'hormones'    => ['_' => 'Endocrinologie'],
        'autre'       => ['_' => 'Médecine Générale'],
    ];

    public function calculateUrgencyScore(int $niveauDeclare, array $signesAlarme): int
    {
        $score = (int) round($niveauDeclare * 0.7);          // pondération du niveau 1-10
        foreach ($signesAlarme as $signe) {
            $score += self::ALARMES[$signe] ?? 0;            // bonus par signe coché
        }

        return min(10, $score);
    }

    public function isUrgence(int $score): bool
    {
        return $score >= self::SEUIL_URGENCE;
    }

    public function determineSpecialty(string $probleme, ?string $zone, ?int $age): string
    {
        if ($age !== null && $age < 15) {
            return 'Pédiatrie';                              // règle démographique prioritaire
        }
        $branche = self::ARBRE[$probleme] ?? self::ARBRE['autre'];

        return $branche[$zone] ?? $branche['_'] ?? 'Médecine Générale';
    }

    /** Analyse complète d'une soumission d'étape 5 + persistance (F1/F2). */
    public function analyser(array $reponses, ?string $patientId = null): Questionnaire
    {
        $score = $this->calculateUrgencyScore(
            (int) ($reponses['niveau_urgence'] ?? 1),
            $reponses['signes_alarme'] ?? []
        );

        return Questionnaire::create([
            'patient_id' => $patientId,
            'specialite_resultat' => $this->determineSpecialty(
                $reponses['probleme'] ?? 'autre',
                $reponses['zone'] ?? null,
                isset($reponses['age']) ? (int) $reponses['age'] : null
            ),
            'niveau_urgence' => $score,
            'urgence_detectee' => $this->isUrgence($score),
            'etapes' => $reponses,
        ]);
    }
}
