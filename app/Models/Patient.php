<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Patient extends Model
{
    use HasUuids;

    protected $fillable = ['utilisateur_id', 'date_naissance', 'sexe', 'groupe_sanguin', 'allergies'];

    /** Allergies chiffrées au repos (section 6). Le groupe sanguin reste en clair :
     *  colonne de 5 caractères, non extensible sans migration, et faible sensibilité. */
    protected $casts = ['date_naissance' => 'date', 'allergies' => 'encrypted'];

    public function utilisateur(): BelongsTo     { return $this->belongsTo(User::class, 'utilisateur_id'); }
    public function rendezVous(): HasMany        { return $this->hasMany(RendezVous::class); }
}
