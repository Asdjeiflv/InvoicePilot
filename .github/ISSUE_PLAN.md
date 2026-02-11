# InvoicePilot 商用導入品質改善 Issues

## P0: Critical（必須）

### Issue #1: 監査ログを Invoice/Payment/Quotation に拡張
**優先度**: P0
**見積時間**: 8h
**依存関係**: なし
**ラベル**: security, audit, P0

**背景**:
現在、監査ログは Client モデルのみ実装されており、金銭取引の中核である Invoice/Payment/Quotation の変更履歴が記録されていない。監査要件を満たすため、全モデルで before/after の差分保存が必須。

**完了条件**:
- [ ] InvoiceObserver を作成し、created/updated/deleted/restored イベントで AuditLog 記録
- [ ] PaymentObserver を作成し、同様に実装
- [ ] QuotationObserver を作成し、同様に実装
- [ ] 監査ログに user_id, ip_address, user_agent を記録
- [ ] Feature テストで監査ログ記録を検証（各モデル 5 ケース以上）

**実装方針**:
```php
// app/Observers/InvoiceObserver.php
class InvoiceObserver
{
    public function updated(Invoice $invoice): void
    {
        $changes = $invoice->getChanges();
        $original = $invoice->getRawOriginal();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'target_type' => Invoice::class,
            'target_id' => $invoice->id,
            'before_json' => json_encode(Arr::only($original, array_keys($changes))),
            'after_json' => json_encode($changes),
            'ip_address' => request()->ip(),
        ]);
    }
}
```

---

### Issue #2: Policy のロール別制御実装
**優先度**: P0
**見積時間**: 6h
**依存関係**: なし
**ラベル**: security, authorization, P0

**背景**:
現在の Policy はすべて `return true;` で全員許可状態。ロール（admin/accounting/sales/auditor）に応じた権限制御が未実装で、セキュリティリスクが高い。

**完了条件**:
- [ ] auditor ロールを追加（閲覧専用、破壊操作不可）
- [ ] InvoicePolicy で create/update/delete をロール別に制御
- [ ] PaymentPolicy で create/update/delete を admin/accounting のみ許可
- [ ] QuotationPolicy で approve/reject を admin のみ許可
- [ ] Feature テストで権限チェックを検証（各 Policy 10 ケース以上）

**実装方針**:
```php
// app/Policies/InvoicePolicy.php
public function create(User $user): bool
{
    return $user->hasAnyRole(['admin', 'sales']);
}

public function update(User $user, Invoice $invoice): bool
{
    // draft のみ編集可能 + ロールチェック
    return $invoice->isEditable() && $user->hasAnyRole(['admin', 'sales']);
}

public function delete(User $user, Invoice $invoice): bool
{
    // admin のみ削除可能
    return $user->isAdmin() && $invoice->status === 'draft';
}

public function viewAny(User $user): bool
{
    // auditor 含む全員閲覧可能
    return true;
}
```

---

### Issue #3: 冪等キー実装（重複処理防止）
**優先度**: P0
**見積時間**: 10h
**依存関係**: なし
**ラベル**: reliability, idempotency, P0

**背景**:
Invoice/Payment 作成時に同じリクエストを 2 回送信すると重複レコードが作成される。ネットワーク遅延時の再送信や、ユーザーの二重クリックで金銭データが破損するリスクがある。

**完了条件**:
- [ ] idempotency_keys テーブル作成（key, user_id, response_json, created_at）
- [ ] IdempotencyMiddleware 作成（POST/PUT/PATCH で自動適用）
- [ ] InvoiceController::store で冪等キーチェック
- [ ] PaymentController::store で冪等キーチェック
- [ ] Feature テストで同一キーの 2 回送信を検証（レスポンスが同一）

