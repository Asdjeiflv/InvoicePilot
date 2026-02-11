# アーキテクチャドキュメント

## システム概要

InvoicePilot は Laravel + Vue3 + Inertia の構成で構築された、請求書管理システムです。見積作成から請求書発行、入金管理、督促送信までのワークフローを一元管理します。

## アーキテクチャ原則

1. **単一責任の原則**: Controller はルーティング、Action はビジネスロジック、Model はデータ操作
2. **疎結合**: Service/Action パターンで再利用性を高める
3. **監査性**: すべての金銭取引を AuditLog で追跡
4. **冪等性**: 重複処理を防止（Idempotency Key パターン）
5. **整合性**: トランザクション + Optimistic Lock で同時更新を制御

## システム構成図

```
┌─────────────────────────────────────────────────────────────┐
│                        ユーザー                              │
└───────────────────────┬─────────────────────────────────────┘
                        │ HTTPS
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                    Web サーバー (Nginx/Apache)               │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                  Laravel アプリケーション                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Controllers  │→ │   Actions    │→ │   Models     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Policies   │  │  Observers   │  │   Services   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└───────────────────────┬─────────────────────────────────────┘
                        │
          ┌─────────────┼─────────────┐
          │             │             │
          ▼             ▼             ▼
    ┌─────────┐   ┌─────────┐   ┌─────────┐
    │  MySQL  │   │  Queue  │   │  Cache  │
    │(Primary)│   │ Worker  │   │ (Redis) │
    └─────────┘   └─────────┘   └─────────┘
          │
          ▼
    ┌─────────┐
    │ Backup  │
    │  (S3)   │
    └─────────┘
```

## レイヤー構成

### 1. Presentation Layer
- **Inertia.js**: サーバーサイドレンダリング（SSR）のような UX
- **Vue 3 (Composition API)**: リアクティブな UI
- **Tailwind CSS**: ユーティリティファーストの CSS

### 2. Application Layer
- **Controllers**: ルーティングとリクエスト/レスポンスの処理
- **Requests**: バリデーション（FormRequest パターン）
- **Resources**: API レスポンスの整形（JSON Resource）
- **Middleware**: 認証、CSRF、権限チェック

### 3. Domain Layer
- **Models**: Eloquent ORM によるデータ操作
- **Actions**: ビジネスロジックのカプセル化
- **Services**: 採番、外部連携などの共通処理
- **Policies**: 認可ロジック（ロールベースアクセス制御）
- **Observers**: 監査ログ自動記録

### 4. Infrastructure Layer
- **Database**: MySQL 8.0
- **Queue**: Laravel Queue（database ドライバ）
- **Cache**: Redis または database
- **Mail**: SMTP または SES
- **Storage**: ローカルディスク または S3

## 主要コンポーネント

### 1. 採番システム（NumberingService）

**目的**: 請求書番号・見積番号の一意性を保証

**実装方式**:
```php
// I-2026-00001 形式
public function generateInvoiceNumber(): string
{
    return DB::transaction(function () {
        $sequence = InvoiceNumberSequence::lockForUpdate()
            ->firstOrCreate(['year' => now()->year], ['last_number' => 0]);
        
        $sequence->increment('last_number');
        
        return sprintf('I-%d-%05d', now()->year, $sequence->last_number);
    });
}
```

**トレードオフ**:
- ✅ 一意性保証（トランザクション + lockForUpdate）
- ✅ 年度別リセット
- ⚠️ シーケンステーブル方式に移行予定（パフォーマンス向上）

### 2. 監査ログ（AuditLog + Observers）

**目的**: すべての重要操作を記録し、変更履歴を追跡

**実装方式**:
```php
// ClientObserver, InvoiceObserver, PaymentObserver, QuotationObserver
public function updated($model): void
{
    $changes = $model->getChanges();
    $original = $model->getRawOriginal();
    
    AuditLog::create([
        'user_id' => auth()->id(),
        'action' => 'updated',
        'target_type' => get_class($model),
        'target_id' => $model->id,
        'before_json' => json_encode(Arr::only($original, array_keys($changes))),
        'after_json' => json_encode($changes),
        'ip_address' => request()->ip(),
    ]);
}
```

**保存データ**:
- **before_json**: 変更前の値（差分のみ）
- **after_json**: 変更後の値（差分のみ）
- **user_id**: 変更者（nullable - システム操作の場合）
- **ip_address**: アクセス元 IP

### 3. 入金消込ロジック（RecalculateInvoiceBalanceAction）

**目的**: 入金記録後に請求書の残高とステータスを自動更新

**フロー**:
```
Payment 作成
  ↓
RecalculateInvoiceBalanceAction::execute()
  ↓
balance_due = total - sum(payments.amount)
  ↓
ステータス判定:
  - balance_due == 0 → 'paid'
  - balance_due > 0 && < total → 'partial_paid'
  - balance_due > 0 && due_date < today → 'overdue'
  - それ以外 → 'issued'
```

**同時更新対策**:
- Optimistic Lock（version カラム）で競合検出
- トランザクションで原子性を保証

### 4. 冪等性（Idempotency Key）

**目的**: ネットワーク遅延時の重複リクエストを防止

**実装方式** （将来実装）:
```php
// IdempotencyMiddleware
public function handle(Request $request, Closure $next)
{
    $key = $request->header('Idempotency-Key');
    
    $cached = IdempotencyKey::where('key', $key)
        ->where('user_id', auth()->id())
        ->first();
    
    if ($cached) {
        return response()->json(json_decode($cached->response_json), 200);
    }
    
    $response = $next($request);
    
    if ($response->isSuccessful()) {
        IdempotencyKey::create([
            'key' => $key,
            'user_id' => auth()->id(),
            'response_json' => $response->getContent(),
        ]);
    }
    
    return $response;
}
```

