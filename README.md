# Laravel 3DSecure

## 概要

## 前提条件

- Docker が使用できる状態であること

## 環境

- PHP 8.4
- Laravel 11

## 開始手順

### コンテナの作成

```bash
cd laravel-3dsecure
docker compose up -d
```

### AlmaLinux（PHP）コンテナに接続

```bash
docker exec -it laravel_3dsecure_php bash
```

### ライブラリのインストール

```bash
composer install
```

### 環境設定

```bash
cp .env.example .env
```

以下の箇所を変更

```
PAYJP_PUBLIC_KEY=[PAY.JP API設定から取得した公開鍵]
PAYJP_SECRET_KEY=[PAY.JP API設定から取得した秘密鍵]
```

```bash
php artisan key:generate
chown -R nginx. /var/www/html/laravel/storage
```
