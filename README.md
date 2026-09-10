# sample-ec-php

Claude Code研修用のサンプルリポジトリです。小規模ECサイトの管理システム
（商品管理・注文管理・在庫管理）を題材に、「注文すると在庫が減る」という
業務ロジックを中心に構成しています。

## 構成図

```
┌────────────────┐        ┌──────────────────────┐
│   ブラウザ      │        │        Nginx          │  :8080
│ (Vue3 SPA)      │──────▶│  /api/*  → PHP-FPM     │
└────────────────┘        │  /*      → Vite(5173)  │
                           └──────────┬────────────┘
                                      │
                    ┌─────────────────┼─────────────────┐
                    ▼                                    ▼
          ┌───────────────────┐              ┌───────────────────┐
          │  frontend (Vite)   │              │  php (CodeIgniter4) │
          │  Vue3 / Pinia /     │              │  REST API            │
          │  vue-router         │              │  PHP 8.3 / PHP-FPM   │
          └───────────────────┘              └──────────┬──────────┘
                                                          │
                                                          ▼
                                              ┌───────────────────┐
                                              │   db (PostgreSQL16) │
                                              └───────────────────┘
```

- **frontend**: Vue 3 (Composition API) + Vite。商品一覧・詳細、カート、注文一覧・詳細画面を提供するSPA。
- **php**: CodeIgniter 4によるREST API。JSONを返却し、画面描画は行わない。
- **nginx**: `/api/*` をPHP-FPMへ、それ以外をVite dev serverへ振り分けるリバースプロキシ。
- **db**: PostgreSQL 16。

## ディレクトリ構成

```
project/
├── docker-compose.yml
├── docker/                 ← Dockerfile / nginx設定
├── backend/                ← CodeIgniter 4 (REST API)
├── frontend/                ← Vue 3 (SPA)
├── db/                      ← トリガー・ストアドプロシージャ・大量データ投入用SQL
└── docs/                    ← 補助ドキュメント
```

## セットアップ

### 1. 環境変数ファイルの準備

各ディレクトリで `.env.example` をコピーして `.env` として使ってください。

```bash
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
```

`backend/.env` にはDB接続情報とダミーのAPIキー（`app.externalApiKey`）が含まれています。
`.env` はどちらのディレクトリでも `.gitignore` により追跡対象外です。実際の秘匿情報は
`.env.example` のようにリポジトリへコミットしないでください。

### 2. Docker Composeで起動

```bash
docker compose build
docker compose up -d
```

起動するコンテナ:

| サービス | 役割 | ポート |
|---|---|---|
| `nginx` | リバースプロキシ | http://localhost:8080 |
| `php` | CodeIgniter 4 (PHP-FPM) | (nginx経由) |
| `frontend` | Vite dev server | http://localhost:5173（nginx経由でも http://localhost:8080 から到達可） |
| `db` | PostgreSQL 16 | localhost:5432 |

### 3. バックエンド: マイグレーション・シーディング

```bash
docker compose exec php php spark migrate
docker compose exec php php spark db:seed DatabaseSeeder
```

- `php spark migrate:status` で適用状況を確認できます。
- `php spark migrate:refresh` でDBを作り直せます（データは消えます）。

### 4. フロントエンド: 依存インストール・起動

Docker Compose起動時に `frontend` コンテナが自動で `npm run dev` を実行しますが、
ローカルで直接操作したい場合は以下を利用してください。

```bash
cd frontend
npm install
npm run dev
```

ブラウザで http://localhost:8080 （nginx経由）または http://localhost:5173
（Vite dev serverに直接）を開いてください。

## テストの実行方法

アプリ固有のテストコードは、研修のテスト実装章で受講者が追加する想定のため
未実装です。テストフレームワーク自体は導入済みなので、以下のコマンドで
すぐにテストを書き始められます。

### バックエンド（PHPUnit）

初回のみ、テスト用DB（`sample_ec_test`）を作成してください。

```bash
docker compose exec db psql -U postgres -c "CREATE DATABASE sample_ec_test;"
```

```bash
docker compose exec php vendor/bin/phpunit
```

CodeIgniter 4標準の`CIUnitTestCase` / `FeatureTestTrait` / `DatabaseTestTrait`が
利用できます。`backend/tests/` 配下にテストクラスを追加してください
（`php spark make:test` でひな形を生成できます）。

### フロントエンド（Vitest）

```bash
cd frontend
npm run test
```

`frontend/vite.config.js` にVitestの設定（`jsdom`環境）を用意済みです。
`frontend/tests/` 配下に `*.spec.js` を追加してください。
（テストファイルが1つもない状態で `npm run test` を実行すると
"No test files found" で終了コード1になりますが、これは想定通りの挙動です。）

## APIエンドポイント一覧

| Method | Path | 概要 |
|---|---|---|
| GET | `/api/products` | 商品一覧取得 |
| GET | `/api/products/{id}` | 商品詳細取得 |
| GET | `/api/orders` | 注文一覧取得 |
| GET | `/api/orders/{id}` | 注文詳細取得（明細付き） |
| POST | `/api/orders` | 注文作成（複数商品まとめて注文可） |

`POST /api/orders` のリクエストボディ例:

```json
{
  "customer_id": 1,
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 3, "quantity": 1 }
  ]
}
```

CORSは `backend/app/Config/Cors.php` で許可オリジンを管理しています
（デフォルトではVite dev serverの `http://localhost:5173` を許可）。

## PL/pgSQL（トリガー・ストアドプロシージャ）

`db/triggers.sql` に、注文明細(`order_items`)へのINSERTをきっかけに
`products.stock` を自動減算し `stock_logs` に記録するトリガー関数と、
複数明細をまとめて登録する（在庫チェック付きの）ストアドプロシージャ
`sp_create_order` を用意しています。

これはAPI（PHP側）の業務ロジックとは独立したデモ用の実装です。
APIは既にPHP側で在庫チェック・在庫減算を行っているため、このトリガーを
適用した状態でAPI経由の注文を行うと在庫が二重に減算される点に注意してください。

適用方法:

```bash
docker compose exec -T db psql -U postgres -d sample_ec -f - < db/triggers.sql
```

ストアドプロシージャの呼び出し例:

```bash
docker compose exec db psql -U postgres -d sample_ec -c \
  "CALL sp_create_order(1, '[{\"product_id\": 1, \"quantity\": 2}]'::jsonb);"
```

### 大量データ投入（検索パフォーマンス改善デモ用）

`db/seed_large_data.sql` は `stock_logs` に10万件のダミーデータを投入する
スクリプトです。自動実行はされないため、手動で実行してください。

```bash
docker compose exec -T db psql -U postgres -d sample_ec -f - < db/seed_large_data.sql
```
