<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDiscountPercentageToProductsTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('products', [
            'discount_percentage' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'default'    => null,
                'after'      => 'stock',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('products', 'discount_percentage');
    }
}
