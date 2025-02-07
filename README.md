# Fields

[![Latest Version on Packagist](https://img.shields.io/packagist/v/backstage/fields.svg?style=flat-square)](https://packagist.org/packages/backstage/fields)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/backstage/fields/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/backstagephp/fields/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/backstage/fields/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/backstagephp/fields/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/backstage/fields.svg?style=flat-square)](https://packagist.org/packages/backstage/fields)

## Nice to meet you, we're [Backstage](https://Backstage.nl)

Hi! We are a web development agency from Nijmegen in the Netherlands and we use Laravel for everything: advanced websites with a lot of bells and whitles and large web applications.

## About the package

This package aims to help you add dynamic, configurable fields to your Filament resources with minimal setup. It comes with all standard Filament Form fields out of the box, while providing a flexible architecture for creating custom fields. By giving users the ability to define and customize form fields through an intuitive interface, this package essentially enables user-driven form building within your Filament admin panel.

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
composer require backstage/fields
```

You should publish the config file first with:

```bash
php artisan vendor:publish --tag="fields-config"
```

This will create a `fields.php` file in your `config` directory. Make sure to fill in the tenant relationship and the tenant model (if you're using multi-tenancy). When running the migrations, the fields table will be created with the correct tenant relationship.

The content of the `fields.php` file is as follows:

```php
<?php

return [
    
    'tenancy' => [
        'is_tenant_aware' => true,

        'relationship' => 'tenant',

        // 'model' => \App\Models\Tenant::class,

        // The key (id, ulid, uuid) to use for the tenant relationship
        'key' => 'id',
    ],

    'custom_fields' => [
        // App\Fields\CustomField::class,
    ],

    // When populating the select field, this will be used to build the relationship options.
    'selectable_resources' => [
        // App\Filament\Resources\ContentResource::class,
    ],
];
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="fields-migrations"
php artisan migrate
```

## Usage

### Define the relation with your models

When one of your models has configurable fields, you need to add the `HasFields` trait to your model.

The trait will add a `fields` relation to your model, and define the `valueColumn` property. This is the column that will be used to store the field values. Because the values are stored as json, you should cast this column to an array.

If you want to use any other column name for the values, you can set the `valueColumn` property in your model.

```php
use Illuminate\Database\Eloquent\Model;
use Backstage\Fields\Concerns\HasFields;

class Content extends Model
{
    use HasFields;

    // ...
}
```

### Adding configurable fields to a resource

To add configurable fields to your related models, we provide a `FieldsRelationManager` that you can add to your resource.

```php
use Backstage\Fields\Filament\RelationManagers\FieldsRelationManager;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

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

To make a resource page configurable, you need to add the `CanMapDynamicFields` trait to your page. For this example, we'll make a `EditContent` page configurable.

```php
<?php

namespace Backstage\Resources\ContentResource\Pages;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Backstage\Fields\Concerns\CanMapDynamicFields;

class EditContent extends EditRecord
{
    protected static string $resource = ContentResource::class;

    use CanMapDynamicFields;

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->resolveFormFields());
    }
}
```

#### Conditionally show the fields relation manager

To conditionally show the fields relation manager, you can override the `canViewForRecord` method in your relation manager.

```php
<?php

namespace App\Filament\Resources\ContentResource\RelationManagers;

use Illuminate\Database\Eloquent\Model;
use Backstage\Fields\Filament\RelationManagers\FieldsRelationManager as RelationManagersFieldsRelationManager;

class FieldsRelationManager extends RelationManagersFieldsRelationManager
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        parent::canViewForRecord($ownerRecord, $pageClass);

        // Add your own logic here
        return ! $ownerRecord->hasPdf();
    }
}
```

### Making a custom page configurable

To make a custom page configurable, you need to add the `CanMapDynamicFields` trait to your page and set the `record` property on the page. This way the fields will be populated with the fields of the record.

```php
class YourCustomPage extends Page
{
    use CanMapDynamicFields;

    public $record;

    public function mount()
    {
        $this->record = YourModel::find($this->recordId);
    }
}
```

### Add resources as options for select fields

When using select fields, you may want to populate the options with relations in your application. To be able to do this, you need to add the resources to the `fields.selectable_resources` config array. We then get the options from the table that belongs to the resource and model.

```php
return [
    // ...
    
    'selectable_resources' => [
        App\Filament\Resources\ContentResource::class,
    ]
];
```

### Creating your own fields

...

### Registering your own fields

To register your own fields, you can add them to the `fields.fields` config array.

```php
'custom_fields' => [
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

- [Baspa](https://github.com/Backstage)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
