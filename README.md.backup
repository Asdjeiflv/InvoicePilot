# BillingFlow - 請求管理SaaS

Laravel 11 + Vue3 + Inertia + TypeScript + MySQLで構築された、見積・請求・入金・督促を一気通貫で管理する請求管理システムです。

## 📋 概要

BillingFlowは小規模事業者向けの包括的な請求管理システムです。見積作成から請求発行、入金管理、督促まで、請求業務の全プロセスをサポートします。

### 主な機能

✅ **認証・認可**
- Laravel Breeze (Inertia + Vue3 + TypeScript)
- ロールベースアクセス制御 (RBAC): admin, accounting, sales

✅ **取引先管理**
- CRUD操作 (Controller, Routes, Policy完全実装)
- 検索・ページネーション
- 支払条件・締め日設定

✅ **見積管理**
- 見積作成・承認フロー
- 明細行管理（税計算対応）
- 採番: Q-YYYY-00001形式
- ステータス管理: draft / sent / approved / rejected

✅ **請求管理**
- 請求作成（手動・見積からの変換）
- 明細行管理（税計算対応）
- 採番: I-YYYY-00001形式
- ステータス管理: draft / issued / partial_paid / paid / overdue / canceled
- PDF出力機能

✅ **入金管理**
- 入金登録（部分入金対応）
- 自動残高再計算
- ステータス自動更新

✅ **督促管理**
- テンプレートベース督促メール (soft/normal/final)
- 送信履歴記録

✅ **添付ファイル**
- Polymorphic関連付け（見積・請求に添付可能）

✅ **監査ログ**
- 主要操作の記録（作成・更新・削除）
- Before/After状態保存

## 🛠 技術スタック

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue3 + Inertia + TypeScript
- **UI**: Tailwind CSS
- **Database**: MySQL 8
- **Auth**: Laravel Breeze
- **PDF**: barryvdh/laravel-dompdf
- **Queue/Mail**: Laravel標準
- **Test**: PHPUnit
- **Lint/Format**: Laravel Pint

## 🏗 アーキテクチャ

### ディレクトリ構成

```
app/
├── Actions/               # ビジネスロジック（Actionパターン）
│   ├── Invoices/
│   │   ├── CreateInvoiceFromQuotationAction.php
│   │   ├── RecalculateInvoiceBalanceAction.php
│   │   └── ChangeInvoiceStatusAction.php
│   └── Reminders/
│       └── SendReminderAction.php
├── Http/
│   ├── Controllers/
│   │   └── ClientController.php  # 完全実装済み
│   ├── Middleware/
│   │   └── EnsureUserHasRole.php # ロール検証
│   └── Requests/
│       ├── StoreClientRequest.php
│       └── UpdateClientRequest.php
├── Models/                # Eloquentモデル（全9モデル実装済み）
│   ├── Client.php
│   ├── Quotation.php
│   ├── QuotationItem.php
│   ├── Invoice.php
│   ├── InvoiceItem.php
│   ├── Payment.php
│   ├── Reminder.php
│   ├── Attachment.php
│   └── AuditLog.php
├── Policies/              # 認可ポリシー
│   └── ClientPolicy.php   # 完全実装済み
└── Services/
    └── NumberingService.php  # 採番ロジック

database/
└── migrations/            # 全9テーブル実装済み
```

### ER図（簡略版）

```
users
  ├─ role (admin/accounting/sales)
  └─ created quotations, invoices, payments, reminders

clients
  ├─ quotations (1:N)
  └─ invoices (1:N)

quotations
  ├─ quotation_items (1:N)
  ├─ invoices (1:N)
  └─ attachments (polymorphic)

invoices
  ├─ invoice_items (1:N)
  ├─ payments (1:N)
  ├─ reminders (1:N)
  └─ attachments (polymorphic)

payments → invoice

audit_logs → user, target (polymorphic)
```

## 🚀 セットアップ手順

### 前提条件

- PHP 8.2以上
- Composer
- Node.js 18以上
- MySQL 8以上
- MAMP または同等のローカル環境

### 1. リポジトリクローン（または既存ディレクトリ使用）

```bash
cd /Applications/MAMP/InvoicePilot
```

### 2. 依存パッケージインストール

```bash
# PHP依存
composer install

# Node依存
npm install
```

### 3. 環境設定

```bash
# .envファイル確認（既に設定済み）
# 主要な設定:
# APP_NAME=BillingFlow
# DB_CONNECTION=mysql
# DB_DATABASE=invoicepilot
# DB_USERNAME=root
# DB_PASSWORD=root
```

