<?php
/**
 * Cardoza Hybrid Search uninstall routine.
 *
 * Runs automatically when the plugin is deleted from the WordPress admin
 * (Plugins → Delete). Removes all data created by the plugin: the index
 * tables, the schema version option and any pending indexing cron events.
 *
 * Note: this file is NOT executed on deactivation, only on deletion.
 *
 * @package Cardoza_Hybrid_Search
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$cardoza_hs_tables = array(
	$wpdb->prefix . 'cardoza_hs_chunks',
	$wpdb->prefix . 'cardoza_hs_documents',
);

foreach ( $cardoza_hs_tables as $cardoza_hs_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS $cardoza_hs_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
}

delete_option( 'cardoza_hs_db_version' );
delete_option( 'cardoza_hs_embedding_endpoint' );
delete_option( 'cardoza_hs_embedding_api_key' );
delete_option( 'cardoza_hs_embedding_model' );
delete_option( 'cardoza_hs_post_types' );
delete_option( 'cardoza_hs_replace_search' );
delete_option( 'cardoza_hs_auto_answer' );
delete_option( 'cardoza_hs_chat_endpoint' );
delete_option( 'cardoza_hs_chat_api_key' );
delete_option( 'cardoza_hs_chat_model' );
delete_option( 'cardoza_hs_chat_prompt' );

delete_option( 'cardoza_hs_reindex' );
delete_option( 'cardoza_hs_reindex_lock' );

delete_post_meta_by_key( '_cardoza_hs_exclude' );

// Cached search results, answers and rate-limit counters.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_cardoza\_hs\_%' OR option_name LIKE '\_transient\_timeout\_cardoza\_hs\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

wp_unschedule_hook( 'cardoza_hs_index_post' );
wp_unschedule_hook( 'cardoza_hs_reindex_batch' );
