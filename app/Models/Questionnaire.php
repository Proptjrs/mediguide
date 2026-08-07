<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Questionnaire extends Model
{
    use HasUuids;
    protected $fillable = ['patient_id', 'specialite_resultat', 'niveau_urgence', 'urgence_detectee', 'etapes'];
    protected $casts = ['etapes' => 'array', 'urgence_detectee' => 'boolean'];

    /** Le questionnaire est anonyme tant que le visiteur ne s'est pas identifié. */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
