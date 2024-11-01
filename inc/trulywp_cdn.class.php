<?php

/**
 * TrulyWP_CDN
 *
 * @since 0.0.1
 */

class TrulyWP_CDN
{


    /**
     * pseudo-constructor
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function instance() {
        new self();
    }


    /**
     * constructor
     *
     * @since   0.0.1
     * @change  1.0.4
     */

    public function __construct() {
        /* CDN rewriter hook */
        add_action(
            'template_redirect',
            [
                __CLASS__,
                'handle_rewrite_hook',
            ]
        );

        /* Hooks */
        add_action(
            'admin_init',
            [
                __CLASS__,
                'register_textdomain',
            ]
        );
        add_action(
            'admin_init',
            [
                'TrulyWP_CDN_Settings',
                'register_settings',
            ]
        );
        add_action(
            'admin_menu',
            [
                'TrulyWP_CDN_Settings',
                'add_settings_page',
            ]
        );
        add_filter(
            'plugin_action_links_' .TRULYWP_CDN_BASE,
            [
                __CLASS__,
                'add_action_link',
            ]
        );

        /* admin notices */
        add_action(
            'all_admin_notices',
            [
                __CLASS__,
                'trulywp_requirements_check',
            ]
        );

        /* add admin purge link */
        add_action(
            'admin_bar_menu',
            [
                __CLASS__,
                'add_admin_links',
            ],
            90
        );
        /* process purge request */
        add_action(
            'admin_notices',
            [
                __CLASS__,
                'process_purge_request',
            ]
        );



        //Detect changes to content

        add_action( 'transition_post_status', [
            __CLASS__,
            'twp_on_all_status_transitions',
        ],10,3);
        
		add_action( 'post_updated',  [
            __CLASS__,
            'process_change',
        ]);

		add_action( 'wp_insert_comment', [
            __CLASS__,
            'process_change',
        ]);
		add_action( 'edit_comment', [
            __CLASS__,
            'process_change',
        ]);
		add_action( 'transition_comment_status', [
            __CLASS__,
            'process_change',
        ]);
		add_action( 'wp_update_nav_menu', [
            __CLASS__,
            'process_change',
        ]);
    }


    public static function twp_on_all_status_transitions( $new_status, $old_status, $post ) {
        if ( $new_status != $old_status ) {
            error_log("on_all_status_transitions :".$new_status."/".$old_status);
            TrulyWP_CDN::process_change();
        }
    }

    /**
     * process change (hook to handle changes in site)
     *
     * @since   1.0.5
     * @change  1.0.6
     *
     */
    public static function process_change() {
        error_log("*********** Change detected!");

        $twp_settings = parse_ini_file("/etc/trulywp/config/wordpress/trulywp.ini");
        $install_id = $twp_settings["install_id"];
        $dashboard_url = $twp_settings["dashboard_url"];
        error_log($install_id);
        error_log($dashboard_url);


        $dashboard_url = rtrim($dashboard_url, '/');
        $url = $dashboard_url."/purge_cdn";

        $params = new stdClass();
        $params->install_id = $install_id;
        $json_data = json_encode($params);

        $result = file_get_contents($url,null,stream_context_create(array(
            'http' => array(
                'protocol_version' => 1.1,
                'user_agent'       => 'PHPExample',
                'method'           => 'POST',
                'header'           => "Content-type: application/json\r\n".
                                    "Connection: close\r\n" .
                                    "Content-length: " . strlen($json_data) . "\r\n",
                'content'          => $json_data,
            ),
        )));

        if ($result) {
            error_log($result);
            return true;
        } else {
            error_log("POST failed");
            return false;
        }
        
    }


    /**
     * add Zone purge link
     *
     * @since   1.0.5
     * @change  1.0.6
     *
     * @hook    mixed
     *
     * @param   object  menu properties
     */

    public static function add_admin_links($wp_admin_bar) {
        global $wp;
        $options = self::get_options();

        // check user role
        if ( ! is_admin_bar_showing() or ! apply_filters('user_can_clear_cache', current_user_can('manage_options')) ) {
            return;
        }

        // redirect to admin page if necessary so we can display notification
        $current_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' .
                        $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $goto_url = get_admin_url();

        if ( stristr($current_url, get_admin_url()) ) {
            $goto_url = $current_url;
        }

        // add admin purge link
        $wp_admin_bar->add_menu(
            [
                'id'      => 'purge-cdn',
                'href'   => wp_nonce_url( add_query_arg('_cdn', 'purge', $goto_url), '_cdn__purge_nonce'),
                'parent' => 'top-secondary',
                'title'     => '<span class="ab-item">'.esc_html__('Purge CDN', 'trulywp-cdn').'</span>',
                'meta'   => ['title' => esc_html__('Purge CDN', 'trulywp-cdn')],
            ]
        );

        if ( ! is_admin() ) {
            // add admin purge link
            $wp_admin_bar->add_menu(
                [
                    'id'      => 'purge-cdn',
                    'href'   => wp_nonce_url( add_query_arg('_cdn', 'purge', $goto_url), '_cdn__purge_nonce'),
                    'parent' => 'top-secondary',
                    'title'     => '<span class="ab-item">'.esc_html__('Purge CDN', 'trulywp-cdn').'</span>',
                    'meta'   => ['title' => esc_html__('Purge CDN', 'trulywp-cdn')],
                ]
            );
        }
    }


