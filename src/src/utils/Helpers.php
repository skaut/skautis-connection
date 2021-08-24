<?php

declare( strict_types=1 );

namespace SkautisIntegration\Utils;

class Helpers {

	public static function showAdminNotice( string $message, string $type = 'warning', string $hideNoticeOnPage = '' ) {
		add_action(
			'admin_notices',
			function () use ( $message, $type, $hideNoticeOnPage ) {
				if ( ! $hideNoticeOnPage || $hideNoticeOnPage != get_current_screen()->id ) {
					$class = 'notice notice-' . $type . ' is-dismissible';
					printf(
						'<div class="%1$s"><p>%2$s</p><button type="button" class="notice-dismiss">
		<span class="screen-reader-text">' . esc_html__( 'Zavřít' ) . '</span>
	</button></div>',
						esc_attr( $class ),
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$message
					);
				}
			}
		);
	}

	public static function getSkautisManagerCapability(): string {
		static $capability = '';

		if ( $capability === '' ) {
			$capability = apply_filters( SKAUTISINTEGRATION_NAME . '_manager_capability', 'manage_options' );
		}

		return $capability;
	}

	public static function userIsSkautisManager(): bool {
		return current_user_can( self::getSkautisManagerCapability() );
	}

	public static function getCurrentUrl(): string {
		if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
			return esc_url_raw( ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}
		return '';
	}

	public static function validateNonceFromUrl( string $url, string $nonceName ) {
		if ( ! wp_verify_nonce( self::getNonceFromUrl( urldecode( $url ), $nonceName ), $nonceName ) ) {
			wp_nonce_ays( $nonceName );
		}
	}

	public static function getVariableFromUrl( string $url, string $variableName ): string {
		$result = array();
		$url    = esc_url_raw( $url );
		if ( preg_match( '~' . $variableName . '=([^\&,\s,\/,\#,\%,\?]*)~', $url, $result ) ) {
			if ( is_array( $result ) && isset( $result[1] ) && $result[1] ) {
				return sanitize_text_field( $result[1] );
			}
		}

		return '';
	}

	public static function getNonceFromUrl( string $url, string $nonceName ): string {
		return self::getVariableFromUrl( $url, $nonceName );
	}

}
