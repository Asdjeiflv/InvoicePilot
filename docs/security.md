# セキュリティドキュメント

## 概要
InvoicePilot のセキュリティ方針、脅威モデル、および対策を記載します。

## 認証・認可

### 認証方式
- Laravel Breeze による標準認証（セッションベース）
- パスワードハッシュ: bcrypt（BCRYPT_ROUNDS=12）
- CSRF トークン保護: すべての POST/PUT/DELETE リクエスト

### ロールベース制御（RBAC）
| ロール | 権限 | 制限事項 |
|--------|------|---------|
| **admin** | 全操作可能 | - |
| **accounting** | 入金管理、督促送信、報告閲覧 | 請求書作成不可 |
| **sales** | 見積・請求作成、draft 編集 | 入金管理不可、削除不可 |
| **auditor** | 全データ閲覧のみ | 作成・更新・削除すべて不可 |

### Policy 実装
- `app/Policies/InvoicePolicy.php`: 請求書の操作権限
- `app/Policies/PaymentPolicy.php`: 入金の操作権限（admin/accounting のみ）
- `app/Policies/QuotationPolicy.php`: 見積の操作権限
- `app/Policies/ClientPolicy.php`: 顧客情報の操作権限

## 監査ログ

### 記録対象
すべての金銭取引および重要データの変更を記録：
- ✅ Client（顧客情報）の作成・更新・削除・復元
- ✅ Invoice（請求書）の作成・更新・削除・復元
- ✅ Payment（入金）の作成・更新・削除
- ✅ Quotation（見積）の作成・更新・削除・復元

### 記録内容
```json
{
  "user_id": 1,
  "action": "updated",
  "target_type": "App\\Models\\Invoice",
  "target_id": 123,
  "before_json": {"total": 10000, "status": "draft"},
  "after_json": {"total": 15000, "status": "issued"},
  "ip_address": "203.0.113.1",
  "created_at": "2026-02-11 10:30:00"
}
```

### 保持期間
- 監査ログは **7年間** 保持（税法対応）
- ストレージ: MySQL データベース + 定期バックアップ

### アクセス権限
- admin: 全監査ログ閲覧可能
- auditor: 全監査ログ閲覧可能（書き込み不可）
- その他: 閲覧不可

## データ保護

### 暗号化
- **通信**: HTTPS 必須（本番環境）
- **保存データ**: 
  - パスワード: bcrypt ハッシュ
  - API トークン（将来実装）: SHA-256 ハッシュ
  - データベース: 平文（アプリケーションレベル暗号化は未実装）

### 秘密情報管理
- `.env` ファイルをバージョン管理に含めない（.gitignore 設定済み）
- `.env.example` には実際の値を記載しない
- API キー、SMTP パスワードは環境変数で管理

### バックアップ
- データベースバックアップ: 毎日 3:00 AM 自動実行
- 保持期間: 90 日
- 保存先: S3 または暗号化されたローカルストレージ
- 復元テスト: 月1回実施を推奨

## 入力検証・サニタイゼーション

### バリデーション
すべてのユーザー入力に対して Laravel Request クラスで検証：
- `app/Http/Requests/StoreInvoiceRequest.php`
- `app/Http/Requests/UpdateInvoiceRequest.php`
- `app/Http/Requests/StorePaymentRequest.php`

### SQLインジェクション対策
- Eloquent ORM 使用（プリペアドステートメント自動適用）
- 生SQLは禁止（必要な場合は `DB::select(?, [$bindings])` 形式）

### XSS 対策
- Blade テンプレート: `{{ }}` で自動エスケープ
- Vue.js: `v-text` または `{{ }}` で自動エスケープ
- 生HTMLが必要な場合: DOMPurify でサニタイズ

### CSRF 対策
- すべてのフォームに `@csrf` トークン埋め込み
- Inertia.js: 自動的に X-XSRF-TOKEN ヘッダー送信

## アクセス制御

### IP制限（オプション）
本番環境では管理画面への IP 制限を推奨：
```php
// app/Http/Middleware/RestrictIpAddress.php
Route::middleware('restrict.ip:203.0.113.0/24')->group(function () {
    // 管理画面ルート
});
```

### レート制限
- ログイン試行: 5回/分（超過時15分ロック）
- API リクエスト: 60回/分（現在未実装）

## 脅威モデル

### 高リスク脅威
1. **不正アクセス**
   - 対策: 強力なパスワード要求、2FA（将来実装）、セッションタイムアウト
   
2. **金額改ざん**
   - 対策: 監査ログ、Optimistic Lock（version カラム）、Policy 制御

3. **入金の重複記録**
   - 対策: 冪等キー（Idempotency Key）、トランザクション制御

4. **データ漏洩**
   - 対策: HTTPS、アクセスログ監視、定期的な脆弱性診断

### 中リスク脅威
1. **セッションハイジャック**
   - 対策: HttpOnly Cookie、Secure フラグ、SameSite=Lax

2. **CSRF攻撃**
   - 対策: Laravel標準のCSRFトークン検証

3. **SQLインジェクション**
   - 対策: Eloquent ORM 使用、プリペアドステートメント

## セキュリティチェックリスト

### 本番デプロイ前
- [ ] `APP_DEBUG=false` に設定
- [ ] `APP_ENV=production` に設定
- [ ] HTTPS 強制（`app/Providers/AppServiceProvider.php` で `URL::forceScheme('https')`）
- [ ] `.env` ファイルの権限を 600 に設定（`chmod 600 .env`）
- [ ] データベースユーザーの権限を最小化（DROP/CREATE 権限削除）
- [ ] 不要なポートをファイアウォールで閉じる
- [ ] エラーログの外部公開を防止
- [ ] composer 依存関係の脆弱性スキャン（`composer audit`）

### 定期実施
- [ ] 監査ログのレビュー（週1回）
- [ ] アクセスログの異常検知（daily）
- [ ] バックアップの復元テスト（月1回）
- [ ] 依存ライブラリの更新（月1回）
- [ ] ペネトレーションテスト（年1回）

## インシデント対応

### 検知
- アプリケーションログ監視: `storage/logs/laravel.log`
- MySQL スロークエリログ
- 異常なログイン試行（Laravel ログイン試行ログ）

### 対応フロー
1. **検知**: アラート受信（Slack/メール）
2. **初期対応**: 影響範囲の特定、緊急パッチ適用
3. **封じ込め**: 該当アカウントの無効化、IP ブロック
4. **調査**: 監査ログ・アクセスログの分析
5. **復旧**: データ復元、システム再起動
6. **報告**: インシデントレポート作成、顧客への通知
7. **再発防止**: 脆弱性修正、モニタリング強化

### 連絡先
- セキュリティ責任者: [管理者メールアドレス]
- 緊急連絡先: [24時間対応可能な連絡先]

## コンプライアンス

### GDPR（該当する場合）
- 個人データの削除要求: `Client::find($id)->forceDelete()`
- データエクスポート: 管理画面から CSV ダウンロード

### 電子帳簿保存法（日本）
- 請求書データの7年間保存
- タイムスタンプ: `created_at`, `updated_at` で記録
- 改ざん防止: 監査ログによる変更履歴の追跡

## セキュリティ連絡先
セキュリティ脆弱性を発見した場合は、以下に報告してください：
- Email: security@example.com
- 対応時間: 24-48時間以内に初期対応

---

最終更新: 2026-02-11  
次回レビュー予定: 2026-05-11
