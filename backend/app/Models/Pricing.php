<?php

namespace App\Models;

/**
 * Prix par defaut utilises quand aucun prix n'est renseigne.
 * Pour changer un tarif par defaut, modifiez les constantes ci-dessous.
 */
class Pricing
{
    /** Un mois represente 4 seances (sert a convertir un prix mensuel en prix par seance). */
    public const SESSIONS_PER_MONTH = 4;

    /** Prix d'une seance quand aucun prix n'est renseigne (meme valeur que le formulaire eleve). */
    public const DEFAULT_SESSION_PRICE = 15.0;

    /** Tarif mensuel de garderie par defaut (0 = aucun tarif par defaut). */
    public const DEFAULT_DAYCARE_MONTHLY_FEE = 0.0;

    /**
     * Prix d'une seance pour une inscription :
     *  1) price_per_session s'il est renseigne
     *  2) sinon price_per_month / 4
     *  3) sinon le tarif par defaut
     *
     * @return array{0: float, 1: string} [prix, source] avec source = session | monthly | default
     */
    public static function enrollmentSessionPrice($enrollment): array
    {
        $perSession = (float) ($enrollment->price_per_session ?? 0);
        if ($perSession > 0) {
            return [$perSession, 'session'];
        }

        $sessions = (int) ($enrollment->sessions_per_month ?? self::SESSIONS_PER_MONTH);
        if ($sessions <= 0) $sessions = self::SESSIONS_PER_MONTH;

        $perMonth = (float) ($enrollment->price_per_month ?? 0);
        if ($perMonth > 0) {
            return [round($perMonth / $sessions, 2), 'monthly'];
        }

        return [self::DEFAULT_SESSION_PRICE, 'default'];
    }

    /**
     * Prix moyen d'une seance pour plusieurs inscriptions (un enseignant qui donne
     * plusieurs matieres au meme eleve). Seuls les prix reellement renseignes comptent
     * dans la moyenne ; sinon on prend le tarif par defaut.
     *
     * @return array{0: float, 1: string} [prix, source]
     */
    public static function averageSessionPrice($enrollments): array
    {
        $prices  = [];
        $sources = [];

        foreach ($enrollments as $enrollment) {
            [$price, $source] = self::enrollmentSessionPrice($enrollment);
            if ($source !== 'default') {
                $prices[]  = $price;
                $sources[] = $source;
            }
        }

        if (count($prices) === 0) {
            return [self::DEFAULT_SESSION_PRICE, 'default'];
        }

        $source = in_array('session', $sources, true) ? 'session' : 'monthly';

        return [round(array_sum($prices) / count($prices), 2), $source];
    }
}