# Contributing to InvoicePilot

InvoicePilot への貢献ありがとうございます！🎉

このガイドでは、プロジェクトへの貢献方法を説明します。

---

## 📋 目次

1. [行動規範](#行動規範)
2. [開発環境のセットアップ](#開発環境のセットアップ)
3. [ブランチ戦略](#ブランチ戦略)
4. [コミットメッセージ](#コミットメッセージ)
5. [プルリクエスト](#プルリクエスト)
6. [コーディング規約](#コーディング規約)
7. [テスト](#テスト)
8. [レビュープロセス](#レビュープロセス)

---

## 行動規範

### 基本原則

- 🤝 **相互尊重**: すべての貢献者を尊重する
- 💬 **建設的なフィードバック**: 批判ではなく改善提案を
- 📚 **継続的な学習**: 新しいアイデアを歓迎する
- 🎯 **品質第一**: 機能よりも品質を優先する

### 禁止事項

- ❌ 攻撃的・差別的な言動
- ❌ スパム・宣伝行為
- ❌ 個人情報の公開
- ❌ セキュリティ脆弱性の公開（security@invoicepilot.com に報告）

---

## 開発環境のセットアップ

### 1. リポジトリのフォーク

```bash
# GitHub でフォークボタンをクリック
# フォークしたリポジトリをクローン
git clone https://github.com/YOUR_USERNAME/invoicepilot.git
cd invoicepilot
```

### 2. 依存関係のインストール

```bash
# プロジェクトセットアップスクリプト実行
./scripts/setup_project.sh
```

### 3. 開発サーバー起動

```bash
php artisan serve
```

---

## ブランチ戦略

### ブランチ命名規則

```
feature/<issue-id>-<short-description>  # 新機能
fix/<issue-id>-<short-description>      # バグ修正
refactor/<short-description>            # リファクタリング
docs/<short-description>                # ドキュメント更新
test/<short-description>                # テスト追加
```

### 例

```bash
# Issue #10 の会計連携機能
git checkout -b feature/10-accounting-export

# Issue #5 の Reminder バグ修正
git checkout -b fix/5-reminder-duplicate

# ドキュメント更新
git checkout -b docs/update-readme
```

---

## コミットメッセージ

### フォーマット

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Type

- **feat**: 新機能
- **fix**: バグ修正
- **docs**: ドキュメント更新
- **style**: コードフォーマット（機能変更なし）
- **refactor**: リファクタリング
- **test**: テスト追加・修正
- **chore**: ビルド・設定変更

### 例

```
feat(accounting): freee 形式 CSV エクスポート追加

会計ソフト freee への連携機能を実装。
- 請求書データを freee 形式で CSV エクスポート
- 税区分・補助科目の自動設定
- 期間指定でのフィルタリング

Closes #10
```

```
fix(reminder): 重複送信防止ロジック追加

同じ請求書に 7 日以内の重複送信を防止。
- sent_at カラムでチェック
- 送信履歴を audit_logs に記録

Fixes #5
```

---

## プルリクエスト

### PR を作成する前に

1. **Issue を作成**: 大きな変更の場合は事前に Issue で議論
2. **最新コードを取得**: `git pull origin main`
3. **テストを実行**: `./scripts/run_quality_checks.sh`
4. **コードスタイル確認**: `./vendor/bin/pint`

### PR テンプレート

PR 作成時に自動的に表示されるテンプレートに従ってください。

**必須項目**:
- [ ] 関連 Issue 番号
- [ ] 変更内容の説明
- [ ] テスト結果
- [ ] スクリーンショット（UI 変更の場合）

### PR タイトル

```
[Type] <short description>
```

**例**:
```
[Feature] 会計連携 CSV エクスポート機能
[Fix] Reminder 重複送信バグ修正
[Docs] README にクイックスタート追加
```

---

## コーディング規約

### PHP (Laravel)

#### PSR-12 準拠

```bash
# Laravel Pint で自動フォーマット
./vendor/bin/pint
```

#### 命名規則

```php
// クラス名: PascalCase
class InvoiceController

// メソッド名: camelCase
public function exportToCsv()

// 変数名: camelCase
$invoiceData = [];

// 定数名: UPPER_SNAKE_CASE
const STATUS_PAID = 'paid';
```

#### ドキュメントコメント

```php
/**
 * Export invoices to CSV format.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function export(Request $request)
{
    // ...
}
```

### JavaScript (Vue 3)

#### 命名規則

```javascript
// コンポーネント名: PascalCase
const InvoiceList = defineComponent({})

// 変数名: camelCase
const invoiceData = ref([])

// 定数名: UPPER_SNAKE_CASE
const MAX_ITEMS = 100
```

### データベース

#### マイグレーション

```php
// テーブル名: 複数形 snake_case
Schema::create('invoice_items', function (Blueprint $table) {
    // カラム名: snake_case
    $table->id();
    $table->foreignId('invoice_id')->constrained();
    $table->string('description');
    $table->decimal('amount', 10, 2);
    $table->timestamps();
});
```

---

## テスト

### テストの種類

1. **Feature Test**: エンドツーエンドの機能テスト
2. **Unit Test**: 個別クラス・メソッドのテスト

### テスト作成

```bash
# Feature Test
php artisan make:test InvoiceExportTest

# Unit Test
php artisan make:test --unit NumberingServiceTest
```

### テスト例

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class InvoiceExportTest extends TestCase
{
    /** @test */
    public function it_exports_invoices_to_freee_format(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($user)
            ->get('/accounting/export/freee?start_date=2026-01-01&end_date=2026-01-31&type=invoices');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
```

### テスト実行

```bash
# すべてのテスト実行
php artisan test

# 特定のテストのみ
php artisan test --filter=InvoiceExportTest

# カバレッジ付き
php artisan test --coverage --min=70
```

---

## レビュープロセス

### レビュー基準

#### 必須項目

- [ ] テストが全通過
- [ ] PHPStan レベル 5 通過
- [ ] Laravel Pint 通過
- [ ] PR テンプレートが埋まっている
- [ ] 関連 Issue がリンクされている

#### 推奨項目

- [ ] カバレッジ 70% 以上
- [ ] パフォーマンスへの影響を確認
- [ ] セキュリティへの影響を確認
- [ ] ドキュメント更新

### レビュアー

- **1名以上の承認**が必要
- **変更が大きい場合**: 2名以上の承認
- **セキュリティ関連**: セキュリティ担当の承認必須

### マージ条件

1. ✅ すべてのチェックが通過
2. ✅ レビュアーの承認
3. ✅ コンフリクト解消済み
4. ✅ main ブランチへのマージ

---

## 質問・サポート

### 質問がある場合

1. **[INDEX.md](../INDEX.md)** でドキュメント検索
2. **GitHub Discussions** で質問
3. **GitHub Issues** でバグ報告・機能提案

### コントリビューター

貢献していただいた方は、以下に記載されます：

- [Contributors List](https://github.com/your-org/invoicepilot/graphs/contributors)

---

## ライセンス

InvoicePilot に貢献することで、あなたのコードが MIT ライセンスの下で配布されることに同意したものとみなされます。

---

**ありがとうございます！🎉**

あなたの貢献が InvoicePilot をより良いプロダクトにします。

---

最終更新: 2026年2月11日
