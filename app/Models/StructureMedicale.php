<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StructureMedicale extends Model
{
    use HasUuids;

    protected $table = 'structures_medicales';
    protected $fillable = ['nom', 'adresse', 'latitude', 'longitude', 'telephone', 'type', 'urgences_24h'];
    protected $casts = ['latitude' => 'float', 'longitude' => 'float', 'urgences_24h' => 'boolean'];

    public function medecins(): HasMany { return $this->hasMany(Medecin::class, 'structure_id'); }
}
