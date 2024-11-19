<?php
/**
 * Contains the Frontend class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Frontend;

use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Auth\WP_Login_Logout;
use Skautis_Integration\Utils\Helpers;

/**
 * Enqueues frontend scripts and styles.
 */
final class Frontend {

	/**
	 * A link to the WP_Login_Logout service instance.
	 *
	 * @var WP_Login_Logout
	 */
	private $wp_login_logout;

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	private $skautis_gateway;

	/**
	 * Whether the current view is the default plugin login view.
	 *
	 * @var bool
	 */
	private $plugin_login_view;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param WP_Login_Logout $wp_login_logout An injected WP_Login_Logout service instance.
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 */
	public function __construct( WP_Login_Logout $wp_login_logout, Skautis_Gateway $skautis_gateway ) {
		$this->wp_login_logout   = $wp_login_logout;
		$this->skautis_gateway   = $skautis_gateway;
		$this->plugin_login_view = false;
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	private function init_hooks() {
		if ( false !== get_option( SKAUTIS_INTEGRATION_NAME . '_login_page_url' ) ) {
			add_filter( 'query_vars', array( self::class, 'register_query_vars' ) );
			add_filter( 'template_include', array( $this, 'register_templates' ) );
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'login_enqueue_scripts', array( self::class, 'enqueue_login_styles' ) );
		if ( $this->skautis_gateway->is_initialized() ) {
			if ( $this->skautis_gateway->get_skautis_instance()->getUser()->isLoggedIn() ) {
				add_action( 'admin_bar_menu', array( $this, 'add_logout_link_to_admin_bar' ), 20 );
			}
		}
	}

	/**
	 * Adds query variables that WordPress is allowed to use when redirecting.
	 *
	 * @param array<string> $vars A list of allowed query variables.
	 *
	 * @return array<string> The updated list.
	 */
	public static function register_query_vars( array $vars = array() ): array {
		$vars[] = 'skautis_login';

		return $vars;
	}

	/**
	 * Shows the SkautIS login template when the "skautis_login" query variable is present.
	 *
	 * @param string $path The path of the template to include. Unmodified when not showing the SkautIS login.
	 */
	public function register_templates( string $path = '' ): string {
		$query_value = get_query_var( 'skautis_login' );
		if ( '' !== $query_value ) {
			if ( file_exists( get_stylesheet_directory() . '/skautis/login.php' ) ) {
				return get_stylesheet_directory() . '/skautis/login.php';
			} elseif ( file_exists( get_template_directory() . '/skautis/login.php' ) ) {
				return get_template_directory() . '/skautis/login.php';
			} else {
				$this->plugin_login_view = true;

				return plugin_dir_path( __FILE__ ) . 'public/views/login.php';
			}
		}

		return $path;
	}

	/**
	 * Enqueues frontend styles.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		if ( $this->plugin_login_view ) {
			wp_enqueue_style( 'buttons' );
		}

		Helpers::enqueue_style( 'frontend', 'frontend/css/skautis-frontend.min.css' );
	}

	/**
	 * Enqueues login styles.
	 *
	 * @return void
	 */
	public static function enqueue_login_styles() {
		Helpers::enqueue_style( 'frontend', 'frontend/css/skautis-frontend.min.css' );
	}

	/**
	 * Adds a link to admin bar right-hand-side menu to log out from both WordPress and SkautIS at once.
	 *
	 * TODO: Duplicated code?
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The WordPress administration bar.
	 *
	 * @return void
	 */
	public function add_logout_link_to_admin_bar( \WP_Admin_Bar $wp_admin_bar ) {
		if ( ! function_exists( 'is_admin_bar_showing' ) ) {
			return;
		}
		if ( ! is_admin_bar_showing() ) {
			return;
		}
		if ( is_null( $wp_admin_bar->get_node( 'user-actions' ) ) ) {
			return;
		}

		$wp_admin_bar->add_menu(
			array(
				'parent' => 'user-actions',
				'id'     => SKAUTIS_INTEGRATION_NAME . '_adminBar_logout',
				'title'  => esc_html__( 'Odhlásit se (i ze skautISu)', 'skautis-integration' ),
				'href'   => $this->wp_login_logout->get_logout_url(),
			)
		);
	}
}
