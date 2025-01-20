# Filament Fields

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vormkracht10/filament-fields.svg?style=flat-square)](https://packagist.org/packages/vormkracht10/filament-fields)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/vormkracht10/filament-fields/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/vormkracht10/filament-fields/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/vormkracht10/filament-fields/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/vormkracht10/filament-fields/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vormkracht10/filament-fields.svg?style=flat-square)](https://packagist.org/packages/vormkracht10/filament-fields)

## Nice to meet you, we're [Vormkracht10](https://vormkracht10.nl)

Hi! We are a web development agency from Nijmegen in the Netherlands and we use Laravel for everything: advanced websites with a lot of bells and whitles and large web applications.

## About the package

This package empowers you to add dynamic, configurable fields to your Filament resources with minimal setup. It comes with all standard Filament Form fields out of the box, while providing a flexible architecture for creating custom fields. By giving users the ability to define and customize form fields through an intuitive interface, this package essentially enables user-driven form building within your Filament admin panel.

## Features

- 🎯 **Easy Integration**: Seamlessly integrates with your Filament resources
- 🔧 **Configurable Fields**: Add and manage custom fields for your models
- 🎨 **Built-in Field Types**: Includes common Filament form fields like:
  - Text
  - Textarea 
  - Rich Text Editor
  - Select
  - Checkbox
  - Checkbox List
  - Key-Value
  - Radio
  - Toggle
  - Color Picker
  - DateTime
  - Tags
- ✨ **Extensible**: Create your own custom field types
- 🔄 **Data Mutation**: Hooks to modify field data before filling forms or saving
- 🏢 **Multi-tenant Support**: Built-in support for multi-tenant applications

This package is perfect for scenarios where you need to:
- Add dynamic custom fields to your models
- Allow users to configure form fields through the admin panel
- Build flexible content management systems
- Create customizable settings pages


## Installation

You can install the package via composer:

```bash
composer require vormkracht10/filament-fields
```

You should publish the config file first with:

```bash
php artisan vendor:publish --tag="filament-fields-config"
```

This will create a `fields.php` file in your `config` directory. Make sure to fill in the tenant relationship and the tenant model (if you're using multi-tenancy). When running the migrations, the fields table will be created with the correct tenant relationship.

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-fields-migrations"
php artisan migrate
```

## Usage

### Define the relation with your models

When one of your models has configurable fields, you need to add the `HasFields` trait to your model.

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Vormkracht10\Fields\Models\Field;

class Content extends Model
{
    use HasFields;

    // ...
}
```

### Adding configurable fields to a resource

To add configurable fields to your related models, we provide a `FieldsRelationManager` that you can add to your resource.

```php
use Vormkracht10\Fields\Filament\RelationManagers\FieldsRelationManager;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    // ...

    public static function getRelations(): array
    {
        return [
            FieldsRelationManager::class,
        ];
    }
}
```

### Making a resource page configurable

To make a resource page configurable, you need to add the `HasFieldsMapper` trait to your page. For this example, we'll make a `EditSettings` page configurable.

```php
<?php

namespace Vormkracht10\Backstage\Resources\SettingResource\Pages;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Vormkracht10\Fields\Concerns\HasFieldsMapper;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    use HasFieldsMapper;

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->resolveFormFields());
    }
}
```

### Add resources as options for select fields

To add resources as options for select fields, you can add them to the `filament-fields.select.resource_options` config array.

```php
return [
    // ...
    
    'select' => [
        'resource_options' => [
            App\Filament\Resources\ContentResource::class,
        ]
    ]
];
```

### Creating your own fields

...

### Registering your own fields

To register your own fields, you can add them to the `filament-fields.fields` config array.

```php
'fields' => [
    App\Fields\CustomField::class,
],
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Baspa](https://github.com/vormkracht10)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
