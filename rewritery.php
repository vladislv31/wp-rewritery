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

        // meta boxes
        add_action('add_meta_boxes', [$this, 'post_meta_boxes']);

        // ajax actions
        add_action('wp_ajax_add-rewrite', [$this, 'add_rewrite_callback']);
        // add_action('wp_ajax_rewrite-cron', [$this, 'rewrite_cron_callback']);

        // add_action('edit_post', [$this, 'change_rewritery_status'], 10, 2);



        // register cron
        add_action('wp', [$this, 'cronstarter_activation']);
        register_deactivation_hook (__FILE__, [$this, 'cronstarter_deactivate']);
        add_action('rewritery_cron_job', [$this, 'rewrite_cron_callback']);
        add_filter('cron_schedules', [$this, 'cron_add_minute']);


        // post list columns
        add_filter('manage_post_posts_columns', function($columns) {
            return array_merge($columns, ['rewritery_status' => 'Rewritery - Status', 'rewritery_date' => 'Rewritery - Last Date']);
        });
         
        add_action('manage_post_posts_custom_column', function($column_key, $post_id) {
            if ($column_key == 'rewritery_status') {
                $status = get_post_meta($post_id, 'rewritery_status', true);
                $status = $status ? $status : 'not rewritered';
                echo ucfirst($status);
            } else if ($column_key == 'rewritery_date') {
                $date = get_post_meta($post_id, 'rewritery_last_date', true);
                $date = $date ? $date : 'Not rewritered yet';
                echo $date;
            }
        }, 1, 2);

        // post list actions
        add_filter('bulk_actions-edit-post', function($bulk_actions) {
            $bulk_actions['rewritery-rewrite'] = 'Rewrite';
            return $bulk_actions;
        });

        add_filter('handle_bulk_actions-edit-post', function($redirect_url, $action, $post_ids) {
            if ($action == 'rewritery-rewrite') {
                foreach ($post_ids as $post_id) {
                    $this->rewrite_post($post_id);
                }
                $redirect_url = add_query_arg('rewritery-rewrite', count($post_ids), $redirect_url);
            }
            return $redirect_url;
        }, 10, 3);

        // error

        // add_action( 'admin_notices', [$this, 'rewritery_error_notice'] );
    }

    function rewritery_error_notice() {
        ?>
        <div class="error">
            <p>test</p>
        </div>
        <?php
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

            <?php
        }
    }

    public function post_meta_boxes() {
        add_meta_box(
            'rewritery_metabox', // ID нашего метабокса
            'Rewrite Settings', // заголовок
            [$this, 'rewritery_metabox_callback'], // функция, которая будет выводить поля в мета боксе
            'post', // типы постов, для которых его подключим
            'normal', // расположение (normal, side, advanced)
            'default' // приоритет (default, low, high, core)
        );
    }

    public function rewritery_metabox_callback($post) {
        $id = $post->ID;
        $status = get_post_meta($id, 'rewritery_status', true) ? get_post_meta($id, 'rewritery_status', true) : 'not rewritered';

        ?>

            <div class="rewritery-post-settings" id="rewritery_block">
                <p>Rewrite Post Status: <b><?php echo $status; ?></b></p>
                <a href="/wp-admin/admin-ajax.php?action=add-rewrite&post_id=<?php echo $id; ?>&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="components-button is-primary rewritery-add-button">Rewrite</a>
                <?php

                    $error = get_post_meta($id, 'rewritery_error', true);
                    if ($error) {
                        ?>

                            <div class="rewritery_error">
                                <p><?php echo $error; ?></p>
                            </div>
                        
                        <?php

                        delete_post_meta($id, 'rewritery_error');
                    }

                ?>
            </div>

        <?php
    }

    public function add_rewrite_callback() {
        $id = $_GET['post_id'];
        $rd_url = $_GET['redirect_url'];

        if (get_post_status($id)) {
            $res = $this->rewrite_post($id);

            echo '<pre>';
            var_dump($res);
            exit();

            if ($res['error'] != null) {
                update_post_meta($id, 'rewritery_error', $res['error']);
            }

            $rd_url .= '#rewritery_block';
            wp_redirect(get_site_url() . $rd_url);
            exit();
        }

        wp_die();
    }

    public function rewrite_post($id) {
        require_once plugin_dir_path(__FILE__).'libs/phpQuery.php';

        $options = get_option('rewritery_settings_options');

        $post = get_post($id);
        $content = $post->post_content;

        $doc = phpQuery::newDocument($content);
        $paragraphs = $doc->find('p');
        $lists = $doc->find('ul');
        $titles = $doc->find('h1, h2, h3, h4, h5, h6');

        $blocks = [];

        foreach ($titles as $t) {
            $blocks[] = [
                'type' => 'paragraph',
                'data' => [
                    'text' => pq($t)->text()
                ]
            ];
        }

        foreach ($paragraphs as $p) {
            $blocks[] = [
                'type' => 'paragraph',
                'data' => [
                    'text' => pq($p)->text()
                ]
            ];
        }

        foreach ($lists as $l) {
            $lis = pq($l)->find('li');

            foreach ($lis as $li) {
                $blocks[] = [
                    'type' => 'paragraph',
                    'data' => [
                        'text' => pq($li)->text()
                    ]
                ];
            }
        }

        require_once plugin_dir_path(__FILE__).'api.php';

        $api = new API($options['api_token']);
        $res = $api->addRewrite($blocks);

        if ($res['error']) {
            return $res;
        } else {
            $rewrite_id = $res['result']['_id'];
            update_post_meta($id, 'rewritery_rewrite_id', $rewrite_id);
            update_post_meta($id, 'rewritery_status', 'in process');

            return $res;
        }
    }

    public function rewrite_cron_callback() {
        $options = get_option('rewritery_settings_options');

        require_once plugin_dir_path(__FILE__).'api.php';
        $api = new API($options['api_token']);

        $args = array(
            'meta_key' => 'rewritery_status',
            'meta_query' => array(
                array(
                    'key' => 'rewritery_status',
                    'value' => 'in process',
                    'compare' => '=',
                )
            )
         );

        $query = new WP_Query($args);

        echo '<pre>';

        while($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();

            $rewrite_id = get_post_meta($id, 'rewritery_rewrite_id', true);
            $rewrites = $api->getRewrite($rewrite_id);

            // var_dump($rewrites);
            // wp_die();

            $content = get_the_content();
            $rewrites_data = [];

            if ($rewrites != null) {
                if ($rewrites['item'] != null) {
                    if ($rewrites['item']['status'] == 9) {
                        foreach($rewrites['item']['blocks'] as $block) {
                            $text = $block['data']['text'];
                            $suggestion = $block['rewriteDataSuggestions'][0]['text'];
                            $rewrites_data[] = [
                                'text' => $text,
                                'suggestion' => $suggestion
                            ];
                        }
        
                        foreach($rewrites_data as $d) {
                            if ($d['suggestion'] != null) {
                                $content = str_replace($d['text'], $d['suggestion'], $content);
                            }
                        }
                
                        wp_update_post(wp_slash(['ID' => $id, 'post_content' => $content]));
        
                        delete_post_meta($id, 'rewritery_rewrite_id');
                        update_post_meta($id, 'rewritery_status', 'rewritered');
                        update_post_meta($id, 'rewritery_last_date', current_time('d m Y H:i'));
                    }
                }
            }
        }

        wp_reset_postdata();
        wp_die();
    }

    public function change_rewritery_status($post_ID, $post) {
        delete_post_meta($post_ID, 'rewritery_status');
    }

    function cronstarter_activation() {
        if (!wp_next_scheduled('rewritery_cron_job')) {  
            wp_schedule_event(time(), 'everyminute', 'rewritery_cron_job');  
        }
    }

    function cronstarter_deactivate() {	
        $timestamp = wp_next_scheduled('rewritery_cron_job');
        wp_unschedule_event($timestamp, 'rewritery_cron_job');
    }

    function cron_add_minute($schedules) {
        $schedules['everyminute'] = array(
            'interval' => 60,
            'display' => __( 'Once Every Minute' )
        );

        return $schedules;
    }
}

if (class_exists('Rewritery')) {
    $rewritery = new Rewritery();
    $rewritery->register();
}
