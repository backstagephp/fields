<?php

namespace Backstage\Fields\Tests;

use Backstage\Fields\Plugins\JumpAnchorRichContentPlugin;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class JumpAnchorPluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up a minimal Laravel application context for facades
        if (! Facade::getFacadeApplication()) {
            $app = new \Illuminate\Foundation\Application(realpath(__DIR__ . '/../'));
            $app->singleton('filament.assets', function () {
                return new \Filament\Support\Assets\AssetManager;
            });

            // Mock the translator service
            $translator = \Mockery::mock(\Illuminate\Contracts\Translation\Translator::class);
            $translator->shouldReceive('get')->andReturn('Jump Anchor');
            $translator->shouldReceive('choice')->andReturn('Jump Anchor');
            $translator->shouldReceive('trans')->andReturn('Jump Anchor');
            $translator->shouldReceive('transChoice')->andReturn('Jump Anchor');

            $app->singleton('translator', function () use ($translator) {
                return $translator;
            });

            Facade::setFacadeApplication($app);
        }
    }

    public function test_plugin_can_be_instantiated()
    {
        $plugin = JumpAnchorRichContentPlugin::get();

        $this->assertInstanceOf(JumpAnchorRichContentPlugin::class, $plugin);
    }

    public function test_plugin_has_correct_id()
    {
        $plugin = new JumpAnchorRichContentPlugin;

        $this->assertEquals('jump-anchor', $plugin->getId());
    }

    public function test_plugin_returns_php_extensions()
    {
        $plugin = new JumpAnchorRichContentPlugin;

        $this->assertIsArray($plugin->getTipTapPhpExtensions());
        $this->assertNotEmpty($plugin->getTipTapPhpExtensions());
    }

    public function test_plugin_returns_js_extensions()
    {
        // Mock the FilamentAsset facade to avoid dependency issues
        FilamentAsset::shouldReceive('getScriptSrc')
            ->with('rich-content-plugins/jump-anchor', 'backstage/fields')
            ->andReturn('/path/to/jump-anchor.js');

        $plugin = new JumpAnchorRichContentPlugin;

        $extensions = $plugin->getTipTapJsExtensions();

        $this->assertIsArray($extensions);
        $this->assertNotEmpty($extensions);
        $this->assertEquals('/path/to/jump-anchor.js', $extensions[0]);
    }

    public function test_plugin_returns_editor_tools()
    {
        $plugin = new JumpAnchorRichContentPlugin;

        $tools = $plugin->getEditorTools();

        $this->assertIsArray($tools);
        $this->assertCount(1, $tools);
        $this->assertEquals('jumpAnchor', $tools[0]->getName());
    }

    public function test_plugin_returns_editor_actions()
    {
        $plugin = new JumpAnchorRichContentPlugin;

        $actions = $plugin->getEditorActions();

        $this->assertIsArray($actions);
        $this->assertCount(1, $actions);
        $this->assertEquals('jumpAnchor', $actions[0]->getName());
    }
}
