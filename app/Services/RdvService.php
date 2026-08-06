<?php

namespace App\Services;

use App\Models\{Disponibilite, Medecin, Patient, RendezVous};
use App\Notifications\RdvConfirme;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Gestion des rendez-vous (mémoire, chap. 4.2.5) :
 * - verifierDisponibilite() : DB::transaction + lockForUpdate pour prévenir
 *   les doubles réservations (test anti-doublon, chap. 4.6.1) ;
 * - creerRendezVous() : statut CONFIRME + notification asynchrone (F5).
 */
class RdvService
{
    /**
     * Créneaux libres d'un médecin pour une semaine donnée (F4).
     * Formule exacte du mémoire (chap. 4.5) :
     *   créneaux = disponibilités de base (admin) − rendez-vous pris − indisponibilités du médecin.
     */
    public function creneauxSemaine(Medecin $medecin, Carbon $lundi): array
    {
        $dispos = $medecin->disponibilites()->base()->where('actif', true)->get()->groupBy('jour_semaine');
        $pris = $medecin->rendezVous()
            ->whereBetween('date_heure', [$lundi, $lundi->copy()->addDays(5)])
            ->where('statut', '!=', 'ANNULE')
            ->pluck('date_heure')
            ->map(fn ($d) => $d->format('Y-m-d H:i'))
            ->all();
        $indispos = $medecin->disponibilites()->indisponibilite()
            ->whereBetween('date', [$lundi->toDateString(), $lundi->copy()->addDays(4)->toDateString()])
            ->get()->groupBy(fn ($d) => $d->date->toDateString());

        $semaine = [];
        foreach (range(1, 5) as $jour) {                         // lundi -> vendredi
            $date = $lundi->copy()->addDays($jour - 1);
            $absencesJour = $indispos->get($date->toDateString(), collect());
            $journeeAbsente = $absencesJour->contains(fn ($a) => $a->heure_debut === null);

            $creneaux = [];
            if (! $journeeAbsente) {
                foreach ($dispos->get($jour, collect()) as $d) {
                    $h = Carbon::parse($date->format('Y-m-d') . ' ' . $d->heure_debut);
                    $fin = Carbon::parse($date->format('Y-m-d') . ' ' . $d->heure_fin);
                    while ($h < $fin) {
                        $absent = $absencesJour->contains(fn ($a) => $a->heure_debut !== null
                            && $h->format('H:i:s') >= $a->heure_debut && $h->format('H:i:s') < $a->heure_fin);
                        $creneaux[] = [
                            'heure' => $h->format('H:i'),
                            'datetime' => $h->format('Y-m-d H:i'),
                            'libre' => ! $absent && ! in_array($h->format('Y-m-d H:i'), $pris) && $h->isFuture(),
                        ];
                        $h->addHour();
                    }
                }
            }
            $semaine[] = ['date' => $date, 'creneaux' => $creneaux];
        }

        return $semaine;
    }

    /**
     * Réservation atomique : le verrou pessimiste garantit qu'un même
     * créneau ne peut jamais être réservé deux fois (anti-doublon).
     *
     * @throws \RuntimeException si le créneau vient d'être pris
     */
    public function creerRendezVous(Patient $patient, Medecin $medecin, Carbon $dateHeure, ?string $motif = null): RendezVous
    {
        return DB::transaction(function () use ($patient, $medecin, $dateHeure, $motif) {
            $deja = RendezVous::where('medecin_id', $medecin->id)
                ->where('date_heure', $dateHeure)
                ->where('statut', '!=', 'ANNULE')
                ->lockForUpdate()                            // verrou jusqu'au commit
                ->exists();

            if ($deja) {
                throw new \RuntimeException('Ce créneau vient d\'être réservé par un autre patient.');
            }

            $rdv = RendezVous::create([
                'patient_id' => $patient->id,
                'medecin_id' => $medecin->id,
                'date_heure' => $dateHeure,
                'statut' => 'CONFIRME',
                'motif' => $motif,
            ]);

            // F5 : confirmation SMS + e-mail, envoyée hors transaction via la queue
            DB::afterCommit(fn () => $patient->utilisateur->notify(new RdvConfirme($rdv)));

            return $rdv;
        });
    }

    public function annuler(RendezVous $rdv): void
    {
        $rdv->update(['statut' => 'ANNULE']);
    }
}
