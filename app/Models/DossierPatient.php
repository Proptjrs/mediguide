<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierPatient extends Model
{
    use HasUuids;
    protected $table = 'dossiers_patients';
    protected $fillable = ['patient_id', 'antecedents'];

    /**
     * Chiffrement au repos des antécédents médicaux (mémoire, section 6 :
     * « chiffrement des documents médicaux »). Le chiffrement/déchiffrement est
     * transparent via Eloquent : en base, la valeur est illisible sans APP_KEY.
     */
    protected $casts = ['antecedents' => 'encrypted'];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
}
