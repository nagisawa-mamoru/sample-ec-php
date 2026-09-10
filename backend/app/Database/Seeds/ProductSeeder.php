<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $products = [
            ['name' => 'ワイヤレスマウス',       'price' => 2480,  'stock' => 50, 'category' => 'PCアクセサリ'],
            ['name' => 'メカニカルキーボード',   'price' => 8900,  'stock' => 30, 'category' => 'PCアクセサリ'],
            ['name' => '27インチ モニター',      'price' => 24800, 'stock' => 15, 'category' => 'PCアクセサリ'],
            ['name' => 'USB-C ハブ',             'price' => 3980,  'stock' => 40, 'category' => 'PCアクセサリ'],
            ['name' => 'ノイズキャンセリングヘッドホン', 'price' => 15800, 'stock' => 20, 'category' => 'オーディオ'],
            ['name' => 'Bluetoothスピーカー',    'price' => 6980,  'stock' => 25, 'category' => 'オーディオ'],
            ['name' => 'コーヒーメーカー',        'price' => 9800,  'stock' => 12, 'category' => '生活家電'],
            ['name' => '電気ケトル',              'price' => 3480,  'stock' => 35, 'category' => '生活家電'],
            ['name' => 'デスクライト',            'price' => 2980,  'stock' => 28, 'category' => 'オフィス用品'],
            ['name' => 'ノートPCスタンド',        'price' => 4280,  'stock' => 22, 'category' => 'オフィス用品'],
        ];

        foreach ($products as &$product) {
            $product['created_at'] = $now;
            $product['updated_at'] = $now;
        }

        $this->db->table('products')->insertBatch($products);
    }
}
