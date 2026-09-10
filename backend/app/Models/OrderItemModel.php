<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table         = 'order_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['order_id', 'product_id', 'quantity', 'unit_price'];

    protected $useTimestamps = false;

    protected $validationRules = [
        'order_id'   => 'required|integer',
        'product_id' => 'required|integer|is_not_unique[products.id]',
        'quantity'   => 'required|integer|greater_than[0]',
        'unit_price' => 'required|decimal|greater_than_equal_to[0]',
    ];
}
