<?php
/**
 * Contains the Admin class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Shortcodes\Admin;

use Skautis_Integration\Rules\Rules_Manager;

/**
 * Adds the TinyMCE shortcode button to the post editor.
 *
 * This class only handles the classic editor, not Gutenberg.
 */
final class Admin {

	/**
	 * A link to the Rules_Manager service instance.
	 *
	 * @var Rules_Manager
	 */
	private $rules_manager;

	/**
	 * An instance of the module Settings service.
	 *
	 * TODO: Unused?
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Rules_Manager $rules_manager An injected Rules_Manager service instance.
	 */
	public function __construct( Rules_Manager $rules_manager ) {
		$this->rules_manager = $rules_manager;
		$this->settings      = new Settings();
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	private function init_hooks() {
		add_action( 'admin_footer', array( $this, 'init_available_rules' ) );

		add_action(
			'admin_init',
			function () {
				if ( get_user_option( 'rich_editing' ) ) {
					add_filter( 'mce_external_plugins', array( $this, 'register_tinymce_plugin' ) );
					add_filter( 'mce_buttons', array( $this, 'add_tinymce_button' ) );
				}
			}
		);
	}

	/**
	 * Registers the TinyMCE shortcode button code.
	 *
	 * @param array<string, string> $plugins A list of button script source URLs keyed by the button ID.
	 */
	public function register_tinymce_plugin( array $plugins = array() ): array {
		$plugins['skautis_rules'] = plugin_dir_url( dirname( __FILE__, 4 ) ) . 'modules/Shortcodes/admin/js/skautis-modules-shortcodes-tinymceRulesButton.min.js';

		return $plugins;
	}

	/**
	 * Adds the TinyMCE shortcode button to the UI.
	 *
	 * @param array<string> $buttons A list of button IDs.
	 */
	public function add_tinymce_button( array $buttons = array() ): array {
		$buttons[] = 'skautis_rules';

		return $buttons;
	}

	/**
	 * Initializes dynamic options for the shortcode button JS code.
	 */
	public function init_available_rules() {
		?>
		<script>
			window.rulesOptions = [];
			window.visibilityOptions = [];

			<?php
			if ( get_option( SKAUTIS_INTEGRATION_NAME . '_modules_shortcodes_visibilityMode', 'hide' ) === 'hide' ) {
				echo 'window.visibilityOptions.push({text: "hideContent", value: "hide"});';
				echo 'window.visibilityOptions.push({text: "showLogin", value: "showLogin"});';
			} else {
				echo 'window.visibilityOptions.push({text: "showLogin", value: "showLogin"});';
				echo 'window.visibilityOptions.push({text: "hideContent", value: "hide"});';
			}

			$rules = array();
			foreach ( (array) $this->rules_manager->get_all_rules() as $rule ) {
				$rules[ $rule->ID ] = $rule->post_title;
			}
			?>
			<script>
				window.rulesOptions = <?php wp_json_encode( $rules ); ?>;
			</script>
			?>
		</script>
		<?php
	}

}
