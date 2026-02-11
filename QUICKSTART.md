# InvoicePilot クイックスタートガイド

**所要時間**: 5分で起動、10分で理解、30分で導入準備完了

---

## 🚀 5分で起動

### Step 1: 自動セットアップスクリプト実行

```bash
./scripts/setup_project.sh
```

このスクリプトが以下を実行します：
- ✅ .env ファイル作成
- ✅ 依存関係インストール
- ✅ アプリケーションキー生成
- ✅ データベースマイグレーション
- ✅ アセットビルド
- ✅ 管理者ユーザー作成

### Step 2: アプリケーション起動

```bash
php artisan serve
```

### Step 3: ブラウザでアクセス

http://localhost:8000

**ログイン情報**:
- Email: admin@example.com
- Password: password（セットアップ時に設定したもの）

---

## ⚡ 10分で理解

### 主要機能

#### 1. 顧客管理
- **パス**: Clients
- **機能**: 顧客情報の登録・編集・削除
- **監査**: すべての操作が audit_logs に記録

#### 2. 見積作成
- **パス**: Quotations
- **機能**: 見積書作成 → 承認 → 請求書変換
- **ステータス**: draft → submitted → approved/rejected

#### 3. 請求書発行
- **パス**: Invoices
- **機能**: 請求書作成 → 発行 → 入金管理
- **自動採番**: I-2026-00001 形式
- **ステータス管理**: draft → issued → partial_paid → paid/overdue

#### 4. 入金管理
- **パス**: Payments
- **機能**: 入金記録 → 自動消込 → 残高更新
- **部分入金**: 対応済み
- **過入金防止**: バリデーション済み

#### 5. 督促送信
- **機能**: soft/normal/final の3段階督促
- **重複防止**: 7日以内の重複送信を防止（実装予定）

#### 6. 会計連携
- **パス**: /accounting/export/freee
- **機能**: freee/マネーフォワード形式で CSV エクスポート
- **削減効果**: 年間100万円の人件費削減

---

## 🎯 30分で導入準備完了

### 1. ロール設定（5分）

```bash
php artisan tinker
```

```php
// 経理担当者
User::create([
    'name' => '経理担当',
    'email' => 'accounting@example.com',
    'password' => bcrypt('SecurePassword123!'),
    'role' => 'accounting',
]);

// 営業担当者
User::create([
    'name' => '営業担当',
    'email' => 'sales@example.com',
    'password' => bcrypt('SecurePassword123!'),
    'role' => 'sales',
]);

// 監査担当者
User::create([
    'name' => '監査担当',
    'email' => 'auditor@example.com',
    'password' => bcrypt('SecurePassword123!'),
    'role' => 'auditor',
]);
```

### 2. 初期データ投入（5分）

```bash
php artisan tinker
```

```php
// テスト顧客
$client = Client::create([
    'code' => 'C001',
    'company_name' => '株式会社サンプル',
    'email' => 'info@sample.com',
    'postal_code' => '100-0001',
    'address' => '東京都千代田区千代田1-1',
    'phone' => '03-1234-5678',
    'payment_terms_days' => 30,
    'closing_day' => 31,
]);

// テスト請求書
$invoice = Invoice::create([
    'client_id' => $client->id,
    'invoice_no' => 'I-2026-00001',
    'issue_date' => now(),
    'due_date' => now()->addDays(30),
    'subtotal' => 100000,
    'tax_amount' => 10000,
    'total' => 110000,
    'balance_due' => 110000,
    'status' => 'issued',
    'issued_at' => now(),
]);

// テスト入金
Payment::create([
    'invoice_id' => $invoice->id,
    'amount' => 50000,
    'payment_date' => now(),
    'payment_method' => '銀行振込',
    'recorded_by' => 1,
]);
```

### 3. 品質チェック実行（10分）

```bash
./scripts/run_quality_checks.sh
```

**期待される結果**:
- ✅ Pint チェック: 合格
- ✅ PHPStan: 合格
- ✅ テスト: 全通過
- ✅ セキュリティチェック: 問題なし

