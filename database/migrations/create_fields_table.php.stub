<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fields', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('parent_ulid')->nullable();

            $table->string('model_type');
            $table->string('model_key');

            $table->string('slug');

            $table->string('name');
            $table->string('field_type');
            $table->json('config')->nullable();

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(['model_type', 'model_key', 'slug']);
        });

        if (config('backstage.fields.tenancy.is_tenant_aware')) {
            $tenant = config('backstage.fields.tenancy.relationship');
            $key = config('backstage.fields.tenancy.key');

            Schema::create('field_' . $tenant, function (Blueprint $table) use ($tenant, $key) {
                $table->foreignUlid($tenant . '_' . $key)->constrained(table: $tenant, column: $key)->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignUlid('field_ulid')->constrained(table: 'fields', column: 'ulid')->cascadeOnUpdate()->cascadeOnDelete();

                $table->index(['field_ulid', $tenant . '_' . $key]);
            });
        }
    }
};
