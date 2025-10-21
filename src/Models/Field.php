<?php

namespace Backstage\Fields\Models;

use Backstage\Fields\Shared\HasPackageFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

/**
 * @property string $ulid
 * @property string|null $parent_ulid
 * @property string $model_type
 * @property string $model_key
 * @property string $slug
 * @property string $name
 * @property string $field_type
 * @property array<string, mixed>|null $config
 * @property int $position
 * @property string|null $group
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Model|null $model
 * @property-read Collection<int, Field> $children
 * @property-read Model|null $tenant
 */
class Field extends Model
{
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
        return $this->morphTo('model', 'model_type', 'model_key', 'ulid');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Field::class, 'parent_ulid')->with('children')->orderBy('position');
    }

    public function tenant(): ?BelongsTo
    {
        $tenantRelationship = Config::get('fields.tenancy.relationship');
        $tenantModel = Config::get('fields.tenancy.model');

        if ($tenantRelationship && class_exists($tenantModel)) {
            return $this->belongsTo($tenantModel, $tenantRelationship . '_ulid');
        }

        return null;
    }

    /**
     * Check if the field has a relation
     */
    public function hasRelation(): bool
    {
        return in_array($this->field_type, ['checkbox-list', 'radio', 'select']) && ! empty($this->config['relations']);
    }
}
