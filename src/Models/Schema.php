<?php

namespace Backstage\Fields\Models;

use Backstage\Fields\Concerns\CanMapDynamicFields;
use Backstage\Fields\Concerns\HasConfigurableFields;
use Backstage\Fields\Concerns\HasFieldTypeResolver;
use Backstage\Fields\Enums\Field;
use Backstage\Fields\Shared\HasPackageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

/**
 * @property string $ulid
 * @property string $name
 * @property string $slug
 * @property string $field_type
 * @property array<string, mixed>|null $config
 * @property int $position
 * @property string $model_type
 * @property string $model_key
 * @property string|null $parent_ulid
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|null $model
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Field> $fields
 * @property-read \Illuminate\Database\Eloquent\Model|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Schema> $children
 */
class Schema extends Model
{
    use CanMapDynamicFields;
    use HasConfigurableFields;
    use HasFieldTypeResolver;
    use HasPackageFactory;
    use HasRecursiveRelationships;
    use HasUlids;

    protected $primaryKey = 'ulid';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }

    public function model(): MorphTo
    {
        return $this->morphTo('model');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class, 'schema_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Schema::class, 'parent_ulid');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Schema::class, 'parent_ulid');
    }
}
