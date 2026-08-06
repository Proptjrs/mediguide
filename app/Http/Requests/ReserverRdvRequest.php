<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** FormRequest de validation serveur (mémoire, chap. 4.2.1). */
class ReserverRdvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'patient';
    }

    public function rules(): array
    {
        return [
            'date_heure' => 'required|date|after:now',
            'motif' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return ['date_heure.after' => 'Le créneau choisi est déjà passé.'];
    }
}
