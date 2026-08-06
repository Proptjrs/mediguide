<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * MustVerifyEmail : l'adresse saisie à l'inscription doit être confirmée par un
 * clic sur le lien reçu, ce qui garantit qu'elle existe réellement et appartient
 * bien à l'utilisateur — c'est là qu'il recevra confirmations et rappels.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasUuids, Notifiable;

    protected $fillable = ['nom', 'prenom', 'email', 'email_verified_at', 'telephone', 'password', 'role', 'actif'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array
    {
        return ['password' => 'hashed', 'actif' => 'boolean', 'email_verified_at' => 'datetime'];
    }

    public function patient(): HasOne  { return $this->hasOne(Patient::class, 'utilisateur_id'); }
    public function medecin(): HasOne  { return $this->hasOne(Medecin::class, 'utilisateur_id'); }
    public function fullName(): string { return "{$this->prenom} {$this->nom}"; }
}
