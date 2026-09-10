<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['name', 'price', 'stock', 'category'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'     => 'required|max_length[255]',
        'price'    => 'required|decimal|greater_than_equal_to[0]',
        'stock'    => 'permit_empty|integer|greater_than_equal_to[0]',
        'category' => 'permit_empty|max_length[100]',
    ];
}
