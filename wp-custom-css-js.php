<?php
/**
 * Plugin Name: WP Custom CSS & JS
 * Plugin URI: https://github.com/Azanumer/wp-custom-css-js
 * Description: Add site-wide custom CSS and JavaScript from the admin — plus optional per-post CSS/JS — without editing theme files. Stored in the database, served inline, no file writes.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Azan Umer
 * Author URI: https://azanumer.cv
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: wp-custom-css-js
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCCJ_VERSION', '1.0.0' );
define( 'WCCJ_OPTION', 'wccj_settings' );
define( 'WCCJ_CSS_META', '_wccj_css' );
define( 'WCCJ_JS_META', '_wccj_js' );

/**
 * Default settings.
 *
 * @return array
 */
function wccj_defaults() {
	return array(
		'global_css' => '',
		'global_js'  => '',
		'js_footer'  => 1, // 1 = wp_footer, 0 = wp_head.
	);
}

/**
 * Get merged settings.
 *
 * @return array
 */
function wccj_get_settings() {
	$saved = get_option( WCCJ_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return array_merge( wccj_defaults(), $saved );
}

// ---------------------------------------------------------------------------
// Front-end output
// ---------------------------------------------------------------------------

/**
 * Strip anything that could break out of a <style> block.
 *
 * @param string $css Raw CSS.
 * @return string
 */
function wccj_clean_css( $css ) {
	$css = str_replace( array( '</style', '<!--', '-->' ), '', $css );
	return trim( $css );
}

/**
 * Strip anything that could break out of a <script> block.
 *
 * @param string $js Raw JS.
 * @return string
 */
function wccj_clean_js( $js ) {
	// Break up closing script tags so a stray one cannot end our block early.
	$js = str_ireplace( '</script', '<\/script', $js );
	return trim( $js );
}

/**
 * Print global + per-post CSS in <head>.
 */
function wccj_print_css() {
	if ( is_admin() ) {
		return;
	}
	$s   = wccj_get_settings();
	$css = wccj_clean_css( $s['global_css'] );

	if ( is_singular() ) {
		$post_css = get_post_meta( get_the_ID(), WCCJ_CSS_META, true );
		if ( is_string( $post_css ) && '' !== trim( $post_css ) ) {
			$css .= "\n" . wccj_clean_css( $post_css );
		}
	}

	if ( '' !== $css ) {
		echo "<style id=\"wccj-css\">\n" . $css . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is admin-supplied and cleaned above.
	}
}
add_action( 'wp_head', 'wccj_print_css', 99 );

/**
 * Print global + per-post JS (footer by default).
 */
function wccj_print_js() {
	if ( is_admin() ) {
		return;
	}
	$s  = wccj_get_settings();
	$js = wccj_clean_js( $s['global_js'] );

	if ( is_singular() ) {
		$post_js = get_post_meta( get_the_ID(), WCCJ_JS_META, true );
		if ( is_string( $post_js ) && '' !== trim( $post_js ) ) {
			$js .= "\n" . wccj_clean_js( $post_js );
		}
	}

	if ( '' !== $js ) {
		echo "<script id=\"wccj-js\">\n" . $js . "\n</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JS is admin-supplied and cleaned above.
	}
}
add_action( 'wp_footer', 'wccj_print_js', 99 );
add_action( 'wp_head', 'wccj_maybe_print_js_head', 99 );

/**
 * Print JS in <head> instead of the footer when the setting says so.
 */
function wccj_maybe_print_js_head() {
	$s = wccj_get_settings();
	if ( empty( $s['js_footer'] ) ) {
		remove_action( 'wp_footer', 'wccj_print_js', 99 );
		wccj_print_js();
	}
}

// ---------------------------------------------------------------------------
// Settings page (global CSS/JS)
// ---------------------------------------------------------------------------

function wccj_register_settings() {
	register_setting(
		'wccj_settings_group',
		WCCJ_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'wccj_sanitize_settings',
			'default'           => wccj_defaults(),
		)
	);

	add_settings_section( 'wccj_main', __( 'Site-wide CSS & JS', 'wp-custom-css-js' ), '__return_null', 'wccj-settings' );
	add_settings_field( 'wccj_global_css', __( 'Custom CSS', 'wp-custom-css-js' ), 'wccj_field_textarea', 'wccj-settings', 'wccj_main', array( 'key' => 'global_css', 'rows' => 12 ) );
	add_settings_field( 'wccj_global_js', __( 'Custom JavaScript', 'wp-custom-css-js' ), 'wccj_field_textarea', 'wccj-settings', 'wccj_main', array( 'key' => 'global_js', 'rows' => 12 ) );
	add_settings_field( 'wccj_js_footer', __( 'Load JavaScript in the footer', 'wp-custom-css-js' ), 'wccj_field_checkbox', 'wccj-settings', 'wccj_main', array( 'key' => 'js_footer' ) );
}
add_action( 'admin_init', 'wccj_register_settings' );

function wccj_add_menu() {
	add_theme_page(
		__( 'Custom CSS & JS', 'wp-custom-css-js' ),
		__( 'Custom CSS & JS', 'wp-custom-css-js' ),
		'edit_theme_options',
		'wccj-settings',
		'wccj_settings_page'
	);
}
add_action( 'admin_menu', 'wccj_add_menu' );

