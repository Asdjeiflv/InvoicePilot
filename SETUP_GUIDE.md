# InvoicePilot セットアップガイド

## 📦 依存関係のインストール

### 1. Larastan (PHPStan) のインストール

```bash
composer require --dev larastan/larastan:^2.0
```

### 2. Laravel Pint のインストール（Laravel 11 では標準インストール済み）

```bash
# 既にインストール済みのはずですが、なければ
composer require --dev laravel/pint
```

### 3. Laravel Horizon のインストール（キュー監視）

```bash
composer require laravel/horizon

# Horizon のアセット公開
php artisan horizon:install

# Horizon の設定
php artisan vendor:publish --tag=horizon-config
```

### 4. 品質チェックツールの動作確認

```bash
# PHPStan 実行
./vendor/bin/phpstan analyse

# Laravel Pint 実行（コードスタイル自動修正）
./vendor/bin/pint

# Laravel Pint テスト（修正せずチェックのみ）
./vendor/bin/pint --test

# テスト実行
php artisan test

# カバレッジ付きテスト実行
php artisan test --coverage --min=70
```

---

## 🚀 初期セットアップ（本番環境）

### Step 1: 環境変数設定

```bash
cp .env.example .env
```

`.env` を編集して以下を設定：

```env
# アプリケーション設定
APP_NAME=InvoicePilot
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_TIMEZONE=Asia/Tokyo

# データベース設定
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoicepilot
DB_USERNAME=invoicepilot_user
DB_PASSWORD=your-secure-password

# キャッシュ・キュー設定
CACHE_STORE=database
QUEUE_CONNECTION=database

# メール設定（Gmail の場合）
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# ログ設定
LOG_CHANNEL=daily
LOG_LEVEL=info

# セッション設定
SESSION_DRIVER=database
SESSION_LIFETIME=120

# セキュリティ設定
BCRYPT_ROUNDS=12
```

### Step 2: アプリケーションキー生成

```bash
php artisan key:generate
```

### Step 3: データベースセットアップ

```bash
# データベース作成（MySQL にログイン）
mysql -u root -p
CREATE DATABASE invoicepilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'invoicepilot_user'@'localhost' IDENTIFIED BY 'your-secure-password';
GRANT ALL PRIVILEGES ON invoicepilot.* TO 'invoicepilot_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# マイグレーション実行
php artisan migrate --force
```

### Step 4: 初期ユーザー作成

```bash
php artisan tinker
```

Tinker で以下を実行：

```php
\App\Models\User::create([
    'name' => 'システム管理者',
    'email' => 'admin@example.com',
    'password' => bcrypt('SecurePassword123!'),
    'role' => 'admin',
]);

// 経理担当者
\App\Models\User::create([
    'name' => '経理担当',
    'email' => 'accounting@example.com',
    'password' => bcrypt('SecurePassword123!'),
    'role' => 'accounting',
]);

// 営業担当者
\App\Models\User::create([
    'name' => '営業担当',
    'email' => 'sales@example.com',
    'password' => bcrypt('SecurePassword123!'),
    'role' => 'sales',
]);

// 監査担当者
\App\Models\User::create([
    'name' => '監査担当',
    'email' => 'auditor@example.com',
    'password' => bcrypt('SecurePassword123!'),
    'role' => 'auditor',
]);
```

### Step 5: アセットビルド

```bash
npm install
npm run build
```

### Step 6: 権限設定

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Step 7: キャッシュ最適化

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 8: キューワーカー起動

```bash
# Laravel Horizon を使う場合（推奨）
php artisan horizon

# または通常の Queue Worker
php artisan queue:work --daemon --tries=3 --timeout=300
```

### Step 9: バックアップ Cron 設定

```bash
crontab -e
```

以下を追加：