**実装方針**:
```php
// app/Http/Middleware/IdempotencyMiddleware.php
public function handle(Request $request, Closure $next)
{
    $key = $request->header('Idempotency-Key');
    if (!$key || !in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
        return $next($request);
    }

    $cached = IdempotencyKey::where('key', $key)
        ->where('user_id', auth()->id())
        ->where('created_at', '>', now()->subHours(24))
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

---

### Issue #4: Invoice/Payment の Optimistic Lock 実装
**優先度**: P0
**見積時間**: 8h
**依存関係**: なし
**ラベル**: concurrency, data-integrity, P0

**背景**:
Payment 追加と Invoice 金額修正が同時発生した場合、後勝ちで balance_due が不正になるリスクがある。version カラムによる楽観的ロックで同時更新を検出する。

**完了条件**:
- [ ] invoices テーブルに version カラム追加（default 1）
- [ ] payments テーブルに version カラム追加
- [ ] UpdateInvoiceRequest に version チェックロジック追加
- [ ] StaleObjectException 例外クラス作成
- [ ] Feature テストで同時更新エラーを検証

**実装方針**:
```php
// database/migrations/xxxx_add_version_to_invoices.php
Schema::table('invoices', function (Blueprint $table) {
    $table->unsignedInteger('version')->default(1);
});

// app/Http/Controllers/InvoiceController.php
public function update(UpdateInvoiceRequest $request, Invoice $invoice)
{
    DB::transaction(function () use ($request, $invoice) {
        $currentVersion = $invoice->version;

        if ($request->version !== $currentVersion) {
            throw new StaleObjectException('This record has been modified by another user.');
        }

        $invoice->fill($request->validated());
        $invoice->version = $currentVersion + 1;
        $invoice->save();
    });
}
```

---

### Issue #5: Reminder 重複送信防止
**優先度**: P0
**見積時間**: 6h
**依存関係**: なし
**ラベル**: notification, idempotency, P0

**背景**:
現在、同じ請求書に対して何度でも Reminder を送信できるため、顧客に迷惑をかけるリスクがある。送信履歴を記録し、一定期間内の重複送信を防止する。

**完了条件**:
- [ ] Reminder モデルに sent_at カラム追加（nullable）
- [ ] SendReminderAction で送信済みチェック（同じ type で 7 日以内の送信を拒否）
- [ ] 管理画面で送信履歴表示（送信日時、送信者、送信結果）
- [ ] Feature テストで重複送信エラーを検証

**実装方針**:
```php
// app/Actions/SendReminderAction.php
public function execute(Invoice $invoice, string $type, User $sentBy): Reminder
{
    // 7日以内の同タイプ送信チェック
    $recentReminder = Reminder::where('invoice_id', $invoice->id)
        ->where('type', $type)
        ->where('sent_at', '>', now()->subDays(7))
        ->first();

    if ($recentReminder) {
        throw new \Exception("A {$type} reminder was already sent on " . $recentReminder->sent_at->format('Y-m-d'));
    }

    // 送信処理
    Mail::raw($body, function ($message) use ($invoice) {
        $message->to($invoice->client->email)->subject($subject);
    });

    return Reminder::create([
        'invoice_id' => $invoice->id,
        'type' => $type,
        'sent_by' => $sentBy->id,
        'sent_at' => now(),
    ]);
}
```

---

### Issue #6: Invoice/Payment CRUD Feature テスト追加
**優先度**: P0
**見積時間**: 12h
**依存関係**: なし
**ラベル**: testing, P0

**背景**:
現在、Invoice と Payment の CRUD 操作に対する Feature テストが存在せず、リグレッションリスクが高い。金銭取引のため、テストカバレッジ必須。

**完了条件**:
- [ ] InvoiceTest::test_can_create_invoice（20 ケース）
- [ ] InvoiceTest::test_can_update_invoice（10 ケース）
- [ ] InvoiceTest::test_can_delete_invoice（5 ケース）
- [ ] InvoiceTest::test_balance_recalculation（部分入金・過入金・同時更新）
- [ ] PaymentTest::test_can_record_payment（15 ケース）
- [ ] PaymentTest::test_overpayment_validation（5 ケース）
- [ ] テストカバレッジ 70% 以上

**実装方針**:
```php
// tests/Feature/InvoiceTest.php
public function test_can_create_invoice_from_quotation(): void
{
    $quotation = Quotation::factory()->create(['status' => 'approved']);

    $response = $this->actingAs(User::factory()->create(['role' => 'sales']))
        ->post(route('invoices.store'), [
            'quotation_id' => $quotation->id,
            'due_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('invoices', [
        'quotation_id' => $quotation->id,
        'status' => 'draft',
    ]);
}

