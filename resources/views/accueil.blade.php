@extends('layouts.app', ['title' => 'MediGuide — Trouvez le bon médecin, au bon endroit, au bon moment'])

@section('content')
<div class="hero">
  <div class="hero-in">
    <div>
      <span class="eyebrow rise d1"><span class="dot"></span> Centre Hospitalier Roi Baudouin · Guédiawaye · Plateforme active</span>
      <h1 class="rise d2">Vous ne savez pas <span class="accent">quel médecin</span> consulter ?<br>C'est normal. <span class="accent2">On vous guide.</span></h1>
      <p class="lead rise d3">62 % des patients arrivent à l'hôpital sans savoir vers quel service se diriger. MediGuide part de ce que vous ressentez — pas d'un nom de spécialité — pour vous orienter vers le bon médecin, au plus proche, avec un créneau disponible.</p>
      <div class="hero-cta rise d4">
        @auth
          @if (auth()->user()->role === 'patient')
            <a class="btn btn-primary" href="{{ route('orientation') }}"><svg><use href="#i-compass"/></svg> Démarrer mon orientation</a>
          @else
            <a class="btn btn-primary" href="{{ route('dashboard') }}"><svg><use href="#i-compass"/></svg> Accéder à mon espace</a>
          @endif
        @else
          {{-- Le questionnaire est ouvert : la connexion n'est demandée qu'à la réservation. --}}
          <a class="btn btn-primary" href="{{ route('orientation') }}"><svg><use href="#i-compass"/></svg> Démarrer l'orientation</a>
        @endauth
        <a class="btn btn-outline" href="{{ route('resultats') }}"><svg><use href="#i-pin"/></svg> Voir les structures proches</a>
        {{-- Accès direct aux secours, sans compte ni questionnaire : quelqu'un qui
             vit une urgence n'a pas à s'inscrire pour trouver un numéro. --}}
        <a class="btn btn-urgence" href="{{ route('urgence') }}">
            <svg><use href="#i-alert"/></svg> Urgence — voir les secours
        </a>
      </div>
      <div class="hero-stats rise d5">
        <div><b>18</b><span>Spécialités couvertes</span></div>
        <div><b>&lt;2 min</b><span>Pour être orienté</span></div>
        <div><b>100%</b><span>Gratuit pour le patient</span></div>
      </div>
    </div>
    <div class="hero-visual rise d3" x-data="{
        zone: null,
        labels: {tete:'Neurologie',gorge:'ORL',poitrine:'Cardiologie',ventre:'Gastro-entérologie',bassin:'Urologie',bras:'Orthopédie',jambes:'Orthopédie'},
        names: {tete:'Tête',gorge:'Gorge',poitrine:'Poitrine',ventre:'Ventre',bassin:'Bassin',bras:'Bras',jambes:'Jambes'}
      }">
      <div class="entry-card">
        <div class="entry-head">
          <span class="entry-step">Aperçu — pas encore connecté</span>
          <h3>Où avez-vous mal ?</h3>
          <p>Touchez la zone concernée pour un aperçu de l'orientation.</p>
        </div>
        <div class="entry-body">
          <svg viewBox="0 0 200 420" class="entry-svg" aria-label="Silhouette du corps humain — cliquez sur une zone">
            <ellipse class="ez" :class="{sel: zone==='tete'}" @click="zone='tete'" cx="100" cy="42" rx="26" ry="30"><title>Tête</title></ellipse>
            <rect class="ez" :class="{sel: zone==='gorge'}" @click="zone='gorge'" x="88" y="74" width="24" height="20" rx="8"><title>Gorge</title></rect>
            <path class="ez" :class="{sel: zone==='poitrine'}" @click="zone='poitrine'" d="M62 96 h76 v56 h-76 z"><title>Poitrine</title></path>
            <path class="ez" :class="{sel: zone==='ventre'}" @click="zone='ventre'" d="M66 154 h68 v52 h-68 z"><title>Ventre</title></path>
            <path class="ez" :class="{sel: zone==='bassin'}" @click="zone='bassin'" d="M68 208 h64 v34 h-64 z"><title>Bassin</title></path>
            <rect class="ez" :class="{sel: zone==='bras'}" @click="zone='bras'" x="30" y="100" width="26" height="112" rx="13"><title>Bras</title></rect>
            <rect class="ez" :class="{sel: zone==='bras'}" @click="zone='bras'" x="144" y="100" width="26" height="112" rx="13"><title>Bras</title></rect>
            <rect class="ez" :class="{sel: zone==='jambes'}" @click="zone='jambes'" x="70" y="246" width="26" height="150" rx="13"><title>Jambes</title></rect>
            <rect class="ez" :class="{sel: zone==='jambes'}" @click="zone='jambes'" x="104" y="246" width="26" height="150" rx="13"><title>Jambes</title></rect>
          </svg>
          <div class="entry-side">
            <p class="entry-hint" x-show="!zone">Sélectionnez une zone pour voir la spécialité correspondante.
              @guest Connectez-vous pour lancer le questionnaire complet. @endguest</p>
            <div class="entry-result" :class="{show: zone}" x-cloak>
              <div class="lbl">Orientation indicative</div>
              <div class="spec" x-text="labels[zone]"></div>
              <div class="zone">Zone : <span x-text="names[zone]"></span></div>
            </div>
            @auth
              @if (auth()->user()->role === 'patient')
                <a href="{{ route('orientation') }}" class="btn btn-primary entry-go" x-show="zone" x-cloak>
                  Répondre au questionnaire <svg><use href="#i-arrow"/></svg>
                </a>
              @endif
            @else
              <a href="{{ route('orientation') }}" class="btn btn-primary entry-go" x-show="zone" x-cloak>
                Répondre au questionnaire <svg><use href="#i-arrow"/></svg>
              </a>
            @endauth
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="route-band wrap">
  <div class="sec-head">
    <h2 class="k-title">Un parcours simple en trois étapes</h2>
    <p class="k-sub">De vos symptômes à votre rendez-vous confirmé, sans détour.</p>
  </div>
  <div class="route">
    <div class="stop">
      <div class="stop-mark"><svg><use href="#i-compass"/></svg></div>
      <div class="stop-idx">ÉTAPE 1</div>
      <h3>Questionnaire</h3>
      <p>Décrivez vos symptômes en cinq étapes simples. En cas d'urgence détectée, redirection immédiate vers le SAMU 1515.</p>
    </div>
    <div class="stop">
      <div class="stop-mark"><svg><use href="#i-pin"/></svg></div>
      <div class="stop-idx">ÉTAPE 2</div>
      <h3>Géolocalisation</h3>
      <p>La structure la plus proche du district qui propose votre spécialité, avec distance et temps de trajet.</p>
    </div>
    <div class="stop">
      <div class="stop-mark"><svg><use href="#i-cal"/></svg></div>
      <div class="stop-idx">ÉTAPE 3</div>
      <h3>Rendez-vous</h3>
      <p>Réservez le créneau le plus adapté en quelques clics. Confirmation et rappel automatiques par SMS et e-mail.</p>
    </div>
  </div>
</div>

<div class="stats-band">
  <div class="wrap">
    {{-- Compteurs animés au défilement, comme dans la maquette (data-count). --}}
    <div class="stats">
      @foreach ([
        [94, '%', 'des patients interrogés utiliseraient MediGuide'],
        [86, '%', 'ont accès à Internet sur leur smartphone'],
        [70, '%', 'ont déjà été orientés vers le mauvais service'],
        [18, '+', 'spécialités médicales couvertes par le moteur'],
      ] as [$cible, $unite, $libelle])
        <div class="stat" x-data="compteur({{ $cible }})" x-intersect.once="demarrer()">
          <div class="n"><span x-text="valeur">0</span><span class="u">{{ $unite }}</span></div>
          <div class="l">{{ $libelle }}</div>
        </div>
      @endforeach
    </div>
    <p class="stats-src">Source — Enquête terrain, 50 patients / 3 médecins, Centre Hospitalier Roi Baudouin et structures environnantes, Guédiawaye, mars 2026</p>
  </div>
</div>
@endsection
