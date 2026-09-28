<?php

namespace App\Controllers\Api\Admin;

use App\Controllers\Api\BaseApiController;
use App\Models\CustomerModel;

class CustomerAdminController extends BaseApiController
{
    protected CustomerModel $customerModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
    }

    /**
     * POST /api/admin/customers
     *
     * リクエストボディ例: { "name": "山田 太郎", "email": "yamada@example.com" }
     */
    public function create()
    {
        $rules = [
            'name'  => 'required|max_length[255]',
            'email' => 'required|valid_email|max_length[255]|is_unique[customers.email]',
        ];

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true);

        $id = $this->customerModel->insert([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        return $this->respondCreated($this->customerModel->find($id));
    }
}