public function test_balance_recalculates_after_partial_payment(): void
{
    $invoice = Invoice::factory()->create(['total' => 10000, 'balance_due' => 10000]);

    Payment::factory()->create(['invoice_id' => $invoice->id, 'amount' => 3000]);

    $invoice->refresh();
    $this->assertEquals(7000, $invoice->balance_due);
    $this->assertEquals('partial_paid', $invoice->status);
}
```

---

### Issue #7: CI/CD パイプライン構築（GitHub Actions）
**優先度**: P0
**見積時間**: 8h
**依存関係**: #6（テスト追加後に実行）
**ラベル**: ci-cd, automation, P0

**背景**:
現在、コード品質チェックとテスト実行が手動で、main マージ前の品質保証が不十分。GitHub Actions で自動化し、品質ゲートを設定する。

**完了条件**:
- [ ] .github/workflows/ci.yml 作成
- [ ] phpunit + coverage 計測（codecov 連携）
- [ ] pint（Laravel コードスタイル）
- [ ] phpstan（level 5 以上）
- [ ] main マージ条件に品質ゲート必須設定

**実装方針**:
```yaml
# .github/workflows/ci.yml
name: CI

on:
  pull_request:
    branches: [main]
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: invoicepilot_test
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - run: composer install
      - run: php artisan test --coverage --min=70

  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: composer install
      - run: ./vendor/bin/pint --test

  static-analysis:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: composer install
      - run: ./vendor/bin/phpstan analyse --level=5
