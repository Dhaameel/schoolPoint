# schoolPoint

schoolPoint is a school management system built with Laravel 13.

Current state: **Stages 1–4 complete** (foundation, people, attendance and fees).

The full product plan is in [ARCHITECTURE.md](ARCHITECTURE.md).

## Stack

- PHP 8.4
- Laravel 13
- MySQL 8.4
- Bootstrap 5.3, Vite, Inter and Bootstrap Icons
- PHPUnit

## Local setup

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

`migrate --seed` creates settings only. It creates no users.

Create the first administrator:

```sh
php artisan app:create-admin
```

Other commands:

```sh
php artisan app:reset-password <username>
php artisan app:disable-two-factor <username>
php artisan app:fees-reconcile
```

## Tests

```sh
php artisan test
php vendor/phpunit/phpunit/phpunit -c phpunit.mysql.xml
```

## Stage tags

```text
stage-0-plan-green
stage-1-foundation-green
stage-2-people-green
stage-3-attendance-green
stage-4-fees-green
stage-5-reports-green
stage-6-ui-green
stage-7-release-green
```
