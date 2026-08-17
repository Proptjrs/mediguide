<?php

namespace App\Services;

use App\Models\EchangeAssistant;
use App\Models\Medecin;
use App\Models\Specialite;
use App\Models\StructureMedicale;
use App\Models\User;

/**
 * L'assistant conversationnel de MediGuide.
 *
 * C'est un système expert : la question est ramenée à une intention par des
 * règles écrites, puis la réponse est composée à partir des données réelles de
 * la plateforme — spécialités, structures, créneaux, rendez-vous du patient.
 *
 * Ce choix n'est pas un pis-aller. Un modèle génératif inventerait des réponses
 * médicales sans qu'on puisse en rendre compte ; ici, chaque réponse se trace
 * jusqu'à la règle qui l'a produite, et un médecin peut relire ces règles. La
 * détection d'urgence, en particulier, ne doit dépendre d'aucune approximation.
 *
 * Un modèle génératif peut néanmoins être branché plus tard : il suffirait de
 * remplacer repondre() par un appel externe en conservant la garde d'urgence
 * ci-dessous, qui doit rester la première chose évaluée.
 */
class AssistantService
{
    /**
     * Signes qui doivent interrompre la conversation et renvoyer au SAMU.
     *
     * Ce sont des expressions régulières, et non des morceaux de phrase : « mal
     * à la poitrine », « douleur au thorax » et « j'ai mal dans la poitrine »
     * doivent tous déclencher, alors qu'aucun ne contient les mêmes mots collés.
     * Cette règle est la plus importante du service : elle passe avant tout, et
     * en cas de doute elle doit se déclencher plutôt que se taire.
     */
    private const SIGNES_VITAUX = [
        '/(douleur|mal|serre|oppress|brule)[^.!?]{0,25}(poitrine|thorax|coeur|torse)/',
        '/(poitrine|thorax)[^.!?]{0,20}(serre|comprime|brule|fait mal)/',
        // Les deux ordres : « je ne respire plus » et « je n'arrive plus à respirer ».
        '/(respire|respirer|souffle)[^.!?]{0,15}(plus|mal|difficile|difficilement)/',
        '/(plus|mal|peine|difficile|difficulte)[^.!?]{0,15}(a |à )?respirer/',
        '/(etouffe|suffoque|asphyxie|manque d.air|essouffl)/',
        '/(perd|perdu|perte)[^.!?]{0,15}connaissance/',
        '/(evanoui|inconscient|coma|convulsion|crise d.epilepsie)/',
        '/(saigne|saignement|hemorragie)[^.!?]{0,20}(beaucoup|abondant|arrete pas|important)/',
        '/(vomi|crache)[^.!?]{0,15}sang/',
        '/(paralys|ne bouge plus|parle plus|bouche de travers)/',
        '/(avc|infarctus|crise cardiaque|accident de la route|noyade|brulure grave)/',
        '/(fievre)[^.!?]{0,20}(4[01]|tres forte|convulsion)/',
        '/(bebe|nourrisson|enfant)[^.!?]{0,25}(convulse|ne respire|inconscient|ne reagit)/',
    ];