```

---

## P1: High（高優先）

### Issue #8: Mail キュー化 + 再試行機構
**優先度**: P1
**見積時間**: 8h
**依存関係**: なし
**ラベル**: notification, reliability, P1

**背景**:
現在、メール送信は同期実行で、SMTP 障害時にリクエストがタイムアウトする。Queue で非同期化し、失敗時の再試行・DLQ を実装する。

**完了条件**:
- [ ] SendReminderAction を Job 化（SendReminderJob）
- [ ] Queue::push で非同期送信（tries=3, backoff=[60, 300, 900]）
- [ ] failed_jobs テーブルで DLQ 管理
- [ ] 管理画面で失敗ジョブ一覧 + 再実行ボタン
- [ ] Feature テストで再試行ロジックを検証

**実装方針**:
```php
// app/Jobs/SendReminderJob.php
class SendReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1分, 5分, 15分

    public function __construct(
        public Invoice $invoice,
        public string $type,
        public User $sentBy
    ) {}

    public function handle(): void
    {
        $action = new SendReminderAction();
        $action->execute($this->invoice, $this->type, $this->sentBy);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Reminder send failed', [
            'invoice_id' => $this->invoice->id,
            'type' => $this->type,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

---

### Issue #9: Webhook 実装 + 失敗復旧
**優先度**: P1
**見積時間**: 12h
**依存関係**: #8（Queue 基盤）
**ラベル**: integration, reliability, P1

**背景**:
外部システムとの連携のため、Webhook が必要。請求書作成・入金記録・督促送信時に外部 URL へ通知し、失敗時は再試行する。

**完了条件**:
- [ ] webhooks テーブル作成（url, event, secret, is_active）
- [ ] WebhookDelivery テーブル作成（送信履歴、ステータス、再試行回数）
- [ ] InvoiceCreated/PaymentRecorded/ReminderSent イベント
- [ ] WebhookJob で POST 送信（HMAC 署名付き）
- [ ] 管理画面で Webhook 設定 + 送信履歴 + 再送ボタン

**実装方針**:
```php
// app/Events/InvoiceCreated.php
class InvoiceCreated
{
    public function __construct(public Invoice $invoice) {}
}

// app/Listeners/TriggerWebhook.php
class TriggerWebhook
{
    public function handle($event): void
    {
        $webhooks = Webhook::where('event', get_class($event))->where('is_active', true)->get();

        foreach ($webhooks as $webhook) {
            WebhookJob::dispatch($webhook, $event);
        }
    }
}

// app/Jobs/WebhookJob.php
class WebhookJob implements ShouldQueue
{
    public $tries = 3;

    public function handle(): void
    {
        $payload = $this->event->toArray();
        $signature = hash_hmac('sha256', json_encode($payload), $this->webhook->secret);

        $response = Http::withHeaders([
            'X-Webhook-Signature' => $signature,
        ])->post($this->webhook->url, $payload);

        WebhookDelivery::create([
            'webhook_id' => $this->webhook->id,
            'status' => $response->successful() ? 'success' : 'failed',
            'response_code' => $response->status(),
            'response_body' => $response->body(),
        ]);
    }
}
```

---

### Issue #10: 会計連携 CSV エクスポート（freee/マネーフォワード形式）
**優先度**: P1
**見積時間**: 10h
**依存関係**: なし
**ラベル**: accounting, integration, P1

**背景**:
経理部門が freee や マネーフォワードに手入力している。CSV エクスポート機能で自動取込を可能にし、業務効率を向上させる。

**完了条件**:
- [ ] AccountingExportController 作成（GET /accounting/export）
- [ ] freee 形式 CSV（日付, 借方科目, 貸方科目, 金額, 摘要, 部門, 税区分）
- [ ] マネーフォワード形式 CSV（同様の項目）
- [ ] 期間指定（start_date, end_date）
- [ ] docs/accounting-export.md にフォーマット仕様を記載

**実装方針**:
```php
// app/Http/Controllers/AccountingExportController.php
public function export(Request $request)
{
    $invoices = Invoice::whereBetween('created_at', [$request->start_date, $request->end_date])
        ->where('status', '!=', 'draft')
        ->with('items', 'payments')
        ->get();

    $csv = [];
    $csv[] = ['取引日', '借方科目', '借方補助', '借方金額', '貸方科目', '貸方補助', '貸方金額', '摘要', '品目', '部門', '税区分'];

    foreach ($invoices as $invoice) {
        $csv[] = [
            $invoice->issued_at->format('Y/m/d'),
            '売掛金', // 借方科目
            $invoice->client->company_name, // 借方補助
            $invoice->total, // 借方金額
            '売上高', // 貸方科目
            '', // 貸方補助
            $invoice->subtotal, // 貸方金額（税抜）
            "請求書 {$invoice->invoice_no}",
            '', // 品目
            '', // 部門
            '課税売上 10%', // 税区分
        ];
    }

    return response()->streamDownload(function () use ($csv) {
        $file = fopen('php://output', 'w');
        foreach ($csv as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
    }, 'accounting_export.csv');
}
```

---

### Issue #11: バックアップ自動化 + 復元手順
**優先度**: P1
**見積時間**: 8h
**依存関係**: なし
**ラベル**: operations, disaster-recovery, P1

**背景**:
現在、データベースのバックアップが未整備で、障害時の復旧方法が不明。daily バックアップと復元手順を整備する。

**完了条件**:
- [ ] artisan command: backup:database（mysqldump 実行）
- [ ] S3 または ローカルストレージに保存（90 日保持）
- [ ] Cron で毎日 3:00 AM 実行
- [ ] artisan command: backup:restore（指定日のバックアップから復元）
- [ ] docs/runbook.md に復元手順を記載

**実装方針**:
```php
// app/Console/Commands/BackupDatabase.php
class BackupDatabase extends Command
{
    protected $signature = 'backup:database';

    public function handle()
    {
        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $path = storage_path('backups/' . $filename);

        $command = sprintf(
            'mysqldump -u%s -p%s %s > %s',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            $path
        );

        exec($command);

        // S3 へアップロード
        Storage::disk('s3')->put($filename, file_get_contents($path));

        // 90日以上前のバックアップを削除
        $oldBackups = Storage::disk('s3')->files('backups');
        foreach ($oldBackups as $backup) {
            if (Storage::disk('s3')->lastModified($backup) < now()->subDays(90)->timestamp) {
                Storage::disk('s3')->delete($backup);
            }
        }

        $this->info('Backup completed: ' . $filename);
    }
}
```

---

### Issue #12: 監視メトリクス + アラート設定
**優先度**: P1
**見積時間**: 10h
**依存関係**: #8（Queue 実装）
**ラベル**: monitoring, operations, P1

**背景**:
キューの滞留、失敗率、応答時間などの監視が未整備で、障害発生時の検知が遅れる。Prometheus + Grafana または Laravel Horizon でメトリクス監視を実装する。

**完了条件**:
- [ ] Laravel Horizon インストール（Queue 監視 UI）
- [ ] Prometheus exporter 追加（カスタムメトリクス）
- [ ] Grafana ダッシュボード作成（キュー滞留、失敗率、応答時間）
- [ ] アラート設定（失敗率 > 10%、キュー滞留 > 100 件）
- [ ] Slack 通知連携

**実装方針**:
```bash
composer require laravel/horizon
php artisan horizon:install
```

```php
// config/horizon.php
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

---

### Issue #13: auditor ロール追加（閲覧専用）
**優先度**: P1
**見積時間**: 4h
**依存関係**: #2（Policy 実装）
**ラベル**: security, authorization, P1

**背景**:
監査担当者（auditor）が管理画面にアクセスする際、誤って削除・変更できてしまうリスクがある。閲覧専用ロールを追加する。

**完了条件**:
- [ ] User::ROLE_AUDITOR 定数追加
- [ ] すべての Policy で delete/update を auditor に拒否
- [ ] Feature テストで auditor の権限制限を検証

**実装方針**:
```php
// app/Models/User.php
const ROLE_AUDITOR = 'auditor';

public function isAuditor(): bool
{
    return $this->role === self::ROLE_AUDITOR;
}

// app/Policies/InvoicePolicy.php
public function delete(User $user, Invoice $invoice): bool
{
    return !$user->isAuditor() && $user->isAdmin();
}
```

---

### Issue #14: エラーハンドリング統一 + ユーザー向け文言
**優先度**: P1
**見積時間**: 6h
**依存関係**: なし
**ラベル**: ux, error-handling, P1

**背景**:
現在、エラーメッセージが技術的で、ユーザーが理解できない。ユーザー向けの日本語エラーメッセージと、内部ログの分離が必要。

**完了条件**:
- [ ] app/Exceptions/Handler.php でカスタム例外ハンドリング
- [ ] resources/lang/ja/errors.php に日本語メッセージ定義
- [ ] Inertia で flash メッセージ表示
- [ ] 内部ログは Log::error で詳細記録

**実装方針**:
```php
// app/Exceptions/Handler.php
public function render($request, Throwable $e)
{
    if ($e instanceof ValidationException) {
        return back()->withErrors($e->errors())->withInput();
    }

    if ($e instanceof StaleObjectException) {
        Log::error('Optimistic lock failure', ['user_id' => auth()->id(), 'exception' => $e]);
        return back()->with('error', 'このレコードは他のユーザーによって更新されました。ページを再読み込みしてください。');
    }

    // デフォルトハンドリング
    Log::error('Unhandled exception', ['exception' => $e]);
    return back()->with('error', 'エラーが発生しました。管理者にお問い合わせください。');
}
```

---

## P2: Medium（中優先）

### Issue #15: 採番ロジック最適化（sequences テーブル化）
**優先度**: P2
**見積時間**: 8h
**依存関係**: なし
**ラベル**: performance, optimization, P2

**背景**:
現在、NumberingService が全レコードを読み込んで最大番号を抽出しているため、レコード増加時にパフォーマンス劣化する。シーケンステーブルで管理する。

**完了条件**:
- [ ] invoice_number_sequences テーブル作成（year, last_number, type）
- [ ] NumberingService をシーケンステーブル参照に変更
- [ ] トランザクション + lockForUpdate で一意性保証
- [ ] Feature テストで並行処理を検証

**実装方針**:
```php
// database/migrations/xxxx_create_invoice_number_sequences.php
Schema::create('invoice_number_sequences', function (Blueprint $table) {
    $table->id();
    $table->string('type'); // 'invoice' or 'quotation'
    $table->year('year');
    $table->unsignedInteger('last_number')->default(0);
    $table->timestamps();
    $table->unique(['type', 'year']);
});

// app/Services/NumberingService.php
public function generateInvoiceNumber(): string
{
    return DB::transaction(function () {
        $sequence = InvoiceNumberSequence::lockForUpdate()
            ->firstOrCreate(
                ['type' => 'invoice', 'year' => now()->year],
                ['last_number' => 0]
            );

        $sequence->increment('last_number');

        return sprintf('I-%d-%05d', now()->year, $sequence->last_number);
    });
}
```

---

### Issue #16: N+1 クエリ解消 + パフォーマンス最適化
**優先度**: P2
**見積時間**: 8h
**依存関係**: なし
**ラベル**: performance, optimization, P2

**背景**:
一覧画面で N+1 クエリが発生している可能性があり、レスポンス時間が遅延する。Eager Loading で最適化する。

**完了条件**:
- [ ] InvoiceController::index で with('client', 'items', 'payments')
- [ ] PaymentController::index で with('invoice.client')
- [ ] Laravel Debugbar で N+1 検証
- [ ] 一覧画面の P95 応答時間 < 200ms

**実装方針**:
```php
// app/Http/Controllers/InvoiceController.php
public function index(Request $request)
{
    $invoices = Invoice::query()
        ->with(['client', 'items', 'payments', 'quotation'])
        ->when($request->search, function ($query, $search) {
            $query->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($q) => $q->where('company_name', 'like', "%{$search}%"));
        })
        ->latest()
        ->paginate(20);

    return Inertia::render('Invoices/Index', [
        'invoices' => $invoices,
    ]);
}
```

---

### Issue #17: UX 改善（請求作成～送付フロー短縮）
**優先度**: P2
**見積時間**: 12h
**依存関係**: なし
**ラベル**: ux, frontend, P2

**背景**:
現在、請求書作成 → 確定 → PDF 生成 → メール送信 が別画面で煩雑。一括操作 UI で効率化する。

**完了条件**:
- [ ] 請求書作成画面に「作成して送信」ボタン追加
- [ ] 確定 + PDF 生成 + メール送信 を 1 アクションで実行
- [ ] プレビュー機能追加（送信前に内容確認）
- [ ] ローディング UI + 成功/失敗通知

**実装方針**:
```vue
<!-- resources/js/Pages/Invoices/Create.vue -->
<template>
  <form @submit.prevent="submitAndSend">
    <!-- フォーム内容 -->
    <PrimaryButton>作成して送信</PrimaryButton>
  </form>