```cron
# 毎日 3:00 AM にバックアップ実行
0 3 * * * cd /path/to/invoicepilot && php artisan backup:database >> /var/log/invoicepilot_backup.log 2>&1

# Laravel のスケジューラー（将来の機能用）
* * * * * cd /path/to/invoicepilot && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 動作確認

### 1. アプリケーション起動確認

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

ブラウザで http://localhost:8000 にアクセス

### 2. ログイン確認

- Email: admin@example.com
- Password: SecurePassword123!

### 3. 主要機能の動作確認

#### 顧客作成
1. Clients > Create
2. 必要事項を入力して保存
3. 監査ログが記録されていることを確認

```bash
php artisan tinker
>>> \App\Models\AuditLog::latest()->first()
```

#### 請求書作成
1. Invoices > Create
2. 顧客を選択し、明細を入力
3. 請求番号が自動採番されることを確認

#### 入金記録
1. Payments > Create
2. 請求書を選択し、金額を入力
3. 残高（balance_due）が自動更新されることを確認

### 4. 会計連携 CSV エクスポート確認

```bash
# freee 形式でエクスポート
curl -H "Cookie: laravel_session=YOUR_SESSION" \
  "http://localhost:8000/accounting/export/freee?start_date=2026-01-01&end_date=2026-01-31&type=invoices" \
  -o freee_export.csv

# CSV の内容確認
cat freee_export.csv
```

### 5. バックアップ確認

```bash
# バックアップ実行
php artisan backup:database

# バックアップファイル確認
ls -lh storage/backups/

# バックアップの復元テスト（テスト環境のみ）
php artisan backup:restore storage/backups/backup_2026-02-11_03-00-00.sql
```

---

## 🔒 セキュリティチェックリスト

### 本番デプロイ前に必ず確認

- [ ] `APP_DEBUG=false` に設定
- [ ] `APP_ENV=production` に設定
- [ ] HTTPS を強制（Nginx/Apache 設定）
- [ ] `.env` ファイルの権限を 600 に設定（`chmod 600 .env`）
- [ ] データベースユーザーの権限を最小化
- [ ] ファイアウォール設定（必要なポートのみ開放）
- [ ] エラーログの外部公開を防止
- [ ] `composer audit` でセキュリティチェック
- [ ] 強力なパスワードポリシー設定
- [ ] CSRF トークン有効確認

### セキュリティ強化（推奨）

```bash
# 1. HTTPS 強制（AppServiceProvider.php に追加）
# app/Providers/AppServiceProvider.php の boot() メソッド

use Illuminate\Support\Facades\URL;

public function boot(): void
{
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
    
    // 既存のコード...
}
```

```bash
# 2. セキュリティヘッダー追加（Middleware）
php artisan make:middleware SecurityHeaders
```

```php
// app/Http/Middleware/SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    
    return $response;
}
```

---

## 📊 監視設定（Laravel Horizon）

### Horizon のインストールと設定

```bash
composer require laravel/horizon

php artisan horizon:install
php artisan vendor:publish --tag=horizon-assets
```

`config/horizon.php` を編集：

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'database',
            'queue' => ['default', 'emails', 'webhooks'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
            'timeout' => 300,
        ],
    ],
],
```

Horizon をサービスとして起動（systemd）：

```bash
sudo nano /etc/systemd/system/horizon.service
```

```ini
[Unit]
Description=Laravel Horizon
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/invoicepilot
ExecStart=/usr/bin/php artisan horizon
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable horizon
sudo systemctl start horizon
sudo systemctl status horizon
```

Horizon ダッシュボードにアクセス：  
http://your-domain.com/horizon

---

## 🐛 トラブルシューティング

### エラー: "Class 'Larastan\Larastan\...' not found"

**対処**:
```bash
composer dump-autoload
php artisan clear-compiled
```

### エラー: "SQLSTATE[HY000] [2002] Connection refused"

**対処**:
```bash
# MySQL が起動しているか確認
sudo systemctl status mysql

# MySQL を起動
sudo systemctl start mysql

# .env の DB 設定を確認
cat .env | grep DB_
```

### エラー: "Permission denied" on storage/

**対処**:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### テストが失敗する

**対処**:
```bash
# テスト用データベースを作成
mysql -u root -p
CREATE DATABASE invoicepilot_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON invoicepilot_test.* TO 'invoicepilot_user'@'localhost';
EXIT;

# .env.testing を作成
cp .env .env.testing

# .env.testing の DB_DATABASE を変更
DB_DATABASE=invoicepilot_test

# テスト実行
php artisan test
```

---

## 📞 サポート

問題が解決しない場合：

1. **ログ確認**: `storage/logs/laravel.log`
2. **GitHub Issues**: https://github.com/your-org/invoicepilot/issues
3. **Email**: support@invoicepilot.com

---

最終更新: 2026-02-11