### 4. データベース作成

```bash
# MySQLに接続してデータベース作成（既に作成済み）
# または以下のPHPコマンドで作成
php -r "
\$conn = new PDO('mysql:host=127.0.0.1;port=3306', 'root', 'root');
\$conn->exec('CREATE DATABASE IF NOT EXISTS invoicepilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo 'Database created successfully';
"
```

### 5. マイグレーション実行

```bash
# 既存DBをクリーンな状態にする場合
php artisan migrate:fresh --seed

# または通常のマイグレーション
php artisan migrate
php artisan db:seed
```

### 6. フロントエンドビルド

```bash
# 開発ビルド
npm run dev

# または本番ビルド
npm run build
```

### 7. アプリケーション起動

```bash
# 開発サーバー起動
php artisan serve

# ブラウザで http://localhost:8000 にアクセス
```

### 8. ログイン

デモ用ユーザー（パスワードは全て `password`）:

```
Admin: admin@example.com
Accounting: accounting@example.com
Sales: sales@example.com
```

**注意**: これらのテストユーザーは本番環境では自動作成されません。本番環境のセットアップについては「本番環境デプロイ」セクションを参照してください。

## 🚀 本番環境デプロイ

### 前提条件

- PHP 8.2以上（php-fpm推奨）
- Composer 2.x
- Node.js 18以上
- MySQL 8以上
- Nginx または Apache
- SSL証明書（Let's Encrypt推奨）
- Redis（オプション、セッション/キャッシュ用）

### 1. 環境変数設定

```bash
# .env.exampleをコピーして編集
cp .env.example .env

# 以下の項目を本番環境用に設定
```

**.env 重要な設定項目:**

```bash
# アプリケーション設定
APP_NAME=InvoicePilot
APP_ENV=production              # 本番環境では必ず "production"
APP_KEY=                        # php artisan key:generate で生成
APP_DEBUG=false                 # 本番環境では必ず false
APP_TIMEZONE=Asia/Tokyo
APP_URL=https://yourdomain.com  # 本番URLに変更

# データベース設定
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoicepilot
DB_USERNAME=your_db_user        # rootは使用しない
DB_PASSWORD=strong_password     # 強力なパスワードを設定

# セッション設定（本番環境）
SESSION_DRIVER=redis            # redisまたはdatabase推奨
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true      # HTTPS環境では必須
SESSION_SAME_SITE=lax

# キャッシュ設定（本番環境）
CACHE_STORE=redis               # redis推奨（高速化）

# Redis設定
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# メール設定（督促機能に必要）
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com        # 使用するSMTPサーバー
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password # Gmailの場合はアプリパスワード
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# ログ設定
LOG_CHANNEL=stack
LOG_STACK=daily                 # 日次ログローテーション
LOG_LEVEL=warning               # warningまたはerror推奨

# セキュリティ設定
BCRYPT_ROUNDS=12                # 12以上推奨
```

### 2. 依存パッケージインストール

```bash
# Composer（本番環境最適化）
composer install --optimize-autoloader --no-dev

# Node.js（本番ビルド）
npm install
npm run build
```

### 3. アプリケーションキー生成

```bash
php artisan key:generate
```

### 4. データベース設定

```bash
# データベースユーザー作成（MySQL）
mysql -u root -p
```

```sql
-- 専用ユーザー作成（rootは使用しない）
CREATE USER 'invoicepilot_user'@'localhost' IDENTIFIED BY 'strong_password_here';

-- データベース作成
CREATE DATABASE invoicepilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 権限付与
GRANT ALL PRIVILEGES ON invoicepilot.* TO 'invoicepilot_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# マイグレーション実行（本番環境）
php artisan migrate --force
```

**重要**: `php artisan db:seed` は実行しないでください。本番環境ではテストユーザーは作成されません。

### 5. 初期管理者ユーザー作成

```bash
# Tinkerで手動作成
php artisan tinker
```

```php
// Tinker内で実行
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::factory()->admin()->create([
    'name' => 'Your Name',
    'email' => 'your@email.com',
    'password' => Hash::make('your-secure-password-here'),
]);

// 複数の管理者を作成する場合
User::factory()->accounting()->create([
    'name' => 'Accounting User',
    'email' => 'accounting@yourdomain.com',
    'password' => Hash::make('another-secure-password'),
]);

exit
```

### 6. ストレージリンク作成

```bash
php artisan storage:link
```

