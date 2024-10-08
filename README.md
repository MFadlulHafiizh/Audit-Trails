# Laravel Activity Log Documentation

## Installation

Laravel activity log requires `laravel 7` or higher and `php 7.3+`

```
composer require kuncen/audittrails
```

Then add `Kuncen\Audittrails\AudittrailsServiceProvider::class` to config/app.php or bootstrap/providers.php on laravel 11
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

Set foreign key values,
If you want to add reference value of your foreign key, add this to your models

```php
    protected $setForeignValues = [
        [
            "foreign"         => "role_id", //foreign key in your models
            "reference_table" => "role", //reference table of your foreign key
            "reference_table_primary" => "role_id", //primary key of your reference table (not required default value is 'id')
            "target_value"    => "nama_role" //this is the name of a column that you want to put on log
        ],

        //repeat add the array if foreign key more than 1
        [
            "foreign"         => "other_foreign",
            "reference_table" => "some_table",
            "target_value"    => "some_target_column"
        ]
    ];
```
By default if you write this on your models it will replace foreign key id to foreign key value you set in this array. But if you still want to keep foreign key id and add foreign key value without replace it, you can add `hideForeignId(false)`

```php
    $data = User::hideForeignId(false)->find(1);
    $data->name = "Dummy Example";
    $data->email = "dummy@gmail.com";
    $data->password = bcrypt("examplepassword");
    $data->update();
```



All transactions data carried out before login like forgot password and register will probably store the null value in user_id column in the table activity_log. If you still need the user identity in the transaction you can cast user id using `withAuth()`, like this:

```php
$data = User::withAuth(17)->find(1);
$data->name = "Dummy Example";
$data->email = "dummy@gmail.com";
$data->password = bcrypt("examplepassword");
$data->update();
```

Disable logging for some function
```php
$data = User::disableAudit(true)->find(1);
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
    http method or action (opsional by default value is "READ"),
    "New values what you need to store it (opsional)",
    "Old values what you need to store it (opsional)"
);
```
