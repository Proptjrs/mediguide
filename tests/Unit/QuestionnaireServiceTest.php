<?php

namespace Tests\Unit;

use App\Services\QuestionnaireService;
use PHPUnit\Framework\TestCase;

/** Tests unitaires du moteur d'orientation (mémoire, chap. 4.6.1). */
class QuestionnaireServiceTest extends TestCase
{
    private QuestionnaireService $service;

    protected function setUp(): void
    {
        $this->service = new QuestionnaireService();
    }

    public function test_douleur_poitrine_oriente_vers_cardiologie(): void
    {
        $this->assertSame('Cardiologie', $this->service->determineSpecialty('douleur', 'poitrine', 34));
    }

    public function test_enfant_de_moins_de_15_ans_oriente_vers_pediatrie(): void
    {
        $this->assertSame('Pédiatrie', $this->service->determineSpecialty('douleur', 'ventre', 8));
    }

    public function test_probleme_inconnu_retombe_sur_medecine_generale(): void
    {
        $this->assertSame('Médecine Générale', $this->service->determineSpecialty('inconnu', null, null));
    }

    /** chap. 4.2 : fievre -> Infectiologie (pas Médecine Générale). */
    public function test_fievre_oriente_vers_infectiologie(): void
    {
        $this->assertSame('Infectiologie', $this->service->determineSpecialty('fievre', null, 34));
    }

    /** chap. 4.2 : ajout tardif mais définitif. */
    public function test_articulations_oriente_vers_rhumatologie(): void
    {
        $this->assertSame('Rhumatologie', $this->service->determineSpecialty('articulations', null, 34));
    }

    /** chap. 4.2 : ajout tardif mais définitif. */
    public function test_hormones_oriente_vers_endocrinologie(): void
    {
        $this->assertSame('Endocrinologie', $this->service->determineSpecialty('hormones', null, 34));
    }

    public function test_score_faible_sans_alarme_nest_pas_une_urgence(): void
    {
        $score = $this->service->calculateUrgencyScore(3, []);
        $this->assertFalse($this->service->isUrgence($score));
    }

    public function test_douleur_thoracique_declenche_urgence(): void
    {
        $score = $this->service->calculateUrgencyScore(6, ['douleur_thoracique']);
        $this->assertGreaterThanOrEqual(QuestionnaireService::SEUIL_URGENCE, $score);
        $this->assertTrue($this->service->isUrgence($score));
    }

    public function test_le_score_est_plafonne_a_10(): void
    {
        $score = $this->service->calculateUrgencyScore(10,
            ['douleur_thoracique', 'difficulte_respiratoire', 'perte_connaissance']);
        $this->assertSame(10, $score);
    }
}
