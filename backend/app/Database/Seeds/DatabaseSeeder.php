<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(ProductSeeder::class);
        $this->call(CustomerSeeder::class);
    }
}
