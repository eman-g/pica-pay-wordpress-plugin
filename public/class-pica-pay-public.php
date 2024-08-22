<?php

if (!session_id()) {
    session_start();
}

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://pica-pay.com
 * @since      1.0.0
 *
 * @package    Pica_Pay
 * @subpackage Pica_Pay/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Pica_Pay
 * @subpackage Pica_Pay/public
 * @author     Pica-Pay <support@pica-pay.com>
 */
class Pica_Pay_Public {

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

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/pica-pay-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

        wp_enqueue_script($this->plugin_name . '-ajax', plugin_dir_url(__FILE__) . 'js/pica-pay-ajax.js', array(), '1.0', true);

        // Localize the script with new data
        $translation_array = array(
            'ajaxurl' => admin_url('admin-ajax.php')
        );
        wp_localize_script($this->plugin_name . '-ajax', 'picaPayParams', $translation_array);
	}

    private function get_options()
    {
        $options = get_option('pica_pay_options');

        if (empty($options['api_url'])) {
            wp_send_json_error('API URL not set in options');
        }
        if (empty($options['api_key'])) {
            wp_send_json_error('API Key not set in options');
        }
        if (empty($options['default_charge'])) {
            wp_send_json_error('Default charge not set in options');
        }

        return $options;
    }

    public function handle_create_transaction()
    {
        $pp_post_id = isset($_POST['pp_post_id']) ? sanitize_text_field($_POST['pp_post_id']) : '';

        $options = $this->get_options();

        $api_url = $options['api_url'] . '/vendor/transaction/create';
        $api_key = $options['api_key'];
        $charge = get_post_meta($pp_post_id, '_pica_pay_charge', true) ?? $options['default_charge'];
        $post = get_post($pp_post_id);

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$api_key,
            ],
            'body' => json_encode([
                'charge' => $charge,
                'description' => 'Charge for ' . $post->post_title . ' (ID ' . $pp_post_id . ')',
            ]),
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Failed to create transaction: '.$response->get_error_message());
        } else {
            $responseObj = json_decode(wp_remote_retrieve_body($response), true);

            if (empty($responseObj['transactionId'])) {
                wp_send_json_error('Transaction ID not found in response');
            }

            $ppSessionId = $options['session_key'] . $responseObj['transactionId'];
            $_SESSION[$ppSessionId] = $pp_post_id;

            wp_send_json_success($responseObj + ['postId' => $pp_post_id]);
        }

        wp_die();
    }

    public function handle_poll_transaction_status()
    {
        $options = $this->get_options();
        $transaction_id = isset($_POST['transactionId']) ? sanitize_text_field($_POST['transactionId']) : '';
        $api_url = $options['api_url'] . '/vendor/transaction/status/' . $transaction_id;
        $api_key = $options['api_key'];

        $response = wp_remote_get($api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$api_key,
            ],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Failed to poll transaction status: ' . $response->get_error_message());
        } else {
            $ppPostId = null;
            if (session_id()) {
                $ppPostId = $_SESSION[$options['session_key'] . $transaction_id];
            }

            if (empty($ppPostId)) {
                wp_send_json_error('Post ID not found');
            } else {
                $status = json_decode(wp_remote_retrieve_body($response), true)['status'];
                if (in_array($status, ['deducted', 'completed'])) {
                    $post = get_post($ppPostId);

                    $_SESSION[$options['session_key'] . $post->ID] = true;

                    wp_send_json_success(['status' => $status, 'postId' => $ppPostId, 'postContent' => ' POST CONTENT - post ID: ' . $ppPostId . ' - ' . $post->post_content]);
                } else {
                    wp_send_json_success(json_decode(wp_remote_retrieve_body($response), true) + ['postId' => $ppPostId]);
                }
            }
        }

        wp_die();
    }

    /**
     * Checks if the current post is post ID X and runs custom logic.
     *
     * @param string $content The content of the current post.
     * @return string The modified or unmodified content.
     */
    public function is_paid_article($content) {
        global $post;

        $options = get_option('pica_pay_options');

        // Check post meta to see if there is a charge for content
        if (get_post_meta($post->ID, '_pica_pay_paid', true) === '1') {
            // Display content if session contains transaction ID
            if (session_id() && ($_SESSION[$options['session_key'] . $post->ID] ?? false) === true) {
                return $content;
            }

            // Get excerpt
            $post = get_post();

            $num_preview_words = $options['preview_length'] ?? 100;

            $content = implode(' ', array_slice(explode(' ', $post->post_content), 0, $num_preview_words)) .
                '...<br><button id="purchase-button" data-pp-post-id=' . get_the_id() . '>Purchase Content</button>
                <div id="article-content"></div>';
        }

        return $content;
    }
}