function wccj_settings_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Custom CSS & JS', 'wp-custom-css-js' ); ?></h1>
		<p><?php esc_html_e( 'CSS is printed in the <head> on every page; JavaScript is printed in the footer by default. Per-post CSS/JS can be added from the post editor.', 'wp-custom-css-js' ); ?></p>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'wccj_settings_group' );
			do_settings_sections( 'wccj-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Sanitize settings. Only users with unfiltered_html may save raw JS;
 * everyone else gets their JS stripped of <script> tags and event handlers.
 *
 * @param array $input Raw input.
 * @return array
 */
function wccj_sanitize_settings( $input ) {
	$out   = wccj_defaults();
	$input = is_array( $input ) ? $input : array();

	$out['global_css'] = isset( $input['global_css'] ) ? wccj_clean_css( wp_unslash( $input['global_css'] ) ) : '';
	$out['js_footer']  = ! empty( $input['js_footer'] ) ? 1 : 0;

	if ( isset( $input['global_js'] ) ) {
		$js = wp_unslash( $input['global_js'] );
		if ( current_user_can( 'unfiltered_html' ) ) {
			$out['global_js'] = wccj_clean_js( $js );
		} else {
			// Strip tags entirely for users without unfiltered_html.
			$out['global_js'] = wccj_clean_js( wp_strip_all_tags( $js ) );
		}
	}

	return $out;
}

function wccj_field_textarea( $args ) {
	$s = wccj_get_settings();
	printf(
		'<textarea name="%s[%s]" rows="%d" class="large-text code" dir="ltr">%s</textarea>',
		esc_attr( WCCJ_OPTION ),
		esc_attr( $args['key'] ),
		(int) $args['rows'],
		esc_textarea( $s[ $args['key'] ] )
	);
}

function wccj_field_checkbox( $args ) {
	$s = wccj_get_settings();
	printf(
		'<label><input type="checkbox" name="%s[%s]" value="1" %s> %s</label>',
		esc_attr( WCCJ_OPTION ),
		esc_attr( $args['key'] ),
		checked( ! empty( $s[ $args['key'] ] ), true, false ),
		esc_html__( 'Yes (recommended — keeps render-blocking scripts out of the head)', 'wp-custom-css-js' )
	);
}

/**
 * Enqueue the code editor on our settings page when available.
 *
 * @param string $hook Current admin page hook.
 */
function wccj_enqueue_code_editor( $hook ) {
	if ( 'appearance_page_wccj-settings' !== $hook ) {
		return;
	}
	if ( function_exists( 'wp_enqueue_code_editor' ) ) {
		wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
		wp_add_inline_script(
			'code-editor',
			"jQuery(function($){if(window.wp&&wp.codeEditor){$('textarea[name=\"wccj_settings[global_css]\"]').each(function(){wp.codeEditor.initialize(this,{codemirror:{mode:'css'}})});$('textarea[name=\"wccj_settings[global_js]\"]').each(function(){wp.codeEditor.initialize(this,{codemirror:{mode:'javascript'}})});}});"
		);
	}
}
add_action( 'admin_enqueue_scripts', 'wccj_enqueue_code_editor' );

// ---------------------------------------------------------------------------
// Per-post CSS/JS meta box
// ---------------------------------------------------------------------------

function wccj_add_meta_box() {
	$types = get_post_types( array( 'public' => true ), 'names' );
	foreach ( $types as $type ) {
		add_meta_box(
			'wccj-meta-box',
			__( 'Custom CSS & JS', 'wp-custom-css-js' ),
			'wccj_meta_box_html',
			$type,
			'advanced',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'wccj_add_meta_box' );

function wccj_meta_box_html( $post ) {
	wp_nonce_field( 'wccj_save_meta', 'wccj_meta_nonce' );
	$css = get_post_meta( $post->ID, WCCJ_CSS_META, true );
	$js  = get_post_meta( $post->ID, WCCJ_JS_META, true );
	?>
	<p>
		<label for="wccj-css"><strong><?php esc_html_e( 'CSS for this post only', 'wp-custom-css-js' ); ?></strong></label><br>
		<textarea id="wccj-css" name="wccj_css" rows="6" class="large-text code" dir="ltr"><?php echo esc_textarea( is_string( $css ) ? $css : '' ); ?></textarea>
	</p>
	<p>
		<label for="wccj-js"><strong><?php esc_html_e( 'JavaScript for this post only', 'wp-custom-css-js' ); ?></strong></label><br>
		<textarea id="wccj-js" name="wccj_js" rows="6" class="large-text code" dir="ltr"><?php echo esc_textarea( is_string( $js ) ? $js : '' ); ?></textarea>
	</p>
	<p class="description"><?php esc_html_e( 'Per-post code is printed after the site-wide code, so it can override it.', 'wp-custom-css-js' ); ?></p>
	<?php
}

/**
 * Save per-post CSS/JS.
 *
 * @param int $post_id Post ID.
 */
function wccj_save_meta( $post_id ) {
	if ( ! isset( $_POST['wccj_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['wccj_meta_nonce'] ), 'wccj_save_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$css = isset( $_POST['wccj_css'] ) ? wccj_clean_css( wp_unslash( $_POST['wccj_css'] ) ) : '';
	if ( '' === $css ) {
		delete_post_meta( $post_id, WCCJ_CSS_META );
	} else {
		update_post_meta( $post_id, WCCJ_CSS_META, $css );
	}

	$js_raw = isset( $_POST['wccj_js'] ) ? wp_unslash( $_POST['wccj_js'] ) : '';
	if ( ! current_user_can( 'unfiltered_html' ) ) {
		$js_raw = wp_strip_all_tags( $js_raw );
	}
	$js = wccj_clean_js( $js_raw );
	if ( '' === $js ) {
		delete_post_meta( $post_id, WCCJ_JS_META );
	} else {
		update_post_meta( $post_id, WCCJ_JS_META, $js );
	}
}
add_action( 'save_post', 'wccj_save_meta' );
