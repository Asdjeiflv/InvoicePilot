# 本番環境チェックリスト

このチェックリストは、InvoicePilotを本番環境にデプロイする前に確認すべき項目をまとめたものです。

## ✅ セキュリティ

- [ ] `.env` ファイルの `APP_ENV=production` 設定
- [ ] `.env` ファイルの `APP_DEBUG=false` 設定
- [ ] `.env` ファイルの `APP_KEY` が生成されている
- [ ] 強力なデータベースパスワード設定（20文字以上推奨）
- [ ] データベース専用ユーザー作成（root使用禁止）
- [ ] `.env` ファイルのパーミッション 600 に設定
- [ ] SSL証明書取得・設定完了
- [ ] `SESSION_SECURE_COOKIE=true` 設定（HTTPS環境）
- [ ] CSPヘッダー確認（ContentSecurityPolicyミドルウェア）
- [ ] CSRF保護有効（Laravel標準で有効）
- [ ] Redis パスワード設定（推奨）
- [ ] ファイアウォール設定完了
- [ ] fail2ban設定完了（ブルートフォース対策）
- [ ] 不要なポートのクローズ確認

## ⚙️ アプリケーション設定

- [ ] `APP_URL` を本番URLに変更
- [ ] `APP_TIMEZONE=Asia/Tokyo` 設定
- [ ] `DB_CONNECTION=mysql` 設定
- [ ] `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` 正しく設定
- [ ] `SESSION_DRIVER=redis` または `database` に設定
- [ ] `CACHE_STORE=redis` に設定（推奨）
- [ ] `QUEUE_CONNECTION=database` または `redis` に設定
- [ ] `MAIL_*` 設定完了（SMTP認証情報）
- [ ] `LOG_CHANNEL=stack` 設定
- [ ] `LOG_STACK=daily` 設定（ログローテーション）
- [ ] `BCRYPT_ROUNDS=12` 以上に設定

## 🗄️ データベース

- [ ] 本番用データベース作成完了
- [ ] 専用データベースユーザー作成完了
- [ ] 適切な権限付与完了（必要最小限）
- [ ] マイグレーション実行完了 (`php artisan migrate --force`)
- [ ] **`php artisan db:seed` を実行していないこと確認**
- [ ] 初期管理者ユーザー手動作成完了
- [ ] データベースバックアップ自動化設定完了
- [ ] スロークエリログ設定（パフォーマンス監視）
- [ ] データベース接続テスト完了

## 📦 依存パッケージ

- [ ] `composer install --optimize-autoloader --no-dev` 実行完了
- [ ] `npm ci` 実行完了
- [ ] `npm run build` 実行完了（本番ビルド）
- [ ] `php artisan storage:link` 実行完了
- [ ] 不要なdev依存パッケージが含まれていないこと確認

## 🚀 パフォーマンス最適化

- [ ] `php artisan config:cache` 実行完了
- [ ] `php artisan route:cache` 実行完了
- [ ] `php artisan view:cache` 実行完了
- [ ] `php artisan event:cache` 実行完了
- [ ] OPcache設定完了（php.ini）
- [ ] `opcache.validate_timestamps=0` 設定（本番環境）
- [ ] Redisインストール・設定完了
- [ ] PHP-FPM worker設定最適化
- [ ] Nginx gzip圧縮有効化
- [ ] 静的ファイルキャッシュヘッダー設定

## 🔧 サーバー設定

- [ ] PHP 8.2以上インストール確認
- [ ] 必要なPHP拡張モジュールインストール確認
  - [ ] php-fpm
  - [ ] php-mysql
  - [ ] php-mbstring
  - [ ] php-xml
  - [ ] php-bcmath
  - [ ] php-curl
  - [ ] php-gd
  - [ ] php-zip
  - [ ] php-redis
- [ ] Nginx/Apache設定完了
- [ ] Nginx設定テスト成功 (`nginx -t`)
- [ ] Webサーバー再起動完了
- [ ] ドキュメントルート `/var/www/InvoicePilot/public` 設定確認

## 📝 パーミッション

- [ ] アプリケーションディレクトリ所有者 `www-data:www-data` 設定
- [ ] `storage/` ディレクトリパーミッション 775 設定
- [ ] `bootstrap/cache/` ディレクトリパーミッション 775 設定
- [ ] `.env` ファイルパーミッション 600 設定
- [ ] `storage/logs/` 書き込み権限確認
- [ ] `storage/framework/sessions/` 書き込み権限確認
- [ ] `storage/framework/cache/` 書き込み権限確認

## 🔄 バックグラウンド処理

- [ ] Supervisorインストール完了
- [ ] キューワーカー設定完了 (`/etc/supervisor/conf.d/invoicepilot-worker.conf`)
- [ ] Supervisor再読み込み完了 (`supervisorctl reread && update`)
- [ ] キューワーカー起動確認 (`supervisorctl status`)
- [ ] Cron設定完了（Laravelスケジューラー）
- [ ] Cron動作確認（`* * * * *` で1分ごと実行）

## 📧 メール設定

- [ ] SMTP認証情報設定完了
- [ ] `MAIL_FROM_ADDRESS` 設定完了
- [ ] `MAIL_FROM_NAME` 設定完了
- [ ] メール送信テスト成功
- [ ] 督促メールテスト成功
- [ ] SPF/DKIM/DMARC設定（ドメイン設定）
- [ ] Gmail使用時: アプリパスワード取得・設定完了

## 🛡️ SSL/TLS

