<h1 class="rewritery_title"><?php esc_html_e('Rewritery Settings', 'rewritery'); ?></h1>
<?php settings_errors(); ?>
<div class="rewritery_content">
    <form action="options.php" method="POST">
        <?php
            settings_fields('rewritery_settings');
            do_settings_sections('rewritery_settings');
            submit_button();
        ?>
    </form>
</div>