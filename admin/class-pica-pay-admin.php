<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://pica-pay.com
 * @since      1.0.0
 *
 * @package    Pica_Pay
 * @subpackage Pica_Pay/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Pica_Pay
 * @subpackage Pica_Pay/admin
 * @author     Pica-Pay <support@pica-pay.com>
 */
class Pica_Pay_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    private $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Pica_Pay_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Pica_Pay_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/pica-pay-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Pica_Pay_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Pica_Pay_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/pica-pay-admin.js', array( 'jquery' ), $this->version, false );
	}

    public function enqueue_pp_block_editor_assets() {
        wp_enqueue_script(
            $this->plugin_name . '-block-editor',
            plugin_dir_url(__FILE__) . '../build/pica-pay-blockeditor.js',
            ['wp-edit-post'],
            $this->version,
            true
        );

        $options = get_option('pica_pay_options');
        $default_charge = $options['default_charge'];
        wp_localize_script($this->plugin_name . '-block-editor', 'picaPayData', array(
            'defaultCharge' => $default_charge
        ));

        wp_script_add_data($this->plugin_name . '-block-editor', 'type', 'module');
    }

    function pica_pay_register_post_meta() {
        register_post_meta('post', '_pica_pay_paid', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        register_post_meta('page', '_pica_pay_paid', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        register_post_meta('post', '_pica_pay_charge', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'number',
            'sanitize_callback' => 'rest_sanitize_number',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        register_post_meta('page', '_pica_pay_charge', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'number',
            'sanitize_callback' => 'rest_sanitize_number',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
    }

    function pica_pay_add_meta_box() {
        $post_types = ['post', 'page']; // Add both post and page
        foreach ($post_types as $post_type) {
            add_meta_box(
                'pica_pay_paid_meta',
                'Pica-Pay Paid',
                'pica_pay_paid_meta_box_callback',
                $post_type,
                'side',
                'high',
                ['__back_compat_meta_box' => true] // Hide the meta box in Gutenberg
            );
            add_meta_box(
                'pica_pay_charge_meta',
                'Pica-Pay Charge',
                'pica_pay_charge_meta_box_callback',
                $post_type,
                'side',
                'high',
                ['__back_compat_meta_box' => true] // Hide the meta box in Gutenberg
            );
        }
    }

    function pica_pay_paid_meta_box_callback($post) {
        // Use nonce for verification
        wp_nonce_field('pica_pay_paid_meta_box', 'pica_pay_paid_meta_box_nonce');

        // Get the current value
        $value = get_post_meta($post->ID, '_pica_pay_paid', true);

        // Checkbox HTML
        echo '<label for="pica_pay_paid">';
        echo '<input type="checkbox" id="pica_pay_paid" name="pica_pay_paid" value="yes" ' . checked($value, 'yes', false) . ' />';
        echo ' Pica-Pay Paid</label> ';
    }

    function pica_pay_charge_meta_box_callback($post) {
        wp_nonce_field('pica_pay_charge_meta_box', 'pica_pay_charge_meta_box_nonce');

        // Set value to value of field, default is default charge from options
        $value = get_post_meta($post->ID, '_pica_pay_charge', true);
        if (empty($value)) {
            $options = get_option('pica_pay_options');
            $value = $options['default_charge'];
        }

        // Checkbox HTML
        echo '<label for="pica_pay_charge">';
        echo '<input type="number" id="pica_pay_charge" name="pica_pay_charge" value="' . $value . '" />';
        echo ' Charge</label>';
    }

    function pica_pay_save_post_meta($post_id) {
        if (!isset($_POST['pica_pay_paid_meta_box_nonce']) || !wp_verify_nonce($_POST['pica_pay_paid_meta_box_nonce'], 'pica_pay_paid_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['pica_pay_paid'])) {
            update_post_meta($post_id, '_pica_pay_paid', true);
        } else {
            delete_post_meta($post_id, '_pica_pay_paid');
        }

        if (isset($_POST['pica_pay_charge'])) {
            update_post_meta($post_id, '_pica_pay_charge', true);
        } else {
            delete_post_meta($post_id, '_pica_pay_charge');
        }
    }

    public function register_bulk_actions($bulk_actions) {
        $bulk_actions['add_pica_pay_paid'] = __('Add Pica-Pay Paid', 'pica-pay');
        $bulk_actions['remove_pica_pay_paid'] = __('Remove Pica-Pay Paid', 'pica-pay');
        return $bulk_actions;
    }

    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction === 'add_pica_pay_paid') {
            foreach ($post_ids as $post_id) {
                update_post_meta($post_id, '_pica_pay_paid', '1');
            }
            $redirect_to = add_query_arg('bulk_add_pica_pay_paid', count($post_ids), $redirect_to);
        }

        if ($doaction === 'remove_pica_pay_paid') {
            foreach ($post_ids as $post_id) {
                delete_post_meta($post_id, '_pica_pay_paid');
            }
            $redirect_to = add_query_arg('bulk_remove_pica_pay_paid', count($post_ids), $redirect_to);
        }

        return $redirect_to;
    }

    public function bulk_action_admin_notice() {
        if (!empty($_REQUEST['bulk_add_pica_pay_paid'])) {
            $count = (int)$_REQUEST['bulk_add_pica_pay_paid'];
            printf('<div id="message" class="updated fade"><p>' . __('Applied Pica-Pay charge to %s posts.', 'pica-pay') . '</p></div>', $count);
        }

        if (!empty($_REQUEST['bulk_remove_pica_pay_paid'])) {
            $count = (int)$_REQUEST['bulk_remove_pica_pay_paid'];
            printf('<div id="message" class="updated fade"><p>' . __('Removed Pica-Pay charge from %s posts.', 'pica-pay') . '</p></div>', $count);
        }
    }

    public function display_quick_edit($column_name, $post_type) {
        if ($column_name !== 'pica_pay_paid') {
            return;
        }
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label class="alignleft">
                    <input type="checkbox" name="pica_pay_paid" class="pica_pay_paid">
                    <span class="checkbox-title"><?php _e('Pica-Pay paid content', 'pica-pay'); ?></span>
                </label>
            </div>
            <?php wp_nonce_field('pica_pay_paid_quick_edit', 'pica_pay_paid_quick_edit_nonce'); ?>
        </fieldset>
        <?php
    }

    public function save_quick_edit_data($post_id) {
        if (!isset($_POST['pica_pay_paid_meta_box_nonce']) || !wp_verify_nonce($_POST['pica_pay_paid_meta_box_nonce'], 'pica_pay_paid_meta_box')) {
            if (!isset($_POST['pica_pay_paid_quick_edit_nonce']) || !wp_verify_nonce($_POST['pica_pay_paid_quick_edit_nonce'], 'pica_pay_paid_quick_edit')) {
                return;
            }
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['pica_pay_paid'])) {
            update_post_meta($post_id, '_pica_pay_paid', true);
        } else {
            delete_post_meta($post_id, '_pica_pay_paid');
        }
    }

    public function add_custom_column($columns) {
        $columns['pica_pay_paid'] = __('Pica-Pay Paid', 'pica-pay');
        return $columns;
    }

    public function custom_column_content($column_name, $post_id) {
        if ($column_name === 'pica_pay_paid') {
            $value = get_post_meta($post_id, '_pica_pay_paid', true);
            echo $value ? __('Yes', 'pica-pay') : __('No', 'pica-pay');
        }
    }

    public function add_options_page() {
        add_options_page(
            'Pica-Pay Settings',
            'Pica-Pay',
            'manage_options',
            'pica-pay-settings',
            [$this, 'create_admin_page']
        );
    }

    public function create_admin_page() {
        $this->options = get_option('pica_pay_options');
        ?>
        <div class="wrap">
            <h1>Pica-Pay Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('pica_pay_option_group');
                do_settings_sections('pica-pay-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function options_page_init() {
        register_setting(
            'pica_pay_option_group',
            'pica_pay_options',
            [$this, 'sanitize']
        );

        add_settings_section(
            'setting_section_id',
            'Settings',
            [$this, 'print_section_info'],
            'pica-pay-settings'
        );

        add_settings_field(
            'api_url',
            'API URL',
           [$this, 'api_url_callback'],
            'pica-pay-settings',
            'setting_section_id'
        );

        add_settings_field(
            'api_key',
            'API Key',
            [$this, 'api_key_callback'],
            'pica-pay-settings',
            'setting_section_id'
        );

        add_settings_field(
            'session_key',
            'Session Key',
            [$this, 'session_key_callback'],
            'pica-pay-settings',
            'setting_section_id'
        );

        add_settings_field(
            'preview_length',
            'Preview Length',
            [$this, 'preview_length_callback'],
            'pica-pay-settings',
            'setting_section_id'
        );

        add_settings_field(
            'default_charge',
            'Default Charge',
            [$this, 'default_charge_callback'],
            'pica-pay-settings',
            'setting_section_id'
        );
    }

    public function sanitize($input) {
        $new_input = array();
        if (isset($input['api_url'])) {
            $new_input['api_url'] = sanitize_text_field($input['api_url']);
        }

        if (isset($input['api_key'])) {
            $new_input['api_key'] = sanitize_text_field($input['api_key']);
        }

        if (isset($input['session_key'])) {
            $new_input['session_key'] = sanitize_text_field($input['session_key']);
        }

        if (isset($input['preview_length'])) {
            $new_input['preview_length'] = sanitize_text_field($input['preview_length']);
        }

        if (isset($input['default_charge'])) {
            $new_input['default_charge'] = sanitize_text_field($input['default_charge']);
        }

        return $new_input;
    }

    public function print_section_info() {
        print 'Enter your settings below:';
    }

    public function api_url_callback() {
        printf(
            '<input type="text" id="api_url" name="pica_pay_options[api_url]" value="%s" />',
            isset($this->options['api_url']) ? esc_attr($this->options['api_url']) : ''
        );
    }

    public function api_key_callback() {
        printf(
            '<input type="text" id="api_key" name="pica_pay_options[api_key]" value="%s" />',
            isset($this->options['api_key']) ? esc_attr($this->options['api_key']) : ''
        );
    }

    public function session_key_callback() {
        printf(
            '<input type="text" id="session_key" name="pica_pay_options[session_key]" value="%s" />',
            !empty($this->options['session_key']) ? esc_attr($this->options['session_key']) : bin2hex(random_bytes(10 / 2))
        );
        print ' <i>Automatically generated if left blank. Changing this value clears all current Pica-Pay sessions.</i>';
    }

    public function preview_length_callback() {
        printf(
            '<input type="text" id="preview_length" name="pica_pay_options[preview_length]" value="%s" />',
            isset($this->options['preview_length']) ? esc_attr($this->options['preview_length']) : 100
        );
        print ' <i>Number of words to display in preview</i>';
    }

    public function default_charge_callback() {
        printf(
            '<input type="text" id="default_charge" name="pica_pay_options[default_charge]" value="%s" />',
            isset($this->options['default_charge']) ? esc_attr($this->options['default_charge']) : 0
        );
        print ' <i>Default charge for content (in cents)</i>';
    }
}
