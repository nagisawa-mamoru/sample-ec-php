# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 概要

Claude Code研修用のECサイト管理システム（商品管理・注文管理・在庫管理）。「注文すると在庫が減る」という業務ロジックを中心に据えた、Vue3 + CodeIgniter4 + PostgreSQLのモノレポ構成。詳しい構成図・セットアップ手順・APIエンドポイント一覧は [README.md](README.md) を参照。

## セットアップ・起動

Docker Compose利用時:

```bash
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
docker compose up -d
docker compose exec php php spark migrate
docker compose exec php php spark db:seed DatabaseSeeder
```

`http://localhost:8080`（nginx経由）でアクセス。

Dockerを使わない場合はPostgreSQL・PHP 8.3・Node.js 20+をローカルに用意し、`backend/.env`のhostnameを`localhost`に、`frontend/.env`の`VITE_API_BASE_URL`を`http://localhost:8888/api`に変更した上で `php spark serve --host localhost --port 8888`（backend）と `npm run dev`（frontend, `http://localhost:5173`）を個別に起動する。手順の詳細はREADME.mdの「Dockerを使わないセットアップ」章。

## よく使うコマンド

### バックエンド（`backend/`）

- 依存インストール: `composer install`
- マイグレーション: `php spark migrate` / 状態確認: `php spark migrate:status` / 作り直し: `php spark migrate:refresh`
- シーディング: `php spark db:seed DatabaseSeeder`
- 全テスト実行: `vendor/bin/phpunit`（Docker経由なら `docker compose exec php vendor/bin/phpunit`）
- 単一テスト実行: `vendor/bin/phpunit tests/unit/HealthTest.php` または `vendor/bin/phpunit --filter <TestClass>::<testMethod>`
- テスト用DBの作成（初回のみ）: `psql -U postgres -c "CREATE DATABASE sample_ec_test;"`
- テスト雛形生成: `php spark make:test <Name>`
- アプリ固有のテストは研修のテスト実装章で受講者が追加する想定のため、現状 `tests/` には雛形（`ExampleDatabaseTest.php`等）しか無い。

### フロントエンド（`frontend/`）

- 依存インストール: `npm install`
- 開発サーバー起動: `npm run dev`
- ビルド: `npm run build` / プレビュー: `npm run preview`
- テスト（Vitest, jsdom環境）: `npm run test` / 単一ファイル: `npm run test -- src/path/to/Foo.spec.js`
- Lint（ESLint flat config, `eslint.config.js`）: `npm run lint`
- テストファイルが1つも無い状態で`npm run test`を実行すると"No test files found"で終了コード1になるが、これは仕様。

## アーキテクチャ

### リクエストフロー

nginx（`docker/nginx/default.conf`）が `/api/*` をPHP-FPM（CodeIgniter4）へ、それ以外をVite dev server（`frontend:5173`）へ振り分けるリバースプロキシとして動作する。CodeIgniter側は画面描画を行わず、常にJSONを返すREST APIに徹している。

### バックエンド（CodeIgniter4）

- ルーティングは `app/Config/Routes.php` に集約。`api`グループ（namespace `App\Controllers\Api`）配下に `admin`サブグループ（namespace `App\Controllers\Api\Admin`）がネストしている。
- 「注文すると在庫が減る」というコアロジックは `App\Models\OrderModel::createOrder()` に集約されている。DBトランザクション内で、明細ごとの在庫チェック→注文・注文明細の作成→在庫減算・`stock_logs`記録、という順に処理する。在庫チェックと減算処理が別ループになっている点に注意して読むこと。
- 商品価格は `App\Models\ProductModel` に集約: `withDiscount()`が表示用の`discounted_price`を、`effectivePrice()`が実際の注文計算に使う単価を返す。両者は同じ割引計算式を独立に実装しているため、割引ロジックを変更する際は両方を更新する必要がある。
- CORSはCodeIgniter標準のCORSフィルタではなく、独自実装（`app/Filters/Cors.php` + `app/Config/Cors.php`）を使用。許可オリジンは`Config\Cors::$allowedOrigins`で管理し、`Routes.php`内で`OPTIONS`プリフライト用のcatch-allルートを個別に登録している。
- コントローラのバリデーションは`$this->validate($rules)` + `$this->request->getJSON(true)`の組み合わせで行う。CodeIgniter4の`Validation::withRequest()`はContent-Typeが`application/json`ならHTTPメソッドに関わらずJSONボディを読むため、POST/PATCH問わずこのパターンで統一されている。
- APIに認証・認可の仕組みは一切無い（`customer_id`もクライアントが直接指定する）。顧客作成用のエンドポイントも存在せず、顧客は`CustomerSeeder`で投入したもののみを使う想定。

### DB層（PL/pgSQL）

`db/triggers.sql` は、CodeIgniter側の業務ロジックとは独立した別実装（トリガーによる自動在庫減算＋在庫チェック付きストアドプロシージャ`sp_create_order`）で、「業務ロジックをアプリ層とDB層のどちらに置くか」を議論するための教材用途。**このトリガーを適用した状態でAPI経由の注文を行うと在庫が二重に減算される**（両者は同時併用を想定していない）。`db/seed_large_data.sql`は検索パフォーマンス改善デモ用に`stock_logs`へ10万件投入するスクリプトで、自動実行はされない。

### フロントエンド（Vue3）

- Composition API + Pinia + vue-router構成。APIアクセスは`src/api/`配下（`products.js`/`orders.js`/`admin.js`）のモジュールにまとめ、各関数が`apiClient`（axios、`src/api/client.js`）経由でリクエストしレスポンスの`.data`を返す薄いラッパーになっている。
- カート状態は`src/stores/cart.js`（Pinia）で管理し、`localStorage`等への永続化はしていない（リロードでカートは消える）。
- 画面は`src/views/`配下。一覧・詳細系のviewは`loading`/`errorMessage`のstateパターンと、テンプレート側で`v-if`/`v-else-if`/`v-else`による複数ルート要素（Vue3のFragments）を使う構成で統一されている。
