<?php


function bp_mcore_activity_fetch_feeds( ) {
	global $bp, $mcore_activity_feeds;

	include_once( ABSPATH . 'wp-includes/rss.php' );

	$feeds = $mcore_activity_feeds;

	$items = array();
	
	$mcore_url = get_option('mcore_url');
	
	foreach ( (array) $feeds as $feed_id => $feed ) {
		$rss = fetch_feed( trim( $feed['feed_url'] ) );

		$maxitems = $rss->get_item_quantity();
		//$maxitems = 1;

		$rss_items = $rss->get_items(0, $maxitems);

		foreach ($rss->get_items(0, $maxitems) as $rss_item ) {
			$date = $rss_item->get_date();
    		$key = strtotime( $date );

			$items[$key]['feed_id'] = $feed_id;
			$items[$key]['link'] = $rss_item->get_link();
			$items[$key]['link'] = preg_replace( '|diff.*prev|', '', $items[$key]['link'] );
			$items[$key]['title'] = $rss_item->get_title();
			$user_id = 0;
			$author = $rss_item->get_author();
			if($author) {
				// This is MediaCore API-specific; the author field is formatted: 
				// allan@mediacore.com (Allan Haggett)
				// We want to query the user via just the email, so we need to 
				// extract that bit from the rest.
				$author_email = explode("(", $author->get_email());
				$mcore_email = trim($author_email[0]);
				// Continuing on ...
				if($userdata = get_user_by('email', $mcore_email)) {
					$user_id = $userdata->ID;
				}
			}
			$items[$key]['author'] = $user_id;
			//$items[$key]['content'] = $rss_item->get_description();
			$media_thumbnail = $rss_item->get_item_tags('http://search.yahoo.com/mrss/', 'thumbnail');
			$items[$key]['content'] .= '<a class="playthis" href="' . $rss_item->get_link() . '">';
			$items[$key]['content'] .= '<img src="' . $media_thumbnail[0]['attribs']['']['url'] . '">';
			$items[$key]['content'] .= '</a>';
		}


	}

	if ( $items ) {
		ksort($items);
		$items = array_reverse($items, true);
	} else {
		return false;
	}

	if ( $bp->loggedin_user->id == 1 ) {
		print '<pre>';
		//print_r($items);
		print '</pre>';
	}
	
	
	
	/* Record found items in activity streams */
	foreach ( (array) $items as $post_date => $post ) {
		$feed_id = $post['feed_id'];
		$author_link = ( $post['author'] ) ? '<a href="' . bp_core_get_user_domain( $post['author'] ) . '">' . bp_core_get_user_displayname( $post['author'] ) . '</a>' :  __( 'A user', 'bp-external-activity' );
		$item_link = '<a href="' . $post['link'] . '">' . $post['title'] . '</a>';
		$activity_action = sprintf( $feeds[$feed_id]['feed_action'], $author_link, $item_link );



		/* Fetch an existing activity_id if one exists. */
		if ( function_exists( 'bp_activity_get_activity_id' ) )
			$id = bp_activity_get_activity_id( array( 'user_id' => $post['author'], 'action' => $activity_action, 'component' => $feeds[$feed_id]['component'], 'type' => $feeds[$feed_id]['type'] ) );

		/* Record or update in activity streams. */
		bp_activity_add( array(
			'id' => $id,
			'content' => $post['content'],
			'user_id' => $post['author'],
			'component' => $feeds[$feed_id]['component'],
			'action' => $activity_action,
			'primary_link' => $post['link'],
			'type' => $feeds[$feed_id]['type'],
			'recorded_time' => gmdate( "Y-m-d H:i:s", $post_date ),
			'hide_sitewide' => false
		) );
	}

	return $items;
}
add_action( 'bp_mcore_activity_cron', 'bp_mcore_activity_fetch_feeds' );
//add_action( 'bp_before_activity_loop', 'bp_mcore_activity_fetch_feeds' );

/* Add a filter option to the filter select box on group activity pages */
function bp_mcore_activity_add_filters() {
	global $mcore_activity_feeds;

	foreach ( $mcore_activity_feeds as $feed ) {
?>
		<option value="<?php echo $feed['type'] ?>"><?php echo $feed['show_text'] ?></option>
<?php
	}
}
add_action( 'bp_group_activity_filter_options', 'bp_mcore_activity_add_filters' );
//add_action( 'bp_activity_filter_options', 'bp_mcore_activity_add_filters' );



/* Fetch group twitter posts after 30 mins expires and someone hits the group page */
function bp_mcore_activity_refetch() {
	global $bp;

	$last_refetch = groups_get_groupmeta( $bp->groups->current_group->id, 'bp_mcore_activity_lastupdate' );
	if ( strtotime( gmdate( "Y-m-d H:i:s" ) ) >= strtotime( '+30 minutes', strtotime( $last_refetch ) ) )
		add_action( 'wp_footer', 'bp_mcore_activity_refetch' );

	/* Refetch the latest group twitter posts via AJAX so we don't stall a page load. */
	//function bp_mcore_activity_refetch() {
	//	global $bp; ?>
		<script type="text/javascript">
			jQuery(document).ready( function() {
				jQuery.post( ajaxurl, {
					action: 'refetch_mcore_activity'
				});
			});
		</script><?php

		groups_update_groupmeta( $bp->groups->current_group->id, 'bp_mcore_activity_lastupdate', gmdate( "Y-m-d H:i:s" ) );
	//}
}
add_action( 'bp_before_activity_loop', 'bp_mcore_activity_refetch' );

/* Refresh via an AJAX post for the group */
function bp_mcore_activity_ajax_refresh() {
	bp_mcore_activity_fetch_feeds( $_POST['group_id'] );
}
add_action( 'wp_ajax_refetch_mcore_activity', 'bp_mcore_activity_ajax_refresh' );

function mediacore_buddy_style() {

		?>
		<style type="text/css">
			.playthis {
				display: block;
				position: relative;
				width: 400px;
			}
			.playthis:after {
				content: "";
				background: url('<?php echo plugins_url() ?>/mediacore-buddypress/images/play.png') 0 0 no-repeat;
				background-position: 0 0;
				height: 95px;
				left: 38%;
				margin: -48px 0 0 0;
				position: absolute;
				top: 50%;
				width: 95px;
				z-index: 100;
			}
			.playthis:hover:after {
				background-position: 0 -95px;
			}
			.playthis img { 
				height: auto;
				max-width: 100%;
				position: relative;
				z-index: 0;
			}
		</style>
		<?php

}

add_action('wp_head', 'mediacore_buddy_style');


?>