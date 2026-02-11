# 運用手順書（Runbook）

## 概要
InvoicePilot の日常運用、障害対応、メンテナンス手順を記載します。

## 目次
1. [日常運用](#日常運用)
2. [バックアップ・復元](#バックアップ復元)
3. [障害対応](#障害対応)
4. [メンテナンス](#メンテナンス)
5. [監視](#監視)

---

## 日常運用

### アプリケーション起動
```bash
# Web サーバー起動
php artisan serve --host=0.0.0.0 --port=8000

# キューワーカー起動（メール送信用）
php artisan queue:work --tries=3 --timeout=300

# Laravel Horizon 起動（推奨）
php artisan horizon
```

### ログ確認
```bash
# アプリケーションログ
tail -f storage/logs/laravel.log

# エラーのみ抽出
grep ERROR storage/logs/laravel.log

# 本日のログ
tail -1000 storage/logs/laravel-$(date +%Y-%m-%d).log
```

### キュー状態確認
```bash
# キュー内のジョブ数確認
php artisan queue:work --once

# 失敗ジョブ一覧
php artisan queue:failed

# 失敗ジョブ再実行
php artisan queue:retry all
```

---

## バックアップ・復元

### データベースバックアップ

#### 手動バックアップ
```bash
# バックアップ実行
php artisan backup:database

# 保存先確認
ls -lh storage/backups/
```

#### 自動バックアップ設定
```bash
# Cron に追加（毎日 3:00 AM 実行）
crontab -e

# 以下を追加
0 3 * * * cd /path/to/invoicepilot && php artisan backup:database >> /dev/null 2>&1
```

### データベース復元

#### 手順
```bash
# 1. アプリケーション停止
php artisan down

# 2. 現在のデータベースをバックアップ（念のため）
mysqldump -u root -p invoicepilot > /tmp/before_restore_$(date +%Y%m%d_%H%M%S).sql

# 3. バックアップファイルから復元
mysql -u root -p invoicepilot < storage/backups/backup_2026-02-10_03-00-00.sql

# 4. マイグレーション実行（スキーマが古い場合）
php artisan migrate --force

# 5. キャッシュクリア
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 6. アプリケーション再開
php artisan up
```

#### 復元後の確認
```bash
# データ整合性チェック
php artisan tinker

# 以下を実行
>>> \App\Models\Invoice::count()
>>> \App\Models\Payment::sum('amount')
>>> \App\Models\AuditLog::latest()->first()
```

---

## 障害対応

### 1. アプリケーションが起動しない

#### 症状
```
HTTP 500 Internal Server Error
```

#### 診断
```bash
# エラーログ確認
tail -100 storage/logs/laravel.log

# 設定ファイルチェック
php artisan config:cache
php artisan route:cache

# .env ファイル存在確認
ls -la .env
```

#### 対処
```bash
# キャッシュクリア
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 権限修正
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# アプリケーションキー再生成（初回デプロイ時のみ）
php artisan key:generate
```

### 2. データベース接続エラー

#### 症状
```
SQLSTATE[HY000] [2002] Connection refused
```

#### 診断
```bash
# MySQL 起動確認
systemctl status mysql
# または
ps aux | grep mysql

# 接続テスト
mysql -u root -p -e "SELECT 1"

# .env の DB 設定確認
cat .env | grep DB_
```

#### 対処
```bash
# MySQL 再起動
sudo systemctl restart mysql

# .env の接続情報を修正
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoicepilot
DB_USERNAME=invoicepilot_user
DB_PASSWORD=[正しいパスワード]

# 設定キャッシュクリア
php artisan config:clear
```

### 3. メール送信エラー

#### 症状
- 督促メールが送信されない
- キューに失敗ジョブが溜まる

#### 診断
```bash
# 失敗ジョブ確認
php artisan queue:failed

# SMTP 設定確認
cat .env | grep MAIL_

# メールログ確認（log ドライバの場合）
tail -50 storage/logs/laravel.log | grep Mail
```

#### 対処
```bash
# 失敗ジョブ再試行
php artisan queue:retry all

# SMTP 設定修正（.env）
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=[Gmailアドレス]
MAIL_PASSWORD=[アプリパスワード]
MAIL_ENCRYPTION=tls

# 設定反映
php artisan config:clear

# キューワーカー再起動
php artisan queue:restart
```

### 4. 請求番号の重複エラー

#### 症状
```
NumberGenerationException: Failed to generate unique invoice number after 10 retries
```

#### 診断
```bash
# 該当年度の請求番号確認
php artisan tinker

>>> \App\Models\Invoice::withTrashed()
      ->where('invoice_no', 'like', 'I-2026-%')
      ->orderBy('invoice_no', 'desc')
      ->take(10)
      ->pluck('invoice_no')
```

#### 対処
```bash
# 1. NumberingService のログ確認
tail -100 storage/logs/laravel.log | grep "NumberingService"

# 2. 手動で次の番号を確認し、採番テーブルをリセット（将来実装後）
# 現在は自動リトライで対応

# 3. トランザクションがロックされている場合
mysql -u root -p invoicepilot -e "SHOW PROCESSLIST;"
# 必要に応じて KILL [process_id]
```

### 5. 同時更新による競合エラー（Optimistic Lock）

#### 症状
```
StaleObjectException: This record has been modified by another user.
```

#### 対処
1. ユーザーに画面リロードを依頼
2. 最新データで再編集
3. 監査ログで変更履歴を確認

```bash
php artisan tinker

>>> \App\Models\AuditLog::where('target_type', 'App\\Models\\Invoice')
      ->where('target_id', 123)
      ->orderBy('created_at', 'desc')
      ->take(5)
      ->get()
```

---

## メンテナンス

### 1. Laravel バージョンアップ

```bash
# 1. 現在のバージョン確認
php artisan --version

# 2. バックアップ
php artisan backup:database
git add . && git commit -m "Backup before Laravel upgrade"

# 3. composer.json 更新
composer update laravel/framework

# 4. マイグレーション実行
php artisan migrate --force

# 5. キャッシュクリア
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 6. テスト実行
php artisan test
```

### 2. 依存ライブラリの更新

```bash
# 脆弱性チェック
composer audit

# 依存関係更新
composer update

# テスト実行
php artisan test

# 本番デプロイ
git add composer.lock
git commit -m "Update dependencies"
```

### 3. ログローテーション

```bash
# daily ローテーション設定（config/logging.php）
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 90, // 90日間保持
],

# 古いログ削除
find storage/logs -name "laravel-*.log" -mtime +90 -delete
```

### 4. データベース最適化

```bash
# テーブル最適化
mysql -u root -p invoicepilot -e "OPTIMIZE TABLE invoices, payments, audit_logs;"

# インデックス再構築
php artisan migrate:refresh --seed # ※本番では禁止

# 統計情報更新
mysql -u root -p invoicepilot -e "ANALYZE TABLE invoices, payments;"
```

---

## 監視

### ヘルスチェック

#### エンドポイント
```
GET /health
```

#### 期待レスポンス
```json
{
  "status": "healthy",
  "database": "connected",
  "queue": "operational",
  "disk_space": "85%"
}
```

### 監視項目

| 項目 | しきい値 | 対処 |
|------|---------|------|
| ディスク使用率 | 90% | ログ削除、バックアップ移動 |
| CPU 使用率 | 80% | プロセス確認、スケールアップ |
| メモリ使用率 | 85% | キャッシュクリア、再起動 |
| キュー滞留 | 100件 | ワーカー追加 |
| 失敗ジョブ | 10件 | 再試行、エラー調査 |
| 応答時間（P95） | 500ms | N+1 クエリ調査、キャッシュ追加 |

### アラート設定

#### Slack 通知（推奨）
```bash
# Laravel Slack 通知設定
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL

# エラーログを Slack に送信
# config/logging.php
'slack' => [
    'driver' => 'slack',
    'url' => env('SLACK_WEBHOOK_URL'),
    'username' => 'InvoicePilot Bot',
    'emoji' => ':boom:',
    'level' => 'error',
],
```

---

## 連絡フロー

### 障害レベル

| レベル | 定義 | 対応時間 | 連絡先 |
|--------|------|---------|--------|
| P0 (Critical) | システム停止、データ損失 | 即時 | 全員 |
| P1 (High) | 主要機能停止、パフォーマンス劣化 | 1時間以内 | 技術チーム |
| P2 (Medium) | 一部機能停止、軽微なバグ | 4時間以内 | 担当者 |
| P3 (Low) | UI 不具合、将来的な改善 | 1営業日以内 | 担当者 |

### エスカレーションフロー
1. **検知**: 監視システムまたはユーザー報告
2. **一次対応**: オンコール担当者
3. **エスカレーション**: 1時間以内に解決しない場合、シニアエンジニアに連絡
4. **経営陣報告**: P0 障害の場合、30分以内に報告

---

## チェックリスト

### デプロイ前
- [ ] テストが全通過（`php artisan test`）
- [ ] 静的解析が通過（`./vendor/bin/phpstan analyse`）
- [ ] バックアップ取得済み
- [ ] マイグレーション確認済み
- [ ] ロールバック手順を準備

### デプロイ後
- [ ] ヘルスチェック確認
- [ ] エラーログ確認（15分間）
- [ ] 主要機能の動作確認（請求作成、入金記録）
- [ ] キュー正常動作確認

---

最終更新: 2026-02-11  
次回レビュー予定: 2026-03-11
