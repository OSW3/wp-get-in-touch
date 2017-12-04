<div class="wrap osw3-plugin">
    <h3><?= $this->config->Name ?></h3>
    <p><?= __('version', 'osw3_plugins' ) ?> <?= $this->config->Version ?></p>
   
    <hr>

    <form method="post" novalidate>
        Hello, i'm the settings page !<br>
        Locate me at : <?= $settings_view_file ?>


        <?php submit_button() ?>

    </form>
</div>