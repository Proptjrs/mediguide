<?php

namespace App\Livewire;

use App\Services\AssistantService;
use Livewire\Component;

/**
 * L'assistant conversationnel, en pleine page.
 *
 * La conversation vit dans la session : elle survit à un rafraîchissement sans
 * qu'aucune parole ne soit conservée sur l'appareil, ce qui compte sur un
 * téléphone partagé.
 */
class AssistantMedical extends Component
{
    public string $question = '';

    /** @var array<int, array{role:string, texte:string, urgence:bool}> */
    public array $conversation = [];

    /** @var array<int, string> */
    public array $pistes = [];

    public function mount(AssistantService $assistant): void
    {
        $this->conversation = session('assistant.conversation', []);
        $this->pistes = session('assistant.pistes', []);

        if ($this->conversation === []) {
            $ouverture = $assistant->repondre('bonjour', auth()->user());
            $this->ajouter('assistant', $ouverture['reponse'], false);
            $this->pistes = $ouverture['pistes'];
            $this->enregistrer();
        }
    }

    public function envoyer(AssistantService $assistant): void
    {
        $this->validate(['question' => 'required|string|min:2|max:500'], [
            'question.required' => 'Écrivez votre question.',
            'question.max' => 'Votre question est trop longue.',
        ]);

        $this->ajouter('patient', $this->question, false);
        $reponse = $assistant->repondre($this->question, auth()->user());
        $this->ajouter('assistant', $reponse['reponse'], $reponse['urgence']);
        $this->pistes = $reponse['pistes'];
        $this->question = '';
        $this->enregistrer();
    }

    /** Une piste proposée se pose comme une question ordinaire. */
    public function suivre(string $piste, AssistantService $assistant): void
    {
        if ($piste === 'Commencer le questionnaire') {
            $this->redirectRoute('orientation', navigate: true);

            return;
        }
        $this->question = $piste;
        $this->envoyer($assistant);
    }

    public function effacer(): void
    {
        $this->conversation = [];
        $this->pistes = [];
        session()->forget(['assistant.conversation', 'assistant.pistes']);
        $this->mount(app(AssistantService::class));
    }

    private function ajouter(string $role, string $texte, bool $urgence): void
    {
        $this->conversation[] = ['role' => $role, 'texte' => $texte, 'urgence' => $urgence];
    }

    private function enregistrer(): void
    {
        session(['assistant.conversation' => $this->conversation, 'assistant.pistes' => $this->pistes]);
    }

    public function render()
    {
        return view('livewire.assistant-medical')
            ->layout('layouts.app', ['title' => 'Assistant — MediGuide']);
    }
}
