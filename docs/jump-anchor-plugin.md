# Jump Anchor Plugin

Add anchor links to rich editor content for navigation.

## Features

-   Add anchor links to selected text
-   Uses standard HTML `id` attributes
-   Hashtag icon in the toolbar
-   Modal interface for configuration
-   Automatic ID generation

## Usage

1. Build the JavaScript extension:

```bash
node bin/build-rich-editor-plugins.js
```

2. Publish assets:

```bash
php artisan filament:assets
```

3. Use in rich editor:
    - Select text
    - Click hashtag button in toolbar
    - Enter anchor ID

## HTML Output

```html
<span id="section-1">Selected Text</span>
```

## Validation

Anchor IDs must contain only letters, numbers, hyphens, and underscores.

## Troubleshooting

-   Build extension: `node bin/build-rich-editor-plugins.js`
-   Publish assets: `php artisan filament:assets`
-   Clear caches: `php artisan cache:clear`
