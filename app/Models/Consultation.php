<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    use HasUuids;

    protected $fillable = ['rendez_vous_id', 'medecin_id', 'patient_id', 'observations', 'prescription'];

    /** Compte rendu et ordonnance chiffrés au repos (mémoire, section 6). */
    protected $casts = ['observations' => 'encrypted', 'prescription' => 'encrypted'];

    public function rendezVous(): BelongsTo { return $this->belongsTo(RendezVous::class, 'rendez_vous_id'); }
    public function medecin(): BelongsTo    { return $this->belongsTo(Medecin::class); }
    public function patient(): BelongsTo    { return $this->belongsTo(Patient::class); }
}
