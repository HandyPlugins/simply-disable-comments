<?php
/**
 * Plugin Name:     Simply Disable Comments
 * Plugin URI:      https://wordpress.org/plugins/simply-disable-comments/
 * Description:     A simple way to complete disable comments on your WordPress.
 * Author:          HandyPlugins
 * Author URI:      https://handyplugins.co/
 * Text Domain:     simply-disable-comments
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         SimplyDisableComments
 */

namespace SimplyDisableComments;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'SIMPLY_DISABLE_COMMENTS_VERSION', '0.1.0' );
define( 'SIMPLY_DISABLE_COMMENTS_PLUGIN_FILE', __FILE__ );
define( 'SIMPLY_DISABLE_COMMENTS_URL', plugin_dir_url( __FILE__ ) );
define( 'SIMPLY_DISABLE_COMMENTS_PATH', plugin_dir_path( __FILE__ ) );

$network_activated = is_network_wide( __FILE__ );

if ( ! defined( 'SIMPLY_DISABLE_COMMENTS_IS_NETWORK' ) ) {
	define( 'SIMPLY_DISABLE_COMMENTS_IS_NETWORK', $network_activated );
}

/**
 * Setup routine.
 *
 * @return void
 */
function setup() {
	add_action( 'init', __NAMESPACE__ . '\\i18n' );
	add_action( 'admin_init', __NAMESPACE__ . '\\remove_from_post_type_support' );
	add_action( 'admin_menu', __NAMESPACE__ . '\\remove_admin_menu' );
	add_action( 'admin_print_styles-index.php', __NAMESPACE__ . '\\admin_styles' );
	add_action( 'admin_print_styles-profile.php', __NAMESPACE__ . '\\admin_styles' );
	add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\remove_dashboard_widget' );
	add_filter( 'pre_option_default_pingback_flag', '__return_zero' );
	add_filter( 'xmlrpc_methods', __NAMESPACE__ . '\\disable_xmlrpc' );
	add_filter( 'rest_endpoints', __NAMESPACE__ . '\\remove_rest_api_endpoints' );
	add_filter( 'rest_pre_insert_comment', __NAMESPACE__ . '\\disable_rest_api_comments', 10, 2 );
	add_filter( 'comments_open', '__return_false', 20, 2 );
	add_filter( 'pings_open', '__return_false', 20, 2 );
	add_filter( 'comments_array', '__return_empty_array', 10, 2 );
	add_action( 'template_redirect', __NAMESPACE__ . '\\remove_from_admin_bar' );
	add_action( 'admin_init', __NAMESPACE__ . '\\remove_from_admin_bar' );
	add_action( 'init', __NAMESPACE__ . '\\remove_comments_blocks', 20 );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_block_editor_scripts', 20 );
}

/**
 * Registers the default textdomain.
 *
 * @return void
 */
function i18n() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'simply-disable-comments' );
	load_textdomain( 'simply-disable-comments', WP_LANG_DIR . '/simply-disable-comments/simply-disable-comments-' . $locale . '.mo' );
	load_plugin_textdomain( 'simply-disable-comments', false, plugin_basename( plugin_dir_path( __FILE__ ) ) . '/languages/' );
}

/**
 * Unregister comments blocks
 *
 * @return void
 */
function remove_comments_blocks() {
	$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

	$comment_blocks = [
		'core/comment-author-name',
		'core/comment-content',
		'core/comment-date',
		'core/comment-edit-link',
		'core/comment-reply-link',
		'core/comment-template',
		'core/comments',
		'core/comments-pagination',
		'core/comments-pagination-next',
		'core/comments-pagination-numbers',
		'core/comments-pagination-previous',
		'core/comments-title',
		'core/latest-comments',
		'core/post-comments-form',
		'core/post-comments',
	];

	foreach ( $comment_blocks as $block ) {
		if ( isset( $registered_blocks[ $block ] ) ) {
			unregister_block_type( $block );
		}
	}
}

/**
 * Remove registered comments blocks from the editor
 *
 * @return void
 */
function enqueue_block_editor_scripts() {

	$assets_data = include_once SIMPLY_DISABLE_COMMENTS_PATH . '/dist/js/editor.asset.php';

	wp_enqueue_script(
		'simply-disable-comments-block-editor',
		SIMPLY_DISABLE_COMMENTS_URL . 'dist/js/editor.js',
		$assets_data['dependencies'],
		$assets_data['version'],
		true
	);
}

/**
 * Remove comment item from admin bar
 *
 * @return void
 */
function remove_from_admin_bar() {
	if ( is_admin_bar_showing() ) {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
		if ( is_multisite() ) {
			add_action( 'admin_bar_menu', __NAMESPACE__ . '\\remove_from_network_admin_bar', 500 );
		}
	}
}

