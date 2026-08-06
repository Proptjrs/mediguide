<div class="q-shell">
    <h2 class="k-title" style="text-align:center">Questionnaire d'orientation</h2>
    <p class="k-sub" style="text-align:center;margin-inline:auto;max-width:520px">Cinq étapes, deux minutes. Vous êtes guidé à chaque instant.</p>

    <div class="q-route" aria-hidden="true">
        <div class="trail" style="width:{{ ($etape - 1) / 4 * 100 }}%"></div>
        @foreach ([1 => 'Localisation', 2 => 'Profil', 3 => 'Problème', 4 => 'Zone du corps', 5 => 'Urgence'] as $n => $lbl)
            <div class="q-dot {{ $etape > $n ? 'done' : ($etape === $n ? 'now' : '') }}">
                {{ $etape > $n ? '✓' : $n }}<span>{{ $lbl }}</span>
            </div>
        @endforeach
    </div>

    <div class="q-card">
        @if ($etape === 1)
            <div class="q-step on">
                <h2>Où êtes-vous ?</h2>
                <p class="hint">Votre position sert uniquement à trouver les structures les plus proches de vous. Elle n'est jamais conservée.</p>
                <div class="geo-box">
                    <div class="st">
                        @if ($lat)
                            <b>Position enregistrée.</b> Vous pouvez continuer.
                        @else
                            <b>Position non détectée.</b> Autorisez la géolocalisation ou choisissez un quartier.
                        @endif
                    </div>
                    <button type="button" class="btn btn-outline btn-sm"
                            onclick="navigator.geolocation.getCurrentPosition(
                                p => @this.setPosition(p.coords.latitude, p.coords.longitude))">
                        <svg><use href="#i-pin"/></svg> Me localiser
                    </button>
                </div>
                <div class="field" style="margin-top:24px">
                    <label>Ou choisissez votre quartier (Guédiawaye)</label>
                    <select wire:change="setPosition(...$event.target.value.split(','))">
                        <option value="">— Sélectionner —</option>
                        <option value="14.7712,-17.4098">Golf Sud</option>
                        <option value="14.7825,-17.4010">Wakhinane Nimzatt</option>
                        <option value="14.7699,-17.4021">Darouminame</option>
                        <option value="14.7745,-17.3945">Médina Gounass</option>
                    </select>
                    @error('lat') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>

        @elseif ($etape === 2)
            <div class="q-step on">
                <h2>Quel est votre profil ?</h2>
                <p class="hint">Ces informations affinent l'orientation (ex. pédiatrie, gynécologie).</p>
                <div class="grid2">
                    <div class="field"><label>Âge</label>
                        <input type="number" wire:model="age" dusk="age" min="0" max="120" placeholder="Ex. 27"></div>
                    <div class="field"><label>Sexe</label>
                        <select wire:model="sexe">
                            <option value="">—</option><option value="F">Féminin</option><option value="M">Masculin</option>
                        </select></div>
                </div>
                <div class="field"><label>Antécédents médicaux (facultatif)</label>
                    <textarea rows="3" wire:model="antecedents" placeholder="Diabète, hypertension, allergies…"></textarea></div>
            </div>

        @elseif ($etape === 3)
            <div class="q-step on">
                <h2>Quel est votre problème principal ?</h2>
                <p class="hint">Choisissez la catégorie la plus proche de ce que vous ressentez.</p>
                <div class="choice-grid">
                    @foreach ([
                        'douleur' => ['Douleur', 'target'], 'fievre' => ['Fièvre / infection', 'alert'],
                        'respiration' => ['Respiration', 'lungs'], 'digestif' => ['Digestif', 'stomach'],
                        'peau' => ['Peau', 'skin'], 'vision' => ['Vision', 'eye'],
                        'orl' => ['Oreille / gorge', 'ear'], 'grossesse' => ['Grossesse / gynéco', 'preg'],
                        'enfant' => ["Santé de l'enfant", 'child'], 'mental' => ['Moral / sommeil', 'brain'],
                        'dents' => ['Dents', 'tooth'], 'articulations' => ['Articulations', 'bone'],
                        'hormones' => ['Hormones / diabète', 'drop'], 'autre' => ['Autre / bilan', 'kit'],
                    ] as $val => [$lbl, $icon])
                        <button type="button" wire:click="$set('probleme', '{{ $val }}')"
                                class="choice {{ $probleme === $val ? 'sel' : '' }}">
                            <svg><use href="#i-{{ $icon }}"/></svg>{{ $lbl }}
                        </button>
                    @endforeach
                </div>
                @error('probleme') <div class="field-error">{{ $message }}</div> @enderror
            </div>

        @elseif ($etape === 4)
            <div class="q-step on">
                <h2>Où se situe la gêne ?</h2>
                <p class="hint">Touchez la silhouette ou choisissez une zone dans la liste.</p>
                <div class="body-wrap">
                    <svg class="body-svg" viewBox="0 0 200 420" aria-label="Silhouette du corps humain">
                        <ellipse class="zone {{ $zone === 'tete' ? 'sel' : '' }}" wire:click="$set('zone','tete')"
                                 cx="100" cy="42" rx="26" ry="30"><title>Tête</title></ellipse>
                        <rect class="zone {{ $zone === 'gorge' ? 'sel' : '' }}" wire:click="$set('zone','gorge')"
                              x="88" y="74" width="24" height="20" rx="8"><title>Gorge / cou</title></rect>
                        <path class="zone {{ $zone === 'poitrine' ? 'sel' : '' }}" wire:click="$set('zone','poitrine')"
                              d="M62 96 h76 v56 h-76 z"><title>Poitrine</title></path>
                        <path class="zone {{ $zone === 'ventre' ? 'sel' : '' }}" wire:click="$set('zone','ventre')"
                              d="M66 154 h68 v52 h-68 z"><title>Ventre</title></path>
                        <path class="zone {{ $zone === 'bassin' ? 'sel' : '' }}" wire:click="$set('zone','bassin')"
                              d="M68 208 h64 v34 h-64 z"><title>Bassin</title></path>
                        <rect class="zone {{ $zone === 'bras' ? 'sel' : '' }}" wire:click="$set('zone','bras')"
                              x="30" y="100" width="26" height="112" rx="13"><title>Bras gauche</title></rect>
                        <rect class="zone {{ $zone === 'bras' ? 'sel' : '' }}" wire:click="$set('zone','bras')"
                              x="144" y="100" width="26" height="112" rx="13"><title>Bras droit</title></rect>
                        <rect class="zone {{ $zone === 'jambes' ? 'sel' : '' }}" wire:click="$set('zone','jambes')"
                              x="70" y="246" width="26" height="150" rx="13"><title>Jambe gauche</title></rect>
                        <rect class="zone {{ $zone === 'jambes' ? 'sel' : '' }}" wire:click="$set('zone','jambes')"
                              x="104" y="246" width="26" height="150" rx="13"><title>Jambe droite</title></rect>
                    </svg>
                    <div class="zone-list">
                        @foreach ([
                            'tete' => 'Tête', 'gorge' => 'Gorge / cou', 'poitrine' => 'Poitrine / cœur',
                            'ventre' => 'Ventre', 'bassin' => 'Bassin', 'bras' => 'Bras / épaules',
                            'jambes' => 'Jambes / genoux', '_' => 'Tout le corps',
                        ] as $val => $lbl)
                            <button type="button" wire:click="$set('zone', '{{ $val }}')"
                                    class="zone-tag {{ $zone === $val ? 'sel' : '' }}">{{ $lbl }}</button>
                        @endforeach
                    </div>
                </div>
                @error('zone') <div class="field-error">{{ $message }}</div> @enderror
            </div>

        @else
            <div class="q-step on">
                <h2>Quel est votre niveau d'urgence ?</h2>
                <p class="hint">1 = gêne légère · 10 = insupportable. Cochez aussi les signes d'alarme éventuels.</p>
                <div class="urg-zone">
                    <div class="urg-val {{ $niveauUrgence >= 7 ? 'high' : ($niveauUrgence >= 4 ? 'mid' : '') }}">
                        {{ $niveauUrgence }}
                    </div>
                    <input type="range" class="urg-slider" min="1" max="10" wire:model.live="niveauUrgence">
                    <div class="urg-scale"><span>1 · Légère</span><span>5 · Modérée</span><span>10 · Insupportable</span></div>
                </div>
                <div style="margin-top:28px">
                    <label style="font-weight:700;font-size:.88rem;color:var(--text-dark);display:block;margin-bottom:12px">
                        Signes d'alarme (ajoutent des points d'urgence)</label>
                    @foreach ([
                        'douleur_thoracique' => 'Douleur thoracique intense ou oppression',
                        'difficulte_respiratoire' => 'Difficulté à respirer',
                        'saignement_important' => 'Saignement important',
                        'perte_connaissance' => 'Perte de connaissance / confusion',
                        'fievre_40' => 'Fièvre > 40 °C',
                    ] as $val => $lbl)
                        <label class="alarm {{ in_array($val, $signesAlarme) ? 'sel' : '' }}">
                            <input type="checkbox" value="{{ $val }}" wire:model="signesAlarme" id="al-{{ $val }}">
                            {{ $lbl }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="q-nav">
            <button type="button" class="btn btn-outline" wire:click="precedent"
                    @if ($etape === 1) style="visibility:hidden" @endif>← Retour</button>
            <button type="button" class="btn btn-primary" wire:click="suivant">
                {{ $etape === 5 ? 'Voir mon orientation' : 'Continuer' }} <svg><use href="#i-arrow"/></svg>
            </button>
        </div>
    </div>
</div>
