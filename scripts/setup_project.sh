#!/bin/bash

echo "🚀 InvoicePilot プロジェクトセットアップ"
echo "========================================"
echo ""

# カラーコード
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Step 1: 環境変数
echo "1️⃣ 環境変数の設定"
echo "-------------------------------------------"
if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${GREEN}✅ .env ファイルを作成しました${NC}"
else
    echo -e "${YELLOW}⚠️ .env ファイルは既に存在します${NC}"
fi

echo ""

# Step 2: 依存関係
echo "2️⃣ 依存関係のインストール"
echo "-------------------------------------------"
composer install
npm install

echo -e "${GREEN}✅ 依存関係のインストール完了${NC}"
echo ""

# Step 3: アプリケーションキー
echo "3️⃣ アプリケーションキーの生成"
echo "-------------------------------------------"
php artisan key:generate

echo ""

# Step 4: データベース確認
echo "4️⃣ データベース設定の確認"
echo "-------------------------------------------"
echo "現在の .env のデータベース設定:"
echo ""
grep "DB_" .env
echo ""
echo "この設定で問題ありませんか？ (y/N)"
read -r db_ok

if [[ ! $db_ok =~ ^[Yy]$ ]]; then
    echo ""
    echo "📝 .env ファイルを編集してください:"
    echo "   vi .env"
    echo ""
    echo "編集後、このスクリプトを再実行してください。"
    exit 1
fi

echo ""

# Step 5: マイグレーション
echo "5️⃣ データベースマイグレーション"
echo "-------------------------------------------"
echo "マイグレーションを実行しますか？ (y/N)"
read -r run_migration

if [[ $run_migration =~ ^[Yy]$ ]]; then
    php artisan migrate
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ マイグレーション完了${NC}"
    else
        echo -e "${RED}❌ マイグレーション失敗${NC}"
        echo "データベース接続を確認してください。"
        exit 1
    fi
fi

echo ""

# Step 6: アセットビルド
echo "6️⃣ フロントエンドアセットのビルド"
echo "-------------------------------------------"
npm run build

echo -e "${GREEN}✅ アセットビルド完了${NC}"
echo ""

# Step 7: 権限設定
echo "7️⃣ ディレクトリ権限の設定"
echo "-------------------------------------------"
chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✅ 権限設定完了${NC}"
echo ""

# Step 8: 初期ユーザー作成
echo "8️⃣ 初期管理者ユーザーの作成"
echo "-------------------------------------------"
echo "管理者ユーザーを作成しますか？ (y/N)"
read -r create_admin

if [[ $create_admin =~ ^[Yy]$ ]]; then
    echo ""
    echo "管理者メールアドレス（デフォルト: admin@example.com）:"
    read -r admin_email
    admin_email=${admin_email:-admin@example.com}
    
    echo "管理者パスワード（デフォルト: password）:"
    read -rs admin_password
    admin_password=${admin_password:-password}
    echo ""
    
    php artisan tinker --execute="
        \App\Models\User::create([
            'name' => 'システム管理者',
            'email' => '$admin_email',
            'password' => bcrypt('$admin_password'),
            'role' => 'admin',
        ]);
        echo 'ユーザー作成完了';
    "
    
    echo -e "${GREEN}✅ 管理者ユーザー作成完了${NC}"
    echo "   Email: $admin_email"
    echo "   Password: (入力したパスワード)"
fi

echo ""
echo "========================================"
echo "🎉 セットアップ完了！"
echo "========================================"
echo ""
echo "次のステップ:"
echo "  1. アプリケーション起動: php artisan serve"
echo "  2. ブラウザでアクセス: http://localhost:8000"
echo "  3. ログイン: $admin_email"
echo ""
echo "品質チェック実行:"
echo "  ./scripts/run_quality_checks.sh"
