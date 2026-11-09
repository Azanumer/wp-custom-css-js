<?php
/**
 * Uninstall WP Custom CSS & JS.
 *
 * Removes the settings option and all per-post CSS/JS postmeta.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wccj_settings' );

global $wpdb;
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s)",
		'_wccj_css',
		'_wccj_js'
	)
);
