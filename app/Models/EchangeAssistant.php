<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un échange avec l'assistant : la question posée, la réponse rendue, et
 * l'intention que les règles ont reconnue. Conservé pour deux raisons : montrer
 * à l'administration ce que les patients cherchent, et permettre de corriger
 * les règles quand une question revient sans réponse.
 */
class EchangeAssistant extends Model
{
    use HasUuids;

    protected $table = 'echanges_assistant';
    protected $fillable = ['utilisateur_id', 'intention', 'question', 'reponse', 'urgence_detectee'];

    protected function casts(): array
    {
        return ['urgence_detectee' => 'boolean'];
    }

    public function utilisateur(): BelongsTo { return $this->belongsTo(User::class, 'utilisateur_id'); }
}
