<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://pica-pay.com
 * @since      1.0.0
 *
 * @package    Pica_Pay
 * @subpackage Pica_Pay/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Pica_Pay
 * @subpackage Pica_Pay/includes
 * @author     Pica-Pay <support@pica-pay.com>
 */
class Pica_Pay {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Pica_Pay_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PICA_PAY_VERSION' ) ) {
			$this->version = PICA_PAY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'pica-pay';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Pica_Pay_Loader. Orchestrates the hooks of the plugin.
	 * - Pica_Pay_i18n. Defines internationalization functionality.
	 * - Pica_Pay_Admin. Defines all hooks for the admin area.
	 * - Pica_Pay_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pica-pay-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pica-pay-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-pica-pay-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-pica-pay-public.php';

		$this->loader = new Pica_Pay_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Pica_Pay_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Pica_Pay_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Pica_Pay_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

        $this->loader->add_action('save_post', $plugin_admin, 'pica_pay_save_post_meta');

        $this->loader->add_action('init', $plugin_admin, 'pica_pay_register_post_meta');

        $this->loader->add_action('enqueue_block_editor_assets', $plugin_admin, 'enqueue_pp_block_editor_assets');

        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'pica_pay_add_meta_box' );

        $this->loader->add_action('admin_notices', $plugin_admin, 'bulk_action_admin_notice');

        $this->loader->add_action('admin_menu', $plugin_admin, 'add_options_page');
        $this->loader->add_action('admin_init', $plugin_admin, 'options_page_init');

        // Add quick edit options
        $this->loader->add_action('quick_edit_custom_box', $plugin_admin, 'display_quick_edit', 10, 2);
        $this->loader->add_action('save_post', $plugin_admin, 'save_quick_edit_data');
        $this->loader->add_action('wp_ajax_save-quick-edit', $plugin_admin, 'save_quick_edit_data');

        // Add bulk editing options
        $this->loader->add_filter('bulk_actions-edit-post', $plugin_admin, 'register_bulk_actions');
        $this->loader->add_filter('bulk_actions-edit-page', $plugin_admin, 'register_bulk_actions');
        $this->loader->add_filter('handle_bulk_actions-edit-post', $plugin_admin, 'handle_bulk_actions', 10, 3);
        $this->loader->add_filter('handle_bulk_actions-edit-page', $plugin_admin, 'handle_bulk_actions', 10, 3);

        // Add custom column
        $this->loader->add_filter('manage_posts_columns', $plugin_admin, 'add_custom_column');
        $this->loader->add_filter('manage_pages_columns', $plugin_admin, 'add_custom_column');
        $this->loader->add_action('manage_posts_custom_column', $plugin_admin, 'custom_column_content', 10, 2);
        $this->loader->add_action('manage_pages_custom_column', $plugin_admin, 'custom_column_content', 10, 2);
    }

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Pica_Pay_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        $this->loader->add_action( 'wp_ajax_create_transaction', $plugin_public, 'handle_create_transaction' );
        $this->loader->add_action( 'wp_ajax_nopriv_create_transaction', $plugin_public, 'handle_create_transaction' );
        $this->loader->add_action( 'wp_ajax_poll_transaction_status', $plugin_public, 'handle_poll_transaction_status' );
        $this->loader->add_action( 'wp_ajax_nopriv_poll_transaction_status', $plugin_public, 'handle_poll_transaction_status' );

        $this->loader->add_filter('the_content', $plugin_public, 'is_paid_article');
    }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Pica_Pay_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
