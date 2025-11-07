<?php

namespace Backstage\Fields;

use Backstage\Fields\Contracts\FieldInspector;
use Backstage\Fields\Services\FieldInspectionService;
use Backstage\Fields\Testing\TestsFields;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FieldsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fields';

    public static string $viewNamespace = 'fields';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('backstage/fields');
            });

        if (file_exists($package->basePath('/../config/backstage/fields.php'))) {
            $package->hasConfigFile('backstage/fields');
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        // Rich Editor Plugin Assets
        FilamentAsset::register(
            $this->getRichEditorPluginAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/fields/{$file->getFilename()}"),
                ], 'fields-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsFields);

        $this->app->bind(FieldInspector::class, FieldInspectionService::class);

        collect($this->app['config']['backstage.fields.custom_fields'] ?? [])
            ->each(function ($field) {
                Fields::registerField($field);
            });
    }

    protected function getAssetPackageName(): ?string
    {
        return 'backstage/fields';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('fields', __DIR__ . '/../resources/dist/components/fields.js'),
            Css::make('fields-styles', __DIR__ . '/../resources/css/fields.css'),
            // Js::make('fields-scripts', __DIR__ . '/../resources/dist/fields.js'),
        ];
    }

    /**
     * @return array<Asset>
     */
    protected function getRichEditorPluginAssets(): array
    {
        return [
            Js::make('rich-content-plugins/jump-anchor', __DIR__ . '/../resources/js/dist/filament/rich-content-plugins/jump-anchor.js')
                ->loadedOnRequest(),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_fields_table',
            'change_unique_column_in_fields',
            'add_group_column_to_fields_table',
            'fix_option_type_string_values_in_fields_table',
        ];
    }
}
