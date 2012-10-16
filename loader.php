<?php
/*
Plugin Name: MediaCore Activity Feed
Plugin URI: http://mediacore.com
Description: Whenever someone uploads a new video, this plugin will add the latest to the site-wide activity stream. It depends on the MediaCore plugin for Wordpress.
Version: 1.0
Requires at least: WordPress 2.9.1 / BuddyPress 1.2
Tested up to: WordPress 3.4.1 / BuddyPress 1.5.6
License: GNU/GPL 2
Author: Allan Haggett <allan@mediacore.com>
Author URI: http://mediacore.com
*/

/* Based in part on Andy Peatling's BP External Group Blogs */
/* This was the BP External Activity plugin http://wordpress.org/extend/plugins/bp-external-activity/ which has been abandoned in an un-working state. */
/* I (Allan Haggett <allan@mediacore.com>) forked it, updated it so it works (several deprecated functions), and modified to act as stand-alone plugin specifically for MediaCore XML feeds, and relying on the general Wordpress MediaCore plugin being installed for its configuration setting of the users' MediaCore.tv address.
*/

$mcore_url = get_option('mcore_url');
$mcore_latest = $mcore_url . '/latest.xml';

$mcore_activity_feeds = array(
	array(
		'feed_url' => $mcore_latest,
		'feed_action' => '%s added to MediaCore %s',
		'component' => 'mediacore',
		'type' => 'mediacore_add',
		'show_text' => __( 'Show Additions to MediaCore', 'bp-mcore-activity' )
		)
);

/* Only load the plugin functions if BuddyPress is loaded and initialized. */
function bp_mcore_activity_init() {
	require( dirname( __FILE__ ) . '/mcore-buddy.php' );
}
add_action( 'bp_init', 'bp_mcore_activity_init' );


/* On activation register the cron to refresh external blog posts. */
function bp_mcore_activity_activate() {
	wp_schedule_event( time(), 'hourly', 'bp_mcore_activity_cron' );
}
register_activation_hook( __FILE__, 'bp_mcore_activity_activate' );

/* On deacativation, clear the cron. */
function bp_mcore_activity_deactivate() {
	wp_clear_scheduled_hook( 'bp_mcore_activity_cron' );

	/* Remove all external blog activity */
	if ( function_exists( 'bp_activity_delete' ) )
		bp_activity_delete( array( 'type' => 'mcore_activity' ) );
}
register_deactivation_hook( __FILE__, 'bp_mcore_activity_deactivate' );

?>