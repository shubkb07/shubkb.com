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
	 * Constructor.
	 */
	public function __construct() {
		// Add our custom tab to the Site Health navigation
		add_filter( 'site_health_navigation_tabs', array( $this, 'add_sync_tab' ) );
		
		// Add content to our custom tab
		add_action( 'site_health_tab_content', array( $this, 'render_sync_tab_content' ) );
		
		// Enqueue scripts for copy functionality
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
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
	 * Render the content for the Sync tab.
	 *
	 * @param string $tab The current tab being rendered.
	 */
	public function render_sync_tab_content( $tab ) {
		// Only proceed if this is our tab
		if ( 'sync' !== $tab ) {
			return;
		}

		// Get sync status and information
		$sync_info = $this->get_sync_info();
		?>
		<div class="health-check-body sync-status-tab">
			<h2><?php esc_html_e( 'WiseSync Status', 'wisesync' ); ?></h2>
			
			<div class="site-health-issues-wrapper">
				<!-- Sync Status Section -->
				<div class="site-health-issue-wrapper">
					<h3><?php esc_html_e( 'Sync Status', 'wisesync' ); ?></h3>
					<div class="site-status-list">
						<div class="site-health-issue-wrapper no-padding">
							<div class="health-check-accordion">
								<h4 class="health-check-accordion-heading">
									<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-sync-status" type="button">
										<span class="title"><?php esc_html_e( 'Last Synchronization', 'wisesync' ); ?></span>
										<span class="badge <?php echo esc_attr( $sync_info['status_class'] ); ?>">
											<?php echo esc_html( $sync_info['status_text'] ); ?>
										</span>
										<span class="icon"></span>
									</button>
								</h4>
								<div id="health-check-accordion-block-sync-status" class="health-check-accordion-panel" hidden="hidden">
									<table class="widefat striped" role="presentation">
										<tbody>
											<tr>
												<td><?php esc_html_e( 'Last Successful Sync', 'wisesync' ); ?></td>
												<td><?php echo esc_html( $sync_info['last_sync_date'] ); ?></td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Sync Frequency', 'wisesync' ); ?></td>
												<td><?php echo esc_html( $sync_info['sync_frequency'] ); ?></td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Next Scheduled Sync', 'wisesync' ); ?></td>
												<td><?php echo esc_html( $sync_info['next_sync_date'] ); ?></td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Sync Method', 'wisesync' ); ?></td>
												<td><?php echo esc_html( $sync_info['sync_method'] ); ?></td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Sync Configuration Section -->
				<div class="site-health-issue-wrapper">
					<h3><?php esc_html_e( 'Sync Configuration', 'wisesync' ); ?></h3>
					<div class="site-status-list">
						<div class="site-health-issue-wrapper no-padding">
							<div class="health-check-accordion">
								<h4 class="health-check-accordion-heading">
									<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-sync-config" type="button">
										<span class="title"><?php esc_html_e( 'Configuration Details', 'wisesync' ); ?></span>
										<span class="icon"></span>
									</button>
								</h4>
								<div id="health-check-accordion-block-sync-config" class="health-check-accordion-panel" hidden="hidden">
									<table class="widefat striped" role="presentation">
										<tbody>
											<tr>
												<td><?php esc_html_e( 'API Key Status', 'wisesync' ); ?></td>
												<td><?php echo esc_html( $sync_info['api_key_status'] ); ?></td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Connection Type', 'wisesync' ); ?></td>
												<td><?php echo esc_html( $sync_info['connection_type'] ); ?></td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Sync Modules', 'wisesync' ); ?></td>
												<td><?php echo esc_html( $sync_info['sync_modules'] ); ?></td>
											</tr>
											<tr>
												<td><?php esc_html_e( 'Data Encryption', 'wisesync' ); ?></td>
												<td><?php echo esc_html( $sync_info['data_encryption'] ); ?></td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Sync Logs Section -->
				<div class="site-health-issue-wrapper">
					<h3><?php esc_html_e( 'Recent Sync Logs', 'wisesync' ); ?></h3>
					<div class="site-status-list">
						<div class="site-health-issue-wrapper no-padding">
							<div class="health-check-accordion">
								<h4 class="health-check-accordion-heading">
									<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-sync-logs" type="button">
										<span class="title"><?php esc_html_e( 'Last 5 Sync Operations', 'wisesync' ); ?></span>
										<span class="icon"></span>
									</button>
								</h4>
								<div id="health-check-accordion-block-sync-logs" class="health-check-accordion-panel" hidden="hidden">
									<div class="sync-logs-wrapper">
										<textarea class="large-text sync-logs" rows="10" readonly><?php echo esc_textarea( $sync_info['sync_logs'] ); ?></textarea>
										<p class="description">
											<?php esc_html_e( 'These logs show the most recent synchronization operations.', 'wisesync' ); ?>
										</p>
										<div class="copy-button-wrapper" style="margin-top: 10px;">
											<button class="button copy-sync-info" data-clipboard-target="sync-logs">
												<?php esc_html_e( 'Copy Sync Logs', 'wisesync' ); ?>
											</button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<!-- All Sync Information Section -->
				<div class="site-health-issue-wrapper">
					<h3><?php esc_html_e( 'Complete Sync Information', 'wisesync' ); ?></h3>
					<p>
						<?php esc_html_e( 'Copy all WiseSync information for troubleshooting or support.', 'wisesync' ); ?>
					</p>
					<div class="copy-button-wrapper">
						<button class="button copy-all-sync-info">
							<?php esc_html_e( 'Copy All Sync Information', 'wisesync' ); ?>
						</button>
						<span class="copy-feedback" aria-live="polite"></span>
					</div>
					<div class="complete-sync-info-container" style="display: none;">
						<textarea id="complete-sync-info" style="display: none;"><?php echo esc_textarea( wp_json_encode( $sync_info, JSON_PRETTY_PRINT ) ); ?></textarea>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get sync information.
	 *
	 * @return array Sync information.
	 */
	private function get_sync_info() {
		// In a real implementation, you would get this information from your plugin's settings and database
		// This is sample data for demonstration
		return array(
			'status_class'    => 'good',
			'status_text'     => __( 'Good', 'wisesync' ),
			'last_sync_date'  => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), time() - 3600 ),
			'sync_frequency'  => __( 'Every 4 hours', 'wisesync' ),
			'next_sync_date'  => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), time() + 11000 ),
			'sync_method'     => __( 'REST API', 'wisesync' ),
			'api_key_status'  => __( 'Valid', 'wisesync' ),
			'connection_type' => __( 'Secure (HTTPS)', 'wisesync' ),
			'sync_modules'    => __( 'Posts, Pages, Users, Media', 'wisesync' ),
			'data_encryption' => __( 'Enabled (AES-256)', 'wisesync' ),
			'sync_logs'       => "2025-05-11 08:32:11 - Sync completed successfully. 27 items synchronized.\n2025-05-10 20:32:07 - Sync completed successfully. 5 items synchronized.\n2025-05-10 16:31:59 - Sync completed with warnings. 18 items synchronized, 2 skipped.\n2025-05-10 12:31:45 - Sync completed successfully. 0 items synchronized.\n2025-05-10 08:32:01 - Sync completed successfully. 13 items synchronized.",
		);
	}

	/**
	 * Enqueue scripts for the Site Health page.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'site-health.php' !== $hook ) {
			return;
		}

		// Enqueue our custom JS for the copy functionality
		wp_enqueue_script(
			'wisesync-site-health',
			plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/site-health.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		// Add inline CSS
		// wp_add_inline_style( 'site-health', '
		// 	.copy-feedback {
		// 		display: inline-block;
		// 		margin-left: 10px;
		// 		opacity: 0;
		// 		transition: opacity 0.3s ease-in-out;
		// 	}
		// 	.copy-feedback.visible {
		// 		opacity: 1;
		// 	}
		// 	.sync-logs {
		// 		font-family: monospace;
		// 		width: 100%;
		// 	}
		// ' );
	}
}