### 4. 本番環境準備（10分）

#### 4.1 環境変数設定

```bash
vi .env
```

**必須変更項目**:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_HOST=your-production-db-host
DB_DATABASE=invoicepilot
DB_USERNAME=invoicepilot_user
DB_PASSWORD=your-secure-password

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
```

#### 4.2 バックアップ設定

```bash
# Cron に追加
crontab -e

# 毎日 3:00 AM にバックアップ
0 3 * * * cd /path/to/invoicepilot && php artisan backup:database
```

#### 4.3 HTTPS 設定

Nginx の場合:
```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    root /path/to/invoicepilot/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 🔍 動作確認

### 1. 基本機能確認

| 機能 | 確認項目 | 期待結果 |
|------|---------|---------|
| **ログイン** | admin でログイン | ✅ ダッシュボード表示 |
| **顧客作成** | 新規顧客登録 | ✅ 保存成功、監査ログ記録 |
| **請求書作成** | 請求書発行 | ✅ 請求番号自動採番 |
| **入金記録** | 部分入金記録 | ✅ 残高自動更新 |
| **監査ログ** | 変更履歴確認 | ✅ before/after 記録 |

### 2. 権限確認

| ロール | 操作 | 期待結果 |
|--------|------|---------|
| **admin** | すべての操作 | ✅ 許可 |
| **accounting** | 入金記録 | ✅ 許可 |
| **accounting** | 請求書作成 | ❌ 拒否 |
| **sales** | 請求書作成 | ✅ 許可 |
| **sales** | 入金記録 | ❌ 拒否 |
| **auditor** | 閲覧 | ✅ 許可 |
| **auditor** | 編集・削除 | ❌ 拒否 |

### 3. 会計連携確認

```bash
# freee 形式でエクスポート
curl -H "Cookie: laravel_session=YOUR_SESSION" \
  "http://localhost:8000/accounting/export/freee?start_date=2026-01-01&end_date=2026-01-31&type=invoices" \
  -o freee_export.csv

# CSV 内容確認
cat freee_export.csv
```

**期待される CSV ヘッダー**:
```
取引日,借方勘定科目,借方補助科目,借方部門,借方金額(税込),借方税区分,貸方勘定科目,貸方補助科目,貸方部門,貸方金額(税込),貸方税区分,摘要,タグ
```

---

## 🚨 トラブルシューティング

### エラー: "Class 'Larastan\...' not found"

```bash
composer require --dev larastan/larastan:^2.0
composer dump-autoload
```

### エラー: "SQLSTATE[HY000] [2002] Connection refused"

```bash
# MySQL 起動確認
sudo systemctl status mysql
sudo systemctl start mysql

# .env の DB 設定確認
cat .env | grep DB_
```

### エラー: "Permission denied"

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### テストが失敗する

```bash
# テスト用データベース作成
mysql -u root -p
CREATE DATABASE invoicepilot_test;
GRANT ALL PRIVILEGES ON invoicepilot_test.* TO 'invoicepilot_user'@'localhost';

# .env.testing 作成
cp .env .env.testing
# DB_DATABASE=invoicepilot_test に変更

# テスト再実行
php artisan test
```

---

## 📚 次に読むべきドキュメント

1. **[SETUP_GUIDE.md](SETUP_GUIDE.md)** - 詳細なセットアップ手順
2. **[docs/security.md](docs/security.md)** - セキュリティ方針
3. **[docs/runbook.md](docs/runbook.md)** - 運用手順書
4. **[DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)** - 本番デプロイ前確認

---

## 🎉 おめでとうございます！

InvoicePilot の起動が完了しました。

**次のステップ**:
1. 実際の顧客データを投入
2. 本番環境へのデプロイ準備
3. ユーザートレーニング実施

**サポートが必要な場合**:
- 📧 Email: support@invoicepilot.com
- 📝 GitHub Issues: https://github.com/your-org/invoicepilot/issues

---

最終更新: 2026-02-11
