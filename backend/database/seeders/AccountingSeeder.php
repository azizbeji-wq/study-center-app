<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    /**
     * Seeder de comptabilité : neutralisé pour ne pas injecter de fausses dépenses statiques.
     * Les dépenses et clôtures sont saisies directement par le directeur via l'interface.
     */
    public function run(): void
    {
        // Aucune fausse donnée statique injectée.
    }
}
