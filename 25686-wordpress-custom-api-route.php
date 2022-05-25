<?php
/**
 * Plugin Name: Mosaika — Créer ses propres routes d'API REST dans WordPress
 * Description: Exemple de code accompagnant l'article de blog expliquant comment étendre l'API REST WordPress.
 * Author: Pierre Saïkali
 * Author URI: https://mosaika.fr/creation-api-rest-wordpress/
 * Version: 1.0.0
 */

namespace Mosaika\Custom_API_Route;

defined( 'ABSPATH' ) || exit;

define( 'MSK_WP_API_DIR', plugin_dir_path( __FILE__ ) );
define( 'MSK_WP_API_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Chargement des fichiers vitaux de cette extension.
 *
 * @return void
 */
function require_files() {
	require_once MSK_WP_API_DIR . '/src/api.php';
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\require_files' );
