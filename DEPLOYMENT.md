# InvoicePilot 本番環境デプロイガイド

このドキュメントは、InvoicePilotを本番環境にデプロイする際の詳細な手順を説明します。

## 📋 デプロイ前チェックリスト

### セキュリティ確認

- [ ] `.env` ファイルが `.gitignore` に含まれている
- [ ] 本番環境用の強力なパスワードを準備
- [ ] SSL証明書を取得済み（Let's Encrypt推奨）
- [ ] データベース専用ユーザーを作成（root使用禁止）
- [ ] ファイアウォール設定を確認
- [ ] バックアップ戦略を策定

### インフラ準備

- [ ] サーバー準備（VPS、クラウド、専用サーバー）
- [ ] PHP 8.2以上インストール済み
- [ ] Composer 2.x インストール済み
- [ ] Node.js 18以上インストール済み
- [ ] MySQL 8以上インストール済み
- [ ] Nginx/Apache インストール済み
- [ ] Redis インストール済み（推奨）
- [ ] Supervisor インストール済み（キューワーカー用）

## 🚀 ステップバイステップデプロイ手順

### Phase 1: サーバー準備

```bash
# システムアップデート（Ubuntu/Debian）
sudo apt-get update
sudo apt-get upgrade -y

# 必要なパッケージインストール
sudo apt-get install -y \
    nginx \
    mysql-server \
    php8.2 \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-curl \
    php8.2-gd \
    php8.2-zip \
    php8.2-redis \
    redis-server \
    supervisor \
    certbot \
    python3-certbot-nginx \
    git \
    unzip

# Node.js インストール（v18 LTS）
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Composer インストール
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Phase 2: MySQL設定

```bash
# MySQL セキュアインストール
sudo mysql_secure_installation
```

回答例:
- Set root password? Yes → 強力なパスワード設定
- Remove anonymous users? Yes
- Disallow root login remotely? Yes
- Remove test database? Yes
- Reload privilege tables? Yes

```sql
-- MySQLにログイン
sudo mysql -u root -p

-- 専用データベースユーザー作成
CREATE USER 'invoicepilot_user'@'localhost' IDENTIFIED BY 'ここに強力なパスワード';

-- データベース作成
CREATE DATABASE invoicepilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 権限付与
GRANT ALL PRIVILEGES ON invoicepilot.* TO 'invoicepilot_user'@'localhost';
FLUSH PRIVILEGES;

-- 確認
SHOW DATABASES;
SELECT User, Host FROM mysql.user;

EXIT;
```

### Phase 3: アプリケーションデプロイ

```bash
# Webルートディレクトリ作成
sudo mkdir -p /var/www/InvoicePilot
sudo chown -R $USER:$USER /var/www/InvoicePilot

# コードデプロイ（方法1: Git）
cd /var/www
git clone https://github.com/yourcompany/InvoicePilot.git

# または方法2: SCP/SFTP/rsyncでファイル転送
rsync -avz --exclude 'node_modules' --exclude 'vendor' \
    /local/path/InvoicePilot/ user@server:/var/www/InvoicePilot/

# ディレクトリ移動
cd /var/www/InvoicePilot
```

### Phase 4: 環境設定

```bash
# .envファイル作成
cp .env.example .env

# .envファイル編集
nano .env
```

**必須設定項目:**

```bash
APP_NAME=InvoicePilot
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Tokyo
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoicepilot
DB_USERNAME=invoicepilot_user
DB_PASSWORD=your_strong_password_here

SESSION_DRIVER=redis
CACHE_STORE=redis

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="InvoicePilot"
```

```bash
# 依存パッケージインストール
composer install --optimize-autoloader --no-dev

# NPMパッケージインストール
npm ci

# フロントエンド本番ビルド
npm run build

# アプリケーションキー生成
php artisan key:generate

# ストレージリンク作成
php artisan storage:link
```

### Phase 5: データベースセットアップ

```bash
# マイグレーション実行
php artisan migrate --force

# 注意: php artisan db:seed は実行しないでください
# テストユーザーは本番環境では作成されません

# 初期管理者ユーザー作成
php artisan tinker
```

Tinker内で実行:

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// 管理者ユーザー作成
User::factory()->admin()->create([
    'name' => 'Your Name',
    'email' => 'admin@yourdomain.com',
    'password' => Hash::make('your-very-secure-password-here'),
]);

// 確認
User::where('role', 'admin')->get();

exit
```

### Phase 6: パーミッション設定

```bash
# 所有者変更
sudo chown -R www-data:www-data /var/www/InvoicePilot

# 書き込み権限設定
sudo chmod -R 775 /var/www/InvoicePilot/storage
sudo chmod -R 775 /var/www/InvoicePilot/bootstrap/cache

# .envファイルのセキュリティ
sudo chmod 600 /var/www/InvoicePilot/.env
```

### Phase 7: Nginx設定

```bash
# Nginx設定ファイル作成
sudo nano /etc/nginx/sites-available/invoicepilot
```

設定内容（README.mdのNginx設定例を参照）を貼り付け後:

