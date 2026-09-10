-- =====================================================================
-- PL/pgSQL デモ用: トリガー関数 & ストアドプロシージャ
--
-- このファイルはアプリケーション（CodeIgniter 4 API）とは独立した、
-- DBレイヤーでの業務ロジック実装のデモ用です。
-- APIの注文作成（POST /api/orders）は既にPHP側で在庫チェック・
-- 在庫減算・stock_logsへの記録を行っているため、このトリガーを
-- 適用した状態でAPI経由の注文を行うと、在庫が二重に減算されます。
-- 「アプリ層とDB層のどちらに業務ロジックを置くべきか」を議論する
-- 教材として、あえて独立した実装にしています。
--
-- 適用方法:
--   docker compose exec -T db psql -U postgres -d sample_ec -f - < db/triggers.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. トリガー関数: order_itemsへのINSERTをきっかけに
--    products.stockを自動的に減算し、stock_logsに記録する
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_decrement_stock_on_order_item()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE products
    SET stock = stock - NEW.quantity,
        updated_at = NOW()
    WHERE id = NEW.product_id;

    INSERT INTO stock_logs (product_id, change, reason, created_at)
    VALUES (NEW.product_id, -NEW.quantity, 'order_item_trigger', NOW());

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_decrement_stock_on_order_item ON order_items;

CREATE TRIGGER trg_decrement_stock_on_order_item
    AFTER INSERT ON order_items
    FOR EACH ROW
    EXECUTE FUNCTION fn_decrement_stock_on_order_item();

-- ---------------------------------------------------------------------
-- 2. ストアドプロシージャ: 複数の注文明細をまとめて登録する
--    在庫チェック（行ロック付き）を行った上で、注文・注文明細を作成する。
--
-- 呼び出し例:
--   CALL sp_create_order(
--       1,
--       '[{"product_id": 1, "quantity": 2}, {"product_id": 3, "quantity": 1}]'::jsonb
--   );
-- ---------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE sp_create_order(
    p_customer_id INT,
    p_items JSONB
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_order_id   INT;
    v_item       JSONB;
    v_product_id INT;
    v_quantity   INT;
    v_unit_price NUMERIC(10, 2);
    v_stock      INT;
    v_total      NUMERIC(10, 2) := 0;
BEGIN
    -- 事前チェック: 明細ごとに行ロックを取りながら在庫を確認する
    FOR v_item IN SELECT * FROM jsonb_array_elements(p_items)
    LOOP
        v_product_id := (v_item ->> 'product_id')::INT;
        v_quantity   := (v_item ->> 'quantity')::INT;

        SELECT stock INTO v_stock FROM products WHERE id = v_product_id FOR UPDATE;

        IF NOT FOUND THEN
            RAISE EXCEPTION '商品が見つかりません（ID: %）', v_product_id;
        END IF;

        IF v_stock < v_quantity THEN
            RAISE EXCEPTION '在庫が不足しています（商品ID: %, 在庫: %, 注文数: %）',
                v_product_id, v_stock, v_quantity;
        END IF;
    END LOOP;

    -- 注文ヘッダを作成
    INSERT INTO orders (customer_id, status, total_amount, created_at, updated_at)
    VALUES (p_customer_id, 'confirmed', 0, NOW(), NOW())
    RETURNING id INTO v_order_id;

    -- 注文明細を登録する。
    -- order_itemsへのINSERTにより、上記トリガーが在庫減算とstock_logs記録を行う。
    FOR v_item IN SELECT * FROM jsonb_array_elements(p_items)
    LOOP
        v_product_id := (v_item ->> 'product_id')::INT;
        v_quantity   := (v_item ->> 'quantity')::INT;

        SELECT price INTO v_unit_price FROM products WHERE id = v_product_id;

        INSERT INTO order_items (order_id, product_id, quantity, unit_price)
        VALUES (v_order_id, v_product_id, v_quantity, v_unit_price);

        v_total := v_total + (v_unit_price * v_quantity);
    END LOOP;

    UPDATE orders SET total_amount = v_total, updated_at = NOW() WHERE id = v_order_id;
END;
$$;