</template>

<script setup>
const submitAndSend = () => {
  form.post(route('invoices.create-and-send'), {
    onSuccess: () => {
      toast.success('請求書を作成し、送信しました');
    },
    onError: () => {
      toast.error('エラーが発生しました');
    },
  });
};
</script>
```

```php
// app/Http/Controllers/InvoiceController.php
public function createAndSend(StoreInvoiceRequest $request)
{
    DB::transaction(function () use ($request) {
        $invoice = Invoice::create($request->validated());
        $invoice->update(['status' => 'issued', 'issued_at' => now()]);
        SendInvoiceEmailJob::dispatch($invoice);
    });

    return redirect()->route('invoices.index')->with('success', '請求書を作成し、送信しました');
}
```

---

### Issue #18: 導入デモ用シードデータ作成
**優先度**: P2
**見積時間**: 6h
**依存関係**: なし
**ラベル**: demo, onboarding, P2

**背景**:
営業デモや導入研修で使用する、実業務フローを再現したシードデータが必要。

**完了条件**:
- [ ] DemoSeeder 作成（Clients 10 件、Quotations 20 件、Invoices 30 件、Payments 50 件）
- [ ] 現実的なステータス分布（draft 10%, issued 50%, paid 30%, overdue 10%）
- [ ] 督促履歴も含む
- [ ] README にデモデータ投入手順を記載

**実装方針**:
```php
// database/seeders/DemoSeeder.php
public function run(): void
{
    $clients = Client::factory(10)->create();

    foreach ($clients as $client) {
        $quotations = Quotation::factory(2)->create(['client_id' => $client->id]);

        foreach ($quotations as $quotation) {
            $invoice = Invoice::factory()->create([
                'client_id' => $client->id,
                'quotation_id' => $quotation->id,
                'status' => fake()->randomElement(['issued', 'partial_paid', 'paid', 'overdue']),
            ]);

            // 80% の請求書に入金記録
            if (fake()->boolean(80)) {
                Payment::factory()->create([
                    'invoice_id' => $invoice->id,
                    'amount' => fake()->numberBetween(1000, $invoice->total),
                ]);
            }
        }
    }
}
```

---

### Issue #19: API エンドポイント実装（routes/api.php）
**優先度**: P2
**見積時間**: 12h
**依存関係**: なし
**ラベル**: api, integration, P2

**背景**:
外部システムとの連携や、モバイルアプリ開発のため、RESTful API が必要。

**完了条件**:
- [ ] routes/api.php に CRUD エンドポイント追加
- [ ] Laravel Sanctum で API トークン認証
- [ ] API Resource で JSON レスポンス整形
- [ ] Swagger/OpenAPI ドキュメント生成
- [ ] Postman Collection エクスポート

**実装方針**:
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('invoices', Api\InvoiceController::class);
    Route::apiResource('payments', Api\PaymentController::class);
    Route::apiResource('clients', Api\ClientController::class);
});

// app/Http/Controllers/Api/InvoiceController.php
class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::with('client', 'items')->paginate(50);
        return InvoiceResource::collection($invoices);
    }

    public function show(Invoice $invoice)
    {
        return new InvoiceResource($invoice->load('client', 'items', 'payments'));
    }

    public function store(StoreInvoiceRequest $request)
    {
        $invoice = Invoice::create($request->validated());
        return new InvoiceResource($invoice);
    }
}

// app/Http/Resources/InvoiceResource.php
class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'client' => new ClientResource($this->whenLoaded('client')),
            'total' => $this->total,
            'balance_due' => $this->balance_due,
            'status' => $this->status,
            'issued_at' => $this->issued_at?->toISOString(),
            'due_date' => $this->due_date?->toISOString(),
        ];
    }
}
```

