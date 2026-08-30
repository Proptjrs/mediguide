@extends('layouts.app', ['title' => 'Administration — MediGuide'])

@section('content')
<div class="wrap dash-shell">
    <span class="session-chip"><svg><use href="#i-check"/></svg>Connecté — {{ auth()->user()->fullName() }}</span>
    <h2 class="k-title">Administration</h2>
    <p class="k-sub" style="margin-bottom:28px">Vue d'ensemble de la plateforme, des comptes et des plannings de consultation.</p>

    <div class="dash-grid">
        <div class="kpi">
            <div class="ic"><svg><use href="#i-users"/></svg></div>
            <div class="n">{{ $nbPatients }}</div>
            <div class="l">Patients inscrits</div>
        </div>
        <div class="kpi">
            <div class="ic"><svg><use href="#i-shield"/></svg></div>
            <div class="n">{{ $nbMedecins }}</div>
            <div class="l">Médecins validés</div>
            @if ($enAttente->count()) <span class="tag" style="background:var(--amber-pale);color:#B45309">{{ $enAttente->count() }} en attente</span> @endif
        </div>
        <div class="kpi">
            <div class="ic"><svg><use href="#i-cal"/></svg></div>
            <div class="n">{{ $rdvSemaine }}</div>
            <div class="l">RDV cette semaine</div>
        </div>
    </div>

    {{-- Tableau de bord décisionnel : ce que la direction doit décider, et non
         le simple décompte des enregistrements. Fenêtre de trente jours. --}}
    <div class="panel">
        <h3>Pilotage <span class="pill b">30 derniers jours</span></h3>
        <p class="panel-note">Ces indicateurs servent à décider : rappeler les patients qui ne viennent pas,
            renforcer une spécialité saturée, surveiller la part des cas urgents.</p>

        <div class="dash-grid quatre">
            <div class="kpi">
                <div class="n" style="color:{{ $indicateurs['tauxManques'] > 15 ? 'var(--red)' : 'var(--green-dark)' }}">
                    {{ $indicateurs['tauxManques'] }} %
                </div>
                <div class="l">Rendez-vous manqués</div>
                <div class="sous">{{ $indicateurs['manques'] }} sur {{ $indicateurs['rdvClos'] }} passés</div>
            </div>
            <div class="kpi">
                <div class="n">{{ $indicateurs['annulations'] }}</div>
                <div class="l">Annulations</div>
                <div class="sous">créneaux libérés à réattribuer</div>
            </div>
            <div class="kpi">
                <div class="n">{{ $indicateurs['questionnaires'] }}</div>
                <div class="l">Orientations demandées</div>
                <div class="sous">urgence moyenne : {{ $indicateurs['urgenceMoyenne'] }} / 10</div>
            </div>
            <div class="kpi">
                <div class="n" style="color:{{ $indicateurs['tauxUrgence'] > 20 ? 'var(--red)' : 'inherit' }}">
                    {{ $indicateurs['urgences'] }}
                </div>
                <div class="l">Urgences détectées</div>
                <div class="sous">{{ $indicateurs['tauxUrgence'] }} % des questionnaires</div>
            </div>
        </div>

        @php $maxMois = max(1, collect($activiteMensuelle)->max('valeur')); @endphp
        <h4 style="margin:26px 0 10px;font-size:.95rem;color:var(--muted)">Activité des six derniers mois</h4>
        <div class="histo" role="img" aria-label="Rendez-vous par mois sur six mois">
            @foreach ($activiteMensuelle as $m)
                <div class="histo-col">
                    <div class="histo-val">{{ $m['valeur'] }}</div>
                    <div class="histo-bar" style="height:{{ max(4, round($m['valeur'] / $maxMois * 100)) }}%"></div>
                    <div class="histo-lbl">{{ $m['libelle'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="panel">
        <h3>Patients à rappeler <span class="pill b">Urgence détectée</span></h3>
        <p class="panel-note">Le questionnaire de ces patients a franchi le seuil d'urgence : ils ont été renvoyés
            vers les urgences plutôt que vers un rendez-vous. Un appel de suivi permet de vérifier leur prise en charge.</p>
        @forelse ($patientsARisque as $q)
            <div class="row-item">
                <div class="ava" style="width:44px;height:44px;font-size:1rem;border-radius:12px;background:var(--red-pale);color:var(--red-dark)">
                    {{ $q->niveau_urgence }}
                </div>
                <div class="grow">
                    <h4>{{ $q->patient?->utilisateur?->fullName() ?? 'Visiteur non identifié' }}</h4>
                    <div class="sub">
                        Orientation proposée : {{ $q->specialite_resultat ?: 'urgences' }}
                        · {{ $q->created_at->diffForHumans() }}
                    </div>
                </div>
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucune urgence détectée sur les trente derniers jours.</p>
        @endforelse
    </div>

    @php
        $totalRdv = collect($rdvParSpecialite)->sum('valeur');
        $rayon = 62;
        $circonference = 2 * M_PI * $rayon;
        $cumul = 0;
    @endphp
    <div class="panel">
        <h3>RDV par spécialité — {{ now()->translatedFormat('F Y') }}</h3>
        @if ($totalRdv > 0)
            <div class="donut-wrap" x-data="{ affiche: false }" x-init="$nextTick(() => affiche = true)">
                <svg class="donut-svg" viewBox="0 0 160 160" aria-label="Répartition des rendez-vous par spécialité">
                    <circle cx="80" cy="80" r="{{ $rayon }}" fill="none" stroke="#F1F5F9" stroke-width="20"/>
                    <g transform="rotate(-90 80 80)">
                        @foreach ($rdvParSpecialite as $part)
                            @php
                                $longueur = $circonference * $part['valeur'] / $totalRdv;
                                $decalage = -$cumul;
                                $cumul += $longueur;
                            @endphp
                            <circle class="donut-seg" cx="80" cy="80" r="{{ $rayon }}" fill="none"
                                    stroke="{{ $part['couleur'] }}" stroke-width="20"
                                    stroke-dashoffset="{{ $decalage }}"
                                    :stroke-dasharray="affiche
                                        ? '{{ round($longueur, 2) }} {{ round($circonference - $longueur, 2) }}'
                                        : '0 {{ round($circonference, 2) }}'"><title>{{ $part['libelle'] }} : {{ $part['valeur'] }}</title></circle>
                        @endforeach
                    </g>
                    <text x="80" y="76" text-anchor="middle" class="donut-center" font-weight="800" font-size="26" fill="#101B2D">{{ $totalRdv }}</text>
                    <text x="80" y="94" text-anchor="middle" font-size="10" fill="#64748B" font-weight="600">RDV / MOIS</text>
                </svg>
                <div class="donut-legend">
                    @foreach ($rdvParSpecialite as $part)
                        <div class="dl-item">
                            <i style="background:{{ $part['couleur'] }}"></i>
                            <span class="lbl">{{ $part['libelle'] }}</span>
                            <span class="val">{{ $part['valeur'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <p style="color:var(--muted);margin:0">Aucun rendez-vous ce mois-ci — le graphique apparaîtra dès la première réservation.</p>
        @endif
    </div>

    <div class="panel">
        <h3>Validations en attente <span class="pill b">Vérification n° Ordre</span></h3>
        @forelse ($enAttente as $m)
            <div class="row-item">
                <div class="ava" style="width:44px;height:44px;font-size:1rem;border-radius:12px">
                    {{ strtoupper(substr($m->utilisateur->prenom, 0, 1) . substr($m->utilisateur->nom, 0, 1)) }}
                </div>
                <div class="grow">
                    <h4>{{ $m->utilisateur->fullName() }}</h4>
                    <div class="sub">{{ $m->specialite->nom }} · n° Ordre : {{ $m->num_ordre }}</div>
                </div>
                {{-- L'administrateur statue : il accepte ou il refuse. N'offrir que
                     « Valider » laissait un numéro d'Ordre douteux en attente pour
                     toujours, sans trace de la décision. --}}
                <div style="display:flex;gap:8px">
                    <form method="POST" action="{{ route('admin.valider', $m) }}">
                        @csrf <button class="btn btn-primary btn-sm">Valider</button>
                    </form>
                    <form method="POST" action="{{ route('admin.invalider', $m) }}"
                          onsubmit="return confirm('Refuser l\'inscription de {{ $m->utilisateur->fullName() }} ? Son compte restera fermé.')">
                        @csrf <button class="btn btn-ghost btn-sm">Invalider</button>
                    </form>
                </div>
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucune validation en attente.</p>
        @endforelse
    </div>

    <div class="panel">
        <h3>Plannings de consultation <span class="pill b">Fixé par l'administration</span></h3>
        <p class="panel-note">L'administration établit les plages de consultation de chaque médecin de la structure.
            Les créneaux proposés aux patients en découlent, déduction faite des rendez-vous pris et des indisponibilités déclarées.</p>
        @forelse ($medecins as $m)
            <div class="row-item">
                <div class="ava" style="width:44px;height:44px;font-size:1rem;border-radius:12px">
                    {{ strtoupper(substr($m->utilisateur->prenom, 0, 1) . substr($m->utilisateur->nom, 0, 1)) }}
                </div>
                <div class="grow">
                    <h4>{{ $m->utilisateur->fullName() }}</h4>
                    <div class="sub">{{ $m->specialite->nom }} · {{ $m->structure->nom }}</div>
                </div>
                <a href="{{ route('admin.planning.edit', $m) }}" class="btn btn-outline btn-sm">Modifier le planning</a>
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucun médecin validé.</p>
        @endforelse
    </div>

    <div class="panel">
        <h3>Comptes utilisateurs <span class="pill b">{{ $nbComptes }} comptes</span></h3>
        <p class="panel-note">Créer, modifier, activer ou désactiver un compte. Un patient s'inscrit seul ;
            un médecin s'inscrit puis attend la validation de son n° d'Ordre ; un administrateur ne se crée qu'ici.</p>
        <a href="{{ route('admin.utilisateurs.index') }}" class="btn btn-outline btn-sm">Ouvrir les comptes</a>
    </div>

    <div class="panel">
        <h3>Structures médicales <span class="pill b">{{ $nbStructures }} référencées</span></h3>
        <p class="panel-note">Ajouter, modifier ou retirer une structure du district. Les coordonnées GPS peuvent être déduites de l'adresse par géocodage OpenStreetMap.</p>
        <a href="{{ route('admin.structures.index') }}" class="btn btn-outline btn-sm">Ouvrir les structures</a>
    </div>

</div>
@endsection
