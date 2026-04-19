=== Quickfire Cache and Speed Booster ===
Contributors: jimmyredline80
Tags: cache, speed, performance, minify, optimization
Requires at least: 5.5
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Boost website performance with page caching, HTML/CSS/JS minification, lazy loading, script deferring, and server optimization.

== Description ==

Quickfire Cache and Speed Booster is a lightweight yet powerful performance optimization plugin that helps you achieve faster page load times and better Core Web Vitals scores.

= Key Features =

**Page Caching**

* Full page HTML caching for lightning-fast delivery
* Configurable cache lifetime
* Automatic cache clearing on post updates
* Cache statistics in dashboard
* Admin bar quick-clear button

**Minification**

* Minify inline HTML (remove whitespace and comments)
* Minify inline CSS within style tags
* Minify CSS files with cached output
* Minify inline JavaScript
* Minify JS files with cached output

**JavaScript Optimization**

* Defer JavaScript loading
* Delay JavaScript until user interaction
* Configurable delay timeout
* Exclusion list for critical scripts
* Remove jQuery Migrate for modern sites

**Lazy Loading**

* Native lazy loading for images
* Lazy load iframes and embeds
* Lazy load videos with preload="none"

**Server Optimization**

* GZIP compression via .htaccess
* Browser caching with proper expires headers
* Remove query strings from static resources

**WordPress Cleanup**

* Disable WordPress emojis
* Disable oEmbed functionality
* Disable XML-RPC
* Remove shortlink from header
* Remove RSD link
* Remove WLW Manifest link
* Remove feed links
* Remove REST API link
* Remove WordPress version number

**Built-in Speed Test**

* Test your homepage loading speed
* Track TTFB, load time, and page size
* Color-coded performance ratings
* Historical test data with trends

= Dashboard =

The modern admin dashboard includes:

* Optimization score showing enabled features
* Quick action buttons for common tasks
* Cache statistics at a glance
* One-click cache clearing

= Smart Caching =

Page caching automatically excludes:

* Logged-in users
* Admin pages
* AJAX requests
* POST requests
* Pages with query strings
* Search and 404 pages
* WooCommerce cart and checkout

= Pro Version =

Upgrade to Pro for additional features:

* Aggressive HTML minification
* Aggressive CSS minification
* Aggressive JS minification
* Combine CSS files
* Combine JS files
* Async CSS loading
* Critical CSS injection
* Force no-cache headers
* Heartbeat control
* Resource hints (preconnect, DNS prefetch, preload)

[Get Pro Version](https://www.plugins-for-wp.com/?ssp_src=repo-speed-booster)

== External Services ==

This plugin connects to external services in the following circumstances:

= Pro Version License Validation =

If you upgrade to the Pro version, the plugin contacts our licensing server to validate your license key.

* **Service:** Plugins for WP License Server
* **When:** On plugin activation and periodic license checks
* **Data sent:** License key, site URL, product identifier
* **Terms of Service:** [https://www.plugins-for-wp.com/terms/](https://www.plugins-for-wp.com/terms/)
* **Privacy Policy:** [https://www.plugins-for-wp.com/privacy/](https://www.plugins-for-wp.com/privacy/)

The free version of this plugin does not connect to any external services.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install via Plugins > Add New
2. Activate the plugin through the Plugins menu
3. Go to Quickfire Cache in the admin menu to configure settings
4. Enable desired optimizations and save

= Recommended Setup =

For best results, enable these features:

1. Page Cache with 3600 second lifetime
2. GZIP Compression
3. Browser Caching
4. Minify HTML
5. Minify CSS (inline and files)
6. Defer JavaScript
7. Lazy Load Images
8. Disable Emojis

Always test your site after enabling each feature to ensure compatibility with your theme and plugins.

== Frequently Asked Questions ==

= Will this work with my theme? =

Quickfire Cache and Speed Booster is designed to work with most WordPress themes. However, aggressive minification or JavaScript deferring can occasionally cause issues. Start with basic features and enable more as you verify compatibility.

= Does this work with WooCommerce? =

Yes! The plugin automatically excludes cart, checkout, and other dynamic WooCommerce pages from caching.

= Can I use this with other caching plugins? =

We recommend using only one page caching solution at a time. You can disable our page cache and use only the minification and optimization features alongside other caching plugins.

= Why is my JavaScript not working after enabling Delay JS? =

Some scripts need to load immediately. Add keywords from the problematic script's filename or handle to the Delay JS Exclusions list (one per line). Common exclusions include jQuery-dependent scripts.

= How do I clear the cache? =

You can clear the cache in several ways:
* Click the Clear Cache button in the admin bar
* Use the Quick Actions on the Dashboard tab
* Cache automatically clears when you update posts/pages

= Does this modify my .htaccess file? =

Yes, when you enable GZIP Compression or Browser Caching, the plugin adds rules to your .htaccess file. These are safely removed when you disable the features or deactivate the plugin.

= What if minification breaks my site? =

Simply disable the minification options in settings. All changes are non-destructive and your original files remain untouched.

== Screenshots ==

1. Dashboard with optimization score and quick actions
2. Caching settings with statistics
3. Optimization settings for HTML, CSS, and JavaScript
4. Speed test tool with historical results
5. Admin bar cache clear button

== Changelog ==

= 1.0.0 =
* Initial release
* Page caching with configurable lifetime
* HTML, CSS, and JavaScript minification
* JavaScript defer and delay options
* Lazy loading for images, iframes, and videos
* GZIP compression and browser caching
* WordPress cleanup options
* Built-in speed testing tool
* Modern admin dashboard with optimization score

== Upgrade Notice ==

= 1.0.0 =
Initial release of Quickfire Cache and Speed Booster.

== Privacy ==

This plugin does not collect any personal data from your site visitors.

The plugin stores cached HTML pages and minified assets locally on your server in the `/wp-content/cache/ssbc/` directory.

Speed test results are stored in your WordPress database and contain only URLs, load times, and page sizes - no personal information.

If you upgrade to Pro, the plugin may contact our server for license validation. See the External Services section for full details.

== Credits ==

Developed by Stupid Simple Plugins
Website: https://www.plugins-for-wp.com/