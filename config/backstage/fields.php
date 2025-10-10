<?php

return [

    'tenancy' => [
        'is_tenant_aware' => false,

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

    // Models that can be used for visibility rules
    'visibility_models' => [
        // \App\Models\Content::class,
    ],
];