    /**
     * Intentions reconnues, dans l'ordre où elles sont essayées.
     * L'ordre compte : « rendez-vous urgent » doit être vu comme une urgence.
     */
    private const REGLES = [
        'salutation'   => ['bonjour', 'bonsoir', 'salut', 'bjr', 'coucou', 'asalam', 'nanga def'],
        'aide'         => ['aide', 'aidez', 'que peux-tu', 'que fais-tu', 'comment ca marche',
                           'comment ça marche', 'tu sers a quoi', 'menu'],
        'orientation'  => ['quel medecin', 'quel médecin', 'quel service', 'quelle specialite',
                           'quelle spécialité', 'ou aller', 'où aller', 'j ai mal', "j'ai mal",
                           'je souffre', 'douleur', 'fievre', 'fièvre', 'symptome', 'symptôme',
                           'malade', 'toux', 'vomis', 'diarrhee', 'diarrhée', 'brulure', 'brûlure'],
        'structure'    => ['hopital', 'hôpital', 'clinique', 'centre de sante', 'centre de santé',
                           'structure', 'proche', 'pres de moi', 'près de moi', 'adresse', 'ou est'],
        // « annuler » et « mes rendez-vous » se cherchent avant « rendez-vous »,
        // sans quoi toute phrase contenant ces mots retomberait sur la réservation.
        'annulation'   => ['annuler', 'annulation', 'supprimer mon rendez', 'reporter'],
        'mes_rdv'      => ['mes rendez', 'mon rendez', 'mes rdv', 'mon rdv', 'prochain rendez',
                           'quand est mon', 'j ai rendez', "j'ai rendez"],
        'rendez_vous'  => ['rendez-vous', 'rendez vous', 'rdv', 'reserver', 'réserver',
                           'prendre un rendez', 'consultation', 'creneau', 'créneau'],
        'horaires'     => ['horaire', 'heure', 'ouvert', 'ouverture', 'ferme', 'quand ouvre'],
        'tarif'        => ['prix', 'tarif', 'combien', 'cout', 'coût', 'payer', 'gratuit'],
        'compte'       => ['mot de passe', 'inscription', 'inscrire', 'compte', 'connexion',
                           'connecter', 'profil'],
        'confidentiel' => ['donnees', 'données', 'confidentiel', 'securite', 'sécurité',
                           'qui voit', 'prive', 'privé'],
        'remerciement' => ['merci', 'jerejef', 'au revoir', 'bye', 'a bientot', 'à bientôt'],
    ];

    public function __construct(private QuestionnaireService $questionnaire) {}

    /**
     * Répond à une question et conserve l'échange.
     *
     * @return array{intention:string, reponse:string, urgence:bool, pistes:array<string>}
     */
    public function repondre(string $question, ?User $utilisateur = null): array
    {
        $normalise = $this->normaliser($question);

        // La garde d'urgence passe avant toute autre règle : une phrase qui
        // décrit un signe vital ne doit jamais recevoir une réponse d'agenda.
        if ($this->signeVital($normalise)) {
            $reponse = $this->reponseUrgence();
            $this->journaliser($utilisateur, 'urgence', $question, $reponse, true);

            return ['intention' => 'urgence', 'reponse' => $reponse, 'urgence' => true,
                    'pistes' => ['Où est l\'hôpital le plus proche ?']];
        }

        $intention = $this->intention($normalise);
        [$reponse, $pistes] = $this->composer($intention, $normalise, $utilisateur);
        $this->journaliser($utilisateur, $intention, $question, $reponse, false);

        return ['intention' => $intention, 'reponse' => $reponse, 'urgence' => false, 'pistes' => $pistes];
    }

    /** Minuscules, sans accents : « Fièvre » et « fievre » doivent se valoir. */
    private function normaliser(string $t): string
    {
        $t = mb_strtolower(trim($t));
        $t = strtr($t, ['à' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                        'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u',
                        'ü' => 'u', 'ç' => 'c']);

        return preg_replace('/\s+/', ' ', $t);
    }

    private function signeVital(string $t): bool
    {
        foreach (self::SIGNES_VITAUX as $motif) {
            if (preg_match($motif, $t) === 1) {
                return true;
            }
        }

        return false;
    }

    private function intention(string $t): string
    {
        foreach (self::REGLES as $intention => $formules) {
            foreach ($formules as $formule) {
                if (str_contains($t, $this->normaliser($formule))) {
                    return $intention;
                }
            }
        }

        // Aucune formule reconnue, mais la phrase nomme tout de même un trouble
        // (« mon enfant tousse », « j'ai un bouton ») : c'est une orientation.
        // Sans ce rattrapage, il faudrait énumérer ici tous les mots de la
        // table des difficultés, et les deux listes finiraient par diverger.
        if ($this->difficulte($t) !== 'autre') {
            return 'orientation';
        }

        return 'inconnue';
    }

