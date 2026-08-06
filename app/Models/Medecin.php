<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Medecin extends Model
{
    use HasUuids;

    protected $fillable = ['utilisateur_id', 'structure_id', 'specialite_id', 'num_ordre', 'valide'];
    protected $casts = ['valide' => 'boolean'];

    public function utilisateur(): BelongsTo    { return $this->belongsTo(User::class, 'utilisateur_id'); }
    public function structure(): BelongsTo      { return $this->belongsTo(StructureMedicale::class, 'structure_id'); }
    public function specialite(): BelongsTo     { return $this->belongsTo(Specialite::class); }
    public function disponibilites(): HasMany   { return $this->hasMany(Disponibilite::class); }
    public function rendezVous(): HasMany       { return $this->hasMany(RendezVous::class); }
}
