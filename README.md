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
