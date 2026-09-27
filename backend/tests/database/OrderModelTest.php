<?php

use App\Models\OrderModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class OrderModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';

    private int $customerId;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();

        $now = date('Y-m-d H:i:s');

        $this->db->table('customers')->insert([
            'name'       => 'テスト 顧客',
            'email'      => 'test@example.com',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->customerId = (int) $this->db->insertID();

        $this->db->table('products')->insert([
            'name'       => 'テスト商品',
            'price'      => 1000,
            'stock'      => 10,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->productId = (int) $this->db->insertID();
    }

    public function testCreateOrderDecrementsStock(): void
    {
        $order = (new OrderModel())->createOrder($this->customerId, [
            ['product_id' => $this->productId, 'quantity' => 3],
        ]);

        $this->assertCount(1, $order['items']);
        $this->seeInDatabase('products', ['id' => $this->productId, 'stock' => 7]);
    }

    public function testCreateOrderAllowsSameProductSplitWithinStock(): void
    {
        $order = (new OrderModel())->createOrder($this->customerId, [
            ['product_id' => $this->productId, 'quantity' => 4],
            ['product_id' => $this->productId, 'quantity' => 6],
        ]);

        $this->assertCount(2, $order['items']);
        $this->seeInDatabase('products', ['id' => $this->productId, 'stock' => 0]);
    }

    public function testCreateOrderRejectsSameProductSplitExceedingStock(): void
    {
        $exception = null;

        try {
            (new OrderModel())->createOrder($this->customerId, [
                ['product_id' => $this->productId, 'quantity' => 8],
                ['product_id' => $this->productId, 'quantity' => 8],
            ]);
        } catch (RuntimeException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, '在庫不足の例外が発生しませんでした');
        $this->assertStringContainsString('在庫が不足しています', $exception->getMessage());
        $this->seeInDatabase('products', ['id' => $this->productId, 'stock' => 10]);
        $this->assertSame(0, $this->db->table('orders')->countAllResults());
        $this->assertSame(0, $this->db->table('stock_logs')->countAllResults());
    }
}
