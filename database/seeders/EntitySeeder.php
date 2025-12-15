<?php
// database/seeders/EntitySeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entity;

class EntitySeeder extends Seeder
{
    public function run(): void
    {
        Entity::create([
            'entity_name' => 'VOLTRONIX CONTRACTING LLC',
            'entity_description' => 'Main contracting entity',
            'status' => 'active',
        ]);

        Entity::create([
            'entity_name' => 'VOLTRONIX SWITCHGEAR LLC',
            'entity_description' => 'Switchgear manufacturing and trading entity',
            'status' => 'active',
        ]);
    }
}
