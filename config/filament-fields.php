<?php

return [

    'tenancy' => [
        'is_tenant_aware' => true,

        'relationship' => 'tenant',

        // 'model' => \App\Models\Tenant::class,
    ],

    'custom_fields' => [
        // App\Fields\CustomField::class,
    ],

    // When populating the select field, this will be used to build the relationship options.
    'selectable_resources' => [
        // App\Filament\Resources\ContentResource::class,
    ],
];
