# Pinterest WordPress Plugins Collection

A comprehensive collection of WordPress plugins for Pinterest integration, featuring both manual image saving and automated pinning functionality.

## 🚀 Plugins Included

### 1. Pinterest Save & Follow Plugin
A WordPress plugin that allows users to save images from blog posts to Pinterest and prompts them to follow your Pinterest page.

**Features:**
- **Hover Save Button**: Adds a Pinterest save button overlay on post images
- **Follow Modal**: Shows a follow prompt after users save images to Pinterest
- **Customizable Settings**: Full control over button labels, modal text, and Pinterest profile URL
- **Shortcode Support**: `[pinterest_follow]` shortcode for standalone follow buttons
- **Responsive Design**: Works on all device sizes
- **Theme Compatible**: Designed to work with any WordPress theme

### 2. Pinterest Auto-Pin Plugin
An advanced WordPress plugin that automatically scans your blog posts and schedules images to be pinned to Pinterest at specified intervals.

**Features:**
- **Automated Scanning**: Scans blog posts within date ranges for images
- **Scheduled Pinning**: Automatically pins images to Pinterest at set intervals
- **Queue Management**: Visual queue system to manage pending pins
- **Pinterest API v5 Integration**: Real Pinterest API integration for actual pinning
- **WordPress Cron**: Background processing using WordPress cron system
- **Board Selection**: Easy board selection with "Get My Boards" feature
- **Error Handling**: Comprehensive error handling and logging
- **Admin Interface**: Complete admin dashboard for settings and management

## 📋 Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- jQuery (included with WordPress)
- Pinterest Developer Account
- Pinterest Production Access (for Auto-Pin plugin write permissions)

## 🛠 Installation

### Pinterest Save & Follow Plugin
1. Navigate to the `wp-pinterest-save-follow/` directory
2. Upload the folder to `/wp-content/plugins/`
3. Activate the plugin in WordPress Admin → Plugins
4. Go to Settings → Pinterest Save & Follow to configure

### Pinterest Auto-Pin Plugin
1. Navigate to the `pinterest-auto-pin-simple/` directory
2. Upload the folder to `/wp-content/plugins/`
3. Activate the plugin in WordPress Admin → Plugins
4. Go to Pinterest Auto-Pin → Settings to configure

## ⚙️ Configuration

### Pinterest API Setup
Both plugins require Pinterest API credentials:

1. **Pinterest App ID**: Get this from [Pinterest Developer Portal](https://developers.pinterest.com/)
2. **Pinterest App Secret**: Get this from Pinterest Developer Portal  
3. **Pinterest Access Token**: Get this from Pinterest Developer Portal
4. **Pinterest Board ID**: Your target Pinterest board ID (for Auto-Pin plugin)

### Pinterest Save & Follow Plugin
Navigate to **Settings → Pinterest Save & Follow** to configure:
- **Pinterest Profile URL**: Your full Pinterest profile URL
- **Enable Automatic Overlay**: Toggle to show/hide save buttons on post images
- **Overlay Button Label**: Text displayed on the save button
- **Follow Prompt Title**: Title of the follow modal
- **Follow Prompt Body**: Description text in the follow modal
- **Follow Button Label**: Text for the follow button

### Pinterest Auto-Pin Plugin
Navigate to **Pinterest Auto-Pin → Settings** to configure:
- **Pinterest API Credentials**: App ID, App Secret, Access Token, Board ID
- **Pin Interval**: Time between pins (minimum 300 seconds)
- **Date Range Scanning**: Select date ranges to scan for images
- **Queue Management**: View and manage pending pins

## 🎯 How It Works

### Pinterest Save & Follow Plugin
1. **Image Detection**: Automatically detects images in post content
2. **Hover Overlay**: Shows a "Save to Pinterest" button on hover
3. **Pinterest Integration**: Opens Pinterest's native save dialog
4. **Follow Prompt**: After saving, shows a modal encouraging users to follow your Pinterest page
5. **Customizable**: All text and behavior can be customized through the settings page

### Pinterest Auto-Pin Plugin
1. **Configure Pinterest API credentials**
2. **Set your target board ID**
3. **Run a scan** to find images in your blog posts
4. **Images are automatically queued** for pinning
5. **Pins are processed automatically** at scheduled intervals
6. **Monitor the queue** to see pin status and manage items

## 📁 File Structure

```
Pinterest Plugin/
├── wp-pinterest-save-follow/              # Pinterest Save & Follow Plugin
│   ├── wp-pinterest-save-follow.php
│   ├── includes/
│   │   ├── class-wpsf-plugin.php
│   │   ├── class-wpsf-settings.php
│   │   └── class-wpsf-frontend.php
│   └── assets/
│       ├── css/frontend.css
│       └── js/frontend.js
├── pinterest-auto-pin-simple/             # Pinterest Auto-Pin Plugin
│   ├── wp-pinterest-auto-pin.php
│   └── assets/
│       ├── css/admin.css
│       └── js/admin.js
└── README.md
```

## 🔧 API Permissions Required

### Pinterest Save & Follow Plugin
- `pins:read` - To read pin information
- `user_accounts:read` - To verify connection

### Pinterest Auto-Pin Plugin
- `pins:write` - To create pins
- `boards:read` - To read board information
- `boards:write` - To create pins on boards
- `user_accounts:read` - To verify connection

## 🐛 Troubleshooting

### Common Issues

1. **Save button not appearing (Save & Follow Plugin)**
   - Check if "Enable automatic image overlay" is enabled in settings
   - Ensure images have proper `src` or `data-src` attributes

2. **Modal not showing after pinning (Save & Follow Plugin)**
   - Verify Pinterest profile URL is set in settings
   - Check browser console for JavaScript errors

3. **Pins not being created (Auto-Pin Plugin)**
   - Verify Pinterest API credentials are correct
   - Check that you have Production access with write permissions
   - Ensure board ID is correct
   - Check WordPress error logs for API errors

4. **Queue not processing (Auto-Pin Plugin)**
   - Verify WordPress cron is working
   - Check if "Process Queue Now" button works manually
   - Ensure database table was created properly

### Debug Mode
Enable WordPress debug mode to see any PHP errors:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📝 Changelog

### Version 2.0.0
- Added Pinterest Auto-Pin Plugin
- Real Pinterest API v5 integration
- Automated pin scheduling and queue management
- WordPress cron system for background processing
- Board selection helper and connection testing

### Version 1.0.0
- Initial release of Pinterest Save & Follow Plugin
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

Created for Upwork project - Pinterest WordPress Plugins Collection

---

**Note**: The Pinterest Auto-Pin Plugin requires Pinterest Production access with write permissions to function properly. The Save & Follow Plugin works with basic Pinterest integration without API credentials.