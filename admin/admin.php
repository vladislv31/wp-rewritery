<?php
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  //Get the active tab from the $_GET param
  $default_tab = null;
  $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

  ?>

  <div class="rewritery-content">

  <!-- Our admin page content should all be inside .wrap -->
  <div class="wrap">
    <!-- Print the page title -->
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
      <a href="?page=rewritery_settings" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Settings</a>
      <a href="?page=rewritery_settings&tab=statistics" class="nav-tab <?php if($tab==='statistics'):?>nav-tab-active<?php endif; ?>">Statistics</a>
    </nav>

  <div class="tab-content">
    <?php switch($tab) :
      case 'statistics':
        require_once plugin_dir_path(__FILE__).'../api.php';

        $options = get_option('rewritery_settings_options');

        $api = new API($options['api_token']);
        $stats = $api->getStatistics();

        if ($stats) {
            echo '<table class="rewritery-content__table"><tbody>';
            echo '<tr><th>Month</th><th>Characters number</th></tr>';

            foreach($stats as $key => $stat) {
                echo '<tr><td>'.$key.'</td><td>'.$stat.'</td></tr>';
            }

            echo '</tbody></table>';
        } else {
            echo 'Stats is clear...';
        }

        break;
      default:
        ?>
            <?php settings_errors(); ?>
                <form action="options.php" method="POST">
                    <?php
                        settings_fields('rewritery_settings');
                        do_settings_sections('rewritery_settings');
                        submit_button();
                    ?>
                </form>
            <?php
        break;
    endswitch; ?>
    </div>
  </div>


   </div>