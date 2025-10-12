<?php
/**
 * SDK: WPPulse – Plugin Analytics Engine
 * Description: Lightweight plugin analytics SDK with optional deactivation feedback modal.
 * Author: Turbo Addons
 * @package WPPulse
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPPulse_SDK {
	private static $textdomain = 'wp-pulse';
	private static $cfg = [];
	private static $file = '';

	public static function init( $file, $args ) {
		self::$file = $file;
		self::$cfg  = wp_parse_args( $args, [
			'name'     => '',
			'slug'     => '',
			'version'  => '',
			'endpoint' => '',
			'textdomain' => '',
		]);

		// ✅ detect client plugin textdomain
		$plugin_data = get_file_data( $file, [ 'TextDomain' => 'Text Domain' ] );
		if ( ! empty( $plugin_data['TextDomain'] ) ) {
			self::$textdomain = sanitize_key( $plugin_data['TextDomain'] );
		}
		if ( ! empty( self::$cfg['textdomain'] ) ) {
			self::$textdomain = sanitize_key( self::$cfg['textdomain'] );
		}

		register_activation_hook( self::$file, [ __CLASS__, 'activated' ] );
		register_deactivation_hook( self::$file, [ __CLASS__, 'noop' ] );
		add_action( 'upgrader_process_complete', [ __CLASS__, 'updated' ], 10, 2 );
		add_action( 'deleted_plugin', [ __CLASS__, 'uninstalled' ], 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( self::$file ), [ __CLASS__, 'filter_links' ] );
		add_action( 'admin_footer', [ __CLASS__, 'print_modal' ] );
		add_action( 'wp_ajax_wppulse_reason_submit', [ __CLASS__, 'ajax_reason_submit' ] );
		add_action( 'wp_ajax_wppulse_reason_skip', [ __CLASS__, 'ajax_reason_skip' ] );
	}

	public static function activated() { self::send( 'activated' ); }
	public static function noop() {}
	public static function updated( $upgrader, $options ) {
		if ( 'plugin' === $options['type'] && 'update' === $options['action']
			&& in_array( plugin_basename( self::$file ), $options['plugins'] ?? [], true ) ) {
			self::send( 'updated' );
		}
	}
	public static function uninstalled( $plugin ) {
		if ( $plugin === plugin_basename( self::$file ) ) self::send( 'uninstalled' );
	}
	public static function filter_links( $links ) {
		if ( isset( $links['deactivate'] ) )
			$links['deactivate'] = str_replace( '<a ', '<a class="wppulse-deactivate-link" ', $links['deactivate'] );
		return $links;
	}

	public static function print_modal() {
		global $pagenow;
		if ( 'plugins.php' !== $pagenow ) return;
		$nonce = wp_create_nonce( 'wppulse_reason_nonce' );
		?>
		<style>/* same CSS as before */</style>
		<div id="wppulse-modal"><div class="wppulse-modal-wrap">
			<div class="wppulse-header">
				<?php // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain ?>
				<h2><?php esc_html_e( 'Before deactivation, could you tell us why?', self::$textdomain ); ?></h2>
			</div>
			<div class="wppulse-body">
				<div id="wppulse-reason-grid" class="wppulse-reason-grid">
				<?php
				$reasons = [
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
					'couldnt-understand'  => __( "Couldn't understand", self::$textdomain ),
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
					'found-better-plugin' => __( 'Found a better plugin', self::$textdomain ),
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
					'missing-feature'     => __( 'Missing a feature', self::$textdomain ),
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
					'not-working'         => __( 'Not working', self::$textdomain ),
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
					'not-what-looking'    => __( 'Not what I was looking for', self::$textdomain ),
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
					'didnt-work-expected' => __( "Didn't work as expected", self::$textdomain ),
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
					'others'              => __( 'Others', self::$textdomain ),
				];
				foreach ( $reasons as $slug => $label ) {
					$icon = match( $slug ) {
						'couldnt-understand' => 'editor-help',
						'found-better-plugin' => 'awards',
						'missing-feature' => 'admin-tools',
						'not-working' => 'dismiss',
						'not-what-looking' => 'search',
						'didnt-work-expected' => 'warning',
						default => 'ellipsis'
					};
					printf(
						'<div class="wppulse-reason-item" data-value="%1$s"><i class="dashicons dashicons-%3$s"></i><span>%2$s</span></div>',
						esc_attr( $slug ), esc_html( $label ), esc_attr( $icon )
					);
				}
				?>
				</div>
				<?php // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain ?>
				<textarea id="wppulse-desc" placeholder="<?php esc_attr_e( 'Optional: tell us more...', self::$textdomain ); ?>"></textarea>
			</div>
			<div class="wppulse-footer">
				<?php // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain ?>
				<button id="wppulse-skip" class="button"><?php esc_html_e( 'Skip & Deactivate', self::$textdomain ); ?></button>
				<div>
					<?php // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain ?>
					<button id="wppulse-cancel" class="button"><?php esc_html_e( 'Cancel', self::$textdomain ); ?></button>
					<?php // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain ?>
					<button id="wppulse-submit" class="button button-primary"><?php esc_html_e( 'Submit & Deactivate', self::$textdomain ); ?></button>
				</div>
			</div>
		</div></div>
		<script>/* same JS as before */</script>
		<?php
	}

	public static function ajax_reason_skip() {
		check_ajax_referer( 'wppulse_reason_nonce', 'nonce' );
		if ( ! current_user_can( 'activate_plugins' ) ) {
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
			wp_send_json_error( __( 'Permission denied.', self::$textdomain ) );
		}
		self::send( 'deactivated' );
		wp_send_json_success();
	}

	public static function ajax_reason_submit() {
		check_ajax_referer( 'wppulse_reason_nonce', 'nonce' );
		if ( ! current_user_can( 'activate_plugins' ) ) {
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
			wp_send_json_error( __( 'Permission denied.', self::$textdomain ) );
		}
		$reason = isset( $_POST['reason_id'] ) ? sanitize_text_field( wp_unslash( $_POST['reason_id'] ) ) : '';
		$text   = isset( $_POST['reason_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason_text'] ) ) : '';
		self::send( 'deactivated', [ 'reason_id' => $reason, 'reason_text' => $text ] );
		wp_send_json_success();
	}

	private static function send( $status, $extra = [] ) {
		if ( empty( self::$cfg['endpoint'] ) ) return;
		$body = array_merge( [
			'domain'  => esc_url_raw( home_url() ),
			'email'   => sanitize_email( get_bloginfo( 'admin_email' ) ),
			'plugin'  => sanitize_text_field( self::$cfg['name'] ),
			'version' => sanitize_text_field( self::$cfg['version'] ),
			'status'  => sanitize_text_field( $status ),
		], $extra );
		wp_remote_post( self::$cfg['endpoint'], [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( $body ),
			'timeout' => 10,
		] );
	}
}
