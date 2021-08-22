<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility\Admin;

use SkautisIntegration\Rules\RulesManager;
use SkautisIntegration\Modules\Visibility\Frontend\Frontend;

final class Metabox {

	private $postTypes;
	private $rulesManager;
	private $frontend;

	public function __construct( array $postTypes, RulesManager $rulesManager, Frontend $frontend ) {
		$this->postTypes    = $postTypes;
		$this->rulesManager = $rulesManager;
		$this->frontend     = $frontend;
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'add_meta_boxes', array( $this, 'addMetaboxForRulesField' ) );
		add_action( 'save_post', array( $this, 'saveRulesCustomField' ) );
	}

	public function addMetaboxForRulesField() {
		foreach ( $this->postTypes as $postType ) {
			add_meta_box(
				SKAUTISINTEGRATION_NAME . '_modules_visibility_rules_metabox',
				__( 'SkautIS pravidla', 'skautis-integration' ),
				array( $this, 'rulesRepeater' ),
				$postType
			);
		}
	}

	public function saveRulesCustomField( int $postId ) {
		if ( isset( $_POST[ SKAUTISINTEGRATION_NAME . '_rules_visibilityMode' ] ) ) {
			if ( isset( $_POST[ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
				$rules = sanitize_meta( SKAUTISINTEGRATION_NAME . '_rules', $_POST[ SKAUTISINTEGRATION_NAME . '_rules' ], 'post' );
			} else {
				$rules = array();
			}
			update_post_meta(
				$postId,
				SKAUTISINTEGRATION_NAME . '_rules',
				$rules
			);

			if ( isset( $_POST[ SKAUTISINTEGRATION_NAME . '_rules_includeChildren' ] ) ) {
				$includeChildren = sanitize_meta( SKAUTISINTEGRATION_NAME . '_rules_includeChildren', $_POST[ SKAUTISINTEGRATION_NAME . '_rules_includeChildren' ], 'post' );
			} else {
				$includeChildren = 0;
			}
			update_post_meta(
				$postId,
				SKAUTISINTEGRATION_NAME . '_rules_includeChildren',
				$includeChildren
			);

			$visibilityMode = sanitize_meta( SKAUTISINTEGRATION_NAME . '_rules_visibilityMode', $_POST[ SKAUTISINTEGRATION_NAME . '_rules_visibilityMode' ], 'post' );
			update_post_meta(
				$postId,
				SKAUTISINTEGRATION_NAME . '_rules_visibilityMode',
				$visibilityMode
			);
		}
	}

	public function rulesRepeater( \WP_Post $post ) {
		$postTypeObject  = get_post_type_object( $post->post_type );
		$includeChildren = get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules_includeChildren', true );
		if ( $includeChildren !== '0' && $includeChildren !== '1' ) {
			$includeChildren = get_option( SKAUTISINTEGRATION_NAME . '_modules_visibility_includeChildren', 0 );
		}

		$visibilityMode = get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules_visibilityMode', true );
		if ( $visibilityMode !== 'content' && $visibilityMode !== 'full' ) {
			$visibilityMode = get_option( SKAUTISINTEGRATION_NAME . '_modules_visibility_visibilityMode', 0 );
		}
		?>

		<?php
		if ( $post->post_parent > 0 ) {
			$parentRules = $this->frontend->getParentPostsWithRules( absint( $post->ID ), $post->post_type );
			if ( ! empty( $parentRules ) ) {
				?>
				<h4><?php esc_html_e( 'Pravidla převzatá z nadřazených stránek', 'skautis-integration' ); ?>:</h4>
				<ul id="skautis_modules_visibility_parentRules" class="skautis-admin-list">
					<?php
					foreach ( $parentRules as $parentRule ) {
						?>
						<li>
							<strong><?php echo esc_html( $parentRule['parentPostTitle'] ); ?></strong>
							<ul>
								<?php
								foreach ( $parentRule['rules'] as $ruleId => $rule ) {
									?>
									<li data-rule="<?php echo esc_attr( $ruleId ); ?>"><?php echo esc_html( $rule ); ?></li>
									<?php
								}
								?>
							</ul>
						</li>
						<?php
					}
					?>
				</ul>
				<hr/>
				<?php
			}
		}
		?>

		<p><?php esc_html_e( 'Obsah bude pro uživatele viditelný pouze při splnění alespoň jednoho z následujících pravidel.', 'skautis-integration' ); ?></p>
		<p><?php esc_html_e( 'Ponecháte-li prázdné - obsah bude viditelný pro všechny uživatele.', 'skautis-integration' ); ?></p>
		<div id="repeater_post">
			<div data-repeater-list="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules">
				<div data-repeater-item>

					<select name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules" class="rule select2">
						<?php
						foreach ( (array) $this->rulesManager->getAllRules() as $rule ) {
							echo '<option value="' . esc_attr( $rule->ID ) . '">' . esc_html( $rule->post_title ) . '</option>';
						}
						?>
					</select>

					<input data-repeater-delete type="button"
						   value="<?php esc_attr_e( 'Odstranit', 'skautis-integration' ); ?>"/>
				</div>
			</div>
			<input data-repeater-create type="button" value="<?php esc_attr_e( 'Přidat', 'skautis-integration' ); ?>"/>
		</div>
		<p>
			<label>
				<input type="hidden" name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules_includeChildren"
					   value="0"/>
				<input type="checkbox" name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules_includeChildren"
					   value="1" <?php checked( 1, $includeChildren ); ?> /><span>
												<?php
												if ( $postTypeObject->hierarchical ) {
													printf( esc_html__( 'Použít vybraná pravidla i na podřízené %s', 'skautis-integration' ), esc_html( lcfirst( $postTypeObject->labels->name ) ) );
												} else {
													esc_html_e( 'Použít vybraná pravidla i na podřízený obsah (média - obrázky, videa, přílohy,...)', 'skautis-integration' );
												}
												?>
					.</span></label>
		</p>
		<p>
			<label><input type="radio" name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules_visibilityMode"
						  value="full" <?php checked( 'full', $visibilityMode ); ?> /><span><?php esc_html_e( 'Úplně skrýt', 'skautis-integration' ); ?></span></label>
			<br/>
			<label><input type="radio" name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules_visibilityMode"
						  value="content" <?php checked( 'content', $visibilityMode ); ?> /><span><?php esc_html_e( 'Skrýt pouze obsah', 'skautis-integration' ); ?></span></label>
		</p>
		<?php
	}

}
