@extends('layouts.app', ['title' => 'Urgence détectée — MediGuide'])

@section('content')
<div class="urgence-screen" role="alertdialog" aria-label="Urgence détectée">
  <div class="urg-card">
    <div class="mk"><svg><use href="#i-alert"/></svg></div>
    <h2>Urgence détectée</h2>
    @if ($score)
        <p>Vos réponses indiquent une situation potentiellement grave (score <b>{{ $score }}</b>/10). N'attendez pas un rendez-vous : contactez immédiatement les secours.</p>
    @else
        {{-- Arrivée directe, sans questionnaire : la personne cherche un numéro,
             pas un diagnostic. --}}
        <p>Devant un signe grave — douleur à la poitrine, difficulté à respirer, perte de connaissance,
            saignement abondant — n'attendez pas un rendez-vous : appelez les secours.</p>
    @endif
    <div class="urg-actions">
      <a class="btn btn-white" href="tel:1515">SAMU — 1515</a>
      <a class="btn btn-white" href="tel:18">Pompiers — 18</a>
    </div>
    <p style="margin-top:24px;font-size:.92rem">Urgences les plus proches : <b>Hôpital Roi Baudouin — Guédiawaye</b> (ouvert 24 h/24)</p>
  </div>
</div>
@endsection
