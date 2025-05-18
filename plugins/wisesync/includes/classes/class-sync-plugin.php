<?php
/**
 * Sync Plugin Class
 *
 * @package WiseSync
 * @since 1.0.0
 */

namespace Sync;

/**
 * Class Sync_Plugin
 *
 * @since 1.0.0
 */
class Sync_Plugin {

	/**
	 * Constructor
	 */
	public function __construct() {}

	/** Development Settings Area. */

	/**
	 * Add Developer Settings.
	 */
	public function add_development_settings() {
		global $sync_settings;

		$sync_settings->add_wp_menu(
			'development-settings',
			__( 'Sync Development Settings', 'wisesync' ),
			array(
				'menu'      => false,
				'menu_name' => 'Developer Debug',
				'callback'  => array( $this, 'developer_settings_view' ),
			),
			100,
			'both' 
		);
	}

	/**
	 * Developer Settings View.
	 */
	public function developer_settings_view() {
		// Add your developer settings view code here.
		ob_start();
		// Check if development.json is present in WP_CONTENT_DIR.
		global $sync_filesystem;
		$development_file = WP_CONTENT_DIR . '/development.json';
	
		if ( $sync_filesystem->exists( $development_file ) ) {
			// Read the development.json file.
			$development_content = $sync_filesystem->get_contents( $development_file );
		
			if ( false !== $development_content ) {
				// Decode the JSON content.
				$development_data = json_decode( $development_content, true );
			
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $development_data ) ) {
					?>

				<div class="development-settings-container">
					<h2><?php echo esc_html__( 'Development Settings', 'wisesync' ); ?></h2>
					
					<!-- Environment Information -->
					<div class="dev-section">
						<h3><?php echo esc_html__( 'Environment Information', 'wisesync' ); ?></h3>
						<table class="widefat fixed striped">
							<tbody>
								<tr>
									<td><strong><?php echo esc_html__( 'Environment', 'wisesync' ); ?></strong></td>
									<td><?php echo esc_html( isset( $development_data['environment'] ) ? $development_data['environment'] : __( 'Not specified', 'wisesync' ) ); ?></td>
								</tr>
								<tr>
									<td><strong><?php echo esc_html__( 'Last Commit Message', 'wisesync' ); ?></strong></td>
									<td><?php echo esc_html( isset( $development_data['last_commit_message'] ) ? $development_data['last_commit_message'] : __( 'Not available', 'wisesync' ) ); ?></td>
								</tr>
								<tr>
									<td><strong><?php echo esc_html__( 'Generated At', 'wisesync' ); ?></strong></td>
									<td><?php echo esc_html( isset( $development_data['generated_at'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $development_data['generated_at'] ) ) : __( 'Not available', 'wisesync' ) ); ?></td>
								</tr>
								<tr>
									<td><strong><?php echo esc_html__( 'Updated At', 'wisesync' ); ?></strong></td>
									<td><?php echo esc_html( isset( $development_data['updated_at'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $development_data['updated_at'] ) ) : __( 'Not available', 'wisesync' ) ); ?></td>
								</tr>
								<tr>
									<td><strong><?php echo esc_html__( 'Workflow Run ID', 'wisesync' ); ?></strong></td>
									<td><?php echo esc_html( isset( $development_data['workflow_run_id'] ) ? $development_data['workflow_run_id'] : __( 'Not available', 'wisesync' ) ); ?></td>
								</tr>
								<tr>
									<td><strong><?php echo esc_html__( 'Total PRs', 'wisesync' ); ?></strong></td>
									<td><?php echo esc_html( isset( $development_data['total_prs'] ) ? $development_data['total_prs'] : '0' ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>

					<!-- Pull Requests Information -->
					<?php if ( isset( $development_data['data'] ) && is_array( $development_data['data'] ) && ! empty( $development_data['data'] ) ) : ?>
						<div class="dev-section">
							<h3><?php echo esc_html__( 'Pull Requests', 'wisesync' ); ?></h3>
							<?php
							// Remove duplicates based on PR number.
							$unique_prs      = array();
							$seen_pr_numbers = array();
							foreach ( $development_data['data'] as $pr ) {
								$pr_number = isset( $pr['pr_number'] ) ? $pr['pr_number'] : 'unknown_' . uniqid();
								if ( ! in_array( $pr_number, $seen_pr_numbers ) ) {
									$unique_prs[]      = $pr;
									$seen_pr_numbers[] = $pr_number;
								}
							}
							?>
							<?php foreach ( $unique_prs as $index => $pr ) : ?>
								<div class="pr-item">
									<h4><?php echo esc_html( isset( $pr['pr_title'] ) ? $pr['pr_title'] : __( 'Untitled PR', 'wisesync' ) ); ?></h4>
									
									<table class="widefat fixed striped">
										<tbody>
											<tr>
												<td><strong><?php echo esc_html__( 'PR Number', 'wisesync' ); ?></strong></td>
												<td><?php echo esc_html( isset( $pr['pr_number'] ) ? '#' . $pr['pr_number'] : __( 'Not available', 'wisesync' ) ); ?></td>
											</tr>
											<tr>
												<td><strong><?php echo esc_html__( 'Status', 'wisesync' ); ?></strong></td>
												<td>
													<span class="pr-status <?php echo esc_attr( isset( $pr['pr_status'] ) ? $pr['pr_status'] : 'unknown' ); ?>">
														<?php echo esc_html( isset( $pr['pr_status'] ) ? ucfirst( $pr['pr_status'] ) : __( 'Unknown', 'wisesync' ) ); ?>
													</span>
												</td>
											</tr>
											<tr>
												<td><strong><?php echo esc_html__( 'Author', 'wisesync' ); ?></strong></td>
												<td><?php echo esc_html( isset( $pr['pr_author'] ) ? $pr['pr_author'] : __( 'Unknown', 'wisesync' ) ); ?></td>
											</tr>
											<tr>
												<td><strong><?php echo esc_html__( 'Branch', 'wisesync' ); ?></strong></td>
												<td>
													<code><?php echo esc_html( isset( $pr['pr_head'] ) ? $pr['pr_head'] : __( 'Not specified', 'wisesync' ) ); ?></code>
													→
													<code><?php echo esc_html( isset( $pr['pr_base'] ) ? $pr['pr_base'] : __( 'Not specified', 'wisesync' ) ); ?></code>
												</td>
											</tr>
											<tr>
												<td><strong><?php echo esc_html__( 'Created At', 'wisesync' ); ?></strong></td>
												<td><?php echo esc_html( isset( $pr['pr_created_at'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $pr['pr_created_at'] ) ) : __( 'Not available', 'wisesync' ) ); ?></td>
											</tr>
											<tr>
												<td><strong><?php echo esc_html__( 'Updated At', 'wisesync' ); ?></strong></td>
												<td><?php echo esc_html( isset( $pr['pr_updated_at'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $pr['pr_updated_at'] ) ) : __( 'Not available', 'wisesync' ) ); ?></td>
											</tr>
											<tr>
												<td><strong><?php echo esc_html__( 'Commits', 'wisesync' ); ?></strong></td>
												<td><?php echo esc_html( isset( $pr['commit_count'] ) ? $pr['commit_count'] : '0' ); ?></td>
											</tr>
											<tr>
												<td><strong><?php echo esc_html__( 'Files Changed', 'wisesync' ); ?></strong></td>
												<td><?php echo esc_html( isset( $pr['files_changed'] ) ? $pr['files_changed'] : '0' ); ?></td>
											</tr>
											<tr>
												<td><strong><?php echo esc_html__( 'Changes', 'wisesync' ); ?></strong></td>
												<td class="additions-deletions">
													<span class="additions">+<?php echo esc_html( isset( $pr['additions'] ) ? $pr['additions'] : '0' ); ?></span>
													/
													<span class="deletions">-<?php echo esc_html( isset( $pr['deletions'] ) ? $pr['deletions'] : '0' ); ?></span>
												</td>
											</tr>
											<tr>
												<td><strong><?php echo esc_html__( 'Draft', 'wisesync' ); ?></strong></td>
												<td><?php echo esc_html( isset( $pr['pr_draft'] ) ? ( $pr['pr_draft'] ? __( 'Yes', 'wisesync' ) : __( 'No', 'wisesync' ) ) : __( 'Unknown', 'wisesync' ) ); ?></td>
											</tr>
											<?php if ( isset( $pr['pr_url'] ) && ! empty( $pr['pr_url'] ) ) : ?>
												<tr>
													<td><strong><?php echo esc_html__( 'PR URL', 'wisesync' ); ?></strong></td>
													<td><a href="<?php echo esc_url( $pr['pr_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $pr['pr_url'] ); ?></a></td>
												</tr>
											<?php endif; ?>
										</tbody>
									</table>

									<!-- PR Description -->
									<?php if ( isset( $pr['pr_desc'] ) && ! empty( $pr['pr_desc'] ) && trim( $pr['pr_desc'] ) !== '' ) : ?>
										<div class="pr-description">
											<h5><?php echo esc_html__( 'Description', 'wisesync' ); ?></h5>
											<div class="pr-description-content">
												<?php echo esc_html( $pr['pr_desc'] ); ?>
											</div>
										</div>
									<?php endif; ?>

									<!-- Commits List -->
									<?php if ( isset( $pr['commits'] ) && is_array( $pr['commits'] ) && ! empty( $pr['commits'] ) ) : ?>
										<div class="commits-list">
											<h5><?php echo esc_html__( 'Commits', 'wisesync' ); ?></h5>
											<div class="commits-container">
												<ul>
													<?php foreach ( $pr['commits'] as $commit ) : ?>
														<li><?php echo esc_html( $commit ); ?></li>
													<?php endforeach; ?>
												</ul>
											</div>
										</div>
									<?php endif; ?>

									<!-- Labels -->
									<?php if ( isset( $pr['pr_labels'] ) && is_array( $pr['pr_labels'] ) && ! empty( $pr['pr_labels'] ) ) : ?>
										<div class="pr-labels">
											<h5><?php echo esc_html__( 'Labels', 'wisesync' ); ?></h5>
											<?php foreach ( $pr['pr_labels'] as $label ) : ?>
												<span class="pr-label">
													<?php echo esc_html( is_array( $label ) ? ( isset( $label['name'] ) ? $label['name'] : $label ) : $label ); ?>
												</span>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<div class="no-data-message">
							<p><?php echo esc_html__( 'No pull request data available.', 'wisesync' ); ?></p>
						</div>
					<?php endif; ?>
				</div>
					<?php
				} else {
					?>
				<div class="error-message">
					<p><?php echo esc_html__( 'Error: Invalid JSON format in development settings file.', 'wisesync' ); ?></p>
				</div>
					<?php
				}
			} else {
				?>
			<div class="error-message">
				<p><?php echo esc_html__( 'Error: Unable to read development settings file.', 'wisesync' ); ?></p>
			</div>
				<?php
			}
		} else {
			?>
		<div class="no-data-message">
			<p><?php echo esc_html__( 'Development settings file not found.', 'wisesync' ); ?></p>
		</div>
			<?php
		}
		return ob_get_clean();
	}

	/** Drop-Ins and Mu-Plugin Errors */

	/**
	 * Show Mu Plugin Path Not Correct Message. 
	 *
	 * @return void
	 */
	public function show_admin_notice_mu_plugin() {
		global $sync_filesystem;

		$items = array();

		// 1. Check your wp-config.php path.
		$items[] = __( 'Please verify that the path to the MU-Plugin load file is correct in your <code>wp-config.php</code>.', 'wisesync' );

		// 2. Edit the sync.php file in the right folder.
		if ( $sync_filesystem->is_vip_site() ) {
			$items[] = sprintf(
				/* translators: %1$s: filename, %2$s: folder name */
				__( 'Edit %1$s inside your %2$s folder.', 'wisesync' ),
				'<code>sync.php</code>',
				'<code>client-mu-plugins</code>'
			);
		} else {
			$items[] = sprintf(
				/* translators: %1$s: filename, %2$s: folder name */
				__( 'Edit %1$s inside your %2$s folder.', 'wisesync' ),
				'<code>sync.php</code>',
				'<code>mu-plugins</code>'
			);
		}

		// 3. Ensure the load-path variable is pointing at the right file.
		$items[] = sprintf(
			/* translators: %1$s: variable name, %2$s: path */
			__( 'Ensure %1$s is pointing to %2$s.', 'wisesync' ),
			'<code>$sync_my_plugin_path</code>',
			'<code>./includes/load/mu-plugins.php</code>'
		);

		// Build our <ul><li>…</li></ul> markup with styled bullet points.
		$message = '<ul style="list-style-type: disc; padding-left: 20px;"><li>' . implode( '</li><li>', array_map( 'wp_kses_post', $items ) ) . '</li></ul>';

		sync_generate_admin_notice(
			$message,
			__( 'Mu-Plugin Load Error', 'wisesync' ),
			'error',
			false,
			true
		);
	}
}
