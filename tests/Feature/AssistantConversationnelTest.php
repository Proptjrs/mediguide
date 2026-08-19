<?php

namespace Tests\Feature;

use App\Models\{EchangeAssistant, Patient, Specialite, StructureMedicale, User};
use App\Services\AssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'assistant conversationnel (mémoire, chap. 4).
 *
 * Le contrôle le plus important est celui de la garde d'urgence : une phrase qui
 * décrit un signe vital ne doit jamais recevoir une réponse d'agenda, quelle que
 * soit la tournure employée.
 */
class AssistantConversationnelTest extends TestCase
{
    use RefreshDatabase;

    private function assistant(): AssistantService
    {
        return app(AssistantService::class);
    }

    private function patient(): User
    {
        $u = User::create(['nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => 'p@t.sn', 'password' => 'password', 'email_verified_at' => now()]);
        Patient::create(['utilisateur_id' => $u->id, 'sexe' => 'M']);

        return $u;
    }

    /** @return array<int, array{0:string}> */
    public static function phrasesDUrgence(): array
    {
        return [
            ["j'ai une douleur à la poitrine"],
            ["j'ai mal dans la poitrine depuis ce matin"],
            ['la poitrine me serre'],
            ['je ne respire plus bien'],
            ["je n'arrive plus à respirer"],
            ['il a perdu connaissance'],
            ['mon bébé convulse'],
            ['je saigne beaucoup après une chute'],
            ['il vomit du sang'],
            ["je crois que c'est un AVC"],
        ];
    }

    /** @dataProvider phrasesDUrgence */
    public function test_un_signe_vital_renvoie_au_samu_et_jamais_a_un_rendez_vous(string $phrase): void
    {
        $r = $this->assistant()->repondre($phrase);

        $this->assertSame('urgence', $r['intention'], "Non détecté : « {$phrase} »");
        $this->assertTrue($r['urgence']);
        $this->assertStringContainsString('1515', $r['reponse']);
        $this->assertStringNotContainsString('créneau disponible', $r['reponse']);
    }

    public function test_une_phrase_ordinaire_donne_une_specialite(): void
    {
        Specialite::create(['nom' => 'Gastro-entérologie']);

        $r = $this->assistant()->repondre("j'ai mal au ventre depuis hier");

        $this->assertSame('orientation', $r['intention']);
        $this->assertFalse($r['urgence']);
        $this->assertStringContainsString('Gastro-entérologie', $r['reponse']);
    }

    public function test_l_assistant_ne_pose_jamais_de_diagnostic(): void
    {
        $r = $this->assistant()->repondre("j'ai de la fièvre");

        $this->assertStringContainsString('aucun diagnostic', $r['reponse']);
    }

    public function test_l_enfant_est_oriente_en_pediatrie(): void
    {
        Specialite::create(['nom' => 'Pédiatrie']);

        $r = $this->assistant()->repondre("mon enfant tousse beaucoup");

        $this->assertStringContainsString('Pédiatrie', $r['reponse']);
    }

    public function test_chaque_echange_est_conserve_avec_son_intention(): void
    {
        $u = $this->patient();
        $this->assistant()->repondre('bonjour', $u);
        $this->assistant()->repondre("j'ai une douleur à la poitrine", $u);

        $this->assertSame(2, EchangeAssistant::count());
        $this->assertSame(1, EchangeAssistant::where('urgence_detectee', true)->count());
        $this->assertSame($u->id, EchangeAssistant::first()->utilisateur_id);
    }

    public function test_une_question_incomprise_propose_une_reformulation(): void
    {
        $r = $this->assistant()->repondre('azerty qwerty');

        $this->assertSame('inconnue', $r['intention']);
        $this->assertNotEmpty($r['pistes']);
    }

    public function test_les_structures_citees_sont_celles_de_la_base(): void
    {
        StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin', 'adresse' => 'Guédiawaye',
            'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);

        $r = $this->assistant()->repondre('quel hôpital est près de moi ?');

        $this->assertSame('structure', $r['intention']);
        $this->assertStringContainsString('Hôpital Roi Baudouin', $r['reponse']);
    }

    public function test_l_assistant_repond_au_visiteur_comme_au_patient(): void
    {
        // S'orienter ne demande pas de compte : celui qui ne sait pas vers quel
        // service se diriger doit pouvoir le découvrir sans barrière.
        $this->get('/assistant')->assertOk()->assertSee('Assistant MediGuide');

        $this->actingAs($this->patient())->get('/assistant')
            ->assertOk()->assertSee('Assistant MediGuide');
    }

    public function test_un_echange_sans_compte_est_conserve_sans_auteur(): void
    {
        $this->assistant()->repondre("j'ai mal au ventre");

        $echange = EchangeAssistant::first();
        $this->assertNotNull($echange);
        $this->assertNull($echange->utilisateur_id);
    }
}
