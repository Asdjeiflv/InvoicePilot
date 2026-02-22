# セキュリティガイド

InvoicePilotのセキュリティ実装ドキュメント

## 実装済みセキュリティ機能

### 1. 認証・認可
- RBAC (4ロール: admin/accounting/sales/auditor)
- Policyベース認可
- bcryptパスワードハッシュ化

### 2. 攻撃防御
- CSRF保護 (全POST/PUT/PATCH/DELETE)
- SQLインジェクション防止 (Eloquent ORM)
- XSS防止 (Vue.js自動エスケープ)

### 3. セキュリティヘッダー
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- Content-Security-Policy
- Permissions-Policy

### 4. Rate Limiting
- 60 requests/min (認証済みユーザー)
- IPベース制限 (ゲストユーザー)

### 5. 監査ログ
- 全CRUD操作を記録
- イミュータブル (追記専用)
- 7年保存

### 6. その他
- Idempotency (24時間)
- Optimistic Locking
- Mass Assignment防止

## テスト
- 127テスト、335アサーション
- セキュリティテスト: 11件

詳細は docs/security.md を参照
