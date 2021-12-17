<?php

/*
Plugin Name: Rewritery
Description: Developed for rewriting posts texts by using ai.dofiltra API
Version: 1.0.0
Author: vladislv31
Author URI: https://github.com/vladislv31
License: GPLv2 or later
Text Domain: rewritery
*/

if (!function_exists('add_action')) die('Hhmmm.....');

class Rewritery
{
    public function register() {
        // enqueue admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_front']);

        // add menu admin
        add_action('admin_menu', [$this, 'add_admin_menu']);

        add_action('admin_init', [$this, 'settings_init']);
    }

    public function enqueue_admin() {
        wp_enqueue_style('rewriteryStyle', plugins_url('/assets/admin/styles.css', __FILE__));
        wp_enqueue_script('rewriteryScript', plugins_url('/assets/admin/scripts.js', __FILE__));
    }

    public function enqueue_front() {
        wp_enqueue_style('rewriteryStyle', plugins_url('/assets/front/styles.css', __FILE__));
        wp_enqueue_script('rewriteryScript', plugins_url('/assets/front/scripts.js', __FILE__));
    }

    public function add_admin_menu() {
        add_menu_page(
            esc_html__('Rewritery Settings Page', 'rewritery'),
            esc_html__('Rewritery', 'rewritery'),
            'manage_options',
            'rewritery_settings',
            [$this, 'rewritery_page'],
            'dashicons-welcome-write-blog',
            100
        );
    }

    public function rewritery_page() {
        require_once plugin_dir_path(__FILE__).'admin/admin.php';
    }

    public function settings_init() {
        register_setting('rewritery_settings', 'rewritery_settings_options');
        add_settings_section('rewritery_settings_section', esc_html__('Settings', 'rewritery'), [$this, 'settings_section_html'], 'rewritery_settings');

        add_settings_field('api_token', esc_html__('API Token', 'rewritery'), [$this, 'api_token_html'], 'rewritery_settings', 'rewritery_settings_section');
    }

    public function settings_section_html() {
        // echo esc_html__('Hello, world!', 'rewritery');
    }

    public function api_token_html() {
        $options = get_option('rewritery_settings_options');

        ?>

            <input type="text" name="rewritery_settings_options[api_token]" value="<?php echo isset($options['api_token']) ? $options['api_token'] : ''; ?>">

        <?php

        if ($options['api_token']) {
            require_once plugin_dir_path(__FILE__).'api.php';

            $api = new API($options['api_token']);
            $balance = $api->getBalance();

            ?>

                <p>Token Balance: <?php echo $balance; ?> coins</p>
                <p>Stats: </p>

            <?php
        }
    }
}

if (class_exists('Rewritery')) {
    $rewritery = new Rewritery();
    $rewritery->register();
}