    /** @return array{0:string, 1:array<string>} la réponse, puis les questions suggérées */
    private function composer(string $intention, string $t, ?User $u): array
    {
        $prenom = $u?->prenom ? ' ' . $u->prenom : '';

        return match ($intention) {
            'salutation' => ["Bonjour{$prenom}. Je suis l'assistant de MediGuide. "
                . "Décrivez-moi ce que vous ressentez, et je vous dirai vers quel service vous adresser.",
                ["J'ai mal au ventre", 'Quel hôpital est près de moi ?', 'Comment prendre un rendez-vous ?']],

            'aide' => ["Je peux vous orienter vers la bonne spécialité à partir de ce que vous ressentez, "
                . "vous indiquer les structures proches, vous expliquer comment réserver un créneau, "
                . "et vous rappeler vos rendez-vous. Je ne pose aucun diagnostic : seul un médecin le fait.",
                ["J'ai de la fièvre", 'Mes rendez-vous', 'Mes données sont-elles protégées ?']],

            'orientation' => $this->orienter($t),

            'structure' => $this->structures(),

            'rendez_vous' => ["Pour réserver, répondez d'abord au questionnaire d'orientation : cinq étapes, "
                . "deux minutes. Il détermine la spécialité, puis vous propose les structures proches et "
                . "les créneaux libres. Vous réservez d'un clic et recevez une confirmation.",
                ['Commencer le questionnaire', 'Quel hôpital est près de moi ?']],

            'mes_rdv' => $this->mesRendezVous($u),

            'annulation' => ["Un rendez-vous s'annule depuis votre espace, sur la ligne du rendez-vous. "
                . "Le créneau libéré redevient aussitôt disponible pour un autre patient.",
                ['Mes rendez-vous']],

            'horaires' => ["Les consultations se tiennent du lundi au vendredi, aux heures ouvrables. "
                . "Chaque médecin ouvre ses propres créneaux : le calendrier affiche ceux qui restent libres. "
                . "Les urgences, elles, sont ouvertes en permanence.",
                ['Comment prendre un rendez-vous ?']],

            'tarif' => ["MediGuide est gratuit : l'orientation et la réservation ne coûtent rien. "
                . "Le tarif de la consultation reste celui de la structure qui vous reçoit.",
                ['Quel hôpital est près de moi ?']],

            'compte' => ["L'accès demande un compte : c'est ce qui protège vos réponses de santé. "
                . "L'inscription prend une minute et l'adresse électronique est confirmée par un lien. "
                . "En cas d'oubli, la page de connexion propose de réinitialiser le mot de passe.",
                ['Mes données sont-elles protégées ?']],

            'confidentiel' => ["Vos renseignements de santé sont chiffrés avant d'être enregistrés : "
                . "une lecture directe de la base ne donne rien de lisible. Chaque rôle ne voit que ce qui "
                . "le concerne, et aucune page consultée après connexion n'est conservée sur l'appareil.",
                ['Comment prendre un rendez-vous ?']],

            'remerciement' => ["Je vous en prie. Prenez soin de vous.", []],

            default => ["Je n'ai pas compris. Dites-moi plutôt ce que vous ressentez — par exemple "
                . "« j'ai mal au ventre » ou « j'ai de la fièvre » — et je vous orienterai. "
                . "Vous pouvez aussi me demander l'hôpital le plus proche.",
                ["J'ai mal à la tête", 'Quel hôpital est près de moi ?', 'Que peux-tu faire ?']],
        };
    }

    /** Oriente à partir de la phrase, en réutilisant l'arbre du questionnaire. */
    private function orienter(string $t): array
    {
        $difficulte = $this->difficulte($t);
        $zone = $this->zone($t);
        $specialite = $this->questionnaire->determineSpecialty($difficulte, $zone, null);

        $existe = Specialite::where('nom', $specialite)->exists();
        $suite = $existe
            ? "Des médecins de cette spécialité exercent dans le réseau : le questionnaire vous indiquera "
              . "lesquels sont les plus proches de vous, et leurs créneaux libres."
            : "Cette spécialité n'est pas encore représentée dans le réseau : le questionnaire vous "
              . "orientera vers la médecine générale, qui vous adressera ensuite au bon service.";

        return ["D'après ce que vous décrivez, la spécialité qui correspond est la <b>{$specialite}</b>. "
            . $suite . " Je ne pose aucun diagnostic : c'est une orientation, que le médecin confirmera.",
            ['Commencer le questionnaire', 'Quel hôpital est près de moi ?']];
    }

