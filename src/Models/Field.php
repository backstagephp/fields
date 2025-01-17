<?php

namespace Vormkracht10\FilamentFields\Models;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vormkracht10\FilamentFields\Shared\HasPackageFactory;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Field extends Model
{
    use HasPackageFactory;
    use HasUlids;
    use HasRecursiveRelationships;

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
        return $this->morphTo('model', 'model_type', 'model_key', 'slug');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Field::class, 'parent_ulid')->with('children')->orderBy('position');
    }

    public function tenant(): ?BelongsTo
    {
        $tenantRelationship = Config::get('fields.tenant_relationship');
        $tenantModel = Config::get('fields.tenant_model');

        if ($tenantRelationship && class_exists($tenantModel)) {
            return $this->belongsTo($tenantModel, $tenantRelationship . '_ulid');
        }

        return null;
    }
}