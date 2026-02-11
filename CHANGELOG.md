# Changelog

All notable changes to InvoicePilot will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added - 商用導入品質改善（2026-02-11）

#### セキュリティ・監査性
- 監査ログを Invoice/Payment/Quotation に拡張
  - すべての金銭取引の変更履歴を記録（before/after JSON 形式）
  - IP アドレス記録による不正アクセス追跡
  - Observer パターンで自動記録
- Policy のロール別制御実装
  - admin: 全操作可能
  - accounting: 入金管理・督促送信のみ
  - sales: 見積・請求作成（draft のみ編集）
  - auditor: 閲覧専用（新規追加）
- User モデルに `isAuditor()` メソッド追加

#### 信頼性・整合性
- Optimistic Lock 実装（将来実装予定）
  - invoices.version カラム追加
  - 同時更新時の StaleObjectException 例外
- 冪等キー実装（将来実装予定）
  - idempotency_keys テーブル
  - 重複リクエスト防止 Middleware

#### テスト
- AuditLogTest 追加（10 テストケース）
  - Invoice/Payment/Quotation の監査ログ記録検証
  - IP アドレス記録検証
  - updated_at のみ変更時は記録しないことを検証
- InvoicePolicyTest 追加（10 テストケース）
  - ロール別の作成・更新・削除権限検証
  - auditor の書き込み制限検証
- PaymentPolicyTest 追加（3 テストケース）
  - admin/accounting のみ入金操作可能を検証

#### CI/CD
- GitHub Actions ワークフロー追加（.github/workflows/ci.yml）
  - lint: Laravel Pint によるコードスタイルチェック
  - static-analysis: PHPStan level 5 による静的解析
  - test: PHPUnit テスト + カバレッジ計測（最低 70%）
  - security: composer audit による脆弱性スキャン
  - all-checks-passed: すべてのチェックが通過したかを検証
- PR テンプレート追加（.github/pull_request_template.md）
- Issue テンプレート追加
  - bug_report.md: バグ報告用
  - feature_request.md: 機能提案用

#### ドキュメント
- docs/security.md 追加
  - 認証・認可方針、監査ログ、データ保護、脅威モデル
  - セキュリティチェックリスト、インシデント対応フロー
- docs/runbook.md 追加
  - 日常運用、バックアップ・復元手順、障害対応
  - メンテナンス手順、監視項目、連絡フロー
- docs/architecture.md 追加
  - システム構成図、レイヤー構成、主要コンポーネント
  - ER 図、設計判断のトレードオフ、今後の拡張計画
- .github/ISSUE_PLAN.md 追加
  - 商用導入品質改善のための 20 Issues 計画

### Changed

#### Policy 変更（破壊的変更）
- **InvoicePolicy**: 全員許可 → ロール別制御
  - `create()`: admin/sales のみ
  - `update()`: admin は常に可、sales は draft のみ、accounting は issued/partial_paid/overdue のみ
  - `delete()`: admin のみ、かつ draft のみ
  - `issue()`, `cancel()` メソッド追加
- **PaymentPolicy**: 全員許可 → admin/accounting のみ
  - `create()`, `update()`: admin/accounting のみ
  - `delete()`: admin のみ
- **QuotationPolicy**: 全員許可 → ロール別制御
  - `create()`: admin/sales のみ
  - `update()`: admin は常に可、sales は draft のみ
  - `approve()`, `reject()`: admin のみ
- **ClientPolicy**: 全員許可 → admin/sales のみ作成・更新可能

#### Observer 追加
- InvoiceObserver 登録（AppServiceProvider）
- PaymentObserver 登録（AppServiceProvider）
- QuotationObserver 登録（AppServiceProvider）

### Migration Guide

#### ロール別制御への移行
既存のユーザーに適切なロールを設定してください：

```php
// admin に昇格
User::where('email', 'admin@example.com')->update(['role' => 'admin']);

// 経理担当を accounting に設定
User::where('email', 'accounting@example.com')->update(['role' => 'accounting']);

// 営業担当を sales に設定
User::where('email', 'sales@example.com')->update(['role' => 'sales']);

// 監査担当を auditor に設定（新規）
User::where('email', 'auditor@example.com')->update(['role' => 'auditor']);
```

#### 監査ログの確認
すべての Invoice/Payment/Quotation の操作が自動的に記録されるようになりました。
監査ログは `audit_logs` テーブルで確認できます：

```sql
SELECT * FROM audit_logs 
WHERE target_type = 'App\\Models\\Invoice' 
ORDER BY created_at DESC 
LIMIT 10;
```

---

## [1.0.0] - 2026-01-01

### Added - 初期リリース
- 顧客管理（CRUD）
- 見積作成・承認
- 請求書作成・発行
- 入金管理・消込
- 督促送信（soft/normal/final）
- 監査ログ（Client のみ）
- ロール管理（admin/accounting/sales）
- 採番システム（I-YYYY-00001 形式）

### Technical Stack
- Laravel 11.x
- Vue 3 (Composition API)
- Inertia.js
- Tailwind CSS
- MySQL 8.0
- PHP 8.2+

---

## Legend
- `Added`: 新機能
- `Changed`: 既存機能の変更
- `Deprecated`: 非推奨化（将来削除予定）
- `Removed`: 削除された機能
- `Fixed`: バグ修正
- `Security`: セキュリティ関連の修正
