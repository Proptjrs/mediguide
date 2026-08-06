<?php

namespace App\Policies;

use App\Models\{DossierPatient, User};

/**
 * DossierPolicy (mémoire, chap. 4.2.7) :
 * - un patient n'accède qu'à SON dossier ;
 * - un médecin n'accède qu'aux dossiers des patients ayant un RDV confirmé avec lui.
 */
class DossierPolicy
{
    public function view(User $user, DossierPatient $dossier): bool
    {
        return match ($user->role) {
            'patient' => $user->patient?->id === $dossier->patient_id,
            'medecin' => $dossier->patient->rendezVous()
                ->where('medecin_id', $user->medecin?->id)
                ->whereIn('statut', ['CONFIRME', 'HONORE'])
                ->exists(),
            'admin' => true,
            default => false,
        };
    }

    public function update(User $user, DossierPatient $dossier): bool
    {
        return $user->role === 'medecin' && $this->view($user, $dossier);
    }
}
