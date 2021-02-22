<?php
/**
 * Theme Dashboard
 *
 * @package Theme Dashboard
 */

/**
 * Theme Dashboard class.
 */
class Airi_Theme_Dashboard {

	/**
	 * The slug name to refer to this menu by.
	 *
	 * @var string $menu_slug The menu slug.
	 */
	public $menu_slug = 'theme-dashboard';

	/**
	 * The pro_status of theme.
	 *
	 * @var string $pro_status The pro_status.
	 */
	public $pro_status = false;

	/**
	 * The starter menu slug.
	 *
	 * @var string $starter_menu_slug The starter menu slug.
	 */
	public $starter_menu_slug = 'starter-sites';

	/**
	 * The plugin slug.
	 *
	 * @var string $starter_plugin_slug The plugin slug.
	 */
	public $starter_plugin_slug = 'athemes-starter-sites';

	/**
	 * The plugin path.
	 *
	 * @var string $starter_plugin_path The plugin path.
	 */
	public $starter_plugin_path = 'athemes-starter-sites/athemes-starter-sites.php';

	/**
	 * The settings of page.
	 *
	 * @var array $settings The settings.
	 */
	public $settings = array(
		'tabs'                => array(),
		'hero_title'          => 'Welcome',
		'hero_themes_desc'    => '',
		'hero_desc'           => '',
		'hero_image'          => '',
		'hero_dashboard_link' => '#',
		'documentation_link'  => '#',
		'promo_title'         => 'Upgrade to Pro',
		'promo_button'        => 'Upgrade to Pro',
		'promo_link'          => '#',
		'changelog_version'   => '1.0',
		'review_link'         => '#',
		'suggest_idea_link'   => '#',
		'changelog_link'      => '#',
		'support_link'        => '#',
		'support_pro_link'    => '#',
		'community_link'      => '#',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$self = $this;

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		add_filter( 'woocommerce_enable_setup_wizard', '__return_false' );

		add_action( 'init', array( $this, 'set_settings' ) );

		add_action( 'init', function () use ( $self ) {
			add_action( 'admin_menu', array( $self, 'add_menu_page' ) );
		} );

		add_action( 'admin_notices', array( $this, 'notice' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'notice_enqueue_scripts' ), 5 );

		add_action( 'wp_ajax_thd_install_starter_plugin', array( $this, 'install_starter_plugin' ) );
		add_action( 'wp_ajax_nopriv_thd_install_starter_plugin', array( $this, 'install_starter_plugin' ) );

		add_action( 'wp_ajax_thd_dismissed_handler', array( $this, 'dismissed_handler' ) );

		add_action( 'switch_theme', array( $this, 'reset_notices' ) );
		add_action( 'after_switch_theme', array( $this, 'reset_notices' ) );
	}

	/**
	 * Add menu page
	 */
	public function add_menu_page() {
		add_submenu_page( 'themes.php', esc_html__( 'Theme Dashboard', 'airi' ), esc_html__( 'Theme Dashboard', 'airi' ), 'manage_options', $this->menu_slug, array( $this, 'html_carcase' ), 1 );
	}

	/**
	 * This function will register scripts and styles for admin dashboard.
	 *
	 * @param string $page Current page.
	 */
	public function admin_enqueue_scripts( $page ) {
		wp_enqueue_script( 'airi', get_template_directory_uri() . '/theme-dashboard/scripts.js', array( 'jquery' ), filemtime( get_template_directory() . '/theme-dashboard/scripts.js' ), true );

		wp_localize_script( 'airi', 'thd_localize', array(
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'nonce' ),
			'failed_message' => esc_html__( 'Something went wrong, contact support.', 'airi' ),
		) );

		// Styles.
		wp_enqueue_style( 'airi', get_template_directory_uri() . '/theme-dashboard/style.css', array(), filemtime( get_template_directory() . '/theme-dashboard/style.css' ) );