```bash
# シンボリックリンク作成
sudo ln -s /etc/nginx/sites-available/invoicepilot /etc/nginx/sites-enabled/

# デフォルト設定を無効化
sudo unlink /etc/nginx/sites-enabled/default

# 設定テスト
sudo nginx -t

# Nginx再起動
sudo systemctl restart nginx
```

### Phase 8: SSL証明書取得

```bash
# Let's Encrypt証明書取得
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# 自動更新確認
sudo certbot renew --dry-run

# 自動更新タイマー確認
sudo systemctl status certbot.timer
```

### Phase 9: キャッシュ最適化

```bash
cd /var/www/InvoicePilot

# 各種キャッシュ生成
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# OPcache設定（php.ini編集）
sudo nano /etc/php/8.2/fpm/php.ini
```

OPcache設定:

```ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

```bash
# PHP-FPM再起動
sudo systemctl restart php8.2-fpm
```

### Phase 10: Supervisor設定（キューワーカー）

```bash
# Supervisor設定作成
sudo nano /etc/supervisor/conf.d/invoicepilot-worker.conf
```

設定内容（README.mdのSupervisor設定例を参照）を貼り付け後:

```bash
# Supervisor再読み込み
sudo supervisorctl reread
sudo supervisorctl update

# ワーカー起動
sudo supervisorctl start invoicepilot-worker:*

# 状態確認
sudo supervisorctl status
```

### Phase 11: Cron設定

```bash
# www-dataユーザーのcrontab編集
sudo crontab -u www-data -e
```

追加:

```cron
* * * * * cd /var/www/InvoicePilot && php artisan schedule:run >> /dev/null 2>&1
```

### Phase 12: ファイアウォール設定

```bash
# UFWインストール・設定
sudo apt-get install ufw

# SSH, HTTP, HTTPS許可
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# MySQL外部アクセス拒否（ローカルのみ）
sudo ufw deny 3306/tcp

# 有効化
sudo ufw enable

# 状態確認
sudo ufw status verbose
```

### Phase 13: 動作確認

```bash
# サービス状態確認
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status redis-server
sudo supervisorctl status

# ログ確認
tail -f /var/www/InvoicePilot/storage/logs/laravel.log
tail -f /var/log/nginx/invoicepilot-error.log

# ブラウザでアクセス
# https://yourdomain.com
```

チェック項目:
- [ ] HTTPSでアクセスできる
- [ ] ログインページが表示される
- [ ] 作成した管理者アカウントでログインできる
- [ ] ダッシュボードが正しく表示される
- [ ] CSPエラーがない（開発者ツールで確認）
- [ ] 各機能（取引先、見積、請求、入金）が動作する

## 🔒 セキュリティ強化

### fail2ban設定（ブルートフォース対策）

```bash
# fail2ban インストール
sudo apt-get install fail2ban

# Nginx用Jail設定
sudo nano /etc/fail2ban/jail.local
```

```ini
[nginx-http-auth]
enabled = true
port = http,https
logpath = /var/log/nginx/invoicepilot-error.log

[nginx-limit-req]
enabled = true
port = http,https
logpath = /var/log/nginx/invoicepilot-error.log
```

```bash
# fail2ban起動
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# 状態確認
sudo fail2ban-client status
```

### Redis設定強化

```bash
# Redis設定編集
sudo nano /etc/redis/redis.conf
```

```conf
# パスワード設定
requirepass your_redis_password_here

# 外部アクセス禁止
bind 127.0.0.1

# 永続化設定
save 900 1
save 300 10
save 60 10000
```

```bash
# Redis再起動
sudo systemctl restart redis-server

# .envファイル更新
nano /var/www/InvoicePilot/.env
```

```bash
REDIS_PASSWORD=your_redis_password_here
```

## 📊 監視とメンテナンス

### ログローテーション設定

```bash
sudo nano /etc/logrotate.d/invoicepilot
```

```conf
/var/www/InvoicePilot/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        php /var/www/InvoicePilot/artisan cache:clear > /dev/null 2>&1
    endscript
}
```

### バックアップ自動化

```bash
# バックアップスクリプト作成
sudo nano /usr/local/bin/backup-invoicepilot.sh
```

スクリプト内容（README.mdのバックアップスクリプトを参照）を貼り付け後:

```bash
# 実行権限付与
sudo chmod +x /usr/local/bin/backup-invoicepilot.sh

# 手動実行テスト
sudo /usr/local/bin/backup-invoicepilot.sh

# Cron設定（毎日午前3時）
sudo crontab -e
```

```cron
0 3 * * * /usr/local/bin/backup-invoicepilot.sh >> /var/log/invoicepilot-backup.log 2>&1
```

### 監視スクリプト

```bash
# ヘルスチェックスクリプト
sudo nano /usr/local/bin/health-check-invoicepilot.sh
```

```bash
#!/bin/bash
URL="https://yourdomain.com/up"
EMAIL="admin@yourdomain.com"

response=$(curl -s -o /dev/null -w "%{http_code}" $URL)

if [ $response -ne 200 ]; then
    echo "InvoicePilot is DOWN! HTTP Status: $response" | \
        mail -s "InvoicePilot Alert" $EMAIL
