<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RendezVous extends Model
{
    use HasUuids;

    protected $table = 'rendez_vous';
    protected $fillable = ['patient_id', 'medecin_id', 'date_heure', 'statut', 'motif', 'rappel_envoye'];
    protected $casts = ['date_heure' => 'datetime', 'rappel_envoye' => 'boolean'];

    public function patient(): BelongsTo     { return $this->belongsTo(Patient::class); }
    public function medecin(): BelongsTo     { return $this->belongsTo(Medecin::class); }
}
