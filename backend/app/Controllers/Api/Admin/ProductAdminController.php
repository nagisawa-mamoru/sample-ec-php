<?php

namespace App\Controllers\Api\Admin;

use App\Controllers\Api\BaseApiController;
use App\Models\ProductModel;
use App\Models\StockLogModel;

class ProductAdminController extends BaseApiController
{
    protected ProductModel $productModel;
    protected StockLogModel $stockLogModel;

    public function __construct()
    {
        $this->productModel  = new ProductModel();
        $this->stockLogModel = new StockLogModel();
    }

    /**
     * POST /api/admin/products/{id}/receive-stock
     *
     * 仕入れによる在庫増加を登録する。
     *
     * リクエストボディ例: { "quantity": 20 }
     */
    public function receiveStock(int $id)
    {
        $product = $this->productModel->find($id);

        if ($product === null) {
            return $this->failNotFound("商品が見つかりません（ID: {$id}）");
        }

        $rules = [
            'quantity' => 'required|integer|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data     = $this->request->getJSON(true);
        $quantity = (int) $data['quantity'];

        // 在庫の加算と在庫ログの記録を一体で行うためトランザクションで保護する
        $db = db_connect();
        $db->transBegin();

        $this->productModel
            ->skipValidation(true)
            ->set('stock', 'stock + ' . $quantity, false)
            ->where('id', $id)
            ->update();

        $this->stockLogModel->insert([
            'product_id' => $id,
            'change'     => $quantity,
            'reason'     => 'purchase',
        ]);

        if ($db->transStatus() === false) {
            $db->transRollback();

            return $this->failServerError('在庫の入荷登録に失敗しました。');
        }

        $product = $this->productModel->withDiscount($this->productModel->find($id));

        return $this->respond($this->productModel->withStockTurnover($product));
    }

    /**
     * PATCH /api/admin/products/{id}/discount
     *
     * 商品の割引率(%)を設定する。0または未指定で割引解除。
     *
     * リクエストボディ例: { "discount_percentage": 15 }
     */
    public function updateDiscount(int $id)
    {
        $product = $this->productModel->find($id);

        if ($product === null) {
            return $this->failNotFound("商品が見つかりません（ID: {$id}）");
        }

        $rules = [
            'discount_percentage' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data                = $this->request->getJSON(true);
        $discountPercentage  = $data['discount_percentage'] ?? null;

        $this->productModel->update($id, [
            'discount_percentage' => ($discountPercentage === '' || $discountPercentage === null)
                ? null
                : (float) $discountPercentage,
        ]);

        $product = $this->productModel->withDiscount($this->productModel->find($id));

        return $this->respond($this->productModel->withStockTurnover($product));
    }
}
