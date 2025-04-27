<?php
/**
 * Sync Settings Class
 *
 * Handles sync settings and operations
 *
 * @package WiseSync
 * @since 1.0.0
 */

namespace Sync;

/**
 * Sync Settings Class
 *
 * @since 1.0.0
 */
class Sync_Settings {

	/**
	 * Menus array.
	 *
	 * @var array
	 */
	private $menus = array();

	/**
	 * Sync Menus Array.
	 *
	 * @var array
	 */
	private $sync_menus = array();

	/**
	 * Forms array.
	 *
	 * @var array
	 */
	private $forms = array();

	/**
	 * Settings array.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Sync Settings constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'init_settings_page' ) );
		add_action( 'network_admin_menu', array( $this, 'init_settings_page' ) );

		// Ensure sync_menus is initialized properly in the constructor.
		$this->sync_menus = array();
	}

	/**
	 * Add WP menu.
	 *
	 * @param string $menu_slug Menu slug.
	 * @param string $menu_name Menu name.
	 * @param int    $position Menu position.
	 * @param bool   $create_sync_menu Create sync menu.
	 * @param string $settings_level Settings level (site, network, both).
	 *
	 * @since 1.0.0
	 */
	public function add_wp_menu( $menu_slug, $menu_name, $position = 100, $create_sync_menu = true, $settings_level = 'site' ) {

		if ( empty( $menu_slug ) || ! is_string( $menu_slug ) || strpos( $menu_slug, 'sync' ) !== false || ! preg_match( '/^[a-z][a-z0-9_-]*$/', $menu_slug ) ) {
			return;
		}
	
		if ( empty( $menu_name ) || ! is_string( $menu_name ) ) {
			return;
		}
	
		if ( ! is_numeric( $position ) || $position < 0 ) {
			return;
		}
	
		if ( ! is_bool( $create_sync_menu ) ) {
			return;
		}
	
		if ( ! in_array( $settings_level, array( 'site', 'network', 'both' ), true ) ) {
			return;
		}

		$this->menus[ $menu_slug ] = array(
			'menu_name'        => $menu_name,
			'position'         => $position,
			'create_sync_menu' => $create_sync_menu,
			'settings_level'   => $settings_level,
		);
	}

	/**
	 * Add Sync Menus.
	 *
	 * @param string      $wp_menu_slug      WP Menu slug.
	 * @param string      $menu_name         Menu name.
	 * @param string|bool $menu_slug         Menu slug (optional).
	 * @param string|null $icon_url          Icon URL (optional).
	 * @param int|null    $position          Menu position (optional).
	 * @param bool        $sub_menu_support  Whether sub-menu support is enabled (optional).
	 */
	public function add_sync_menus( $wp_menu_slug, $menu_name, $menu_slug = false, $icon_url = null, $position = null, $sub_menu_support = false ) {
		// Validate inputs.
		if ( empty( $wp_menu_slug ) || ( ! is_string( $wp_menu_slug ) && isset( $this->menus[ $wp_menu_slug ] ) && $this->menus[ $wp_menu_slug ]['create_sync_menu'] ) ) {
			return;
		}

		if ( empty( $menu_name ) || ! is_string( $menu_name ) ) {
			return;
		}

		if ( false !== $menu_slug && is_string( $menu_slug ) && strpos( $menu_slug, 'sync' ) === true && ! preg_match( '/^[a-z][a-z0-9_-]*$/', $menu_slug ) ) {
			return;
		}

		if ( null !== $position && ( ! is_numeric( $position ) || 0 > $position ) ) {
			return;
		}

		if ( null !== $sub_menu_support && false === is_bool( $sub_menu_support ) ) {
			return;
		}

		if ( ! isset( $this->sync_menus[ $wp_menu_slug ] ) ) {
			$this->sync_menus[ $wp_menu_slug ] = array();
		}

		$this->sync_menus[ $wp_menu_slug ][ $menu_slug ] = array(
			'menu_name' => $menu_name,
			'icon_url'  => $icon_url,
			'position'  => $position,
			'sub_menu'  => $sub_menu_support ? array() : false,
		);
	}

