#!/bin/bash

echo "🔍 InvoicePilot 品質チェック実行"
echo "================================"
echo ""

# カラーコード
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# エラーカウント
errors=0

# 1. Laravel Pint（コードスタイル）
echo "1️⃣ Laravel Pint（コードスタイルチェック）"
echo "-------------------------------------------"
./vendor/bin/pint --test

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Pint チェック: 合格${NC}"
else
    echo -e "${RED}❌ Pint チェック: 不合格${NC}"
    echo "   修正: ./vendor/bin/pint"
    ((errors++))
fi

echo ""

# 2. PHPStan（静的解析）
echo "2️⃣ PHPStan（静的解析 Level 5）"
echo "-------------------------------------------"

if [ -f "./vendor/bin/phpstan" ]; then
    ./vendor/bin/phpstan analyse --memory-limit=2G
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ PHPStan: 合格${NC}"
    else
        echo -e "${RED}❌ PHPStan: 不合格${NC}"
        ((errors++))
    fi
else
    echo -e "${YELLOW}⚠️ PHPStan 未インストール${NC}"
    echo "   インストール: composer require --dev larastan/larastan:^2.0"
    ((errors++))
fi

echo ""

# 3. PHPUnit（テスト）
echo "3️⃣ PHPUnit（テスト実行）"
echo "-------------------------------------------"
php artisan test

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ テスト: 全通過${NC}"
else
    echo -e "${RED}❌ テスト: 失敗あり${NC}"
    ((errors++))
fi

echo ""

# 4. Composer Audit（セキュリティチェック）
echo "4️⃣ Composer Audit（セキュリティチェック）"
echo "-------------------------------------------"
composer audit

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ セキュリティチェック: 問題なし${NC}"
else
    echo -e "${YELLOW}⚠️ セキュリティチェック: 脆弱性あり${NC}"
    echo "   対処: composer update"
fi

echo ""
echo "================================"
echo "📊 品質チェック結果サマリー"
echo "================================"

if [ $errors -eq 0 ]; then
    echo -e "${GREEN}✅ すべてのチェックが合格しました！${NC}"
    echo ""
    echo "🎉 商用導入可能な品質です！"
    exit 0
else
    echo -e "${RED}❌ $errors 個のチェックが不合格です${NC}"
    echo ""
    echo "修正が必要な項目があります。上記のエラーを確認してください。"
    exit 1
fi
