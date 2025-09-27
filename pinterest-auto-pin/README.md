# Pinterest Auto-Pin WordPress Plugin

A powerful WordPress plugin that automatically scans your blog posts by date range and schedules images to be pinned to your Pinterest boards with custom intervals.

## 🚀 Features

- **Date Range Scanning**: Scan blog posts within specific date ranges
- **Automatic Image Extraction**: Extracts images from post content, featured images, and galleries
- **Pinterest API Integration**: Direct integration with Pinterest API for seamless pinning
- **Custom Scheduling**: Set custom intervals for when images should be pinned
- **Queue Management**: View and manage your pin queue with status tracking
- **Image Quality Filtering**: Only pins high-quality images meeting size requirements
- **Custom Pin Descriptions**: Template-based pin descriptions with variables
- **Multiple Post Types**: Support for posts, pages, and custom post types
- **Category Filtering**: Include or exclude specific categories
- **Manual Override**: Pin images immediately or remove from queue

## 📋 Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Pinterest Developer Account
- Pinterest App with API access

## 🛠 Installation

1. Download the plugin files
2. Upload the `pinterest-auto-pin` folder to `/wp-content/plugins/`
3. Activate the plugin in WordPress Admin → Plugins
4. Go to Pinterest Auto-Pin → Settings to configure your Pinterest API credentials

## ⚙️ Pinterest API Setup

### Step 1: Create Pinterest App
1. Go to [Pinterest Developers](https://developers.pinterest.com/)
2. Create a new app
3. Note down your App ID and App Secret

### Step 2: Get Access Token
1. In your Pinterest app settings, generate an access token
2. Copy the access token for use in the plugin

### Step 3: Get Board ID
1. Go to your Pinterest board
2. The board ID is in the URL: `https://pinterest.com/username/board-name/`
3. The board ID is the last part of the URL

## 🎯 How to Use

### 1. Configure Settings
Navigate to **Pinterest Auto-Pin → Settings** and configure:
- Pinterest API credentials
- Target Pinterest board
- Pin interval (minimum 5 minutes)
- Pin description template
- Image quality requirements

### 2. Scan Posts
Go to **Pinterest Auto-Pin → Scan Posts**:
- Select date range for posts to scan
- Choose post types to include
- Click "Start Scan" to begin

### 3. Manage Queue
Visit **Pinterest Auto-Pin → Pin Queue** to:
- View all scheduled pins
- Pin images immediately
- Remove items from queue
- Monitor pin status

## 📁 File Structure

```
pinterest-auto-pin/
├── wp-pinterest-auto-pin.php          # Main plugin file
├── includes/
│   ├── class-wppap-plugin.php         # Core plugin class
│   ├── class-wppap-settings.php       # Admin settings
│   ├── class-wppap-pinterest-api.php  # Pinterest API integration
│   ├── class-wppap-image-scanner.php  # Image extraction
│   ├── class-wppap-scheduler.php      # Scheduling system
│   └── class-wppap-queue-manager.php  # Queue management
├── assets/
│   ├── css/
│   │   └── admin.css                  # Admin styles
│   └── js/
│       └── admin.js                   # Admin JavaScript
└── README.md                          # This file
```

## 🎨 Pin Description Templates

Use these variables in your pin description template:
- `{site_name}` - Your site name
- `{post_title}` - Post title
- `{post_excerpt}` - Post excerpt
- `{post_url}` - Post URL
- `{image_alt}` - Image alt text

Example template:
```
Check out this amazing content from {site_name}! {post_title}
```

## 🔧 Technical Details

### Database Tables
- `wp_wppap_pin_queue` - Stores scheduled pins and their status

### Cron Jobs
- `wppap_process_pin_queue` - Processes the pin queue at scheduled intervals

### Hooks Used
- `wp_ajax_*` - AJAX handlers for admin actions
- `cron_schedules` - Custom cron intervals
- `admin_menu` - Admin menu registration
- `admin_enqueue_scripts` - Admin asset loading

## 🐛 Troubleshooting

### Common Issues

1. **Pinterest API Errors**
   - Verify your API credentials are correct
   - Check that your access token has the required permissions
   - Ensure your Pinterest app is approved for production use

2. **Images Not Being Pinned**
   - Check that images meet minimum size requirements
   - Verify the Pinterest board ID is correct
   - Check the pin queue for error messages

3. **Scheduling Issues**
   - Ensure WordPress cron is working properly
   - Check that the pin interval is at least 300 seconds (5 minutes)
   - Verify auto-pin is enabled in settings

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📝 Changelog

### Version 1.0.0
- Initial release
- Pinterest API integration
- Date range scanning
- Custom scheduling
- Queue management
- Image quality filtering
- Template-based descriptions

## 🤝 Support

For support or feature requests, please contact the developer through Upwork.

## 📄 License

GPL-2.0-or-later - See LICENSE file for details.

## ⚠️ Important Notes

- Pinterest has rate limits for API calls
- Minimum pin interval is 5 minutes (300 seconds)
- Images must meet Pinterest's quality requirements
- Always test with a small date range first
- Monitor your Pinterest account for any policy violations

## 🔒 Security

- All API credentials are stored securely in WordPress options
- AJAX requests are protected with nonces
- User capabilities are checked for all admin actions
- Input data is sanitized and validated

---

**Note**: This plugin requires a valid Pinterest Developer account and API access. Make sure to comply with Pinterest's API terms of service and content policies.
