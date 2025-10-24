<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all fields with config containing optionType
        $fields = DB::table('fields')
            ->whereNotNull('config')
            ->where('config', 'like', '%optionType%')
            ->get();

        foreach ($fields as $field) {
            $config = json_decode($field->config, true);
            
            if (isset($config['optionType']) && is_string($config['optionType'])) {
                // Convert string to array format
                $config['optionType'] = [$config['optionType']];
                
                // Update the field with the corrected config
                DB::table('fields')
                    ->where('ulid', $field->ulid)
                    ->update(['config' => json_encode($config)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all fields with config containing optionType arrays
        $fields = DB::table('fields')
            ->whereNotNull('config')
            ->where('config', 'like', '%optionType%')
            ->get();

        foreach ($fields as $field) {
            $config = json_decode($field->config, true);
            
            if (isset($config['optionType']) && is_array($config['optionType']) && count($config['optionType']) === 1) {
                // Convert array back to string format
                $config['optionType'] = $config['optionType'][0];
                
                // Update the field with the reverted config
                DB::table('fields')
                    ->where('ulid', $field->ulid)
                    ->update(['config' => json_encode($config)]);
            }
        }
    }
};
