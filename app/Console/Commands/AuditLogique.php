<?php
namespace App\Console\Commands;
use App\Services\{GeolocService, QuestionnaireService};
use Illuminate\Console\Command;
class AuditLogique extends Command {
    protected $signature = 'audit:logique';
    protected $description = 'Verifie les regles metier du cahier des charges (4.1 a 4.5)';
    private int $ko = 0;
    private function verifier(string $libelle, $attendu, $obtenu): void {
        if ($attendu === $obtenu) { $this->line("  OK   $libelle"); return; }
        $this->error("  ECHEC $libelle : attendu " . var_export($attendu, true) . ", obtenu " . var_export($obtenu, true));
        $this->ko++;
    }
    public function handle(GeolocService $geo, QuestionnaireService $q): int {
        $this->info('--- 4.2 Arbre de decision : les 19 lignes du tableau ---');
        $arbre = [
            ['douleur','poitrine','Cardiologie'], ['douleur','tete','Neurologie'],
            ['douleur','ventre','Gastro-enterologie'], ['douleur','bassin','Urologie'],
            ['douleur','bras','Orthopedie'], ['douleur','jambes','Orthopedie'],
            ['douleur','gorge','ORL'], ['fievre',null,'Infectiologie'],
            ['respiration',null,'Pneumologie'], ['digestif',null,'Gastro-enterologie'],
            ['peau',null,'Dermatologie'], ['vision',null,'Ophtalmologie'],
            ['orl',null,'ORL'], ['grossesse',null,'Gynecologie'],
            ['enfant',null,'Pediatrie'], ['mental',null,'Psychiatrie'],
            ['dents',null,'Dentisterie'], ['articulations',null,'Rhumatologie'],
            ['hormones',null,'Endocrinologie'], ['autre',null,'Medecine Generale'],
        ];
        $accents = ['Gastro-enterologie'=>'Gastro-entérologie','Orthopedie'=>'Orthopédie','Gynecologie'=>'Gynécologie','Pediatrie'=>'Pédiatrie','Medecine Generale'=>'Médecine Générale'];
        foreach ($arbre as [$p, $z, $attendu]) {
            $attendu = $accents[$attendu] ?? $attendu;
            $this->verifier("$p / " . ($z ?? '—'), $attendu, $q->determineSpecialty($p, $z, 34));
        }
        $this->info('--- 4.2 Regle prioritaire : < 15 ans => Pediatrie ---');
        $this->verifier('14 ans + douleur/poitrine', 'Pédiatrie', $q->determineSpecialty('douleur','poitrine',14));
        $this->verifier('15 ans NON prioritaire', 'Cardiologie', $q->determineSpecialty('douleur','poitrine',15));
        $this->info('--- 4.1 Score urgence : round(niveau*0.7) + alarmes, plafonne a 10, seuil 7 ---');
        $this->verifier('niveau 3, aucune alarme', 2, $q->calculateUrgencyScore(3, []));
        $this->verifier('niveau 10, aucune alarme', 7, $q->calculateUrgencyScore(10, []));
        $this->verifier('niveau 1 + douleur_thoracique(3)', 4, $q->calculateUrgencyScore(1, ['douleur_thoracique']));
        $this->verifier('niveau 3 + thoracique + respiratoire', 8, $q->calculateUrgencyScore(3, ['douleur_thoracique','difficulte_respiratoire']));
        $this->verifier('plafond 10', 10, $q->calculateUrgencyScore(10, ['douleur_thoracique','difficulte_respiratoire','saignement_important','perte_connaissance','fievre_40']));
        $this->verifier('seuil : 6 non urgent', false, $q->isUrgence(6));
        $this->verifier('seuil : 7 urgent', true, $q->isUrgence(7));
        $this->info('--- 4.3 Haversine ---');
        $this->verifier('distance nulle', 0.0, round($geo->haversine(14.7712,-17.4098,14.7712,-17.4098), 4));
        $d = round($geo->haversine(14.7712,-17.4098,14.7758,-17.4056), 2);
        $this->verifier('Golf Sud -> Roi Baudouin ~0.68 km', 0.68, $d);
        if ($this->ko === 0) { $this->info('  => Toutes les regles metier sont conformes.'); return 0; }
        $this->error("  => $this->ko regle(s) non conforme(s).");
        return 1;
    }
}
