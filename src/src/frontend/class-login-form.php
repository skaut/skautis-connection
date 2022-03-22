<?php

declare( strict_types=1 );

namespace SkautisIntegration\Frontend;

use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Services\Services;
use SkautisIntegration\Modules\Register\Register;
use SkautisIntegration\Utils\Helpers;

final class Login_Form {

	private $wpLoginLogout;
	private $frontendDirUrl = '';

	public function __construct( WP_Login_Logout $wpLoginLogout ) {
		$this->wpLoginLogout  = $wpLoginLogout;
		$this->frontendDirUrl = plugin_dir_url( __FILE__ ) . 'public/';
		$this->init_hooks();
	}

	private function init_hooks() {
		if ( ! Services::get_services_container()['modulesManager']->isModuleActivated( Register::getId() ) ) {
			add_action( 'login_form', array( $this, 'loginLinkInLoginForm' ) );
			add_filter( 'login_form_bottom', array( $this, 'loginLinkInLoginFormReturn' ) );
		}
	}

	// TODO: Not used anywhere?
	public function enqueue_styles() {
		Helpers::enqueue_style( 'frontend', 'frontend/css/skautis-frontened.min.css' );
	}

	public function loginLinkInLoginForm() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->loginLinkInLoginFormReturn();
	}

	public function loginLinkInLoginFormReturn(): string {
		return '
		<p style="margin-bottom: 0.3em;">
			<a class="button button-primary button-hero button-skautis" style="float: none; width: 100%; text-align: center;"
			   href="' . esc_attr( $this->wpLoginLogout->get_login_url() ) . '">' . esc_html__( 'Log in with skautIS', 'skautis-integration' ) . '</a>
			   <br/>
		</p>
		<br/>
		';
	}

}
