<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'product_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'change' => [
                'type'       => 'INT',
                'constraint' => 11,
                'comment'    => '在庫の増減数（マイナスは減算）',
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('stock_logs');
    }

    public function down()
    {
        $this->forge->dropTable('stock_logs');
    }
}
