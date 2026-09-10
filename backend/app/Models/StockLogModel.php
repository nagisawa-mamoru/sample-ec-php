<?php

namespace App\Models;

use CodeIgniter\Model;

class StockLogModel extends Model
{
    protected $table         = 'stock_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['product_id', 'change', 'reason'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
