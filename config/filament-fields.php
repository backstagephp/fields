<?php

return [

    'is_tenant_aware' => true,

    'tenant_ownership_relationship_name' => 'tenant',

    'tenant_relationship' => 'tenant',

    // 'tenant_model' => \App\Models\Tenant::class,

    'fields' => [
        // App\Fields\CustomField::class,
    ],

    'select' => [
        'resource_options' => [
            // App\Filament\Resources\ContentResource::class,
        ],
    ],
];
