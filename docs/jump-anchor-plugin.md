# Jump Anchor Plugin for Rich Editor

The Jump Anchor plugin adds the ability to create anchor links within rich editor content that can be used for navigation and jumping to specific sections.

## Features

- Add anchor links to selected text in the rich editor
- Visual indicators with yellow highlighting and link icon
- Customizable anchor IDs with validation
- Modal interface for entering anchor details
- Automatic ID generation if none provided

## Usage

### 1. Build the JavaScript Extension

First, you need to build the JavaScript extension:

```bash
# Install esbuild if not already installed
npm install esbuild --save-dev

# Build the rich editor plugins
node bin/build-rich-editor-plugins.js
```

### 2. Publish Assets

Publish the compiled assets to your application's public directory:

```bash
php artisan filament:assets
```

### 3. Using in Rich Editor

The jump anchor functionality is automatically available in all RichEditor fields. Users can:

1. Select text in the rich editor
2. Click the jump anchor button (🔗) in the toolbar
3. Enter a custom anchor ID or use the auto-generated one
4. The selected text will be wrapped with an anchor link

### 4. Toolbar Configuration

The jump anchor button is included by default in the toolbar buttons. You can configure it in the field configuration:

```php
use Backstage\Fields\Enums\ToolbarButton;

// Include jump anchor in toolbar
$toolbarButtons = [
    'bold', 'italic', 'jumpAnchor', 'link', // ... other buttons
];
```

## Technical Details

### Plugin Structure

- **PHP Plugin**: `src/Plugins/JumpAnchorRichContentPlugin.php`
- **JavaScript Extension**: `resources/js/filament/rich-content-plugins/jump-anchor.js`
- **Build Script**: `bin/build-rich-editor-plugins.js`

### HTML Output

The plugin generates HTML with `data-anchor-id` attributes:

```html
<span data-anchor-id="section-1" class="jump-anchor">Selected Text</span>
```

### CSS Styling

The plugin includes CSS styles for visual indicators:

- Yellow background highlighting
- Link icon (🔗) after the text
- Hover effects
- Responsive design

### Validation

Anchor IDs are validated to ensure they contain only:
- Letters (a-z, A-Z)
- Numbers (0-9)
- Hyphens (-)
- Underscores (_)

No spaces or special characters are allowed.

## Customization

### Styling

You can customize the appearance by modifying the CSS in `resources/css/fields.css`:

```css
.jump-anchor {
    @apply bg-blue-100 border-b-2 border-blue-400 px-1 rounded-sm;
    /* Your custom styles */
}
```

### Icon

The plugin uses the `AnchorIcon` from Heroicons. You can change this in the plugin class:

```php
->icon(YourCustomIcon::class)
```

### Validation Rules

Modify the validation rules in the plugin's action method:

```php
->rules(['regex:/^[a-zA-Z0-9-_]+$/'])
```

## Troubleshooting

### Plugin Not Loading

1. Ensure the JavaScript extension is built: `node bin/build-rich-editor-plugins.js`
2. Publish assets: `php artisan filament:assets`
3. Clear caches: `php artisan cache:clear`

### Styling Issues

1. Ensure Tailwind CSS is properly configured
2. Check that the CSS file is being loaded
3. Verify the class names match between CSS and JavaScript

### Build Errors

1. Install esbuild: `npm install esbuild --save-dev`
2. Check file paths in the build script
3. Ensure all dependencies are installed
