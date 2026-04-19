=== Scripts and Styles Manager ===
Contributors: abdelhalimkhouas
Tags: scripts, styles, performance, optimization, assets
Tested up to: 6.7
Stable tag: 1.0.0
License: GPL v3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Easily manage the scripts and styles loaded on your WordPress pages to improve performance by toggling off unnecessary assets.

== Description ==

**Scripts and Styles Manager** helps you optimize your WordPress website's performance by letting you manage the scripts and styles loaded on each page. Identify and disable assets that you don't need on specific pages, reducing page load times and enhancing user experience.

**Features:**
- View all scripts and styles loaded on a specific page.
- Toggle the loading of assets on/off with a simple interface.
- Copy the source URL of an asset to the clipboard.
- Pagination and search for easy navigation of assets.
- Responsive and user-friendly design.
- Lightweight and optimized for performance.

This plugin is ideal for users who want to optimize their website’s loading speed without diving into code.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/scripts-and-styles-manager` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to the **Scripts and Styles Manager** page in the WordPress admin dashboard.
4. Select a page and manage the assets as needed.

== Frequently Asked Questions ==

= How does this plugin work? =
The plugin scans the scripts and styles enqueued on each page and displays them in a list. You can toggle specific assets on or off to control their loading.

= Does this plugin modify files permanently? =
No, it only disables assets dynamically for specific pages during runtime. It doesn’t alter the files or the database.

= Will it break my site if I disable certain assets? =
It’s possible. Be cautious when disabling assets. Make sure to test your pages after making changes.

= Is this plugin compatible with caching plugins? =
Yes, but you may need to clear your cache after making changes for them to take effect.

= Why I don't see scripts from all the plugins I installed? =
The plugin scans the scripts and styles enqueued using wp_enqueue_script, scripts that are loaded dynamically or use other methods will not be fetched

= How do I contact support? =
You can reach us at [abdelhalimkhouas@gmail.com](mailto:abdelhalimkhouas@gmail.com).

== Screenshots ==

1. The Scripts and Styles Manager interface with toggles for each asset.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added asset management features.
* Included pagination, search, and copy-to-clipboard functionality.

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install and activate to manage your assets.

== License ==

This plugin is licensed under the MIT License. See [License URI](https://opensource.org/licenses/MIT) for details.
