<?php

use Backstage\Fields\Fields\Select;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\Select as Input;
use Illuminate\Database\Eloquent\Model;

it('creates a select field with cascading configuration', function () {
    $field = new Field([
        'name' => 'Test Cascading Select',
        'field_type' => 'select',
        'config' => [
            'parentField' => 'category_id',
            'parentRelationship' => 'categories',
            'childRelationship' => 'products',
            'parentKey' => 'id',
            'childKey' => 'id',
            'parentValue' => 'name',
            'childValue' => 'name',
        ],
    ]);

    $input = Select::make('test_field', $field);

    expect($input)->toBeInstanceOf(Input::class);
    expect($input->getName())->toBe('test_field');
    expect($input->getLabel())->toBe('Test Cascading Select');
});

it('creates a select field with live reactive options when cascading is configured', function () {
    $field = new Field([
        'name' => 'Test Cascading Select',
        'field_type' => 'select',
        'config' => [
            'parentField' => 'category_id',
            'parentRelationship' => 'categories',
            'childRelationship' => 'products',
            'parentKey' => 'id',
            'childKey' => 'id',
            'parentValue' => 'name',
            'childValue' => 'name',
        ],
    ]);

    $input = Select::make('test_field', $field);

    // Check if the field has live() method applied
    $reflection = new ReflectionClass($input);
    $liveProperty = $reflection->getProperty('isLive');
    $liveProperty->setAccessible(true);
    
    expect($liveProperty->getValue($input))->toBeTrue();
});

it('creates a regular select field when no cascading is configured', function () {
    $field = new Field([
        'name' => 'Test Regular Select',
        'field_type' => 'select',
        'config' => [
            'options' => ['option1' => 'Option 1', 'option2' => 'Option 2'],
        ],
    ]);

    $input = Select::make('test_field', $field);

    // Check if the field has live() method applied
    $reflection = new ReflectionClass($input);
    $liveProperty = $reflection->getProperty('isLive');
    $liveProperty->setAccessible(true);
    
    $isLive = $liveProperty->getValue($input);
    expect($isLive)->toBeNull(); // Regular select fields don't have isLive set
});

it('normalizes select values correctly for single selection', function () {
    $field = new Field([
        'ulid' => 'test_field',
        'config' => ['multiple' => false],
    ]);

    $record = new class extends Model {
        public $valueColumn = 'values';
        public $values = ['test_field' => 'single_value'];
    };

    $data = ['values' => []];
    $data = Select::mutateFormDataCallback($record, $field, $data);

    expect($data['values']['test_field'])->toBe('single_value');
});

it('normalizes select values correctly for multiple selection', function () {
    $field = new Field([
        'ulid' => 'test_field',
        'config' => ['multiple' => true],
    ]);

    $record = new class extends Model {
        public $valueColumn = 'values';
        public $values = ['test_field' => '["value1", "value2"]'];
    };

    $data = ['values' => []];
    $data = Select::mutateFormDataCallback($record, $field, $data);

    expect($data['values']['test_field'])->toBe(['value1', 'value2']);
});

it('handles null values correctly', function () {
    $field = new Field([
        'ulid' => 'test_field',
        'config' => ['multiple' => false],
    ]);

    $record = new class extends Model {
        public $valueColumn = 'values';
        public $values = ['test_field' => null];
    };

    $data = ['values' => []];
    $data = Select::mutateFormDataCallback($record, $field, $data);

    expect($data['values'])->toHaveKey('test_field');
    expect($data['values']['test_field'])->toBeNull();
});

it('handles empty arrays for multiple selection', function () {
    $field = new Field([
        'ulid' => 'test_field',
        'config' => ['multiple' => true],
    ]);

    $record = new class extends Model {
        public $valueColumn = 'values';
        public $values = ['test_field' => null];
    };

    $data = ['values' => []];
    $data = Select::mutateFormDataCallback($record, $field, $data);

    expect($data['values'])->toHaveKey('test_field');
    expect($data['values']['test_field'])->toBe([]);
});
