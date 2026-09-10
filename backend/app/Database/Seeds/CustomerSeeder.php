<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $customers = [
            ['name' => '山田 太郎', 'email' => 'yamada@example.com'],
            ['name' => '佐藤 花子', 'email' => 'sato@example.com'],
            ['name' => '鈴木 一郎', 'email' => 'suzuki@example.com'],
        ];

        foreach ($customers as &$customer) {
            $customer['created_at'] = $now;
            $customer['updated_at'] = $now;
        }

        $this->db->table('customers')->insertBatch($customers);
    }
}
