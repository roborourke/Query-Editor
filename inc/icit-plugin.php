<?php

/**
 * Standard class for all ICIT WordPress products
 *
 * - Register the plugin to add settings/boiler plate and API page
 * - Use add_meta_box() or settings API to extend the page, page id is the file path
 *
 *
 * v0.3
 *
 * TODO:
 * Add donate URL support (Amazon? Paypal? Flattr?)
 * API key check
 * Fancier settings UI (tabs to go through settings sections)
 * Magic boats
 * Sub module register/unregister/enable/disable system
 *
 */

if ( ! class_exists( 'icit_plugins' ) ) {

	add_action( 'plugins_loaded', array( 'icit_plugins', 'instance' ), 1 );

	class icit_plugins {

		public $plugins = array();
		public $plugin = '';

		// initilisation structure
		protected static $instance = null;

		public static function instance() {
			null === self :: $instance AND self :: $instance = new self;
			return self :: $instance;
		}

		/**
		 * Setup
		 *
		 * @return void
		 */
		function __construct() {

			// create plugin page
			add_action( 'admin_menu', array( $this, 'plugin_pages' ), 100 );

			// handle save after pages are prepped and settings registered
			add_action( 'admin_init', array( $this, 'save' ), 100 );

		}

		/**
		 * Registers the plugin so we can automatically add a page for it
		 *
		 * @return void
		 */
		function register( $id = false, $plugin_file = false, $args = array() ) {

			if ( ! $id || ! $plugin_file )
				return;

			/** WordPress Plugin Administration API */
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			$plugin_data = get_plugin_data( $plugin_file );

			$args = wp_parse_args( $args, array(
				'ID' 			=> $id,
				'page_title' 	=> $plugin_data[ 'Name' ],
				'menu_title' 	=> $plugin_data[ 'Name' ],
				'menu_slug' 	=> $id,
				'capability' 	=> 'manage_options',
				'parent_slug' 	=> 'options-general.php',
				'icon_url' 		=> '',
				'position' 		=> 110, // after settings
				'extra_content' => '',
				'file' 			=> $plugin_file
			) );

			// add plugin to list
			$this->plugins[ $id ] = $args;
		}


		/**
		 * Add options page for each registered plugin and set current page
		 *
		 * @return Type    Description
		 */
		function plugin_pages() {

			foreach( $this->plugins as $id => $plugin ) {

				// choose the current page if any
				if ( isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == $plugin[ 'menu_slug' ] )
					$this->plugin = $id;

				// plugin page CSS & JS
				if ( $this->is_plugin_page() ) {
					add_action( 'admin_print_styles', array( $this, 'plugin_css' ) );
					add_action( 'admin_enqueue_styles', array( $this, 'css' ) );
					add_action( 'admin_enqueue_scripts', array( $this, 'js' ) );
				}

				// create page
				if ( $plugin[ 'parent_slug' ] )
					add_submenu_page( $plugin[ 'parent_slug' ], $plugin[ 'page_title' ], $plugin[ 'menu_title' ], $plugin[ 'capability' ], $plugin[ 'menu_slug' ], array( $this, 'build_page' ) );
				else
					add_menu_page( $plugin[ 'page_title' ], $plugin[ 'menu_title' ], $plugin[ 'capability' ], $plugin[ 'menu_slug' ], array( $this, 'build_page' ), $plugin[ 'icon_url' ], $plugin[ 'position' ] );

			}

		}



		function build_page() {

			$id = $this->plugin;

			if ( empty( $id ) )
				return;

			$plugin = $this->plugins[ $id ];
			$plugin_data = get_plugin_data( $plugin[ 'file' ] );

			if ( ! current_user_can( $plugin[ 'capability' ] ) )
				wp_die( __( 'You do not have permission to use this page.' ) );

			echo '
		<div class="wrap icit-plugin metabox-holder">';

			// title
			if ( isset( $plugin[ 'icon' ] ) )
				echo $plugin[ 'icon' ];
			echo '<h2>' . esc_html( $plugin[ 'page_title' ] ) . '</h2>';

			// wrap form around everything
			echo '
			<form name="icit" action="' . admin_url( 'options.php' ) . '" method="post" enctype="multipart/form-data">';

			settings_fields( $id );

			// error/update messages
			settings_errors( 'general' ); 	// standard 'updated' message
			settings_errors( $id ); 		// custom errors

			// version & info metabox
			echo '
				<div class="right-column">
					<div class="column-inner">
						<div class="postbox icit-branding">
							<h3>' . $plugin_data[ 'Name' ] . '</h3>
							<div class="version">v' . $plugin_data[ 'Version' ] . '</div>
							<p class="description">' . $plugin_data[ 'Description' ] . '</p>
							<div class="plugin-url"><a href="' . $plugin_data[ 'PluginURI' ] . '">' . __( 'Visit plugin page' ) . '</a></div>
							<div class="credit">by <a href="' . $plugin_data[ 'AuthorURI' ] . '">interconnect/it</a></div>
						</div>';

			// process sidebar metaboxes
			do_meta_boxes( $id, 'side', $plugin );

			echo '
					</div>
				</div>
				<div class="left-column">
					<div class="column-inner">';

			// custom callback content
			if ( is_callable( $plugin[ 'extra_content' ] ) )
				call_user_func_array( $plugin[ 'extra_content' ], array( 'plugin_id' => $id, 'plugin_data' => $plugin ) );

			// API key field
			$this->api();

			// settings API hooks
			ob_start();
			do_settings_fields( $id, 'default' );
			$settings_fields = trim( ob_get_clean() );

			ob_start();
			do_settings_sections( $id );
			$settings_sections = trim( ob_get_clean() );

			if ( ! empty( $settings_fields ) || ! empty( $settings_sections ) ) {

				ob_start();
				if ( ! empty( $settings_fields ) )
					echo '<table class="form-table">' . $settings_fields . '</table>';
				if ( ! empty( $settings_sections ) )
					echo $settings_sections;

				echo ob_get_clean();

			}

			ob_start();
			// normal context metaboxes
			do_meta_boxes( $id, 'normal', $plugin );

			// advanced context metaboxes
			do_meta_boxes( $id, 'advanced', $plugin );

			$metaboxes = ob_get_clean();

			echo $metaboxes;

			if ( ! empty( $settings_fields ) || ! empty( $settings_sections ) || ! empty( $meta_boxes ) )
				submit_button();

			echo '
					</div>
				</div>
			</form>
		</div>';

		}

		function get() {
			return $this->plugins;
		}

		function save() {
			do_action( "icit_plugin_save", $this->plugin );
		}

		function plugin_css() {
			?>
			<style>
				.icit-plugin .right-column { float: right; width: 280px; }
				.icit-plugin .left-column { float: left; width: 100%; margin-right: -300px; }
				.icit-plugin .left-column .column-inner { margin-right: 300px; }
				.icit-plugin .icit-branding { background: #fff; }
				.icit-plugin .icit-branding h3,
				.icit-plugin .icit-branding h3:hover { margin: 0; padding: 10px 10px 0; cursor: text; background: none; color: #464646; border: 0; border-top: 20px solid #c00; font-size: 18px; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; }
				.icit-plugin .icit-branding p { margin: 20px 10px; line-height: 16px; }
				.icit-plugin .icit-branding p cite { display: none; }
				.icit-plugin .icit-branding div { margin: 10px; }
				.icit-plugin .icit-branding .version { margin: 5px 10px 10px; font-size: 16px; color: #787878; }
				.icit-plugin .meta-box-sortables { clear: both; margin-top: 20px; }
			</style>
			<?php
		}

		function css() {
			wp_enqueue_style( 'postbox' );
			do_action( "enqueue_styles_{$this->plugin}" );
		}

		function js() {
			wp_enqueue_script( 'postbox' );
			do_action( "enqueue_scripts_{$this->plugin}" );
		}

		function is_plugin_page() {
			return ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == $this->plugin ) || ( isset( $_GET[ 'option_page' ] ) && $_GET[ 'option_page' ] == $this->plugin );
		}

		/**
		 * Checks if API key is required and returns the field for the settings page if it is.
		 *
		 * @return string    HTML for API key field
		 */
		function api() {

		}

	}

	if ( ! function_exists( 'icit_register_plugin' ) ) {

		/**
		 * Public method to register ICIT plugins
		 *
		 * @param string 	$id 			A unique ID used to refer to the page via the settings API and metabox API
		 * @param string 	$plugin_file 	The full path of the main plugin file
		 * @param array 	$args        	Optional settings for the boiler plate page
		 *
		 *	'page_title' 	=> Plugin page title, defaults to plugin name
		 *	'menu_title' 	=> Plugin page title in menu, defaults to plugin name
		 *	'menu_slug' 	=> Unique slug for query string parameter, default generated from plugin name
		 *	'capability' 	=> Capability type required to see page, defaults to 'manage_options'
		 *	'parent_slug' 	=> Parent page file name or slug, defaults to 'options-general.php'
		 *	'extra_content' => Callable function to output anything you want
		 *
		 * @return icit_plugins::register()
		 */
		function icit_register_plugin( $id, $plugin_file, $args = array() ) {
			$icit_plugins_class = icit_plugins::instance();
			return $icit_plugins_class->register( $id, $plugin_file, $args );
		}

	}

}


if ( ! class_exists( 'icit_plugin_helper' ) ) {

	class icit_plugin_helper {

		public $plugins = array();
		public $plugin = '';

		// initilisation structure
		protected static $instance = null;

		public static function instance() {
			null === self :: $instance AND self :: $instance = new self;
			return self :: $instance;
		}

		/**
		 * Setup
		 *
		 * @return void
		 */
		function __construct() {

			

			// create plugin page
			add_action( 'admin_menu', array( $this, 'plugin_pages' ), 100 );

			// handle save after pages are prepped and settings registered
			add_action( 'admin_init', array( $this, 'save' ), 100 );

		}

		/**
		 * Registers the plugin so we can automatically add a page for it
		 *
		 * @return void
		 */
		function register( $id = false, $plugin_file = false, $args = array() ) {

			if ( ! $id || ! $plugin_file )
				return;

			/** WordPress Plugin Administration API */
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			$plugin_data = get_plugin_data( $plugin_file );

			$args = wp_parse_args( $args, array(
				'ID' 			=> $id,
				'page_title' 	=> $plugin_data[ 'Name' ],
				'menu_title' 	=> $plugin_data[ 'Name' ],
				'menu_slug' 	=> $id,
				'capability' 	=> 'manage_options',
				'parent_slug' 	=> 'options-general.php',
				'icon_url' 		=> '',
				'position' 		=> 110, // after settings
				'extra_content' => '',
				'file' 			=> $plugin_file
			) );

			// add plugin to list
			$this->plugins[ $id ] = $args;
		}


		/**
		 * Add options page for each registered plugin and set current page
		 *
		 * @return Type    Description
		 */
		function plugin_pages() {

			foreach( $this->plugins as $id => $plugin ) {

				// choose the current page if any
				if ( isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == $plugin[ 'menu_slug' ] )
					$this->plugin = $id;

				// plugin page CSS & JS
				if ( $this->is_plugin_page() ) {
					add_action( 'admin_print_styles', array( $this, 'plugin_css' ) );
					add_action( 'admin_enqueue_styles', array( $this, 'css' ) );
					add_action( 'admin_enqueue_scripts', array( $this, 'js' ) );
				}

				// create page
				if ( $plugin[ 'parent_slug' ] )
					add_submenu_page( $plugin[ 'parent_slug' ], $plugin[ 'page_title' ], $plugin[ 'menu_title' ], $plugin[ 'capability' ], $plugin[ 'menu_slug' ], array( $this, 'build_page' ) );
				else
					add_menu_page( $plugin[ 'page_title' ], $plugin[ 'menu_title' ], $plugin[ 'capability' ], $plugin[ 'menu_slug' ], array( $this, 'build_page' ), $plugin[ 'icon_url' ], $plugin[ 'position' ] );

			}

		}



		function build_page() {

			$id = $this->plugin;

			if ( empty( $id ) )
				return;

			$plugin = $this->plugins[ $id ];
			$plugin_data = get_plugin_data( $plugin[ 'file' ] );

			if ( ! current_user_can( $plugin[ 'capability' ] ) )
				wp_die( __( 'You do not have permission to use this page.' ) );

			echo '
		<div class="wrap icit-plugin metabox-holder">';

			// title
			if ( isset( $plugin[ 'icon' ] ) )
				echo $plugin[ 'icon' ];
			echo '<h2>' . esc_html( $plugin[ 'page_title' ] ) . '</h2>';

			// wrap form around everything
			echo '
			<form name="icit" action="' . admin_url( 'options.php' ) . '" method="post" enctype="multipart/form-data">';

			settings_fields( $id );

			// error/update messages
			settings_errors( 'general' ); 	// standard 'updated' message
			settings_errors( $id ); 		// custom errors

			// version & info metabox
			echo '
				<div class="right-column">
					<div class="column-inner">
						<div class="postbox icit-branding">
							<h3>' . $plugin_data[ 'Name' ] . '</h3>
							<div class="version">v' . $plugin_data[ 'Version' ] . '</div>
							<p class="description">' . $plugin_data[ 'Description' ] . '</p>
							<div class="plugin-url"><a href="' . $plugin_data[ 'PluginURI' ] . '">' . __( 'Visit plugin page' ) . '</a></div>
							<div class="credit">by <a href="' . $plugin_data[ 'AuthorURI' ] . '">interconnect/it</a></div>
						</div>';

			// process sidebar metaboxes
			do_meta_boxes( $id, 'side', $plugin );

			echo '
					</div>
				</div>
				<div class="left-column">
					<div class="column-inner">';

			// custom callback content
			if ( is_callable( $plugin[ 'extra_content' ] ) )
				call_user_func_array( $plugin[ 'extra_content' ], array( 'plugin_id' => $id, 'plugin_data' => $plugin ) );

			// API key field
			$this->api();

			// settings API hooks
			ob_start();
			do_settings_fields( $id, 'default' );
			$settings_fields = trim( ob_get_clean() );

			ob_start();
			do_settings_sections( $id );
			$settings_sections = trim( ob_get_clean() );

			if ( ! empty( $settings_fields ) || ! empty( $settings_sections ) ) {

				ob_start();
				if ( ! empty( $settings_fields ) )
					echo '<table class="form-table">' . $settings_fields . '</table>';
				if ( ! empty( $settings_sections ) )
					echo $settings_sections;

				echo ob_get_clean();

			}

			ob_start();
			// normal context metaboxes
			do_meta_boxes( $id, 'normal', $plugin );

			// advanced context metaboxes
			do_meta_boxes( $id, 'advanced', $plugin );

			$metaboxes = ob_get_clean();

			echo $metaboxes;

			if ( ! empty( $settings_fields ) || ! empty( $settings_sections ) || ! empty( $meta_boxes ) )
				submit_button();

			echo '
					</div>
				</div>
			</form>
		</div>';

		}

		function get() {
			return $this->plugins;
		}

		function save() {
			do_action( "icit_plugin_save", $this->plugin );
		}

		function plugin_css() {
			?>
			<style>
				.icit-plugin .right-column { float: right; width: 280px; }
				.icit-plugin .left-column { float: left; width: 100%; margin-right: -300px; }
				.icit-plugin .left-column .column-inner { margin-right: 300px; }
				.icit-plugin .icit-branding { background: #fff; }
				.icit-plugin .icit-branding h3,
				.icit-plugin .icit-branding h3:hover { margin: 0; padding: 10px 10px 0; cursor: text; background: none; color: #464646; border: 0; border-top: 20px solid #c00; font-size: 18px; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; }
				.icit-plugin .icit-branding p { margin: 20px 10px; line-height: 16px; }
				.icit-plugin .icit-branding p cite { display: none; }
				.icit-plugin .icit-branding div { margin: 10px; }
				.icit-plugin .icit-branding .version { margin: 5px 10px 10px; font-size: 16px; color: #787878; }
				.icit-plugin .meta-box-sortables { clear: both; margin-top: 20px; }
			</style>
			<?php
		}

		function css() {
			wp_enqueue_style( 'postbox' );
			do_action( "enqueue_styles_{$this->plugin}" );
		}

		function js() {
			wp_enqueue_script( 'postbox' );
			do_action( "enqueue_scripts_{$this->plugin}" );
		}

		function is_plugin_page() {
			return ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == $this->plugin ) || ( isset( $_GET[ 'option_page' ] ) && $_GET[ 'option_page' ] == $this->plugin );
		}

		/**
		 * Checks if API key is required and returns the field for the settings page if it is.
		 *
		 * @return string    HTML for API key field
		 */
		function api() {

		}

	}

}

?>
