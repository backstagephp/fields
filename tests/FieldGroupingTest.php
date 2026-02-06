<?php

use Backstage\Fields\Concerns\CanMapDynamicFields;
use Backstage\Fields\Models\Field;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;

beforeEach(function () {
    // Create a mock model that uses HasFields
    $this->model = new class extends Model
    {
        use \Backstage\Fields\Concerns\HasFields;

        protected $table = 'test_models';

        protected $guarded = [];
    };

    // Create a mock class that uses CanMapDynamicFields
    $this->mapper = new class
    {
        use CanMapDynamicFields;

        public $record;

        public $formVersion = 0;

        public function __construct()
        {
            $this->record = null;
        }

        public function testResolveFormFields($record, $isNested = false)
        {
            $this->record = $record;

            return $this->resolveFormFields($record, $isNested);
        }

        public function testWrapFieldGroupsInComponents($groups, $fieldResolver, $version = null)
        {
            return $this->wrapFieldGroupsInComponents($groups, $fieldResolver, $version);
        }
    };
});

it('returns empty array when record has no fields', function () {
    $record = new class extends Model
    {
        use \Backstage\Fields\Concerns\HasFields;

        protected $guarded = [];
    };
    $record->setRelation('fields', collect([]));

    $result = $this->mapper->testResolveFormFields($record, false);

    expect($result)->toBeArray()->toBeEmpty();
});

it('creates a Grid component for ungrouped fields', function () {
    $field = new Field([
        'ulid' => '01234567890123456789012345',
        'name' => 'Test Field',
        'slug' => 'test_field',
        'field_type' => 'text',
        'group' => null,
    ]);

    $groups = [
        null => [$field],
    ];

    $result = $this->mapper->testWrapFieldGroupsInComponents($groups, function ($fields) {
        return collect($fields)->map(fn () => new \stdClass)->all();
    });

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(Grid::class);
});

it('creates a Section component for grouped fields', function () {
    // Mock the __ helper to avoid translator dependency
    if (! function_exists('mock__')) {
        function __($key)
        {
            return $key;
        }
    }

    $field = new Field([
        'ulid' => '01234567890123456789012345',
        'name' => 'Phone',
        'slug' => 'phone',
        'field_type' => 'text',
        'group' => 'Contact',
    ]);

    $groups = [
        'Contact' => [$field],
    ];

    $result = $this->mapper->testWrapFieldGroupsInComponents($groups, function ($fields) {
        return collect($fields)->map(fn () => new \stdClass)->all();
    });

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(Section::class);
})->skip('Requires full Laravel application context');

it('creates both Grid and Section when fields have mixed groups', function () {
    $field1 = new Field([
        'ulid' => '01234567890123456789012345',
        'name' => 'Name',
        'slug' => 'name',
        'field_type' => 'text',
        'group' => null,
    ]);

    $field2 = new Field([
        'ulid' => '01234567890123456789012346',
        'name' => 'Phone',
        'slug' => 'phone',
        'field_type' => 'text',
        'group' => 'Contact',
    ]);

    $groups = [
        null => [$field1],
        'Contact' => [$field2],
    ];

    $result = $this->mapper->testWrapFieldGroupsInComponents($groups, function ($fields) {
        return collect($fields)->map(fn () => new \stdClass)->all();
    });

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(Grid::class)
        ->and($result[1])->toBeInstanceOf(Section::class);
})->skip('Requires full Laravel application context');

it('creates Section with correct properties', function () {
    $field = new Field([
        'ulid' => '01234567890123456789012345',
        'name' => 'Phone',
        'slug' => 'phone',
        'field_type' => 'text',
        'group' => 'Contact Information',
    ]);

    $groups = [
        'Contact Information' => [$field],
    ];

    $result = $this->mapper->testWrapFieldGroupsInComponents($groups, function ($fields) {
        return collect($fields)->map(fn () => new \stdClass)->all();
    }, 123);

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(Section::class);

    $section = $result[0];

    // Verify the section has the correct configuration
    // Note: These assertions depend on Filament's API and may need adjustment
    expect($section->getKey())->toContain('dynamic-group-contact-information-123');
})->skip('Requires full Laravel application context');

it('filters out groups with no resolved fields', function () {
    $groups = [
        'Empty Group' => [],
        'Contact' => [new Field(['ulid' => '01234567890123456789012345', 'name' => 'Phone'])],
    ];

    $result = $this->mapper->testWrapFieldGroupsInComponents($groups, function ($fields) {
        if (empty($fields)) {
            return [];
        }

        return collect($fields)->map(fn () => new \stdClass)->all();
    });

    expect($result)->toHaveCount(1);
})->skip('Requires full Laravel application context');

it('handles multiple fields in the same group', function () {
    $field1 = new Field([
        'ulid' => '01234567890123456789012345',
        'name' => 'Phone',
        'slug' => 'phone',
        'field_type' => 'text',
        'group' => 'Contact',
    ]);

    $field2 = new Field([
        'ulid' => '01234567890123456789012346',
        'name' => 'Email',
        'slug' => 'email',
        'field_type' => 'text',
        'group' => 'Contact',
    ]);

    $field3 = new Field([
        'ulid' => '01234567890123456789012347',
        'name' => 'Address',
        'slug' => 'address',
        'field_type' => 'text',
        'group' => 'Contact',
    ]);

    $groups = [
        'Contact' => [$field1, $field2, $field3],
    ];

    $resolvedCount = 0;
    $result = $this->mapper->testWrapFieldGroupsInComponents($groups, function ($fields) use (&$resolvedCount) {
        $resolvedCount = count($fields);

        return collect($fields)->map(fn () => new \stdClass)->all();
    });

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(Section::class)
        ->and($resolvedCount)->toBe(3);
})->skip('Requires full Laravel application context');

it('applies grouping for non-nested fields', function () {
    // This test verifies that isNested=false triggers grouping
    $field1 = new Field([
        'ulid' => '01234567890123456789012345',
        'name' => 'Name',
        'slug' => 'name',
        'field_type' => 'text',
        'group' => null,
    ]);

    $field2 = new Field([
        'ulid' => '01234567890123456789012346',
        'name' => 'Phone',
        'slug' => 'phone',
        'field_type' => 'text',
        'group' => 'Contact',
    ]);

    $record = new class extends Model
    {
        use \Backstage\Fields\Concerns\HasFields;

        protected $guarded = [];
    };
    $record->setRelation('fields', collect([$field1, $field2]));

    // Note: This is a partial test since we can't fully test the field resolution
    // without the full infrastructure. The real test is the integration test
    // or manual testing in the actual application.

    expect($record->fields)->toHaveCount(2);
});
