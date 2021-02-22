<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

?>

<div class="modal modal-no-close" id="backup-progress-modal">

  <div class="modal-wrapper" style="max-width: 900px; max-width: min(900px, 80vw)">
    <div class="modal-content center">

      <div class="mm60 f26 bold black"><?php _e('Backup in progress', 'backup-migration') ?></div>

      <div class="progress-bar-wrapper">

        <div class="progress-bar">
          <div class="progress-active-bar" style="width: 0%;"></div>
          <div class="progress-percentage" style="left: 0%;">0%</div>
        </div>

      </div>

      <div class="step-progress cf">
        <div class="right f16 medium hoverable pointer"
          data-show="<?php _e('Show live log', 'backup-migration') ?>"
          data-hide="<?php _e('Hide live log', 'backup-migration') ?>"
          id="live-log-toggle"
        >
          <?php _e('Hide live log', 'backup-migration') ?>
        </div>
        <div class="left f16 medium">
          <?php _e('Step: ', 'backup-migration') ?>
          <span id="current_step"><?php _e('Preparing backup process...', 'backup-migration') ?></span>
        </div>
      </div>

      <div class="live-log" id="live-log-wrapper">

        <div class="log-wrapper">
          <pre></pre>
        </div>
        <div class="f16 semibold secondary hoverable pointer">
          <a href="<?php echo get_site_url(); ?>/?backup-migration=PROGRESS_LOGS&progress-id=latest.log&backup-id=current&t=<?php echo time(); ?>"
             download="<?php _e('live_progress', 'backup-migration') ?>" class="nlink">
            <?php _e('Download live log​', 'backup-migration') ?>
          </a>
        </div>

      </div>

      <div class="f18 semibold mtll">
        <?php _e('Please don’t do any major modifications on your site while the backup is running.', 'backup-migration') ?>
      </div>

      <div class="mtl">
        <div>
          <a href="#" class="btn inline btn-with-img btn-img-low-pad btn-pad modal-closer backup-minimize">
            <div class="text">
              <img src="<?php echo $this->get_asset('images', 'minimize-min.png'); ?>" alt="minimize-img">
              <div class="f18 semibold"><?php _e('Minimize this window', 'backup-migration') ?></div>
            </div>
          </a>
        </div>
        <div class="text-grey text-muted mtll f16 modal-closer medium red-text inline" data-close="backup-progress-modal" id="backup-stop">
          <?php _e('Stop the backup process', 'backup-migration') ?>
        </div>
      </div>

    </div>
  </div>

</div>
