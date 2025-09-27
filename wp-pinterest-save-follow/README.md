# Pinterest Save & Follow

Adds a hover Save/Pin overlay to WordPress post images and, after pinning, prompts the user to follow your Pinterest page. Includes a shortcode to render a standalone Follow button.

## Requirements
- WordPress 6.0+
- PHP 7.4+

## Installation
1. Upload the `wp-pinterest-save-follow` folder to `wp-content/plugins/`.
2. Activate the plugin in WordPress Admin → Plugins.
3. Go to Settings → Pinterest Save & Follow to configure your Pinterest profile URL and texts.

## Usage
- Automatic overlay: When enabled, post images will display a "Save to Pinterest" overlay on hover. Clicking opens Pinterest’s Pin dialog. After a brief delay, a follow prompt/modal appears linking to your configured Pinterest profile.
- Shortcode: Place the follow button anywhere with:

```
[pinterest_follow]
```

## Settings
- Pinterest Profile URL: Full URL to your Pinterest profile (e.g., https://www.pinterest.com/yourusername/).
- Enable automatic image overlay: Toggle to enable/disable overlays on post images.
- Overlay button label: Text shown on the overlay button.
- Follow prompt title/body/button label: Texts for the follow modal shown after pinning.

## Notes
- The plugin opens Pinterest’s standard pin creation window.
- For best results, ensure your images have descriptive `alt` attributes; these will be used for the pin description.

## Uninstall
Deactivate and delete via WordPress Admin → Plugins.