    /**
     * process purge request
     *
     * @since   1.0.5
     * @change  1.0.6
     *
     * @param   array  $data  array of metadata
     */
    public static function process_purge_request($data) {
        $options = self::get_options();

        // check if clear request
        if ( empty($_GET['_cdn']) OR $_GET['_cdn'] !== 'purge' ) {
            return;
        }

        // validate nonce
        if ( empty($_GET['_wpnonce']) OR ! wp_verify_nonce($_GET['_wpnonce'], '_cdn__purge_nonce') ) {
            return;
        }

        // check user role
        if ( ! is_admin_bar_showing() ) {
            return;
        }

        // load if network
        if ( ! function_exists('is_plugin_active_for_network') ) {
            require_once( ABSPATH. 'wp-admin/includes/plugin.php' );
        }


        error_log("Purge CDN called");

       


        $ok = TrulyWP_CDN::process_change();
        if ($ok == true) {
            printf(
                '<div class="notice notice-info is-dismissible"><p>CDN purge request was successful. This might take some time to propagate.</p></div>'
            );
        } else {
            printf(
                '<div class="notice notice-error is-dismissible"><p>CDN purge request was unsuccessful. Please try again later.</p></div>'
            );
        }



        if ( ! is_admin() ) {
            wp_safe_redirect(
                remove_query_arg(
                    '_cache',
                    wp_get_referer()
                )
            );

            exit();
        }
    }



    /**
     * add action links
     *
     * @since   0.0.1
     * @change  0.0.1
     *
     * @param   array  $data  alreay existing links
     * @return  array  $data  extended array with links
     */

    public static function add_action_link($data) {
        // check permission
        if ( ! current_user_can('manage_options') ) {
            return $data;
        }

        return array_merge(
            $data,
            [
                sprintf(
                    '<a href="%s">%s</a>',
                    add_query_arg(
                        [
                            'page' => 'trulywp-cdn',
                        ],
                        admin_url('options-general.php')
                    ),
                    __("Settings")
                ),
            ]
        );
    }


    /**
     * run uninstall hook
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function handle_uninstall_hook() {
        delete_option('trulywp-cdn');
    }


    /**
     * run activation hook
     *
     * @since   0.0.1
     * @change  1.0.5
     */

    public static function handle_activation_hook() {
        add_option(
            'trulywp-cdn',
            [
                'url'            => get_option('home'),
                'dirs'           => 'wp-content,wp-includes',
                'excludes'       => '.php',
                'relative'       => '1',
                'https'          => '',
                'enabled'        => '0',
            ]
        );
        error_log("TrulyWP_CDN activation hook called");
    }


    /**
     * check plugin requirements
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function trulywp_requirements_check() {
        // WordPress version check
        if ( version_compare($GLOBALS['wp_version'], TRULYWP_CDN_MIN_WP.'alpha', '<') ) {
            show_message(
                sprintf(
                    '<div class="error"><p>%s</p></div>',
                    sprintf(
                        __("TrulyWP_CDN is optimized for WordPress %s. Please disable the plugin or upgrade your WordPress installation (recommended).", "trulywp"),
                        TRULYWP_CDN_MIN_WP
                    )
                )
            );
        }
    }


    /**
     * register textdomain
     *
     * @since   1.0.3
     * @change  1.0.3
     */

    public static function register_textdomain() {
        load_plugin_textdomain(
            'trulywp-cdn',
            false,
            'trulywp/lang'
        );
    }


    /**
     * return plugin options
     *
     * @since   0.0.1
     * @change  1.0.5
     *
     * @return  array  $diff  data pairs
     */

    public static function get_options() {
        return wp_parse_args(
            get_option('trulywp-cdn'),
            [
                'url'             => get_option('home'),
                'dirs'            => 'wp-content,wp-includes',
                'excludes'        => '.php',
                'relative'        => 1,
                'https'           => 0,
                'enabled'        => '0',
            ]
        );
    }


    /**
     * run rewrite hook
     *
     * @since   0.0.1
     * @change  1.0.5
     */

    public static function handle_rewrite_hook() {
        $options = self::get_options();

        //TODO : Temp removed to allow localhost
        // check if origin equals cdn url
        // if (get_option('home') == $options['url']) {
        //     return;
        // }

        error_log("handle rewrite called");

        if ($options['enabled'] == 0) {
            error_log("CDN is disabled");
            return;
        }

        $twp_settings = parse_ini_file("/etc/trulywp/config/wordpress/trulywp.ini");
        $install_id = $twp_settings["install_id"];

        $excludes = array_map('trim', explode(',', $options['excludes']));

        //$cdn_url = 'http://localhost';
        if ($options['https'] == 0) {
            $cdn_url = "http://".$install_id."-d101.kxcdn.com";
        } else {
            $cdn_url = "https://".$install_id."-d101.kxcdn.com";
        }

        $rewriter = new TrulyWP_Rewriter(
            get_option('home'),
            $cdn_url,
            $options['dirs'],
            $excludes,
            $options['relative'],
            $options['https'],
            $options['enabled']
        );
        ob_start(array(&$rewriter, 'rewrite'));
    }

}
