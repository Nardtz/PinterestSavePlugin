# Pinterest Save & Follow WordPress Plugin

A WordPress plugin that adds Pinterest save/pin functionality to post images with a follow-up modal to encourage users to follow your Pinterest page.

## 🚀 Features

- **Hover Save Button**: Adds a Pinterest save button overlay on post images
- **Follow Modal**: Shows a follow prompt after users save images to Pinterest
- **Customizable Settings**: Full control over button labels, modal text, and Pinterest profile URL
- **Shortcode Support**: `[pinterest_follow]` shortcode for standalone follow buttons
- **Responsive Design**: Works on all device sizes
- **Theme Compatible**: Designed to work with any WordPress theme

## 📋 Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- jQuery (included with WordPress)

## 🛠 Installation

1. Download the plugin files
2. Upload the `wp-pinterest-save-follow` folder to `/wp-content/plugins/`
3. Activate the plugin in WordPress Admin → Plugins
4. Go to Settings → Pinterest Save & Follow to configure your Pinterest profile

## ⚙️ Configuration

### Settings Page
Navigate to **Settings → Pinterest Save & Follow** to configure:

- **Pinterest Profile URL**: Your full Pinterest profile URL (e.g., `https://www.pinterest.com/yourusername/`)
- **Enable Automatic Overlay**: Toggle to show/hide save buttons on post images
- **Overlay Button Label**: Text displayed on the save button
- **Follow Prompt Title**: Title of the follow modal
- **Follow Prompt Body**: Description text in the follow modal
- **Follow Button Label**: Text for the follow button

### Shortcode Usage
Add a standalone Pinterest follow button anywhere:

```
[pinterest_follow]
```

## 🎯 How It Works

1. **Image Detection**: Automatically detects images in post content
2. **Hover Overlay**: Shows a "Save to Pinterest" button on hover
3. **Pinterest Integration**: Opens Pinterest's native save dialog
4. **Follow Prompt**: After saving, shows a modal encouraging users to follow your Pinterest page
5. **Customizable**: All text and behavior can be customized through the settings page

## 📁 File Structure

```
wp-pinterest-save-follow/
├── wp-pinterest-save-follow.php          # Main plugin file
├── includes/
│   ├── class-wpsf-plugin.php             # Core plugin class
│   ├── class-wpsf-settings.php           # Admin settings
│   └── class-wpsf-frontend.php           # Frontend functionality
├── assets/
│   ├── css/
│   │   └── frontend.css                  # Frontend styles
│   └── js/
│       └── frontend.js                   # Frontend JavaScript
└── README.md                             # This file
```

## 🎨 Customization

### CSS Classes
- `.wpsf-image-wrap`: Container for images with save button
- `.wpsf-save-btn`: Pinterest save button
- `.wpsf-modal-backdrop`: Modal overlay
- `.wpsf-modal`: Modal content container
- `.wpsf-follow-button`: Follow button styling

### JavaScript Events
The plugin uses jQuery and includes event handlers for:
- Image hover detection
- Pinterest dialog opening
- Modal display after pinning
- Modal close functionality

## 🔧 Technical Details

### Hooks Used
- `wp_enqueue_scripts`: Enqueue frontend assets
- `the_content`: Filter to add image wrappers
- `wp_footer`: Render modal container
- `admin_init`: Register settings
- `admin_menu`: Add settings page

### Dependencies
- WordPress core functions
- jQuery (WordPress default)
- No external APIs required

## 🐛 Troubleshooting

### Common Issues

1. **Save button not appearing**
   - Check if "Enable automatic image overlay" is enabled in settings
   - Ensure images have proper `src` or `data-src` attributes

2. **Modal not showing after pinning**
   - Verify Pinterest profile URL is set in settings
   - Check browser console for JavaScript errors

3. **Styling conflicts**
   - The plugin uses high-specificity CSS to avoid conflicts
   - Check for theme CSS overriding `.wpsf-*` classes

### Debug Mode
Enable WordPress debug mode to see any PHP errors:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📝 Changelog

### Version 1.0.0
- Initial release
- Pinterest save button overlay
- Follow modal after pinning
- Admin settings page
- Shortcode support
- Responsive design

## 🤝 Support

For support or feature requests, please contact the developer through Upwork.

## 📄 License

GPL-2.0-or-later - See LICENSE file for details.

## 👨‍💻 Developer

Created for Upwork project - Pinterest Save & Follow WordPress Plugin

---

**Note**: This plugin requires a valid Pinterest profile URL to function properly. The follow modal will only appear if a Pinterest profile is configured in the settings.