### 7. パーミッション設定

```bash
# Laravelが書き込み可能なディレクトリ
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# または、ユーザーグループに応じて
sudo chown -R nginx:nginx storage bootstrap/cache
```

### 8. キャッシュ最適化

```bash
# 設定キャッシュ
php artisan config:cache

# ルートキャッシュ
php artisan route:cache

# ビューキャッシュ
php artisan view:cache

# イベントキャッシュ
php artisan event:cache
```

### 9. Webサーバー設定

#### Nginx設定例

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/InvoicePilot/public;
    index index.php index.html;

    # SSL証明書
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # セキュリティヘッダー
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # CSPヘッダーはContentSecurityPolicyミドルウェアで設定されます

    # アクセスログ
    access_log /var/log/nginx/invoicepilot-access.log;
    error_log /var/log/nginx/invoicepilot-error.log;

    # 最大アップロードサイズ
    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # タイムアウト設定
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # 静的ファイルキャッシュ
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

#### Apache設定例 (.htaccess)

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### 10. SSL証明書取得（Let's Encrypt）

```bash
# Certbot インストール
sudo apt-get update
sudo apt-get install certbot python3-certbot-nginx

# 証明書取得（Nginx）
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# 自動更新設定確認
sudo systemctl status certbot.timer
```

### 11. キューワーカー設定（督促メール用）

```bash
# Supervisorインストール
sudo apt-get install supervisor

# 設定ファイル作成
sudo nano /etc/supervisor/conf.d/invoicepilot-worker.conf
```

```ini
[program:invoicepilot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/InvoicePilot/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/InvoicePilot/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Supervisor再読み込み
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start invoicepilot-worker:*

# ワーカー状態確認
sudo supervisorctl status
```

### 12. Cronジョブ設定（スケジュールタスク用）

```bash
# Crontab編集
crontab -e
```

```cron
# Laravel Scheduler
* * * * * cd /var/www/InvoicePilot && php artisan schedule:run >> /dev/null 2>&1
```

### 13. デプロイ後の確認チェックリスト

- [ ] `.env` ファイルの `APP_ENV=production` 設定確認
- [ ] `.env` ファイルの `APP_DEBUG=false` 設定確認
- [ ] `.env` ファイルの `APP_URL` を本番URLに設定
- [ ] `APP_KEY` が生成されている
- [ ] データベース接続確認（`php artisan migrate:status`）
- [ ] 初期管理者ユーザー作成完了
- [ ] ストレージディレクトリのパーミッション確認
- [ ] SSL証明書が有効
- [ ] メール送信テスト（督促機能テスト）
- [ ] キューワーカーが稼働中（`supervisorctl status`）
- [ ] Cronジョブが登録済み（`crontab -l`）
- [ ] ログファイルが正しく書き込まれている
- [ ] セッションが正常に動作（ログイン/ログアウトテスト）
- [ ] CSPヘッダーが設定されている（開発者ツールで確認）

### 14. セキュリティ強化（推奨）

```bash
# ファイアウォール設定（UFW）
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# fail2ban設定（ブルートフォース対策）
sudo apt-get install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

**.env追加設定:**

```bash
# Rate Limiting（APIやログインの制限）
# config/auth.php, routes/web.php で設定
```

### 15. バックアップ設定

```bash
# データベースバックアップスクリプト例
nano /usr/local/bin/backup-invoicepilot.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/invoicepilot"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="invoicepilot"
DB_USER="invoicepilot_user"
DB_PASS="your_password"

# ディレクトリ作成
mkdir -p $BACKUP_DIR

# データベースバックアップ
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# ストレージディレクトリバックアップ
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/InvoicePilot/storage

# 7日以上前のバックアップ削除
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
# 実行権限付与
chmod +x /usr/local/bin/backup-invoicepilot.sh

# Crontab追加（毎日午前3時にバックアップ）
crontab -e
```

```cron
0 3 * * * /usr/local/bin/backup-invoicepilot.sh >> /var/log/invoicepilot-backup.log 2>&1
```

### 16. 更新時の手順

```bash
# コード更新後
cd /var/www/InvoicePilot

# メンテナンスモード有効化
php artisan down

# Git pull（または新しいコードをデプロイ）
git pull origin main

# Composer更新
composer install --optimize-autoloader --no-dev

# NPM更新とビルド
npm install
npm run build

# マイグレーション実行
php artisan migrate --force

# キャッシュクリア
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# キャッシュ再生成
php artisan config:cache
php artisan route:cache
php artisan view:cache

