<?php

namespace Vormkracht10\Fields\Commands;

use Illuminate\Console\Command;

class FieldsCommand extends Command
{
    public $signature = 'filament-fields';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