fi
```

```bash
# 実行権限付与
sudo chmod +x /usr/local/bin/health-check-invoicepilot.sh

# Cron設定（5分ごと）
crontab -e
```

```cron
*/5 * * * * /usr/local/bin/health-check-invoicepilot.sh
```

## 🔄 更新・デプロイ手順

### ゼロダウンタイムデプロイ（推奨）

```bash
cd /var/www/InvoicePilot

# 1. メンテナンスモード有効化
php artisan down --render="errors::503" --retry=60

# 2. 最新コード取得
git fetch origin
git reset --hard origin/main

# 3. 依存関係更新
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# 4. マイグレーション実行
php artisan migrate --force

# 5. キャッシュクリア・再生成
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. パーミッション確認
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 7. OPcache クリア
sudo systemctl reload php8.2-fpm

# 8. キューワーカー再起動
sudo supervisorctl restart invoicepilot-worker:*

# 9. メンテナンスモード解除
php artisan up

# 10. 動作確認
curl -I https://yourdomain.com
tail -f storage/logs/laravel.log
```

## 🐛 トラブルシューティング

### 問題: ページが表示されない（Nginx 502 Bad Gateway）

```bash
# PHP-FPMステータス確認
sudo systemctl status php8.2-fpm

# Nginxエラーログ確認
sudo tail -f /var/log/nginx/error.log

# PHP-FPM再起動
sudo systemctl restart php8.2-fpm
```

### 問題: データベース接続エラー

```bash
# MySQL接続テスト
mysql -u invoicepilot_user -p invoicepilot

# .env設定確認
cat /var/www/InvoicePilot/.env | grep DB_

# Laravelログ確認
tail -f /var/www/InvoicePilot/storage/logs/laravel.log
```

### 問題: キューが処理されない

```bash
# Supervisorステータス確認
sudo supervisorctl status invoicepilot-worker:*

# ワーカーログ確認
tail -f /var/www/InvoicePilot/storage/logs/worker.log

# ワーカー再起動
sudo supervisorctl restart invoicepilot-worker:*
```

### 問題: 静的ファイルが404

```bash
# ビルド確認
ls -la /var/www/InvoicePilot/public/build/

# 再ビルド
cd /var/www/InvoicePilot
npm run build

# パーミッション確認
sudo chown -R www-data:www-data /var/www/InvoicePilot/public
```

### 問題: メール送信エラー

```bash
# Laravel Tinkerでテスト
php artisan tinker
```

```php
use Illuminate\Support\Facades\Mail;

Mail::raw('Test email from InvoicePilot', function ($message) {
    $message->to('test@example.com')
            ->subject('Test Email');
});

// ログ確認
exit
```

```bash
tail -f storage/logs/laravel.log
```

Gmail使用時の注意:
- アプリパスワード使用（2段階認証有効化後）
- 「安全性の低いアプリのアクセス」は非推奨

## 📈 パフォーマンスチューニング

### MySQL最適化

```sql
-- MySQLにログイン
sudo mysql -u root -p

-- インデックス確認
USE invoicepilot;
SHOW INDEX FROM invoices;
SHOW INDEX FROM quotations;

-- クエリパフォーマンス分析
EXPLAIN SELECT * FROM invoices WHERE status = 'overdue';

-- スロークエリログ有効化
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

### Redis最適化

```bash
# Redis監視
redis-cli
```

```redis
INFO memory
INFO stats
MONITOR
```

### PHP-FPM最適化

```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

```bash
sudo systemctl restart php8.2-fpm
```

## ✅ デプロイ完了チェックリスト

- [ ] サーバーセットアップ完了
- [ ] MySQL設定・データベース作成完了
- [ ] アプリケーションコード配置完了
- [ ] .env設定完了（APP_ENV=production, APP_DEBUG=false）
- [ ] Composer/NPM依存関係インストール完了
- [ ] APP_KEY生成完了
- [ ] マイグレーション実行完了
- [ ] 初期管理者ユーザー作成完了
- [ ] パーミッション設定完了
- [ ] Nginx設定完了
- [ ] SSL証明書取得完了
- [ ] キャッシュ最適化完了
- [ ] Supervisor設定完了（キューワーカー）
- [ ] Cron設定完了（スケジューラー）
- [ ] ファイアウォール設定完了
- [ ] fail2ban設定完了
- [ ] Redis設定完了
- [ ] バックアップ自動化設定完了
- [ ] ログローテーション設定完了
- [ ] ヘルスチェック設定完了
- [ ] 動作確認完了（全機能テスト）
- [ ] メール送信テスト完了
- [ ] SSL/HTTPSアクセス確認完了
- [ ] CSPヘッダー確認完了
- [ ] パフォーマンステスト完了

## 📚 参考資料

- [Laravel Deployment Documentation](https://laravel.com/docs/11.x/deployment)
- [Nginx Configuration](https://nginx.org/en/docs/)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
- [Supervisor Documentation](http://supervisord.org/)
- [MySQL Optimization](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)

---

**デプロイ完了後は、定期的なバックアップ確認とログ監視を行ってください。**