- [ ] Let's Encrypt証明書取得完了
- [ ] 証明書自動更新設定確認 (`certbot renew --dry-run`)
- [ ] HTTPSアクセステスト成功
- [ ] HTTP→HTTPS自動リダイレクト設定完了
- [ ] SSL Labs テスト A評価以上（https://www.ssllabs.com/ssltest/）
- [ ] 証明書有効期限監視設定

## 📊 監視・ログ

- [ ] アプリケーションログ確認 (`storage/logs/laravel.log`)
- [ ] Nginxアクセスログ確認
- [ ] Nginxエラーログ確認
- [ ] ログローテーション設定完了
- [ ] ヘルスチェックエンドポイント確認 (`/up`)
- [ ] 外部監視サービス設定（UptimeRobot等）
- [ ] エラー通知設定（オプション）

## 🔐 バックアップ

- [ ] データベースバックアップスクリプト作成
- [ ] ストレージディレクトリバックアップスクリプト作成
- [ ] バックアップCron設定完了
- [ ] バックアップ自動実行テスト成功
- [ ] バックアップリストア手順確認
- [ ] 古いバックアップ自動削除設定（7日以上前）
- [ ] バックアップ保存先セキュリティ確認

## 🧪 動作確認

- [ ] HTTPSでアクセス可能
- [ ] ログインページ表示確認
- [ ] 管理者アカウントでログイン成功
- [ ] ダッシュボード表示確認
- [ ] 全機能動作確認
  - [ ] 取引先 CRUD操作
  - [ ] 見積作成・編集・削除
  - [ ] 請求作成・編集・削除
  - [ ] 入金登録
  - [ ] 督促メール送信（テスト）
- [ ] CSPエラーがないこと確認（開発者ツール）
- [ ] JavaScriptエラーがないこと確認（開発者ツール）
- [ ] レスポンシブデザイン確認（モバイル/タブレット）
- [ ] ダークモード切り替え確認
- [ ] セッション維持確認
- [ ] ログアウト動作確認

## 🚫 本番環境で避けるべきこと

- [ ] **テストユーザー（admin@example.com等）が存在しないこと確認**
- [ ] **`APP_DEBUG=true` になっていないこと確認**
- [ ] **`APP_ENV=local` や `development` になっていないこと確認**
- [ ] **`composer install` 実行時に `--dev` フラグを使用していないこと**
- [ ] **Gitリポジトリに `.env` ファイルをコミットしていないこと確認**
- [ ] **本番環境で `php artisan migrate:fresh` を実行しないこと**
- [ ] **本番環境で `php artisan db:seed` を実行しないこと**
- [ ] **rootユーザーでデータベース接続していないこと**
- [ ] **デフォルトパスワード（password）を使用していないこと**
- [ ] **不要な開発ツール（Telescope, Debugbar等）が有効になっていないこと**

## 📋 デプロイ後24時間以内の確認事項

- [ ] エラーログ確認（異常なエラーがないか）
- [ ] アクセスログ確認（正常にアクセスされているか）
- [ ] CPU/メモリ使用率確認
- [ ] ディスク使用率確認
- [ ] データベース接続数確認
- [ ] キューワーカー稼働状況確認
- [ ] Cron実行履歴確認
- [ ] バックアップ実行確認
- [ ] SSL証明書有効期限確認
- [ ] メール送信テスト（実際のメール送信）

## 🎯 週次確認事項

- [ ] エラーログレビュー
- [ ] アクセス数・パフォーマンス確認
- [ ] ディスク使用率確認（ログ・バックアップ増加）
- [ ] セキュリティアップデート確認
- [ ] バックアップリストアテスト（月1回推奨）

## 📚 ドキュメント

- [ ] 本番環境接続情報ドキュメント作成
- [ ] 障害対応手順書作成
- [ ] バックアップリストア手順書作成
- [ ] デプロイ手順書作成（このREADME/DEPLOYMENT.md）
- [ ] 運用マニュアル作成
- [ ] 管理者向け操作マニュアル作成

## 🆘 緊急時連絡先

- サーバー管理者: _______________
- アプリケーション担当: _______________
- データベース管理者: _______________
- ホスティング会社サポート: _______________

---

## 📌 重要な変更点（開発環境→本番環境）

### 1. テストユーザーの自動作成を無効化

**変更ファイル**: `database/seeders/DatabaseSeeder.php`

```php
// 本番環境ではテストユーザーを作成しない
if (!app()->environment('production')) {
    // テストユーザー作成コード
}
```

### 2. 環境変数の変更

**ファイル**: `.env`

| 項目 | 開発環境 | 本番環境 |
|------|---------|---------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `APP_URL` | `http://localhost:8000` | `https://yourdomain.com` |
| `DB_USERNAME` | `root` | `invoicepilot_user` |
| `DB_PASSWORD` | `root` | 強力なパスワード |
| `SESSION_DRIVER` | `file` | `redis` |
| `CACHE_STORE` | `file` | `redis` |
| `MAIL_MAILER` | `log` | `smtp` |

### 3. セキュリティ設定

- ContentSecurityPolicyミドルウェアが本番環境用の厳格なCSPを自動適用
- 開発環境: `unsafe-eval`, `unsafe-inline` 許可（Vite HMR用）
- 本番環境: 厳格なCSP適用

### 4. 初期ユーザー作成方法

**開発環境:**
```bash
php artisan migrate:fresh --seed
```

**本番環境:**
```bash
php artisan migrate --force
php artisan tinker
# Tinker内で手動作成
User::factory()->admin()->create([...]);
```

---

**このチェックリストを印刷して、デプロイ作業時に各項目をチェックしてください。**
