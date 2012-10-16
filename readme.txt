=== MediaCore BuddyPress ===
Contributors: getmediacore, boonebgorges, cuny-academic-commons
Tags: buddypress, activity, external, mediacore
Requires at least: WordPress 2.9.1 / BuddyPress 1.2
Tested up to: WordPress 3.4.2 / BuddyPress 1.6.1
Stable Tag: 1.0

== Description ==

In conjunction with the standard MediaCore plugin (http://wordpress.org/extend/plugins/mediacore/), when someone publishes a new video to your MediaCore site, the activity is noted in your site-wide activity feed. If the email address of the MediaCore publisher matches a Wordpress user, the activity is linked to the user.

This plugin used to allow admins to import data from arbitrary RSS feeds into their BuddyPress site-wide activity stream. It has been forked, fixed and customized to suit the needs of MediaCore.

You may find that you need to decrease your Simplepie cache time to make it work:
`add_filter( 'wp_feed_cache_transient_lifetime', create_function('$a', 'return 600;') );`
reduces the RSS cache to ten minutes, for example. Put that in your bp-custom.php file if you are having problems with the plugin.

== Installation ==

 1. Upload the mediacore-buddypress directory to your WP plugins folder
 2. Activate

== Changelog ==

= 1.0 =
* Initial release.