	/**
	 * Add Sync Sub Menus.
	 *
	 * @param string   $wp_menu_slug WP Menu slug.
	 * @param string   $parent_menu_slug Parent menu slug.
	 * @param string   $menu_name Menu name.
	 * @param string   $menu_slug Menu slug.
	 * @param int|null $position Menu position.
	 */
	public function add_sync_sub_menus( $wp_menu_slug, $parent_menu_slug, $menu_name, $menu_slug, $position = null ) {
		// Validate inputs.
		if ( empty( $wp_menu_slug ) || ( ! is_string( $wp_menu_slug ) && isset( $this->menus[ $wp_menu_slug ] ) && ! isset( $this->sync_menus[ $wp_menu_slug ] ) ) ) {
			return;
		}

		if ( empty( $parent_menu_slug ) || ( ! is_string( $parent_menu_slug ) && ! isset( $this->sync_menus[ $wp_menu_slug ][ $parent_menu_slug ] ) ) ) {
			return;
		}

		if ( ! isset( $this->sync_menus[ $wp_menu_slug ][ $parent_menu_slug ]['sub_menu'] ) || false === $this->sync_menus[ $wp_menu_slug ][ $parent_menu_slug ]['sub_menu'] ) {
			return; // Parent menu slug does not support sub-menus.
		}

		if ( empty( $menu_name ) || ! is_string( $menu_name ) ) {
			return;
		}

		if ( false !== $menu_slug && is_string( $menu_slug ) && strpos( $menu_slug, 'sync' ) === true && ! preg_match( '/^[a-z][a-z0-9_-]*$/', $menu_slug ) ) {
			return;
		}

		if ( null !== $position && ( ! is_numeric( $position ) || 0 > $position ) ) {
			return;
		}

		$this->sync_menus[ $wp_menu_slug ][ $parent_menu_slug ]['sub_menu'][ $menu_slug ] = array(
			'menu_name' => $menu_name,
			'position'  => $position,
		);
	}

	/**
	 * Initialize settings.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_page() {

		add_menu_page( 'Sync', 'Sync', 'manage_options', 'sync', false, 'dashicons-sort', is_network_admin() ? 23 : 63 );
		$this->menus['sync'] = array(
			'menu_name'        => 'Sync',
			'position'         => -1,
			'create_sync_menu' => true,
			'settings_level'   => 'both',
		);

		/**
		 * Sync Settings Page Hook
		 */
		do_action( 'sync_add_settings_page' );

		/**
		 * Sync Settings Page Filter
		 */
		apply_filters( 'sync_settings_page', $this->menus );

