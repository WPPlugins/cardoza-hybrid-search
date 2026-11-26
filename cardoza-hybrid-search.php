<?php
/**
 * Plugin Name:       Cardoza Hybrid Search
 * Description:       AI-powered hybrid search for WordPress: combines keyword (full-text) and semantic vector search using embeddings, with optional AI answers from an LLM. Runs on MySQL alone, so it works on shared hosting with no vector database.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      8.2
 * Author:            Vinoj Cardoza
 * Author URI:        https://vinojcardoza.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cardoza-hybrid-search
 * Domain Path:       /languages
 *
 * @package Cardoza_Hybrid_Search
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CARDOZA_HS_PLUGIN_FILE', __FILE__ );
define( 'CARDOZA_HS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CARDOZA_HS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CARDOZA_HS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once CARDOZA_HS_PLUGIN_PATH . 'app/class-autoloader.php';
Cardoza_Hybrid_Search\Autoloader::register();

// Load all the hooks.
( new Cardoza_Hybrid_Search\Hooks_Manager() )->register();