# キューワーカー再起動
sudo supervisorctl restart invoicepilot-worker:*

# メンテナンスモード解除
php artisan up
```

### トラブルシューティング

#### 問題: "500 Internal Server Error"

```bash
# ログ確認
tail -f storage/logs/laravel.log

# パーミッション確認
ls -la storage bootstrap/cache

# キャッシュクリア
php artisan cache:clear
php artisan config:clear
```

#### 問題: CSPエラー（ブラウザコンソール）

本番環境では `ContentSecurityPolicy` ミドルウェアが自動的に厳格なCSPを設定します。開発環境とは異なり、`unsafe-eval` や `unsafe-inline` は許可されません。

#### 問題: メール送信失敗

```bash
# メール設定テスト
php artisan tinker
```

```php
Mail::raw('Test email', function ($message) {
    $message->to('test@example.com')->subject('Test');
});
```

```bash
# ログ確認
tail -f storage/logs/laravel.log
```

### パフォーマンス最適化

```bash
# OPcache有効化（php.ini）
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0  # 本番環境のみ

# Redis設定
# config/database.php でRedis設定確認

# データベースインデックス最適化
php artisan db:show
```

## 🧪 テスト実行

```bash
# 全テスト実行
php artisan test

# Lintチェック
./vendor/bin/pint --test

# 型チェック（フロントエンド）
npm run type-check
```

## 📊 実装状況

### ✅ 完全実装済み（動作確認可能）

#### バックエンド基盤
- [x] Laravel 11プロジェクト初期化
- [x] Laravel Breeze (Inertia + Vue + TypeScript)
- [x] RBAC（3ロール: admin/accounting/sales）
- [x] ロール検証ミドルウェア
- [x] Gate定義

#### データベース
- [x] 全9テーブルマイグレーション
- [x] 全モデル + リレーション + ヘルパーメソッド
- [x] 外部キー制約・インデックス
- [x] SoftDeletes対応

#### Services & Actions
- [x] NumberingService（採番ロジック）
- [x] RecalculateInvoiceBalanceAction
- [x] ChangeInvoiceStatusAction
- [x] CreateInvoiceFromQuotationAction
- [x] SendReminderAction

#### Client機能（完全実装済み）
- [x] ClientController（CRUD）
- [x] Routes定義
- [x] StoreClientRequest（バリデーション）
- [x] UpdateClientRequest（バリデーション）
- [x] ClientPolicy（認可）
- [x] 監査ログ統合

### 🚧 骨格実装済み（拡張が必要）

- [x] QuotationPolicy, InvoicePolicy, PaymentPolicy（作成済み、実装が必要）
- [x] 各種FormRequests（作成済み、バリデーションルール追加が必要）

### 📝 未実装（実装ガイド有り）

#### Quotation機能
- [ ] QuotationController
- [ ] Quotation CRUD Views
- [ ] 明細行エディタコンポーネント
- [ ] 税計算ロジック統合

#### Invoice機能
- [ ] InvoiceController
- [ ] Invoice CRUD Views
- [ ] 見積→請求変換UI
- [ ] ステータス変更UI

#### Payment機能
- [ ] PaymentController
- [ ] 入金登録View/Modal
- [ ] 残高再計算統合

#### Reminder機能
- [ ] ReminderController
- [ ] 督促送信UI
- [ ] メールテンプレート編集

#### PDF出力
- [ ] InvoicePDFController
- [ ] PDFテンプレートView（resources/views/pdf/invoice.blade.php）
- [ ] 日本語フォント設定

#### Dashboard & Reports
- [ ] DashboardController
- [ ] ReportController
- [ ] チャート・グラフコンポーネント

#### テスト
- [ ] Feature Tests
- [ ] Unit Tests
- [ ] Policy Tests

#### CI/CD
- [ ] GitHub Actions ワークフロー

## 🔨 今後の実装手順

### Priority 1: Quotation機能

```bash
# Controller作成
php artisan make:controller QuotationController --resource

# Policy実装
# app/Policies/QuotationPolicy.php を実装

# FormRequests作成
php artisan make:request StoreQuotationRequest
php artisan make:request UpdateQuotationRequest

# Routes追加（routes/web.php）
Route::resource('quotations', QuotationController::class);

# Views作成
# resources/js/Pages/Quotations/Index.vue
# resources/js/Pages/Quotations/Create.vue
# resources/js/Pages/Quotations/Edit.vue
# resources/js/Pages/Quotations/Show.vue
```

**QuotationController実装例:**

```php
use App\Services\NumberingService;
use App\Models\Quotation;

