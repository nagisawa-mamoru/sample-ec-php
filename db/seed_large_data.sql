-- =====================================================================
-- 検索パフォーマンス改善デモ用: stock_logsに10万件のダミーデータを投入する。
--
-- 自動実行はされません。研修のPL/pgSQLデモ章で手動実行してください。
--   docker compose exec -T db psql -U postgres -d sample_ec -f - < db/seed_large_data.sql
--
-- 商品ID 1〜10（デフォルトのProductSeederで投入される件数）を前提にしています。
-- 商品を追加・変更した場合は、下記の "1 + floor(random() * 10)" の 10 を
-- 実際の商品数に合わせて調整してください。
-- =====================================================================

INSERT INTO stock_logs (product_id, change, reason, created_at)
SELECT
    (1 + floor(random() * 10))::INT AS product_id,
    ((CASE WHEN random() < 0.5 THEN -1 ELSE 1 END) * (1 + floor(random() * 20)))::INT AS change,
    (ARRAY['order', 'restock', 'adjustment', 'return'])[1 + floor(random() * 4)::INT] AS reason,
    NOW() - (random() * INTERVAL '365 days') AS created_at
FROM generate_series(1, 100000);
