# Log Audit trails

The `connf/auditlog` package is built on top of the `spatie/laravel-activitylog` package. The package is based on v1.16.0 due to incompatbility with v3 but does provide easy to use functions to log the activities of users. It can also automatically log model events. All audit logs will be stored in the `audit_log` table.

## Usage

### Information

This package has been developed with the sole purpose to automate the logging of audit information where possible. 

### Installation

Simply require the package with Composer and then add the following to the `config\app.php` in order to begin:

```php
// config/app.php
'providers' => [
    ...
    Connf\Auditlog\AuditlogServiceProvider::class,
];
```

### Auto-Logging within a Model

Add the following to any Model in order to automatically log changes against records.

```php
namespace NameSpace;

use ...
use Connf\Auditlog\Traits\LogsAudit;
```

```php
class ClassName extends Model [implements ...]
{
    use ...
    use LogsAudit;
    ...

    // Set Audit_log to only log changed properties
    protected static $logOnlyDirty = true;

    ...
}
```

## Installation

You can install the package via composer:

```bash
// composer require connf/auditlog // TBC **********
```

***** Composer repo not configured. Install via VCS in composer.json manually.

Next, you must install the service provider:

```php
// config/app.php
'providers' => [
    ...
    Connf\Auditlog\AuditlogServiceProvider::class,
];
```

You can publish the migration with:
```bash
php artisan vendor:publish --provider="Connf\Auditlog\AuditlogServiceProvider" --tag="migrations"
```

*Note*: The default migration assumes you are using integers for your model IDs. If you are using UUIDs, or some other format, adjust the format of the subject_id and causer_id fields in the published migration before continuing.

After the migration has been published you can create the `audit_log` table by running the migrations:


```bash
php artisan migrate
```

You can optionally publish the config file with:
```bash
php artisan vendor:publish --provider="Connf\Auditlog\AuditlogServiceProvider" --tag="config"
```

This is the contents of the published config file:

```php
return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ENABLE_AUDIT_LOGGING', true),

    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => 3650,

    /*
     * If no log name is passed to the Audit() helper
     * we use this default log name.
     */
    'default_log_name' => 'audit_auto',

    /*
     * You can specify an auth driver here that gets user models.
     * If this is null we'll use the default Laravel auth driver.
     */
    'default_auth_driver' => null,

    /*
     * If set to true, the subject returns soft deleted models.
     */
    'subject_returns_soft_deleted_models' => false,

    /*
     * This model will be used to log Audit. The only requirement is that
     * it should be or extend the Connf\Auditlog\Models\Audit model.
     */
    'audit_model' => \Connf\Auditlog\Models\Audit::class,
];
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

```bash
$ composer test
```

## Credits

- [Connah Fearnley](https://github.com/connahile) Ported the Spatie Activity Log package to Audit Log

- [Freek Van der Herten](https://github.com/freekmurze)
- [Sebastian De Deyne](https://github.com/sebastiandedeyne)

## Spatie-based Example

Here's an example on [manual event logging](https://docs.spatie.be/laravel-Auditlog/v1/advanced-usage/logging-model-events).

```php
$newsItem->name = 'updated name';
$newsItem->save();

//updating the newsItem will cause an audit being logged
$audit = audit::all()->last();

$audit->description; //returns 'updated'
$audit->subject; //returns the instance of NewsItem that was updated
```

Calling `$audit->changes` will return this array:

```php
[
   'attributes' => [
        'name' => 'updated name',
        'text' => 'Lorum',
    ],
    'old' => [
        'name' => 'original name',
        'text' => 'Lorum',
    ],
];
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
