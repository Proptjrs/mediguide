<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * La secrétaire médicale assiste un médecin : elle tient son agenda, ouvre et
 * ferme ses créneaux. Elle n'accède ni aux comptes, ni aux structures.
 */
class Secretaire extends Model
{
    use HasUuids;

    protected $table = 'secretaires';
    protected $fillable = ['utilisateur_id', 'medecin_id'];

    public function utilisateur(): BelongsTo { return $this->belongsTo(User::class, 'utilisateur_id'); }
    public function medecin(): BelongsTo     { return $this->belongsTo(Medecin::class, 'medecin_id'); }
}
