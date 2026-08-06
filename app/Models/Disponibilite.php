<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deux usages du même modèle (mémoire, chap. 3) :
 * - type BASE : plage hebdomadaire fixée par l'admin — le planning de référence.
 * - type INDISPONIBILITE : absence ponctuelle déclarée par le médecin (exception au planning de base).
 */
class Disponibilite extends Model
{
    use HasUuids;

    protected $fillable = [
        'medecin_id', 'type', 'jour_semaine', 'date',
        'heure_debut', 'heure_fin', 'motif', 'actif',
    ];
    protected $casts = ['actif' => 'boolean', 'date' => 'date'];

    public function medecin(): BelongsTo { return $this->belongsTo(Medecin::class); }

    public function scopeBase($query)
    {
        return $query->where('type', 'BASE');
    }

    public function scopeIndisponibilite($query)
    {
        return $query->where('type', 'INDISPONIBILITE');
    }
}