		$this->init_settings_pages();
	}

	/**
	 * Initialize settings pages.
	 *
	 * @since 1.0.0
	 */
	private function init_settings_pages() {

		foreach ( $this->menus as $menu_slug => $menu ) {

			if ( ! is_network_admin() && ! in_array( $menu['settings_level'], array( 'site', 'both' ), true ) ) {
				return; // Only show on site admin.
			} elseif ( is_network_admin() && ! in_array( $menu['settings_level'], array( 'network', 'both' ), true ) ) {
				return; // Only show on network admin.
			}

			if ( $menu['create_sync_menu'] ) {
				if ( isset( $this->sync_menus[ $menu_slug ] ) && is_array( $this->sync_menus[ $menu_slug ] ) ) {
					$has_valid_sub_menu = false;
					foreach ( $this->sync_menus[ $menu_slug ] as $sub_menu ) {
						if ( false !== $menu_slug || ( isset( $sub_menu['sub_menu'] ) && ! empty( $sub_menu['sub_menu'] ) ) ) {
							$has_valid_sub_menu = true;
							break;
						}
					}

					if ( ! $has_valid_sub_menu ) {
						continue; // Skip adding this menu if no valid sub-menu exists.
					}
				}
			}

			add_submenu_page(
				'sync',
				$menu['menu_name'],
				$menu['menu_name'],
				'manage_options',
				'sync' === $menu_slug ? 'sync' : 'sync-' . $menu_slug,
				array( $this, 'settings_page' ),
				$menu['position']
			);
		}
	}

	/**
	 * Process icon.
	 *
	 * @param string $icon_url Icon URL.
	 *
	 * @since 1.0.0
	 */
	public function process_icon( $icon_url ) {}

	/**
	 * Settings page.
	 *
	 * @since 1.0.0
	 */
	public function settings_page() {
		global $plugin_page;

		// Remove sync- from the plugin_page to get the actual menu slug.
		$current_settings_page = str_replace( 'sync-', '', $plugin_page );
		?>
<div class="sync-container">
	<!-- Main navigation with logo and mobile menu toggle -->
	<header class="sync-header">
	<div class="sync-logo">
		<span class="sync-logo-icon">S</span>
		<span class="sync-logo-text">SYNC</span>
		<span class="sync-tagline">SYNC the Web</span>
	</div>
	<button class="sync-mobile-toggle" id="sync-mobile-toggle">
		<span class="dashicons dashicons-menu-alt"></span>
	</button>
	</header>

		<?php
		if ( $this->menus[ $current_settings_page ]['create_sync_menu'] && isset( $this->sync_menus[ $current_settings_page ] ) && is_array( $this->sync_menus[ $current_settings_page ] ) ) {
			?>
	<!-- Side navigation -->
	<nav class="sync-sidebar" id="sync-sidebar">
	<ul class="sync-menu">
			<?php
			$current_sync_menu = $this->sync_menus[ $current_settings_page ];
			foreach ( $current_sync_menu as $menu_slug => $menu ) {
				?>
				<li class="sync-menu-item">
				<a href="#<?php echo esc_attr( 'sync-' . $menu_slug ); ?>" class="sync-menu-link">
					<?php $this->process_icon( $menu['icon_url'] ); ?>
					<span class="sync-menu-text"><?php echo esc_html( $menu['menu_name'] ); ?></span>
				</a>
				<?php
				if ( isset( $menu['sub_menu'] ) && is_array( $menu['sub_menu'] ) && ! empty( $menu['sub_menu'] ) ) {
					?>
					<ul class="sync-submenu">
						<?php
						foreach ( $menu['sub_menu'] as $sub_menu_slug => $sub_menu ) {
							?>
							<li class="sync-submenu-item">
								<a href="#<?php echo esc_attr( 'sync-' . $sub_menu_slug ); ?>" class="sync-submenu-link"><?php echo esc_html( $sub_menu['menu_name'] ); ?></a>
							</li>
							<?php
						}
						?>
					</ul>
					<?php
				}
				?>
				</li>
				<?php
			}
			?>
	</ul>
	</nav>

			<?php
		}
		?>

	<!-- Main content area -->
	<main class="sync-content">
	<div class="sync-content-header">
		<h1 class="sync-page-title">Dashboard</h1>
	</div>

	<!-- Dashboard welcome card -->
	<div class="sync-card sync-welcome-card">
		<div class="sync-card-dismissible">
		<span class="sync-dismiss-icon dashicons dashicons-no-alt"></span>
		</div>
		<div class="sync-card-content">
		<h2 class="sync-card-title">Congratulations!</h2>
		<p class="sync-highlight">Sync is now activated and ready to work for you.<br>Your sites should be synchronized faster now!</p>
		<p>To guarantee efficient synchronization, Sync automatically applies best practices for WordPress multi-site management.</p>
		<p>We also enable options that provide immediate benefits to your workflow.</p>
		<p>Continue to the options to further optimize your synchronization!</p>
		</div>
	</div>

	<!-- Account section -->
	<div class="sync-section">
		<div class="sync-section-header">
		<h2 class="sync-section-title">My Account</h2>
		<button class="sync-refresh-btn">
			<span class="dashicons dashicons-update"></span>
			Refresh info
		</button>
		</div>

		<div class="sync-section-content">
		<div class="sync-row">
			<div class="sync-col">
			<div class="sync-info-group">
				<div class="sync-info-item">
				<span class="sync-info-label">License</span>
				<span class="sync-info-value sync-info-highlight">Infinite</span>
				</div>
				<div class="sync-info-item">
				<span class="sync-info-label">Expiration Date</span>
				<span class="sync-info-value">
					<span class="sync-info-icon dashicons dashicons-yes-alt"></span>
					January 1, 2030
				</span>
				</div>
			</div>
			
			<div class="sync-toggle-group">
				<span class="sync-toggle-label">Sync Analytics</span>
				<label class="sync-toggle">
				<input type="checkbox" id="sync-analytics-toggle">
				<span class="sync-toggle-slider"></span>
				</label>
			</div>
			<p class="sync-toggle-description">I agree to share anonymous data with the development team to help improve Sync.</p>
			<a href="#" class="sync-link">What info will we collect?</a>
			
			<div class="sync-button-container">
				<button class="sync-button sync-primary-button">VIEW MY ACCOUNT</button>
			</div>
			</div>
		  
			<div class="sync-col">
			<div class="sync-quick-actions">
				<h3 class="sync-quick-actions-title">Quick Actions</h3>
			  
				<div class="sync-action-group">
				<h4 class="sync-action-title">Cache files</h4>
				<p class="sync-action-description">This action will clear and reload all the cache files.</p>
				<button class="sync-button sync-action-button">
					<span class="dashicons dashicons-trash"></span>
					CLEAR AND RELOAD
				</button>
				</div>
			  
				<div class="sync-action-group">
				<h4 class="sync-action-title">Priority Elements</h4>
				<p class="sync-action-description">Configure which elements should be synchronized first.</p>
				<button class="sync-button sync-action-button">CONFIGURE</button>
				</div>
			</div>
			</div>
		</div>
		</div>
	</div>
	</main>
</div>
		<?php
	}

	/**
	 * Add settings pages dynamically.
	 *
	 * @since 1.0.0
	 */
	private function add_dynamic_settings_pages() {
		do_action('sync_register_menu_settings', function($page_details) {
			foreach ($page_details as $slug => $details) {
				echo '<section id="' . esc_attr($slug) . '">';
				if (is_callable($details['callback'])) {
					call_user_func($details['callback'], $details);
				} else {
					echo '<p>' . esc_html($details['name']) . '</p>';
				}
				echo '</section>';
			}
		});
	}

	/**
	 * Create single AJAX settings page.
	 *
	 * @param array $page_details Page details.
	 * @param array $settings_array Settings array.
	 * @param bool  $refresh Refresh flag.
	 *
	 * @since 1.0.0
	 */
	public function create_single_ajax_settings_page($page_details, $settings_array, $refresh = false) {
		$nonce = wp_create_nonce($page_details['slug']);
		echo '<form id="' . esc_attr($page_details['slug']) . '" method="post">';
		foreach ($settings_array as $key => $value) {
			echo '<label>' . esc_html($key) . '</label>';
			echo '<input type="text" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
		}
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce) . '" />';
		echo '<button type="submit">Save</button>';
		echo '</form>';
	}

	/**
	 * Create each AJAX settings page.
	 *
	 * @param array $page_details Page details.
	 * @param array $settings_array Settings array.
	 *
	 * @since 1.0.0
	 */
	public function create_each_ajax_settings_page($page_details, $settings_array) {
		$nonce = wp_create_nonce($page_details['slug']);
		foreach ($settings_array as $key => $value) {
			echo '<label>' . esc_html($key) . '</label>';
			echo '<input type="checkbox" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
		}
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce) . '" />';
	}
}
