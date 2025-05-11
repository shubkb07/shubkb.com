<?php
/**
 * WiseSync Site Health Integration
 *
 * This file integrates WiseSync with WordPress Site Health.
 *
 * @package   WiseSync
 * @since     1.0.0
 */

namespace Sync;

/**
 * Class Sync_Site_Health
 *
 * Handles site health checks and UI for the WiseSync plugin.
 *
 * @package Sync
 */
class Sync_Site_Health {

	/**
	 * Store registered sections
	 *
	 * @var array
	 */
	private $registered_sections = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add our custom tab to the Site Health navigation.
		add_filter( 'site_health_navigation_tabs', array( $this, 'add_sync_tab' ) );
		
		// Add content to our custom tab.
		add_action( 'site_health_tab_content', array( $this, 'render_sync_tab_content' ) );
	}

	/**
	 * Add the Sync tab to Site Health navigation.
	 *
	 * @param array $tabs The existing tabs.
	 * @return array Modified tabs.
	 */
	public function add_sync_tab( $tabs ) {
		// translators: Tab heading for Site Health navigation.
		$tabs['sync'] = esc_html_x( 'Sync', 'Site Health', 'wisesync' );
		
		return $tabs;
	}

	/**
	 * Register a new section in the Site Health tab.
	 *
	 * @param string      $slug The section slug.
	 * @param string      $name The section name.
	 * @param string|bool $description Optional description, false by default.
	 * @return void
	 */
	public function register_site_health_section( $slug, $name, $description = false ) {
		$this->registered_sections[ $slug ] = array(
			'name'        => $name,
			'description' => $description,
			'items'       => array(),
		);
	}

	/**
	 * Register a table section within a registered section.
	 *
	 * @param string      $section_slug The parent section slug.
	 * @param string      $name The table section name.
	 * @param array       $section_data Key-value pairs for the table.
	 * @param string|bool $description Optional description, false by default.
	 * @param string|bool $section_for Optional category (Performance, Security), false by default.
	 * @param string|bool $status Optional status (Good, Recommended, Critical, Should be improved), false by default.
	 * @return void
	 */
	public function register_site_health_table_section( $section_slug, $name, $section_data, $description = false, $section_for = false, $status = false ) {
		if ( ! isset( $this->registered_sections[ $section_slug ] ) ) {
			return;
		}

		$status_class = '';
		$status_text  = '';

		if ( $status ) {
			switch ( $status ) {
				case 'Good':
					$status_class = 'good';
					$status_text  = __( 'Good', 'wisesync' );
					break;
				case 'Recommended':
					$status_class = 'recommended';
					$status_text  = __( 'Recommended', 'wisesync' );
					break;
				case 'Critical':
					$status_class = 'critical';
					$status_text  = __( 'Critical', 'wisesync' );
					break;
				case 'Should be improved':
					$status_class = 'warning';
					$status_text  = __( 'Should be improved', 'wisesync' );
					break;
			}
		}

		$this->registered_sections[ $section_slug ]['items'][] = array(
			'type'         => 'table',
			'idx'          => $this->text_to_kebab_case( $name ),
			'name'         => $name,
			'description'  => $description,
			'data'         => $section_data,
			'for'          => $section_for,
			'status_class' => $status_class,
			'status_text'  => $status_text,
		);
	}

	/**
	 * Register a log section within a registered section.
	 *
	 * @param string      $section_slug The parent section slug.
	 * @param string      $name The log section name.
	 * @param string      $section_data The log content.
	 * @param string|bool $description Optional description, false by default.
	 * @param string|bool $section_for Optional category (Performance, Security), false by default.
	 * @param bool        $separate_copy_button Whether to show a separate copy button.
	 * @return void
	 */
	public function register_site_health_log_section( $section_slug, $name, $section_data, $description = false, $section_for = false, $separate_copy_button = false ) {
		if ( ! isset( $this->registered_sections[ $section_slug ] ) ) {
			return;
		}

		$this->registered_sections[ $section_slug ]['items'][] = array(
			'type'                 => 'log',
			'idx'                  => $this->text_to_kebab_case( $name ),
			'name'                 => $name,
			'description'          => $description,
			'data'                 => $section_data,
			'for'                  => $section_for,
			'separate_copy_button' => $separate_copy_button,
		);
	}

	/**
	 * Render the content for the Sync tab.
	 *
	 * @param string $tab The current tab being rendered.
	 */
	public function render_sync_tab_content( $tab ) {
		// Only proceed if this is our tab.
		if ( 'sync' !== $tab ) {
			return;
		}

		do_action( 'sync_site_health_before' );

		?>
		<div class="health-check-body sync-status-tab">
			<h2><?php esc_html_e( 'Sync Info', 'wisesync' ); ?></h2>
			
			<div class="site-health-issues-wrapper">
				<?php
				foreach ( $this->registered_sections as $slug => $section_data ) {
					?>
					<div class="site-health-issue-wrapper">
						<h3><?php echo esc_html( $section_data['name'] ); ?></h3>
						<p><?php echo esc_html( $section_data['description'] ); ?></p>
						<div class="site-status-list">
							<div class="health-check-accordion">
								<?php
								foreach ( $section_data['items'] as $item ) {
									?>
									<h4 class="health-check-accordion-heading">
										<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-<?php echo esc_attr( $item['idx'] ); ?>" type="button">
											<span class="title"><?php echo esc_html( $item['name'] ); ?></span>
											<?php
											if ( $item['for'] ) {
												?>
												<span class="badge blue"><?php echo esc_html( $item['for'] ); ?></span>
												<?php
											}
											?>
											<?php if ( ! empty( $item['status_text'] ) ) { ?>
												<?php
												$status_svg = '';
												switch ( $item['status_class'] ) {
													case 'good':
														$color   = '#2ecc40'; // Green.
														$percent = 100;
														break;
													case 'recommended':
														$color   = '#b6e685'; // Light Green.
														$percent = 85;
														break;
													case 'warning':
														$color   = '#ffe066'; // Yellow.
														$percent = 50;
														break;
													case 'critical':
														$color   = '#ff4136'; // Red.
														$percent = 0;
														break;
													default:
														$color   = '#ccc';
														$percent = 0;
												}
												$radius        = 8;
												$circumference = 2 * M_PI * $radius;
												$offset        = $circumference * ( 1 - $percent / 100 );
												?>
												&nbsp;&nbsp;
												<svg width="20" height="20" viewBox="0 0 20 20" style="vertical-align:middle;margin-right:6px;">
													<circle cx="10" cy="10" r="<?php echo esc_attr( $radius ); ?>" stroke="#e0e0e0" stroke-width="2" fill="none"/>
													<circle cx="10" cy="10" r="<?php echo esc_attr( $radius ); ?>" stroke="<?php echo esc_attr( $color ); ?>" stroke-width="2" fill="none"
														stroke-dasharray="<?php echo esc_attr( $circumference ); ?>"
														stroke-dashoffset="<?php echo esc_attr( $offset ); ?>"$radius
														stroke-linecap="round"
														transform="rotate(-90 10 10)"
													/>
												</svg>
												<span style="margin-left:2px;"><?php echo esc_html( $item['status_text'] ); ?></span>
											<?php } ?>
											<span class="icon"></span>
										</button>
									</h4>
									<?php
									if ( 'log' === $item['type'] ) {
										?>
										<div id="health-check-accordion-block-<?php echo esc_attr( $item['idx'] ); ?>" class="health-check-accordion-panel" hidden="hidden">
											<p class="description"><?php echo esc_html( $item['description'] ); ?></p>
											<textarea class="large-text sync-logs" rows="10" readonly><?php echo esc_textarea( $item['data'] ); ?></textarea>
											<?php if ( $item['separate_copy_button'] ) : ?>
												<div class="copy-button-wrapper" style="margin-top: 10px;">
													<button class="button copy-sync-info" data-clipboard-target="<?php echo esc_attr( $item['name'] ); ?>-logs">
														<?php esc_html_e( 'Copy Logs', 'wisesync' ); ?>
													</button>
												</div>
											<?php endif; ?>
										</div>
										<?php
									} elseif ( 'table' === $item['type'] ) {
										?>
										<div id="health-check-accordion-block-<?php echo esc_attr( $item['idx'] ); ?>" class="health-check-accordion-panel" hidden="hidden">
											<p class="description"><?php echo esc_html( $item['description'] ); ?></p>
											<table class="widefat striped" role="presentation">
												<tbody>
													<?php foreach ( $item['data'] as $key => $value ) : ?>
														<tr>
															<td><?php echo esc_html( $key ); ?></td>
															<td><?php echo esc_html( $value ); ?></td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
										<?php
									}
								}
								?>
							</div>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Convert an arbitrary text string to kebab-case.
	 *
	 * @param string $text Input text to convert.
	 * @return string Kebab-cased string.
	 * @throws \InvalidArgumentException If the input is not a string.
	 */
	private function text_to_kebab_case( $text ) {
		// 1. Validate input type.
		if ( ! is_string( $text ) ) {
			throw new \InvalidArgumentException( 'text_to_kebab_case(): Expected a string.' );
		}

		// 2. Transliterate Unicode to ASCII if possible.
		if ( extension_loaded( 'intl' ) ) {
			$transliterator = \Transliterator::create( 'Any-Latin; Latin-ASCII' );
			if ( $transliterator instanceof \Transliterator ) {
				$text = $transliterator->transliterate( $text );
			}
		} else {
			// iconv fallback (may drop some chars).
			$converted = iconv( 'UTF-8', 'ASCII//TRANSLIT', $text );
			if ( false !== $converted ) {
				$text = $converted;
			}
		}

		// 3. Lowercase everything.
		$text = strtolower( $text );

		// 4. Remove apostrophes (’ or ').
		$text = preg_replace( '/[\'’]/u', '', $text );

		// 5. Replace any sequence of non-alphanumeric chars with a single hyphen.
		$text = preg_replace( '/[^a-z0-9]+/', '-', $text );

		// 6. Trim leading/trailing hyphens.
		$text = trim( $text, '-' );

		return $text;
	}
}