public function store(StoreQuotationRequest $request, NumberingService $numberingService)
{
    DB::transaction(function () use ($request, $numberingService) {
        $quotation = Quotation::create([
            'quotation_no' => $numberingService->generateQuotationNumber(),
            'client_id' => $request->client_id,
            'issue_date' => $request->issue_date,
            'valid_until' => $request->valid_until,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        // Create items and calculate totals
        $subtotal = 0;
        $taxTotal = 0;

        foreach ($request->items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $subtotal += $lineTotal;
            $taxTotal += $lineTotal * ($item['tax_rate'] / 100);

            $quotation->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'],
                'line_total' => $lineTotal,
            ]);
        }

        $quotation->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $subtotal + $taxTotal,
        ]);

        AuditLog::log('created', Quotation::class, $quotation->id, null, $quotation->toArray());

        return $quotation;
    });
}
```

### Priority 2: Invoice機能

InvoiceControllerはQuotationControllerと同様のパターンで実装。追加で以下を実装：

```php
// 見積から請求作成
public function createFromQuotation(Quotation $quotation, CreateInvoiceFromQuotationAction $action)
{
    $invoice = $action->execute($quotation, [
        'issue_date' => now(),
        'due_date' => now()->addDays(30),
    ]);

    return redirect()->route('invoices.show', $invoice);
}

// ステータス変更
public function changeStatus(Invoice $invoice, Request $request, ChangeInvoiceStatusAction $action)
{
    $invoice = $action->execute($invoice, $request->status);
    
    return back()->with('success', 'Status updated successfully.');
}
```

### Priority 3: Payment機能

```php
public function store(StorePaymentRequest $request, RecalculateInvoiceBalanceAction $action)
{
    DB::transaction(function () use ($request, $action) {
        $invoice = Invoice::findOrFail($request->invoice_id);

        // Validate payment amount
        if ($request->amount > $invoice->balance_due) {
            throw new \InvalidArgumentException('Payment amount exceeds balance due');
        }

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'method' => $request->method,
            'reference_no' => $request->reference_no,
            'note' => $request->note,
            'created_by' => auth()->id(),
        ]);

        // Recalculate invoice balance and status
        $action->execute($invoice);

        AuditLog::log('payment_received', Invoice::class, $invoice->id, null, [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
        ]);

        return $payment;
    });
}
```

### Priority 4: PDF出力

```bash
# Controller作成
php artisan make:controller InvoicePDFController

# Blade template作成
# resources/views/pdf/invoice.blade.php
```

**InvoicePDFController実装例:**

```php
use Barryvdh\DomPDF\Facade\Pdf;

public function show(Invoice $invoice)
{
    $invoice->load(['client', 'items', 'creator']);

    $pdf = Pdf::loadView('pdf.invoice', compact('invoice'));

    return $pdf->download("invoice-{$invoice->invoice_no}.pdf");
}
```

**PDFテンプレート例（resources/views/pdf/invoice.blade.php）:**

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>請求書 {{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .invoice-info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total-row { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>請 求 書</h1>
    </div>

    <div class="invoice-info">
        <p><strong>請求番号:</strong> {{ $invoice->invoice_no }}</p>
        <p><strong>発行日:</strong> {{ $invoice->issue_date->format('Y年m月d日') }}</p>
        <p><strong>支払期限:</strong> {{ $invoice->due_date->format('Y年m月d日') }}</p>
    </div>

    <div class="client-info">
        <h3>{{ $invoice->client->company_name }} 御中</h3>
        <p>{{ $invoice->client->address }}</p>
    </div>

    <h3>明細</h3>
    <table>
        <thead>
            <tr>
                <th>品目</th>
                <th>数量</th>
                <th>単価</th>
                <th>税率</th>
                <th>金額</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>¥{{ number_format($item->unit_price) }}</td>
                <td>{{ $item->tax_rate }}%</td>
                <td>¥{{ number_format($item->line_total) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">小計</td>
                <td>¥{{ number_format($invoice->subtotal) }}</td>
            </tr>
            <tr>
                <td colspan="4">消費税</td>
                <td>¥{{ number_format($invoice->tax_total) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="4">合計</td>
                <td>¥{{ number_format($invoice->total) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
```

## 🎨 フロントエンド実装パターン

Client機能のViewsを参考実装として使用できます（実装予定）。以下のパターンで実装してください：

