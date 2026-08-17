<div class="q-shell">
    <h2 class="k-title" style="text-align:center">Assistant MediGuide</h2>
    <p class="k-sub" style="text-align:center;margin-inline:auto;max-width:560px">
        Décrivez ce que vous ressentez, avec vos mots. L'assistant vous indique le service qui
        correspond. <strong>Il ne pose aucun diagnostic.</strong>
    </p>

    {{-- La conversation peut être écoutée, comme le questionnaire : une partie
         des patients du district lit mal le français écrit. --}}
    <div style="display:flex;justify-content:center;gap:10px;margin-bottom:14px">
        <button type="button" class="btn-voix" data-lire=".chat-fil" aria-pressed="false"
                title="Lire la conversation à voix haute">
            <svg viewBox="0 0 24 24"><path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M18.5 5.5a9 9 0 0 1 0 13"/></svg>
            <span class="libelle">Écouter</span>
        </button>
        <button type="button" class="btn-voix" wire:click="effacer" title="Recommencer la conversation">
            <svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
            <span class="libelle">Recommencer</span>
        </button>
    </div>

    <div class="q-card">
        <div class="chat-fil" role="log" aria-live="polite" aria-label="Conversation avec l'assistant">
            @foreach ($conversation as $message)
                <div class="chat-ligne {{ $message['role'] }}">
                    <div class="chat-bulle {{ $message['urgence'] ? 'alerte' : '' }}">
                        {!! $message['texte'] !!}
                    </div>
                </div>
            @endforeach
        </div>

        @if ($pistes)
            <div class="chat-pistes">
                @foreach ($pistes as $piste)
                    <button type="button" wire:click="suivre(@js($piste))" class="chat-piste">
                        {{ $piste }}
                    </button>
                @endforeach
            </div>
        @endif

        <form wire:submit="envoyer" class="chat-saisie">
            <label for="question" class="sr-only">Votre question</label>
            <input id="question" type="text" wire:model="question" autocomplete="off"
                   placeholder="Par exemple : j'ai mal au ventre depuis hier">
            <button type="submit" class="btn-primary">
                <span wire:loading.remove wire:target="envoyer">Envoyer</span>
                <span wire:loading wire:target="envoyer">…</span>
            </button>
        </form>
        @error('question') <p class="hint" style="color:var(--red)">{{ $message }}</p> @enderror

        <p class="hint" style="margin-top:12px">
            L'assistant applique des règles écrites, relisibles par un médecin. En cas de signe grave,
            il interrompt la conversation et renvoie au 1515.
        </p>
    </div>
</div>
