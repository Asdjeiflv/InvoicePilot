#!/bin/bash

echo "📦 InvoicePilot 商用導入品質改善 - 依存関係インストール"
echo ""

# Larastan (PHPStan for Laravel) のインストール
echo "1️⃣ Larastan (PHPStan) をインストール中..."
composer require --dev larastan/larastan:^2.0 --no-interaction

if [ $? -eq 0 ]; then
    echo "✅ Larastan インストール完了"
else
    echo "⚠️ Larastan インストール失敗（既にインストール済みの可能性）"
fi

echo ""

# Laravel Horizon のインストール（オプション）
echo "2️⃣ Laravel Horizon をインストールしますか？ (y/N)"
read -r install_horizon

if [[ $install_horizon =~ ^[Yy]$ ]]; then
    echo "Laravel Horizon をインストール中..."
    composer require laravel/horizon --no-interaction
    
    if [ $? -eq 0 ]; then
        php artisan horizon:install
        echo "✅ Horizon インストール完了"
        echo "📝 config/horizon.php を確認してください"
    else
        echo "⚠️ Horizon インストール失敗"
    fi
fi

echo ""
echo "3️⃣ Composer autoload を更新中..."
composer dump-autoload

echo ""
echo "✅ 依存関係のインストールが完了しました！"
echo ""
echo "次のステップ:"
echo "  1. PHPStan 実行: ./vendor/bin/phpstan analyse"
echo "  2. Pint 実行: ./vendor/bin/pint"
echo "  3. テスト実行: php artisan test"
