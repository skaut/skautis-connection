<?php
/**
 * Contains the Shortcodes class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Shortcodes;

use Skautis_Integration\Auth\Skautis_Login;
use Skautis_Integration\Modules\Module;
use Skautis_Integration\Modules\Shortcodes\Admin\Admin;
use Skautis_Integration\Modules\Shortcodes\Frontend\Frontend;
use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Auth\WP_Login_Logout;

final class Shortcodes implements Module {

	const REGISTER_ACTION = 'shortcodes';

	// TODO: Unused?
	private $rules_manager;
	// TODO: Unused?
	private $skautis_login;
	// TODO: Unused?
	private $wp_login_logout;

	public static $id = 'module_Shortcodes';

	/**
	 * Constructs the module and saves all dependencies.
	 */
	public function __construct( Rules_Manager $rules_manager, Skautis_Login $skautis_login, WP_Login_Logout $wp_login_logout ) {
		$this->rules_manager   = $rules_manager;
		$this->skautis_login   = $skautis_login;
		$this->wp_login_logout = $wp_login_logout;
		if ( is_admin() ) {
			( new Admin( $this->rules_manager ) );
		} else {
			( new Frontend( $this->skautis_login, $this->rules_manager, $this->wp_login_logout ) );
		}
	}

	/**
	 * Returns the module ID.
	 */
	public static function get_id(): string {
		return self::$id;
	}

	/**
	 * Returns the localized module name.
	 */
	public static function get_label(): string {
		return __( 'Shortcodes', 'skautis-integration' );
	}

	/**
	 * Returns the path to the module.
	 */
	public static function get_path(): string {
		return plugin_dir_path( __FILE__ );
	}

	/**
	 * Returns the URL of the module.
	 */
	public static function get_url(): string {
		return plugin_dir_url( __FILE__ );
	}

}
