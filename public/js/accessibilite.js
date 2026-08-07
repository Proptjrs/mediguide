/**
 * Lecture à voix haute des pages de MediGuide.
 *
 * Beaucoup de patients du district lisent mal le français écrit. Chaque écran
 * du questionnaire porte donc un bouton « Écouter » qui lit son contenu au
 * moyen de la synthèse vocale du navigateur : aucun fichier audio à
 * télécharger, aucun service extérieur, et la lecture reste possible hors
 * connexion.
 */
(function () {
    'use strict';

    const synthese = window.speechSynthesis;
    if (!synthese) return;                       // navigateur trop ancien : on s'abstient

    let voixFrancaise = null;

    function choisirVoix() {
        const voix = synthese.getVoices();
        voixFrancaise = voix.find(v => v.lang === 'fr-FR')
                     || voix.find(v => v.lang && v.lang.startsWith('fr'))
                     || null;
    }
    choisirVoix();
    synthese.addEventListener('voiceschanged', choisirVoix);

    /** Texte lisible d'un élément : titres, aides et libellés de choix. */
    function texteDe(element) {
        const morceaux = [];
        element.querySelectorAll('h1, h2, h3, .hint, label, .choice-grid .lbl, p')
            .forEach(n => {
                const t = (n.innerText || '').trim();
                if (t && !morceaux.includes(t)) morceaux.push(t);
            });
        return morceaux.join('. ');
    }

    function arreter(bouton) {
        synthese.cancel();
        document.querySelectorAll('.btn-voix[aria-pressed="true"]')
            .forEach(b => b.setAttribute('aria-pressed', 'false'));
        if (bouton) bouton.querySelector('.libelle').textContent = 'Écouter';
    }

    function lire(bouton, cible) {
        const enCours = bouton.getAttribute('aria-pressed') === 'true';
        arreter(bouton);
        if (enCours) return;                     // second clic : on interrompt

        const message = new SpeechSynthesisUtterance(texteDe(cible));
        message.lang = 'fr-FR';
        message.rate = 0.92;                     // un peu plus lent que la normale
        if (voixFrancaise) message.voice = voixFrancaise;
        message.onend = () => arreter(bouton);

        bouton.setAttribute('aria-pressed', 'true');
        bouton.querySelector('.libelle').textContent = 'Arrêter';
        synthese.speak(message);
    }

    document.addEventListener('click', (e) => {
        const bouton = e.target.closest('.btn-voix');
        if (!bouton) return;
        e.preventDefault();
        const cible = document.querySelector(bouton.dataset.lire || 'main');
        if (cible) lire(bouton, cible);
    });

    // Un changement d'étape du questionnaire ne doit pas laisser la voix
    // continuer à lire l'écran précédent.
    document.addEventListener('livewire:navigated', () => arreter(null));
    document.addEventListener('livewire:update', () => arreter(null));
    window.addEventListener('beforeunload', () => synthese.cancel());
})();
