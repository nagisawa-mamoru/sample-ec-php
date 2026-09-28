<?php

namespace App\Controllers\Api;

use App\Models\CustomerModel;

class CustomerController extends BaseApiController
{
    protected CustomerModel $customerModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
    }

    /**
     * GET /api/customers
     */
    public function index()
    {
        return $this->respond($this->customerModel->orderBy('name', 'ASC')->findAll());
    }
}