    /** Reconnaît la difficulté principale dans la phrase. */
    private function difficulte(string $t): string
    {
        $table = [
            'fievre' => ['fievre', 'temperature', 'chaud', 'frisson'],
            'respiration' => ['respir', 'souffle', 'asthme', 'toux'],
            'digestif' => ['vomi', 'diarrhee', 'nausee', 'estomac', 'digest', 'constipation'],
            'peau' => ['peau', 'bouton', 'demangeaison', 'eruption', 'plaie'],
            'vision' => ['oeil', 'yeux', 'vue', 'vision', 'voir'],
            'orl' => ['oreille', 'gorge', 'nez', 'sinus', 'entend'],
            'grossesse' => ['enceinte', 'grossesse', 'accouch', 'regles'],
            'enfant' => ['mon enfant', 'mon bebe', 'ma fille', 'mon fils', 'nourrisson'],
            'mental' => ['angoisse', 'depression', 'stress', 'anxiete', 'dors pas', 'insomnie'],
            'dents' => ['dent', 'gencive', 'carie'],
            'articulations' => ['articulation', 'genou', 'rhumatisme', 'arthrose'],
            'hormones' => ['diabete', 'thyroide', 'sucre dans le sang'],
            'douleur' => ['mal', 'douleur', 'souffre'],
        ];
        foreach ($table as $difficulte => $mots) {
            foreach ($mots as $mot) {
                if (str_contains($t, $mot)) {
                    return $difficulte;
                }
            }
        }

        return 'autre';
    }

    /** Reconnaît la zone du corps citée. */
    private function zone(string $t): ?string
    {
        $table = [
            'poitrine' => ['poitrine', 'thorax', 'coeur', 'sein'],
            'tete' => ['tete', 'crane', 'migraine', 'cerveau'],
            'ventre' => ['ventre', 'abdomen', 'estomac', 'intestin'],
            'bassin' => ['bassin', 'urine', 'vessie', 'rein'],
            'bras' => ['bras', 'main', 'epaule', 'coude', 'poignet'],
            'jambes' => ['jambe', 'pied', 'genou', 'cheville', 'cuisse'],
            'gorge' => ['gorge', 'cou', 'avaler'],
        ];
        foreach ($table as $zone => $mots) {
            foreach ($mots as $mot) {
                if (str_contains($t, $mot)) {
                    return $zone;
                }
            }
        }

        return null;
    }

    private function structures(): array
    {
        $n = StructureMedicale::count();
        $exemples = StructureMedicale::orderBy('nom')->limit(3)->pluck('nom')->implode(', ');
        $texte = $n > 0
            ? "Le réseau compte {$n} structures, dont : {$exemples}. Le questionnaire les classe par "
              . "distance réelle depuis votre position, avec le temps de trajet à pied et en voiture."
            : "Le réseau ne contient encore aucune structure enregistrée.";

        return [$texte, ['Commencer le questionnaire', 'Comment prendre un rendez-vous ?']];
    }

    private function mesRendezVous(?User $u): array
    {
        if (! $u?->patient) {
            return ["Connectez-vous à votre compte patient pour que je retrouve vos rendez-vous.",
                    ['Comment prendre un rendez-vous ?']];
        }
        $prochain = $u->patient->rendezVous()
            ->where('date_heure', '>=', now())->orderBy('date_heure')->with('medecin.utilisateur')->first();

        if (! $prochain) {
            return ["Vous n'avez aucun rendez-vous à venir. Le questionnaire vous en propose un en deux minutes.",
                    ['Commencer le questionnaire']];
        }
        $medecin = $prochain->medecin?->utilisateur?->fullName() ?? 'votre médecin';

        return ["Votre prochain rendez-vous est le "
            . $prochain->date_heure->translatedFormat('l j F Y \à H\hi') . ", avec {$medecin}.",
            ['Annuler un rendez-vous']];
    }

    private function reponseUrgence(): string
    {
        return "Ce que vous décrivez peut relever de l'urgence. <b>N'attendez pas un rendez-vous.</b> "
            . "Appelez le <b>1515</b> (SAMU) ou rendez-vous immédiatement aux urgences les plus proches. "
            . "Je ne peux pas vous proposer de créneau dans cette situation.";
    }

    private function journaliser(?User $u, string $intention, string $q, string $r, bool $urgence): void
    {
        EchangeAssistant::create([
            'utilisateur_id' => $u?->id,
            'intention' => $intention,
            'question' => mb_substr($q, 0, 500),
            'reponse' => $r,
            'urgence_detectee' => $urgence,
        ]);
    }
}
