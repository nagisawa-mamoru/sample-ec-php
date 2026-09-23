<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table         = 'orders';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['customer_id', 'status', 'total_amount'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'customer_id'  => 'required|integer|is_not_unique[customers.id]',
        'status'       => 'permit_empty|in_list[pending,confirmed,cancelled]',
        'total_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
    ];

    /**
     * 注文を明細一覧付きで取得する。
     */
    public function findWithItems(int $id): ?array
    {
        $order = $this->find($id);

        if ($order === null) {
            return null;
        }

        $order['items'] = $this->db->table('order_items')
            ->select('order_items.*, products.name as product_name')
            ->join('products', 'products.id = order_items.product_id')
            ->where('order_id', $id)
            ->get()
            ->getResultArray();

        return $order;
    }

    /**
     * 注文一覧を顧客名付きで取得する。
     */
    public function listWithCustomer(): array
    {
        return $this->select('orders.*, customers.name as customer_name')
            ->join('customers', 'customers.id = orders.customer_id')
            ->orderBy('orders.id', 'DESC')
            ->findAll();
    }

    /**
     * 複数商品をまとめて注文する。
     *
     * 在庫チェック後、注文・注文明細を登録し、商品の在庫を減算する。
     * 一連の処理はトランザクションで保護する。
     *
     * @param array<int, array{product_id: int, quantity: int}> $items
     *
     * @throws \RuntimeException 商品が存在しない、または在庫が不足している場合
     */
    public function createOrder(int $customerId, array $items): array
    {
        $productModel   = new ProductModel();
        $orderItemModel = new OrderItemModel();
        $stockLogModel  = new StockLogModel();

        $this->db->transStart();

        $totalAmount    = 0;
        $orderItemsData = [];

        foreach ($items as $item) {
            $product = $productModel->find((int) $item['product_id']);

            if ($product === null) {
                throw new \RuntimeException("商品が見つかりません（ID: {$item['product_id']}）");
            }

            if ($product['stock'] < $item['quantity']) {
                throw new \RuntimeException("在庫が不足しています: {$product['name']}");
            }

            $unitPrice    = $productModel->effectivePrice($product);
            $totalAmount += $unitPrice * $item['quantity'];

            $orderItemsData[] = [
                'product_id' => $product['id'],
                'name'       => $product['name'],
                'quantity'   => $item['quantity'],
                'unit_price' => $unitPrice,
            ];
        }

        $orderId = $this->insert([
            'customer_id'  => $customerId,
            'status'       => 'confirmed',
            'total_amount' => $totalAmount,
        ]);

        foreach ($orderItemsData as $itemData) {
            $orderItemModel->insert([
                'order_id'   => $orderId,
                'product_id' => $itemData['product_id'],
                'quantity'   => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
            ]);

            $productModel->skipValidation(true)
                ->set('stock', 'stock - ' . (int) $itemData['quantity'], false)
                ->where('id', $itemData['product_id'])
                ->update();

            $stockLogModel->insert([
                'product_id' => $itemData['product_id'],
                'change'     => -$itemData['quantity'],
                'reason'     => 'order',
            ]);
        }

        $this->db->transComplete();

        return $this->findWithItems($orderId);
    }
}
