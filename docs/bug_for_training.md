# 【講師用】仕込みバグ カンニングペーパー

このファイルは研修講師向けです。受講者向けのコードやREADMEには一切記載していません。

## 仕込んだバグ

**バックエンドの在庫チェックロジックが不十分で、在庫数を超えた注文が成立してしまう不具合。**

### 該当箇所

`backend/app/Models/OrderModel.php` の `createOrder()` メソッド。

```php
foreach ($items as $item) {
    $product = $productModel->find((int) $item['product_id']);

    if ($product === null) {
        throw new \RuntimeException(...);
    }

    if ($product['stock'] < $item['quantity']) {
        throw new \RuntimeException("在庫が不足しています: {$product['name']}");
    }
    // ...
}
```

在庫チェックは「注文明細（`items`配列）の1行ごと」に、DBから毎回 `find()` で
取得した**その時点の**在庫数と比較している。しかし在庫の減算は全明細のチェックが
終わった後の**別ループ**でまとめて行われるため、同一リクエスト内で同じ商品を
複数の明細行に分けて注文すると、各行のチェック時点では在庫がまだ減算されておらず、
チェックをすり抜けられる。

### 再現手順

1. 商品ID=7（コーヒーメーカー、初期在庫12）を例にする。
2. 以下のように、同一商品を2行に分けて合計在庫を超える注文をPOSTする。

   ```bash
   curl -X POST http://localhost:8080/api/orders \
     -H "Content-Type: application/json" \
     -d '{"customer_id":1,"items":[{"product_id":7,"quantity":8},{"product_id":7,"quantity":8}]}'
   ```

3. 本来は合計16個 > 在庫12個のため注文が拒否されるべきだが、HTTP 201で成立してしまう。
4. 注文後に `GET /api/products/7` を確認すると、在庫が `-4` のように**負の値**になる。

単発リクエスト・同時実行なしで確定的に再現できる（並行リクエストによる
競合状態を利用したバグではない点に注意）。

### 想定される正しい修正方針

1. **同一注文内での数量集約**: リクエスト内の `items` を `product_id` ごとに
   事前に合計し、集約後の数量で在庫チェックを行う。
   ```php
   $quantitiesByProduct = [];
   foreach ($items as $item) {
       $quantitiesByProduct[$item['product_id']]
           = ($quantitiesByProduct[$item['product_id']] ?? 0) + $item['quantity'];
   }
   // 集約後の数量でチェック
   ```
2. **チェックと更新を同じロックの中で行う**: `SELECT ... FOR UPDATE` で商品行を
   ロックした上で在庫チェック・減算を行い、他リクエストとの競合（TOCTOU）にも
   耐えられるようにする（`db/triggers.sql` の `sp_create_order` ストアドプロシージャは
   この方式の実装例になっている）。
3. どちらか一方でも良いが、実務的には1と2の両方を組み合わせるのが望ましい
   （1だけでは複数リクエストの同時実行には対応できない）。

### 研修での使い方の例

- 受講者にAPIの正常系動作を確認させた後、上記の再現手順を提示し、
  「なぜ在庫チェックをすり抜けられるのか」をコードリーディングで特定させる。
- 修正後、`backend/tests/Feature/OrderApiTest.php`（テスト実装章で受講者が
  作成する想定。現時点ではリポジトリに含めていない）に「同一商品を複数行に
  分けた注文が拒否されること」を検証するテストケースを追加させると、
  テスト駆動での不具合修正の練習になる。