---

### Issue #20: ドキュメント一式作成
**優先度**: P2
**見積時間**: 10h
**依存関係**: すべての実装完了後
**ラベル**: documentation, P2

**背景**:
商用導入のため、アーキテクチャ、運用手順、セキュリティ方針のドキュメントが必須。

**完了条件**:
- [ ] docs/architecture.md（設計方針・境界・トレードオフ）
- [ ] docs/runbook.md（障害対応、復旧、連絡フロー）
- [ ] docs/security.md（秘密情報管理、権限、監査ログ方針）
- [ ] docs/accounting-export.md（CSV 仕様、項目定義、エラー時対処）
- [ ] CHANGELOG.md 追加（全変更履歴）
- [ ] README.md を営業向けにリライト

**実装方針**:
```markdown
# docs/architecture.md

## アーキテクチャ概要
InvoicePilot は Laravel + Vue3 + Inertia のモノリシック構成です。

### レイヤー構成
- **Presentation**: Inertia + Vue3 (resources/js)
- **Application**: Controllers + Requests (app/Http)
- **Domain**: Models + Actions + Services (app/Models, app/Actions)
- **Infrastructure**: Database + Queue + Mail

### 主要な設計判断
1. **採番ロジック**: シーケンステーブルで年度別管理（並行処理対応）
2. **監査ログ**: Observer パターンで全モデルの変更追跡
3. **冪等性**: Idempotency Key パターンで重複処理防止
4. **通知**: Queue で非同期送信 + 再試行 3 回

### トレードオフ
- **モノリシック vs マイクロサービス**: 初期はモノリシックで速度重視、将来的に分割可能な設計
- **同期 vs 非同期**: メール/Webhook は非同期、CRUD は同期（UX 優先）
```

---

## Issue 作成コマンド

以下のスクリプトで GitHub Issues を一括作成できます：

```bash
#!/bin/bash

# Issue #1
gh issue create \
  --title "[P0] 監査ログを Invoice/Payment/Quotation に拡張" \
  --label "security,audit,P0" \
  --body "$(cat <<EOF
**背景**: 現在、監査ログは Client モデルのみ実装されており、金銭取引の中核である Invoice/Payment/Quotation の変更履歴が記録されていない。

**完了条件**:
- [ ] InvoiceObserver を作成
- [ ] PaymentObserver を作成
- [ ] QuotationObserver を作成
- [ ] Feature テストで検証
EOF
)"

# 以下、Issue #2〜#20 を同様に作成
```
