<?php

namespace Tests\Feature;

use App\Models\{Specialite, StructureMedicale, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une panne du serveur de messagerie ne doit annuler aucune action.
 *
 * L'envoi se fait pendant la requête : quand le serveur SMTP ne répondait pas,
 * l'exception remontait jusqu'au navigateur et l'inscription se soldait par une
 * erreur 500 — alors que le compte venait pourtant d'être créé.
 */
class PanneMessagerieTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Provoque une vraie panne, et non une simulation.
     *
     * Une doublure du service de courrier ne prouvait rien : l'envoi part d'un
     * écouteur d'événement de Laravel qu'elle n'interceptait pas, le test
     * passait donc sans jamais rencontrer la panne qu'il prétendait décrire.
     * On dirige ici le courrier vers un hôte qui n'existe pas.
     */
    private function messagerieEnPanne(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'serveur.qui.nexiste.pas.invalid',
            'mail.mailers.smtp.port' => 2525,
            'mail.mailers.smtp.timeout' => 1,
        ]);
    }

    public function test_linscription_aboutit_meme_si_le_courriel_ne_part_pas(): void
    {
        $this->messagerieEnPanne();

        $reponse = $this->post(route('register'), [
            'prenom' => 'Awa', 'nom' => 'NDIAYE', 'email' => 'awa@mediguide.sn',
            'password' => 'motdepasse123', 'password_confirmation' => 'motdepasse123',
        ]);

        $reponse->assertSessionHasNoErrors();
        $this->assertNotNull(User::where('email', 'awa@mediguide.sn')->first(),
            'le compte doit exister même si le lien de confirmation n\'a pas pu partir');
    }

    public function test_la_validation_dun_medecin_aboutit_meme_si_le_courriel_ne_part_pas(): void
    {
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);
        $spec = Specialite::create(['nom' => 'Cardiologie']);

        $this->post(route('register.medecin'), [
            'prenom' => 'Fatou', 'nom' => 'WADE', 'email' => 'fatou@mediguide.sn',
            'structure_id' => $structure->id, 'specialite_id' => $spec->id,
            'num_ordre' => 'SN-9999', 'password' => 'password', 'password_confirmation' => 'password',
        ]);
        $medecin = User::where('email', 'fatou@mediguide.sn')->first()->medecin;

        $admin = User::create(['nom' => 'ADMIN', 'prenom' => 'ISI', 'role' => 'admin',
            'email' => 'admin@mediguide.sn', 'password' => 'password', 'email_verified_at' => now()]);

        $this->messagerieEnPanne();
        $this->actingAs($admin)->post(route('admin.valider', $medecin))->assertSessionHasNoErrors();

        $this->assertTrue($medecin->fresh()->valide,
            'le médecin doit être validé même si l\'avis par courriel a échoué');
    }
}
