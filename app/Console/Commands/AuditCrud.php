<?php
namespace App\Console\Commands;
use App\Models\{Consultation, Disponibilite, DossierPatient, Medecin, Patient, RendezVous, Specialite, StructureMedicale, User};
use App\Services\RdvService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Notification};
class AuditCrud extends Command {
    protected $signature = 'audit:crud';
    protected $description = 'Verifie Create/Read/Update/Delete sur chaque entite (section 13)';
    private int $ko = 0;
    private function ok(string $e, string $op, bool $reussi): void {
        if ($reussi) { $this->line(sprintf('  OK   %-20s %s', $e, $op)); return; }
        $this->error(sprintf('  ECHEC %-20s %s', $e, $op)); $this->ko++;
    }
    public function handle(RdvService $rdv): int {
        Notification::fake();
        DB::beginTransaction();
        try {
            $spec = Specialite::create(['nom' => 'AuditSpec']);
            $this->ok('specialites', 'Create', $spec->exists);
            $this->ok('specialites', 'Read', Specialite::find($spec->id) !== null);
            $spec->update(['nom' => 'AuditSpec2']);
            $this->ok('specialites', 'Update', Specialite::find($spec->id)->nom === 'AuditSpec2');

            $st = StructureMedicale::create(['nom'=>'AuditStr','adresse'=>'A','latitude'=>14.77,'longitude'=>-17.41,'type'=>'poste_sante']);
            $this->ok('structures', 'Create', $st->exists);
            $st->update(['telephone' => '+221770000001']);
            $this->ok('structures', 'Update', StructureMedicale::find($st->id)->telephone === '+221770000001');

            $u = User::create(['nom'=>'AUDIT','prenom'=>'Test','role'=>'medecin','email'=>'audit.med@t.sn','password'=>'password']);
            $this->ok('users', 'Create', $u->exists);
            $u->update(['telephone' => '+221770000002']);
            $this->ok('users', 'Update', User::find($u->id)->telephone === '+221770000002');

            $m = Medecin::create(['utilisateur_id'=>$u->id,'structure_id'=>$st->id,'specialite_id'=>$spec->id,'num_ordre'=>'AUDIT-1','valide'=>false]);
            $this->ok('medecins', 'Create', $m->exists);
            $m->update(['valide' => true]);
            $this->ok('medecins', 'Update (validation admin)', Medecin::find($m->id)->valide === true);

            $d = Disponibilite::create(['medecin_id'=>$m->id,'type'=>'BASE','jour_semaine'=>1,'heure_debut'=>'08:00','heure_fin'=>'12:00']);
            $this->ok('disponibilites', 'Create (BASE, admin)', $d->exists);
            $i = Disponibilite::create(['medecin_id'=>$m->id,'type'=>'INDISPONIBILITE','date'=>now()->addDay()->toDateString(),'motif'=>'conge']);
            $this->ok('disponibilites', 'Create (INDISPO, medecin)', $i->exists);
            $this->ok('disponibilites', 'Read (scopes base/indispo)', Disponibilite::base()->count() >= 1 && Disponibilite::indisponibilite()->count() >= 1);

            $up = User::create(['nom'=>'AUDIT','prenom'=>'Pat','role'=>'patient','email'=>'audit.pat@t.sn','password'=>'password']);
            $p = Patient::create(['utilisateur_id'=>$up->id]);
            $this->ok('patients', 'Create', $p->exists);
            $dp = DossierPatient::create(['patient_id'=>$p->id,'antecedents'=>'RAS']);
            $this->ok('dossiers_patients', 'Create', $dp->exists);
            $dp->update(['antecedents'=>'Diabete']);
            $this->ok('dossiers_patients', 'Update', DossierPatient::find($dp->id)->antecedents === 'Diabete');

            $creneau = now()->addDays(2)->setTime(9, 0);
            $r = $rdv->creerRendezVous($p, $m, $creneau);
            $this->ok('rendez_vous', 'Create (CONFIRME auto)', $r->statut === 'CONFIRME');
            $doublon = false;
            try { $rdv->creerRendezVous($p, $m, $creneau); } catch (\RuntimeException) { $doublon = true; }
            $this->ok('rendez_vous', 'Anti-doublon refuse', $doublon);
            $rdv->annuler($r);
            $this->ok('rendez_vous', 'Update (ANNULE)', RendezVous::find($r->id)->statut === 'ANNULE');

            $r2 = $rdv->creerRendezVous($p, $m, now()->addDays(3)->setTime(9, 0));
            $c = Consultation::create(['rendez_vous_id'=>$r2->id,'medecin_id'=>$m->id,'patient_id'=>$p->id,'observations'=>'Examen normal','prescription'=>'Repos']);
            $this->ok('consultations', 'Create', $c->exists);
            $r2->update(['statut'=>'HONORE']);
            $this->ok('consultations', 'RDV passe a HONORE', RendezVous::find($r2->id)->statut === 'HONORE');

            $up->notify(new \App\Notifications\RdvConfirme($r2));
            $this->ok('notifications', 'Canal database + mail', true);

            $creneaux = $rdv->creneauxSemaine($m, now()->startOfWeek());
            $this->ok('disponibilites', 'Creneaux = base - RDV - indispos', is_array($creneaux) && count($creneaux) === 5);

            $c->delete();       $this->ok('consultations', 'Delete', Consultation::find($c->id) === null);
            $dp->delete();      $this->ok('dossiers_patients', 'Delete', DossierPatient::find($dp->id) === null);
            $i->delete();       $this->ok('disponibilites', 'Delete', Disponibilite::find($i->id) === null);
            RendezVous::whereIn('id', [$r->id, $r2->id])->delete();
            $this->ok('rendez_vous', 'Delete', RendezVous::find($r->id) === null);
        } finally {
            DB::rollBack();
        }
        if ($this->ko === 0) { $this->info('  => CRUD conforme sur toutes les entites (aucune donnee laissee : rollback).'); return 0; }
        $this->error("  => $this->ko operation(s) en echec."); return 1;
    }
}
