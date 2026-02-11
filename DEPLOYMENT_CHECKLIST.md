# 本番デプロイチェックリスト

## 📋 デプロイ前チェック（必須）

### 1. コード品質
- [ ] すべてのテストが通過: `php artisan test`
- [ ] PHPStan レベル 5 通過: `./vendor/bin/phpstan analyse`
- [ ] Laravel Pint 通過: `./vendor/bin/pint --test`
- [ ] カバレッジ 70% 以上: `php artisan test --coverage --min=70`
- [ ] Git に未コミットの変更がない: `git status`

### 2. セキュリティ
- [ ] `APP_DEBUG=false` に設定
- [ ] `APP_ENV=production` に設定
- [ ] HTTPS 強制設定（AppServiceProvider で URL::forceScheme('https')）
- [ ] `.env` ファイルの権限を 600 に設定: `chmod 600 .env`
- [ ] 秘密情報の平文コミットなし（.env, credentials 等）
- [ ] 強力なパスワードポリシー（8文字以上、大小英数字+記号）
- [ ] セキュリティヘッダー設定（X-Frame-Options, CSP 等）
- [ ] 依存関係の脆弱性チェック: `composer audit`

### 3. データベース
- [ ] 本番用データベース作成済み
- [ ] データベースユーザーの権限を最小化（DROP/CREATE 権限削除）
- [ ] マイグレーション実行: `php artisan migrate --force`
- [ ] インデックス最適化確認
- [ ] バックアップ体制確立（daily Cron 設定）

### 4. 環境設定
- [ ] `.env` の全項目を本番用に設定
  - [ ] APP_URL
  - [ ] DB_* （接続情報）
  - [ ] MAIL_* （SMTP 設定）
  - [ ] CACHE_STORE
  - [ ] QUEUE_CONNECTION
  - [ ] LOG_CHANNEL=daily
- [ ] タイムゾーン設定: `APP_TIMEZONE=Asia/Tokyo`
- [ ] ログローテーション設定（90日保持）

### 5. パフォーマンス
- [ ] 設定キャッシュ: `php artisan config:cache`
- [ ] ルートキャッシュ: `php artisan route:cache`
- [ ] ビューキャッシュ: `php artisan view:cache`
- [ ] Composer autoload 最適化: `composer install --optimize-autoloader --no-dev`
- [ ] アセットビルド: `npm run build`

### 6. インフラ
- [ ] Web サーバー設定（Nginx/Apache）
- [ ] SSL/TLS 証明書設定（Let's Encrypt 等）
- [ ] ファイアウォール設定（必要なポートのみ開放）
- [ ] ファイル権限設定: `chmod -R 775 storage bootstrap/cache`
- [ ] 所有者設定: `chown -R www-data:www-data storage bootstrap/cache`
- [ ] PHP 設定最適化（php.ini: memory_limit, upload_max_filesize 等）

### 7. 監視・ログ
- [ ] Laravel Horizon インストール・設定
- [ ] ログ監視設定（エラーログアラート）
- [ ] アプリケーション監視（APM: New Relic/Datadog 等）
- [ ] ディスク使用率監視
- [ ] データベース接続監視

### 8. バックアップ
- [ ] データベースバックアップ Cron 設定: `0 3 * * * php artisan backup:database`
- [ ] バックアップ保存先設定（S3 等）
- [ ] 復元手順の動作確認（テスト環境）
- [ ] バックアップ世代管理（90日保持）

### 9. ドキュメント
- [ ] README.md 更新（本番 URL、連絡先等）
- [ ] CHANGELOG.md 更新
- [ ] 運用手順書確認（docs/runbook.md）
- [ ] 障害対応フロー確認
- [ ] エスカレーションフロー整備

### 10. 初期データ
- [ ] 管理者ユーザー作成
- [ ] 各ロールのテストユーザー作成（admin/accounting/sales/auditor）
- [ ] 初期マスタデータ投入（必要に応じて）

---

## 🚀 デプロイ手順

### Step 1: コードデプロイ

```bash
# 1. 最新コードを取得
git pull origin main

# 2. 依存関係インストール
composer install --optimize-autoloader --no-dev
npm install
npm run build

# 3. マイグレーション実行（必ずバックアップ後）
php artisan backup:database
php artisan migrate --force

# 4. キャッシュクリア＆再生成
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 2: サービス再起動

```bash
# Queue Worker 再起動
php artisan queue:restart

# または Horizon 再起動
php artisan horizon:terminate
sudo systemctl restart horizon

# Web サーバー再起動（必要に応じて）
sudo systemctl restart nginx
# または
sudo systemctl restart apache2

# PHP-FPM 再起動（必要に応じて）
sudo systemctl restart php8.2-fpm
```

### Step 3: 動作確認

```bash
# 1. ヘルスチェック
curl https://your-domain.com/

# 2. ログ確認（エラーがないか）
tail -100 storage/logs/laravel.log

# 3. 主要機能の動作確認
# - ログイン
# - 請求書作成
# - 入金記録
# - 会計連携 CSV エクスポート

# 4. キュー動作確認
php artisan queue:work --once

# 5. Horizon ダッシュボード確認
# https://your-domain.com/horizon
```

---

## 🔄 ロールバック手順

### データベースロールバック

```bash
# 1. メンテナンスモード ON
php artisan down

# 2. バックアップから復元
mysql -u root -p invoicepilot < storage/backups/backup_YYYY-MM-DD_HH-MM-SS.sql

# 3. 旧バージョンのコードに戻す
git checkout <previous-commit-hash>

# 4. マイグレーション調整（必要に応じて）
php artisan migrate:rollback --step=1

# 5. キャッシュクリア
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 6. メンテナンスモード OFF
php artisan up
```

### アプリケーションロールバック

```bash
# 1. 旧バージョンにチェックアウト
git checkout <tag-or-commit>

# 2. 依存関係再インストール
composer install --no-dev
npm install
npm run build

# 3. サービス再起動
php artisan queue:restart
sudo systemctl restart horizon
```

---

## 📊 デプロイ後確認（24時間監視）

### 即座に確認
- [ ] アプリケーションが正常起動
- [ ] ログインできる
- [ ] 主要機能（請求作成、入金記録）が動作
- [ ] エラーログに異常なし

### 1時間後
- [ ] キューが正常動作（Horizon ダッシュボード）
- [ ] メモリ使用率が正常範囲内
- [ ] CPU 使用率が正常範囲内
- [ ] レスポンス時間が許容範囲内（P95 < 500ms）

### 24時間後
- [ ] バックアップが自動実行された
- [ ] ディスク使用率が正常範囲内
- [ ] エラー率が許容範囲内（< 1%）
- [ ] ユーザーからのエラー報告なし

---

## 🚨 緊急連絡先

### P0（Critical）障害
- **対応時間**: 即時（24時間）
- **連絡先**: emergency@invoicepilot.com
- **Slack**: #critical-alerts

### P1（High）障害
- **対応時間**: 1時間以内
- **連絡先**: support@invoicepilot.com
- **Slack**: #support

### P2/P3 障害
- **対応時間**: 営業時間内（平日 10:00-18:00）
- **連絡先**: support@invoicepilot.com

---

## ✅ 最終確認

デプロイ責任者: __________________  
デプロイ日時: __________________  
バックアップ確認者: __________________  
動作確認者: __________________  

**署名**: __________________  
**日付**: __________________

---

最終更新: 2026-02-11
次回レビュー予定: デプロイ後1週間