/**
 * Remove from network admin bar
 *
 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
 *
 * @return void
 */
function remove_from_network_admin_bar( $wp_admin_bar ) {
	$wp_admin_bar->remove_menu( 'blog-' . get_current_blog_id() . '-c' );
}

/**
 * Disable support for comments and trackbacks in post types
 *
 * @return void
 */
function remove_from_post_type_support() {
	foreach ( get_post_types() as $post_type ) {
		if ( post_type_supports( $post_type, 'comments' ) ) {
			\remove_post_type_support( $post_type, 'comments' );
			\remove_post_type_support( $post_type, 'trackbacks' );
		}
	}
}

/**
 * Remove admin menu
 *
 * @return void
 */
function remove_admin_menu() {
	global $pagenow;

	if ( 'comment.php' === $pagenow || 'edit-comments.php' === $pagenow ) {
		wp_die( esc_html__( 'Comments are closed.', 'simply-disable-comments' ), '', [ 'response' => 403 ] );
	}

	remove_menu_page( 'edit-comments.php' );

	if ( 'options-discussion.php' === $pagenow ) {
		wp_die( esc_html__( 'Comments are closed.', 'simply-disable-comments' ), '', [ 'response' => 403 ] );
	}

	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
}

/**
 * Hide on dashboard
 *
 * @return void
 */
function admin_styles() {
	?>
	<style>
		#dashboard_right_now .comment-count,
		#dashboard_right_now .comment-mod-count,
		#latest-comments,
		#welcome-panel .welcome-comments,
		.user-comment-shortcuts-wrap {
			display: none !important;
		}
	</style>
	<?php
}

/**
 * Remove dashboard widget
 *
 * @return void
 */
function remove_dashboard_widget() {
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
}

/**
 * Remove newComment method from XMLRPC
 *
 * @param array $methods XMLRPC methods.
 *
 * @return mixed
 */
function disable_xmlrpc( $methods ) {
	unset( $methods['wp.newComment'] );

	return $methods;
}


/**
 * Remove the comments endpoint for the REST API
 *
 * @param array $endpoints Registered endpoints.
 */
function remove_rest_api_endpoints( $endpoints ) {
	unset( $endpoints['comments'] );

	return $endpoints;
}

/**
 * Remove the comments endpoint for the REST API
 *
 * @param array|\WP_Error  $prepared_comment The prepared comment data for wp_insert_comment().
 * @param \WP_REST_Request $request          Request used to insert the comment.
 *
 * @return \WP_Error
 */
function disable_rest_api_comments( $prepared_comment, $request ) {
	return new \WP_Error( 'rest_comments_disabled', esc_html__( 'Comments are closed.', 'simply-disable-comments' ), [ 'status' => 403 ] );
}

/**
 * Register general settings on multisite (to give site based control)
 *
 * @return void
 */
function register_general_settings_field() {
	register_setting( 'general', 'simply_disable_comments_enable_comments', 'boolval' );

	add_settings_field(
		'simply-disable-comments-enable-comments',
		esc_html__( 'Enable Comments', 'simply-disable-comments' ),
		__NAMESPACE__ . '\\render_general_settings_field',
		'general',
	);
}

/**
 * Settings field
 *
 * @return void
 */
function render_general_settings_field() {
	$value = get_option( 'simply_disable_comments_enable_comments', false );
	?>
	<fieldset>
		<legend class="screen-reader-text">
			<span><?php esc_html_e( 'Enable Comments', 'simply-disable-comments' ); ?></span>
		</legend>
		<label for="simply_disable_comments_enable_comments">
			<input <?php checked( true, $value ); ?>name="simply_disable_comments_enable_comments" type="checkbox" id="simply_disable_comments_enable_comments" value="1">
			<?php esc_html_e( 'Enable commenting on this site', 'simply-disable-comments' ); ?>
		</label>
	</fieldset>
	<?php
}

/**
 * Is plugin activated network wide?
 *
 * @param string $plugin_file file path
 *
 * @return bool
 * @since 1.0
 */
function is_network_wide( $plugin_file ) {
	if ( ! is_multisite() ) {
		return false;
	}

	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}

	return is_plugin_active_for_network( plugin_basename( $plugin_file ) );
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );

/**
 * Bootstrap the plugin
 *
 * @return void
 */
function init() {
	$comments_enabled = false;

	if ( SIMPLY_DISABLE_COMMENTS_IS_NETWORK ) {
		add_filter( 'admin_init', __NAMESPACE__ . '\\register_general_settings_field' );
		$comments_enabled = get_option( 'simply_disable_comments_enable_comments', false );
	}

	$comment_status = apply_filters( 'simply_disable_comments_enable_comments', $comments_enabled );

	if ( ! $comment_status ) {
		setup();
	}
}