		// Add RTL support.
		wp_style_add_data( 'airi', 'rtl', 'replace' );
	}

	/**
	 * Settings
	 *
	 * @param array $settings The settings.
	 */
	public function set_settings( $settings ) {
		$this->settings = apply_filters( 'thd_register_settings', $this->settings );

		if ( isset( $this->settings['pro_status'] ) ) {
			$this->pro_status = $this->settings['pro_status'];
		}

		if ( isset( $this->settings['starter_plugin_slug'] ) ) {
			$this->starter_plugin_slug = $this->settings['starter_plugin_slug'];
		}

		if ( isset( $this->settings['starter_plugin_path'] ) ) {
			$this->starter_plugin_path = $this->settings['starter_plugin_path'];
		}

		if ( isset( $this->settings['starter_menu_slug'] ) ) {
			$this->starter_menu_slug = $this->settings['starter_menu_slug'];
		}
	}

	/**
	 * Is visible
	 *
	 * @param array $data The data.
	 */
	public function is_visible( $data ) {

		$status = isset( $data['visible'] ) ? $data['visible'] : array();

		if ( in_array( 'free', $status, true ) && ! $this->pro_status ) {
			return true;
		}
		if ( in_array( 'pro', $status, true ) && $this->pro_status ) {
			return true;
		}
	}

	/**
	 * Get plugin status.
	 *
	 * @param string $plugin_path Plugin path.
	 */
	public function get_plugin_status( $plugin_path ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_path ) ) {
			return 'not_installed';
		} elseif ( in_array( $plugin_path, (array) get_option( 'active_plugins', array() ), true ) || is_plugin_active_for_network( $plugin_path ) ) {
			return 'active';
		} else {
			return 'inactive';
		}
	}

	/**
	 * Install a plugin.
	 *
	 * @param string $plugin_slug Plugin slug.
	 */
	public function install_plugin( $plugin_slug ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		if ( false === filter_var( $plugin_slug, FILTER_VALIDATE_URL ) ) {
			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $plugin_slug,
					'fields' => array(
						'short_description' => false,
						'sections'          => false,
						'requires'          => false,
						'rating'            => false,
						'ratings'           => false,
						'downloaded'        => false,
						'last_updated'      => false,
						'added'             => false,
						'tags'              => false,
						'compatibility'     => false,
						'homepage'          => false,
						'donate_link'       => false,
					),
				)
			);

			$download_link = $api->download_link;
		} else {
			$download_link = $plugin_slug;
		}

		// Use AJAX upgrader skin instead of plugin installer skin.
		// ref: function wp_ajax_install_plugin().
		$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );

		$install = $upgrader->install( $download_link );

		if ( false === $install ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Activate a plugin.
	 *
	 * @param string $plugin_path Plugin path.
	 */
	public function activate_plugin( $plugin_path ) {

		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		$activate = activate_plugin( $plugin_path, '', false, true );

		if ( is_wp_error( $activate ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Install starter plugin.
	 */
	public function install_starter_plugin() {
		check_ajax_referer( 'nonce', 'nonce' );

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( esc_html__( 'Insufficient permissions to install the plugin.', 'airi' ) );
			wp_die();
		}

		if ( 'not_installed' === $this->get_plugin_status( $this->starter_plugin_path ) ) {

			$this->install_plugin( $this->starter_plugin_slug );

			$this->activate_plugin( $this->starter_plugin_path );

		} elseif ( 'inactive' === $this->get_plugin_status( $this->starter_plugin_path ) ) {

			$this->activate_plugin( $this->starter_plugin_path );
		}

		if ( 'active' === $this->get_plugin_status( $this->starter_plugin_path ) ) {
			wp_send_json_success();
		}

		wp_send_json_error( esc_html__( 'Failed to initialize or activate importer plugin.', 'airi' ) );

		wp_die();
	}

	/**
	 * Html Features
	 *
	 * @param array $data The data.
	 */
	public function html_features( $data ) {
		if ( ! $data ) {
			return;
		}
		?>
		<div class="thd-theme-features">
			<?php
			foreach ( $data as $feature ) {

				$feature_status = 'thd-theme-feature-active';

				if ( isset( $feature['type'] ) && 'pro' === $feature['type'] ) {
					$feature_status = $this->pro_status ? $feature_status : 'thd-theme-feature-noactive';
				}
				?>
				<div class="thd-theme-feature <?php echo esc_attr( $feature_status ); ?>">
					<div class="thd-theme-feature-row">
						<?php if ( isset( $feature['name'] ) && $feature['name'] ) { ?>
							<div class="thd-theme-feature-name">
								<?php echo esc_html( $feature['name'] ); ?>
							</div>
						<?php } ?>

						<?php
						if ( isset( $feature['type'] ) && $feature['type'] ) {

							$badge = 'thd-badge';

							switch ( $feature['type'] ) {
								case 'free':
									$badge .= ' thd-badge-success';
									break;
								case 'pro':
									$badge .= ' thd-badge-info';
									break;
							}
							?>
							<div class="thd-theme-feature-badge <?php echo esc_html( $badge ); ?>">
								<?php echo esc_html( $feature['type'] ); ?>
							</div>
						<?php } ?>
					</div>
					<div class="thd-theme-feature-row">
						<?php
						if ( isset( $feature['customize_uri'] ) && $feature['customize_uri'] ) {

							$customize_uri = $feature['customize_uri'];

							if ( isset( $feature['type'] ) && 'pro' === $feature['type'] ) {
								$customize_uri = $this->pro_status ? $customize_uri : 'javascript:void(0);';
							}
							?>
							<a href="<?php echo esc_attr( $customize_uri ); ?>" class="thd-theme-feature-customize" target="_blank">
								<?php esc_html_e( 'Customize', 'airi' ); ?>
							</a>
						<?php } ?>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Html Performance.
	 */
	public function html_performance() {
		?>
		<div class="thd-performance">
			<div class="thd-performance-item">
				<div class="thd-performance-item-outer">
					<div class="thd-performance-item-thumbnail">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/theme-dashboard/images/fastest-wordpress-hosting.jpg' ); ?>">
					</div>
					<div class="thd-performance-item-content">
						<div class="thd-performance-item-name">
							<?php esc_html_e( 'Best WordPress Speed Optimization Plugins', 'airi' ); ?>
						</div>

						<div class="thd-performance-item-desc">
							<?php esc_html_e( 'We run through the top 10 WordPress performance plugins to keep your site running fast. Goes beyond just caching plugins (though those are on there too!). A must-read for all WordPress site owners.', 'airi' ); ?>
						</div>

						<a class="thd-performance-item-read-more" href="https://athemes.com/collections/best-wordpress-speed-optimization-plugins/" target="_blank">
							<?php esc_html_e( 'Read more', 'airi' ); ?>
						</a>
					</div>
				</div>
			</div>
			<div class="thd-performance-item">
				<div class="thd-performance-item-outer">
					<div class="thd-performance-item-thumbnail">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/theme-dashboard/images/wp-rocket.jpg' ); ?>">
					</div>
					<div class="thd-performance-item-content">
						<div class="thd-performance-item-name">
							<?php esc_html_e( 'WP Rocket Review', 'airi' ); ?>
						</div>

						<div class="thd-performance-item-desc">
							<?php esc_html_e( 'Every site that is serious about speed (and should be pretty much all sites these days) needs a caching plugin and WP Rocket is our pick of the bunch. Get the lowdown on why we recommend it n our data-backed review: ', 'airi' ); ?>
						</div>

						<a class="thd-performance-item-read-more" href="https://athemes.com/reviews/wp-rocket-review/" target="_blank">
							<?php esc_html_e( 'Read more', 'airi' ); ?>
						</a>
					</div>
				</div>
			</div>
			<div class="thd-performance-item">
				<div class="thd-performance-item-outer">
					<div class="thd-performance-item-thumbnail">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/theme-dashboard/images/best-wordpress-speed.jpg' ); ?>">
					</div>
					<div class="thd-performance-item-content">
						<div class="thd-performance-item-name">
							<?php esc_html_e( 'Fastest WordPress Hosts', 'airi' ); ?>
						</div>

						<div class="thd-performance-item-desc">
							<?php esc_html_e( 'A slow host can really put a drag on your site’s performance, even an optimized one. Make sure you get a host that won’t hold you back by reading our review, complete with real speed tests, of the fastest WordPress hosts. ', 'airi' ); ?>
						</div>

						<a class="thd-performance-item-read-more" href="https://athemes.com/reviews/fastest-wordpress-hosting/" target="_blank">
							<?php esc_html_e( 'Read more', 'airi' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Html Hero
	 *
	 * @param string $location The location.
	 */
	public function html_hero( $location = null ) {
		global $pagenow;

		$screen = get_current_screen();
		?>
		<div class="thd-hero">
			<div class="thd-hero-content">
				<div class="thd-hero-hello">
					<?php esc_html_e( 'Hello, ', 'airi' ); ?>

					<?php
					$current_user = wp_get_current_user();

					echo esc_html( $current_user->display_name );
					?>

					<?php esc_html_e( '👋🏻', 'airi' ); ?>
				</div>

				<div class="thd-hero-title">
					<?php echo wp_kses_post( $this->settings['hero_title'] ); ?>

					<?php if ( $this->pro_status ) { ?>
						<span class="thd-badge thd-badge-info">pro</span>
					<?php } else { ?>
						<span class="thd-badge thd-badge-success">free</span>
					<?php } ?>
				</div>

				<?php if ( 'themes' === $location ) { ?>
					<?php if ( isset( $this->settings['hero_themes_desc'] ) && $this->settings['hero_themes_desc'] ) { ?>
						<div class="thd-hero-desc">
							<?php echo wp_kses_post( $this->settings['hero_themes_desc'] ); ?>
						</div>
					<?php } ?>
				<?php } else { ?>
					<?php if ( isset( $this->settings['hero_desc'] ) && $this->settings['hero_desc'] ) { ?>
						<div class="thd-hero-desc">
							<?php echo wp_kses_post( $this->settings['hero_desc'] ); ?>
						</div>
					<?php } ?>
				<?php } ?>


				<div class="thd-hero-actions">
					<?php
					$target = 'redirect';

					if ( 'active' === $this->get_plugin_status( $this->starter_plugin_path ) ) {
						$target = 'active';
					}
					?>

					<a href="<?php echo esc_url( add_query_arg( 'page', $this->starter_menu_slug, admin_url( 'themes.php' ) ) ); ?>" data-target="<?php echo esc_attr( $target ); ?>" class="thd-hero-go button button-primary">
						<?php esc_html_e( 'Starter Sites', 'airi' ); ?>
					</a>

					<?php if ( 'themes.php' === $pagenow && 'themes' === $screen->base ) { ?>
						<a href="<?php echo esc_url( add_query_arg( 'page', $this->menu_slug, admin_url( 'themes.php' ) ) ); ?>" class="button">
							<?php esc_html_e( 'Theme Dashboard', 'airi' ); ?>
						</a>
					<?php } ?>
				</div>

				<?php if ( 'active' !== $this->get_plugin_status( $this->starter_plugin_path ) ) { ?>
					<div class="thd-hero-notion">
						<?php esc_html_e( 'Clicking “Starter Sites” button will install and activate the demo importer plugin.', 'airi' ); ?>
					</div>
				<?php } ?>
			</div>

			<?php if ( isset( $this->settings['hero_image'] ) && $this->settings['hero_image'] ) { ?>
				<div class="thd-hero-image">
					<span>
						<img src="<?php echo esc_url( $this->settings['hero_image'] ); ?>">
					</span>
				</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Html Carcase
	 */
	public function html_carcase() {
		?>
		<div class="thd-wrap thd-theme-dashboard">
			<div class="thd-header">
				<div class="thd-header-left">
					<div class="thd-header-col thd-header-col-logo">
						<div class="thd-logo">
							<a target="_blank" href="<?php echo esc_url( 'https://athemes.com/' ); ?>">
								<img width="96px" height="24px" src="<?php echo esc_url( get_template_directory_uri() . '/theme-dashboard/images/logo.svg' ); ?>" alt="<?php esc_attr_e( 'aThemes', 'airi' ); ?>">
							</a>
						</div>
					</div>
				</div>

				<div class="thd-header-right">
					<a href="<?php echo esc_url( $this->settings['documentation_link'] ); ?>" class="thd-link-documentation" target="_blank">
						<span><?php esc_html_e( 'Documentation', 'airi' ); ?></span>

						<i class="dashicons dashicons-external"></i>
					</a>
				</div>
			</div>

			<div class="wrap">

				<h1 class="hidden"><?php esc_html_e( 'Theme Dashboard', 'airi' ); ?></h1>

				<?php $this->html_hero(); ?>

				<div class="thd-main">
					<div class="thd-main-content">
						<?php if ( $this->settings['tabs'] ) { ?>
							<div class="thd-panel thd-panel-general">
								<div class="thd-panel-head thd-panel-tabs">
									<?php
									$counter = 0;
									foreach ( $this->settings['tabs'] as $tab ) {
										$counter++;
										if ( ! $this->is_visible( $tab ) ) {
											continue;
										}
										?>
										<div class="thd-panel-tab <?php echo esc_attr( 1 === $counter ? 'thd-panel-tab-active' : null ); ?>">
											<h3 class="thd-panel-title">
												<a href="#tab-content"><?php echo esc_html( $tab['name'] ); ?></a>
											</h3>
										</div>
									<?php } ?>
								</div>

								<div class="thd-panel-content thd-panel-content-tabs">
									<?php
									$counter = 0;
									foreach ( $this->settings['tabs'] as $tab ) {
										$counter++;
										if ( ! $this->is_visible( $tab ) ) {
											continue;
										}
										?>
										<div class="thd-panel-tab <?php echo esc_attr( 1 === $counter ? 'thd-panel-tab-active' : null ); ?>">
											<?php
											switch ( $tab['type'] ) {
												case 'features':
													$this->html_features( $tab['data'] );
													break;
												case 'performance':
													$this->html_performance();
													break;
												case 'html':
													call_user_func( 'printf', '%s', $tab['data'] );
													break;
											}
											?>
										</div>
									<?php } ?>
								</div>
							</div>
						<?php } ?>

						<div class="thd-panel thd-panel-support">
							<div class="thd-panel-head">
								<h3 class="thd-panel-title"><?php esc_html_e( 'Support', 'airi' ); ?></h3>

								<?php if ( $this->pro_status ) { ?>
									<div class="thd-badge thd-badge-success"><?php esc_html_e( 'Priority Status', 'airi' ); ?></div>
								<?php } ?>
							</div>
							<div class="thd-panel-content">
								<div class="thd-conttent-primary">
									<div class="thd-title">
										<?php echo wp_kses_post( __( 'Need help? We\'re here for you!', 'airi' ) ); ?>
									</div>

									<div class="thd-description"><?php esc_html_e( 'Have a question? Hit a bug? Get the help you need, when you need it from our friendly support staff.', 'airi' ); ?></div>

									<div class="thd-button-wrap">
										<a href="<?php echo esc_url( $this->settings['support_link'] ); ?>" class="thd-button button" target="_blank">
											<?php echo esc_html_e( 'Get Support', 'airi' ); ?>
										</a>
									</div>
								</div>

								<?php if ( ! $this->pro_status ) { ?>
									<div class="thd-conttent-secondary">
										<div class="thd-title">
											<?php echo wp_kses_post( __( 'Priority Support', 'airi' ) ); ?>

											<div class="thd-badge"><?php esc_html_e( 'pro', 'airi' ); ?></div>
										</div>

										<div class="thd-description"><?php esc_html_e( 'Want your questions answered faster? Go Pro to be first in the queue!', 'airi' ); ?></div>

										<div class="thd-button-wrap">
											<a href="<?php echo esc_url( $this->settings['support_pro_link'] ); ?>" class="thd-button button" target="_blank">
												<?php echo esc_html_e( 'Go PRO', 'airi' ); ?>
											</a>
										</div>
									</div>
								<?php } ?>
							</div>
						</div>

						<div class="thd-panel thd-panel-community">
							<div class="thd-panel-head">
								<h3 class="thd-panel-title"><?php esc_html_e( 'Community', 'airi' ); ?></h3>
							</div>
							<div class="thd-panel-content">
								<div class="thd-title">
									<?php echo wp_kses_post( __( 'Join our Facebook community', 'airi' ) ); ?>
								</div>

								<div class="thd-description"><?php esc_html_e( 'Discuss products and ask for community support or help the community.', 'airi' ); ?></div>

								<div class="thd-button-wrap">
									<a href="<?php echo esc_url( $this->settings['community_link'] ); ?>" class="thd-button button" target="_blank">
										<?php echo esc_html_e( 'Join Now', 'airi' ); ?>
									</a>
								</div>
							</div>
						</div>
					</div>

					<div class="thd-main-sidebar">
						<?php if ( ! $this->pro_status ) { ?>
							<div class="thd-panel thd-panel-promo">
								<div class="thd-panel-inner">
									<div class="thd-heading">
										<?php echo wp_kses_post( $this->settings['promo_title'] ); ?>
									</div>

									<?php if ( isset( $this->settings['promo_desc'] ) && $this->settings['promo_desc'] ) { ?>
										<div class="thd-description"><?php echo wp_kses_post( $this->settings['promo_desc'] ); ?></div>
									<?php } ?>

									<div class="thd-button-wrap">
										<a href="<?php echo esc_url( $this->settings['promo_link'] ); ?>" class="thd-button button" target="_blank">
											<?php echo wp_kses_post( $this->settings['promo_button'] ); ?>
										</a>
									</div>
								</div>
							</div>
						<?php } ?>

						<div class="thd-panel thd-panel-review">
							<div class="thd-panel-head">
								<h3 class="thd-panel-title"><?php esc_html_e( 'Review', 'airi' ); ?></h3>
							</div>
							<div class="thd-panel-content">
								<img class="thd-stars" src="<?php echo esc_url( get_template_directory_uri() . '/theme-dashboard/images/stars@2x.png' ); ?>" width="136px" height="24px">

								<div class="thd-description"><?php esc_html_e( 'It makes us happy to hear from our users. We would appreciate a review.', 'airi' ); ?></div>

								<div class="thd-button-wrap">
									<a href="<?php echo esc_url( $this->settings['review_link'] ); ?>" class="thd-button button" target="_blank">
										<?php echo esc_html_e( 'Submit a Review', 'airi' ); ?>
									</a>
								</div>

								<div class="thd-line"></div>

								<div class="thd-heading"><?php esc_html_e( 'Have an idea how to improve?', 'airi' ); ?></div>

								<div class="thd-description"><?php esc_html_e( 'Let us know. We process all suggestions to make the theme perfect.', 'airi' ); ?></div>

								<a href="<?php echo esc_url( $this->settings['suggest_idea_link'] ); ?>" class="thd-suggest-idea-link" target="_blank">
									<?php echo esc_html_e( 'Suggest an Idea', 'airi' ); ?>
								</a>
							</div>
						</div>

						<div class="thd-panel thd-panel-changelog">
							<div class="thd-panel-head">
								<h3 class="thd-panel-title"><?php esc_html_e( 'Changelog', 'airi' ); ?></h3>

								<?php if ( $this->settings['changelog_version'] ) { ?>
									<div class="thd-version"><?php echo esc_html( $this->settings['changelog_version'] ); ?></div>
								<?php } ?>
							</div>
							<div class="thd-panel-content">
								<div class="thd-description"><?php esc_html_e( 'Keep informed with the latest changes about each theme.', 'airi' ); ?></div>

								<a href="<?php echo esc_url( $this->settings['changelog_link'] ); ?>" class="thd-changelog-link" target="_blank">
									<?php echo esc_html_e( 'See the Changelog', 'airi' ); ?>
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Html Notice
	 */
	public function notice() {
		global $pagenow;

		$screen = get_current_screen();

		if ( 'themes.php' === $pagenow && 'themes' === $screen->base ) {
			$transient_name = sprintf( '%s_hero_notice', get_template() );

			if ( ! get_transient( $transient_name ) ) {
				?>
				<div class="thd-notice notice notice-success thd-theme-dashboard thd-theme-dashboard-notice is-dismissible" data-notice="<?php echo esc_attr( $transient_name ); ?>">
					<button type="button" class="notice-dismiss"></button>

					<?php $this->html_hero( 'themes' ); ?>
				</div>
				<?php
			}
		}
	}

	/**
	 * Purified from the database information about notification.
	 */
	public function reset_notices() {
		delete_transient( sprintf( '%s_hero_notice', get_template() ) );
	}

	/**
	 * Dismissed handler
	 */
	public function dismissed_handler() {
		wp_verify_nonce( null );

		if ( isset( $_POST['notice'] ) ) { // Input var ok; sanitization ok.

			set_transient( sanitize_text_field( wp_unslash( $_POST['notice'] ) ), true, 90 * DAY_IN_SECONDS ); // Input var ok.
		}
	}

	/**
	 * Notice Enqunue Scripts
	 *
	 * @param string $page Current page.
	 */
	public function notice_enqueue_scripts( $page ) {
		wp_enqueue_script( 'jquery' );

		ob_start();
		?>
		<script>
			jQuery(function($) {
				$( document ).on( 'click', '.thd-notice .notice-dismiss', function () {
					jQuery.post( 'ajax_url', {
						action: 'thd_dismissed_handler',
						notice: $( this ).closest( '.thd-notice' ).data( 'notice' ),
					});
				} );
			});
		</script>
		<?php
		$script = str_replace( 'ajax_url', admin_url( 'admin-ajax.php' ), ob_get_clean() );

		wp_add_inline_script( 'jquery', str_replace( array( '<script>', '</script>' ), '', $script ) );
	}
}

new Airi_Theme_Dashboard();