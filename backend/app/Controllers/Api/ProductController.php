<?php

namespace App\Controllers\Api;

use App\Models\ProductModel;

class ProductController extends BaseApiController
{
    protected ProductModel $productModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
    }

    /**
     * GET /api/products
     *
     * 商品名の部分一致検索・カテゴリ絞り込みは未実装。
     * 将来の拡張時は $this->request->getGet() で条件を受け取り、
     * $this->productModel->like()/where() をチェーンする想定。
     */
    public function index()
    {
        $products = $this->productModel->orderBy('id', 'ASC')->findAll();
        $products = array_map(function (array $product) {
            $product = $this->productModel->withDiscount($product);

            return $this->productModel->withStockTurnover($product);
        }, $products);

        return $this->respond($products);
    }

    /**
     * GET /api/products/{id}
     */
    public function show(int $id)
    {
        $product = $this->productModel->find($id);

        if ($product === null) {
            return $this->failNotFound("商品が見つかりません（ID: {$id}）");
        }

        $product = $this->productModel->withDiscount($product);
        $product = $this->productModel->withStockTurnover($product);

        return $this->respond($product);
    }
}
