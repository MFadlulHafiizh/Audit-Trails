# Laravel Activity Log Documentation

## Installation

Laravel activity log requires `laravel 8` or higher and `php 8.0+`

```
composer require kuncen/audittrails:dev-main
```

Then add `Kuncen\Audittrails\AudittrailsServiceProvider::class` to app/config
```php

 'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
         ...
         ...
         Kuncen\Audittrails\AudittrailsServiceProvider::class,
 ])->toArray(),

```


## Configuration

After installing the activity log you must publish its config using command:

```
php artisan vendor:publish --provider="Kuncen\Audittrails\AudittrailsServiceProvider"
```


After that activity log will create the table on your application to store transactional data. that's why you need to `migrate` your database

```
php artisan migrate
```


## Usage

This package automatically save all transactional activity like save, update, delete, login and logout. But before use, you must add `LogTransaction` trait on your Models like this

```php

use Laravel\Sanctum\HasApiTokens;
use Kuncen\Audittrails\LogTransaction;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogTransaction;
}

```


All transactions data carried out before login like forgot password and register will probably store the null value in user_id column in the table activity_log. If you still need the user identity in the transaction you can cast user id using `withAuth()`, like this:

```php
$data = User::withAuth(17)->find(1);
$data->name = "Dummy Example";
$data->email = "dummy@gmail.com";
$data->password = bcrypt("examplepassword");
$data->update();
```


If you want all transactional in your application to be recorded as entering a menu or page that another than action to save(), update(), delete() login and logout. You can add `setActivityLog()` helper to the function you made

```php
setActivityLog(
    "description about this function/page/menu (opsional default is null)",
    user id (opsional if you need user identity for your function default is null),
    http method (opsional by default http method is "GET")
);
```
