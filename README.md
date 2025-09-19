# Backstage: Fields in Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/backstage/fields.svg?style=flat-square)](https://packagist.org/packages/backstage/fields)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/backstage/fields/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/backstagephp/fields/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/backstage/fields/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/backstagephp/fields/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/backstage/fields.svg?style=flat-square)](https://packagist.org/packages/backstage/fields)

## Nice to meet you, we're [Backstage](https://Backstage.nl)

Hi! We are a web development agency from Nijmegen in the Netherlands and we use Laravel for everything: advanced websites with a lot of bells and whitles and large web applications.

## About the package

This package aims to help you add dynamic, configurable fields to your Filament resources with minimal setup. It comes with all standard Filament Form fields out of the box, while providing a flexible architecture for creating custom fields. By giving users the ability to define and customize form fields through an intuitive interface, this package essentially enables user-driven form building within your Filament admin panel.

## Features

-   🎯 **Easy Integration**: Seamlessly integrates with your Filament resources
-   🔧 **Configurable Fields**: Add and manage custom fields for your models
-   🎨 **Built-in Field Types**: Includes common Filament form fields like:
    -   Text
    -   Textarea
    -   Rich Text Editor (with Jump Anchor plugin)
    -   Select
    -   Checkbox
    -   Checkbox List
    -   Key-Value
    -   Radio
    -   Toggle
    -   Color Picker
    -   DateTime
    -   Tags
-   ✨ **Extensible**: Create your own custom field types
-   🔄 **Data Mutation**: Hooks to modify field data before filling forms or saving
-   🏢 **Multi-tenant Support**: Built-in support for multi-tenant applications

This package is perfect for scenarios where you need to:

-   Add dynamic custom fields to your models
-   Allow users to configure form fields through the admin panel
-   Build flexible content management systems
-   Create customizable settings pages

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

    public function mutateFormDataBeforeSave(array $data): array
    {
        $this->mutateBeforeSave($data);

        return $data;
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

To create your own custom fields, you need to extend the `Base` field class and implement the required methods. Here's an example of a custom field:

```php
<?php

namespace App\Fields;

use Backstage\Fields\Fields\Base;
use Filament\Forms\Components\TextInput;

class CustomField extends Base
{
    public static function make(string $name, ?Field $field = null): TextInput
    {
        $input = self::applyDefaultSettings(TextInput::make($name), $field);

        // Add your custom field logic here
        $input->placeholder('Custom placeholder');

        return $input;
    }

    public function getForm(): array
    {
        return [
            // Your custom form configuration
            TextInput::make('config.customOption')
                ->label('Custom Option'),
        ];
    }

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'customOption' => null,
        ];
    }
}
```

#### Excluding base fields from custom fields

When creating custom fields, you may want to exclude certain base fields that don't apply to your field type. For example, a Repeater field doesn't need a "Default value" field since it's a container for other fields.

You can exclude base fields by overriding the `excludeFromBaseSchema()` method:

```php
<?php

namespace App\Fields;

use Backstage\Fields\Fields\Base;

class RepeaterField extends Base
{
    // Exclude the default value field since it doesn't make sense for repeaters
    protected function excludeFromBaseSchema(): array
    {
        return ['defaultValue'];
    }

    // Your field implementation...
}
```

Available base fields that can be excluded:

-   `required` - Required field toggle
-   `disabled` - Disabled field toggle
-   `hidden` - Hidden field toggle
-   `helperText` - Helper text input
-   `hint` - Hint text input
-   `hintColor` - Hint color picker
-   `hintIcon` - Hint icon input
-   `defaultValue` - Default value input

#### Best practices for field exclusion

-   **Only exclude what doesn't apply**: Don't exclude fields just because you don't use them - only exclude fields that conceptually don't make sense for your field type
-   **Document your exclusions**: Add comments explaining why certain fields are excluded
-   **Test thoroughly**: Make sure your field still works correctly after excluding base fields
-   **Consider inheritance**: If your field extends another custom field, make sure to call `parent::excludeFromBaseSchema()` if you need to add more exclusions

Example of a field that excludes multiple base fields:

```php
class ImageField extends Base
{
    protected function excludeFromBaseSchema(): array
    {
        return [
            'defaultValue', // Images don't have default values
            'hint',         // Image fields typically don't need hints
            'hintColor',    // No hint means no hint color
            'hintIcon',     // No hint means no hint icon
        ];
    }
}
```

### Registering your own fields

To register your own fields, you can add them to the `fields.fields` config array.

```php
'custom_fields' => [
    App\Fields\CustomField::class,
],
```

## Documentation

### Rich Editor Plugins

The package includes a powerful Rich Editor with custom plugins:

-   **[Jump Anchor Plugin](docs/jump-anchor-plugin.md)** - Add anchor links to selected text for navigation and jumping to specific sections

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

-   [Baspa](https://github.com/Backstage)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