### 5. ロールベースアクセス制御（RBAC）

**ロール定義**:
- **admin**: 全操作可能
- **accounting**: 入金管理、督促送信、報告閲覧
- **sales**: 見積・請求作成、draft 編集
- **auditor**: 全データ閲覧のみ（書き込み不可）

**Policy 実装例**:
```php
// InvoicePolicy
public function update(User $user, Invoice $invoice): bool
{
    if ($user->isAuditor()) return false;
    if ($user->isAdmin()) return true;
    if ($user->isSales() && $invoice->status === 'draft') return true;
    if ($user->isAccounting() && in_array($invoice->status, ['issued', 'partial_paid', 'overdue'])) return true;
    
    return false;
}
```

## データモデル

### ER図（簡易版）

```
┌──────────┐       ┌──────────────┐       ┌──────────┐
│  Client  │──1:N──│  Quotation   │──1:N──│ Invoice  │
└──────────┘       └──────────────┘       └──────────┘
                           │                     │
                           │ 1:N                 │ 1:N
                           ▼                     ▼
                   ┌────────────────┐    ┌──────────┐
                   │ QuotationItem  │    │ Payment  │
                   └────────────────┘    └──────────┘
                                               │ 1:N
                                               ▼
                                        ┌──────────┐
                                        │ Reminder │
                                        └──────────┘
```

### 主要テーブル

| テーブル | 役割 | 重要カラム |
|---------|------|----------|
| **clients** | 顧客情報 | code (unique), company_name, payment_terms_days |
| **quotations** | 見積 | quotation_no (unique), status, total, subtotal |
| **invoices** | 請求書 | invoice_no (unique), status, total, balance_due, version |
| **payments** | 入金記録 | amount, payment_date, invoice_id |
| **reminders** | 督促履歴 | type (soft/normal/final), sent_at |
| **audit_logs** | 監査ログ | target_type, target_id, before_json, after_json |

## 主要な設計判断

### 1. モノリシック vs マイクロサービス
**選択**: モノリシック（単一 Laravel アプリケーション）

**理由**:
- 初期開発速度の優先
- チーム規模が小さい（1-5人）
- トランザクション整合性が重要
- 将来的に分割可能な設計（Action パターン）

### 2. 同期 vs 非同期（Queue）
**選択**: メール・Webhook は非同期、CRUD は同期

**理由**:
- CRUD: ユーザーに即座にフィードバック必要
- メール: 失敗時の再試行が必要、UX への影響は小さい
- Webhook: 外部システムのタイムアウトを回避

### 3. Optimistic Lock vs Pessimistic Lock
**選択**: Optimistic Lock（version カラム）

**理由**:
- 同時更新の頻度が低い
- Pessimistic Lock はデッドロックリスク
- ユーザーに「他のユーザーが編集中」を明示

### 4. Soft Delete の採用
**選択**: すべての主要モデルで Soft Delete

**理由**:
- 誤削除からの復旧が容易
- 監査ログとの整合性
- 請求番号の連番維持（削除済みも番号消費）

## パフォーマンス最適化

### 1. データベースインデックス
```sql
-- invoices テーブル
CREATE INDEX idx_invoices_status ON invoices(status);
CREATE INDEX idx_invoices_due_date ON invoices(due_date);
CREATE INDEX idx_invoices_client_status ON invoices(client_id, status);

-- payments テーブル
CREATE INDEX idx_payments_invoice ON payments(invoice_id);

-- audit_logs テーブル
CREATE INDEX idx_audit_logs_target ON audit_logs(target_type, target_id);
```

### 2. Eager Loading
```php
// N+1 クエリ回避
$invoices = Invoice::with(['client', 'items', 'payments'])->paginate(20);
```

### 3. キャッシュ戦略
- **設定キャッシュ**: `php artisan config:cache`
- **ルートキャッシュ**: `php artisan route:cache`
- **ビューキャッシュ**: `php artisan view:cache`
- **クエリキャッシュ**（将来実装）: Redis で頻繁にアクセスされるデータをキャッシュ

## セキュリティ

### 認証
- Laravel Breeze（セッションベース）
- CSRF トークン検証

### 認可
- Policy による細粒度制御
- ロールベースアクセス制御（RBAC）

### データ保護
- パスワードハッシュ: bcrypt
- HTTPS 強制（本番環境）
- SQL インジェクション対策: Eloquent ORM

詳細は [docs/security.md](./security.md) を参照。

## 運用

### バックアップ
- データベース: 毎日 3:00 AM 自動バックアップ
- 保持期間: 90 日
- 保存先: S3 または暗号化ストレージ

### 監視
- Laravel Horizon: キュー監視
- Prometheus + Grafana: メトリクス可視化
- ログ監視: CloudWatch Logs または ELK Stack

詳細は [docs/runbook.md](./runbook.md) を参照。

## 今後の拡張計画

### フェーズ2（3-6ヶ月）
- [ ] API エンドポイント（RESTful API）
- [ ] 会計ソフト連携（freee/マネーフォワード）
- [ ] 2FA 認証
- [ ] レポートダッシュボード

### フェーズ3（6-12ヶ月）
- [ ] 多通貨対応
- [ ] 多言語対応（i18n）
- [ ] モバイルアプリ（API 経由）
- [ ] 機械学習による入金予測

---

最終更新: 2026-02-11  
次回レビュー予定: 2026-05-11