### Index Page（一覧）

```vue
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface Props {
    clients: {
        data: Array<Client>;
        links: any;
        meta: any;
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

function search() {
    router.get(route('clients.index'), { search: searchQuery.value }, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Clients" />
        <!-- 検索フォーム -->
        <!-- テーブル -->
        <!-- ページネーション -->
    </AuthenticatedLayout>
</template>
```

### Create/Edit Page（作成・編集）

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    code: '',
    company_name: '',
    // ...その他のフィールド
});

function submit() {
    form.post(route('clients.store'));
}
</script>

<template>
    <form @submit.prevent="submit">
        <!-- フォームフィールド -->
        <!-- エラー表示 -->
        <!-- 送信ボタン -->
    </form>
</template>
```

## 🧩 主要コンポーネント実装例

### LineItemsEditor.vue（明細行エディタ）

```vue
<script setup lang="ts">
import { ref } from 'vue';

interface LineItem {
    description: string;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    line_total: number;
}

const items = ref<LineItem[]>([
    { description: '', quantity: 1, unit_price: 0, tax_rate: 10, line_total: 0 }
]);

function addItem() {
    items.value.push({ description: '', quantity: 1, unit_price: 0, tax_rate: 10, line_total: 0 });
}

function removeItem(index: number) {
    items.value.splice(index, 1);
}

function calculateLineTotal(item: LineItem) {
    item.line_total = item.quantity * item.unit_price;
}
</script>

<template>
    <div>
        <table>
            <thead>
                <tr>
                    <th>品目</th>
                    <th>数量</th>
                    <th>単価</th>
                    <th>税率</th>
                    <th>小計</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in items" :key="index">
                    <td><input v-model="item.description" /></td>
                    <td><input v-model.number="item.quantity" @input="calculateLineTotal(item)" /></td>
                    <td><input v-model.number="item.unit_price" @input="calculateLineTotal(item)" /></td>
                    <td>
                        <select v-model.number="item.tax_rate">
                            <option :value="0">0%</option>
                            <option :value="8">8%</option>
                            <option :value="10">10%</option>
                        </select>
                    </td>
                    <td>{{ item.line_total }}</td>
                    <td><button @click="removeItem(index)">削除</button></td>
                </tr>
            </tbody>
        </table>
        <button @click="addItem">行を追加</button>
    </div>
</template>
```

## 🔒 セキュリティ

### 実装済みセキュリティ対策

- ✅ CSRF保護（Laravel標準）
- ✅ SQLインジェクション対策（Eloquent ORM使用）
- ✅ XSS対策（Vue + Inertia自動エスケープ）
- ✅ ロールベースアクセス制御
- ✅ ポリシーベース認可
- ✅ FormRequestバリデーション
- ✅ パスワードハッシュ化（bcrypt）

### 今後の推奨対策

- Rate Limiting（Laravel標準機能使用）
- 2FA認証（Laravel Fortify統合）
- API Token認証（必要に応じてSanctum使用）

## 📈 今後の拡張案

### 短期（3-6ヶ月）

1. **定期請求機能**
   - 月次・年次の自動請求作成
   - サブスクリプション管理

2. **ダッシュボード強化**
   - 売上グラフ（Chart.js統合）
   - 未収金レポート
   - 督促状況サマリー

3. **通知機能**
   - 期限超過アラート
   - 入金通知
   - Slack/Email統合

### 中期（6-12ヶ月）

1. **会計ソフト連携**
   - freee API連携
   - MFクラウド連携
   - CSV/XML エクスポート

2. **帳票カスタマイズ**
   - PDFテンプレートエディタ
   - ロゴ・印影追加
   - 複数言語対応

3. **マルチテナント対応**
   - 会社ごとのデータ分離
   - テナント管理画面

### 長期（12ヶ月以上）

1. **API公開**
   - RESTful API
   - Webhook
   - API ドキュメント（Swagger）

2. **モバイルアプリ**
   - React Native
   - Flutter

3. **AI機能**
   - 督促メール自動生成
   - 入金予測
   - 異常検知

## 🤝 コントリビューション

現在はプライベートプロジェクトですが、将来的にOSS化を検討中です。

## 📄 ライセンス

Private License - All Rights Reserved

## 📞 サポート

質問・バグ報告は Issues で受け付けています。

---

**開発状況**: Phase 1（基盤実装）完了 - 2026年2月

**次のマイルストーン**: Phase 2（主要機能実装）- 2026年3月予定
