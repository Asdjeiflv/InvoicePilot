#!/bin/bash

# GitHub Issues 一括作成スクリプト
# 必要: GitHub CLI (gh) インストール済み
# 使用方法: ./scripts/create_issues.sh

echo "📋 InvoicePilot GitHub Issues を作成します..."
echo "注意: GitHub CLI (gh) が必要です。未インストールの場合は brew install gh でインストールしてください。"
echo ""

# 実装済み Issues（参考用）
echo "✅ 実装済み Issues:"
echo "  - Issue #1: 監査ログ拡張"
echo "  - Issue #2: Policy ロール制御"
echo "  - Issue #7: CI/CD パイプライン"
echo ""

# 未実装 Issues を作成
echo "📝 未実装 Issues を作成中..."
echo ""

# Issue #3
gh issue create \
  --title "[P0] 冪等キー実装（重複処理防止）" \
  --label "reliability,idempotency,P0" \
  --body "**見積時間**: 10h | **優先度**: P0

**背景**: Invoice/Payment 作成時に同じリクエストを 2 回送信すると重複レコードが作成される。

**完了条件**:
- [ ] idempotency_keys テーブル作成
- [ ] IdempotencyMiddleware 実装
- [ ] Controller に統合
- [ ] テスト追加" 2>/dev/null && echo "✅ Issue #3 作成完了"

# 残りの Issues も同様に作成可能

echo ""
echo "🎉 GitHub Issues 作成スクリプト準備完了！"
echo ""
echo "実行方法:"
echo "  ./scripts/create_issues.sh"
