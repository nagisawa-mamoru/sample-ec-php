<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['name', 'price', 'stock', 'category', 'discount_percentage'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'                => 'required|max_length[255]',
        'price'               => 'required|decimal|greater_than_equal_to[0]',
        'stock'               => 'permit_empty|integer|greater_than_equal_to[0]',
        'category'            => 'permit_empty|max_length[100]',
        'discount_percentage' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
    ];

    /**
     * 割引適用後の価格（discounted_price）を付与する。
     * 割引が設定されていない場合はnullを返す。
     */
    public function withDiscount(array $product): array
    {
        $product['discounted_price'] = null;

        if (! empty($product['discount_percentage']) && (float) $product['discount_percentage'] > 0) {
            $product['discounted_price'] = round(
                (float) $product['price'] * (1 - (float) $product['discount_percentage'] / 100),
                2
            );
        }

        return $product;
    }

    /**
     * 割引が設定されている場合は割引後価格を、無ければ通常価格を返す。
     * 注文金額の計算など、実際に使う単価を1箇所で決めるためのヘルパー。
     */
    public function effectivePrice(array $product): float
    {
        if (! empty($product['discount_percentage']) && (float) $product['discount_percentage'] > 0) {
            return round((float) $product['price'] * (1 - (float) $product['discount_percentage'] / 100), 2);
        }

        return (float) $product['price'];
    }

    /**
     * 在庫回転率（％） = 累計販売数 ÷ 現在庫 × 100 を付与する。
     * 数値が大きいほど、現在庫に対してよく売れている商品ということになり、
     * 発注タイミングの目安として管理画面での表示を想定している。
     */
    public function withStockTurnover(array $product): array
    {
        $soldRow = $this->db->table('stock_logs')
            ->selectSum('change')
            ->where('product_id', $product['id'])
            ->where('reason', 'order')
            ->get()
            ->getRowArray();

        $totalSold = abs((int) ($soldRow['change'] ?? 0));

        $product['stock_turnover_rate'] = round($totalSold / $product['stock'] * 100, 1);

        return $product;
    }
}
