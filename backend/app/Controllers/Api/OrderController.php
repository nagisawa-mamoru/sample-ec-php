<?php

namespace App\Controllers\Api;

use App\Models\OrderModel;

class OrderController extends BaseApiController
{
    protected OrderModel $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    /**
     * GET /api/orders
     */
    public function index()
    {
        $orders = $this->orderModel->listWithCustomer();

        return $this->respond($orders);
    }

    /**
     * GET /api/orders/{id}
     */
    public function show(int $id)
    {
        $order = $this->orderModel->findWithItems($id);

        if ($order === null) {
            return $this->failNotFound("注文が見つかりません（ID: {$id}）");
        }

        return $this->respond($order);
    }

    /**
     * POST /api/orders
     *
     * リクエストボディ例:
     * {
     *   "customer_id": 1,
     *   "items": [
     *     { "product_id": 1, "quantity": 2 },
     *     { "product_id": 3, "quantity": 1 }
     *   ]
     * }
     */
    public function create()
    {
        $rules = [
            'customer_id'        => 'required|integer|is_not_unique[customers.id]',
            'items'              => 'required',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity'   => 'required|integer|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true);

        try {
            $order = $this->orderModel->createOrder((int) $data['customer_id'], $data['items']);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->respondCreated($order);
    }
}
