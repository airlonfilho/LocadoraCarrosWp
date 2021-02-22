<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

?>

<div class="modal modal-no-close" id="restore-success-modal">

  <div class="modal-wrapper no-hpad" style="max-width: 850px; max-width: min(850px, 80vw)">
    <div class="modal-content center">

      <div class="mm60 f35 bold black mbl mtl"><?php _e('Restore successful!​', 'backup-migration') ?></div>
      <img class="mb mtl" src="<?php echo $this->get_asset('images', 'happy-smile.png'); ?>" alt="success">

      <div class="mbl f20 lh30">
        <?php _e("Liked how easy it was? Then PLEASE support the further", 'backup-migration'); ?><br>
        <?php _e("development of our plugins by doing the following:", 'backup-migration'); ?>
      </div>

      <div class="cf mb inline center block suc-buttns">
        <div class="left a1">
          <a href="https://wordpress.org/plugins/backup-backup/#reviews" target="_blank" class="btn lime">
            <div class="flex nowrap flexcenter">
              <div class="fcentr">
                <img class="center block inline" src="<?php echo $this->get_asset('images', 'thumb.png'); ?>" alt="trash">
              </div>
              <div class="fbcont lh20">
                <span class="fbhead semibold"><?php _e("Give us a nice rating", 'backup-migration'); ?></span>
                <?php _e("…so that others discover our", 'backup-migration'); ?>
                <?php _e("plugin & benefit from it too.", 'backup-migration'); ?>
              </div>
            </div>
          </a>
        </div>
        <div class="left a2">
          <a href="<?php echo BMI_AUTHOR_URI; ?>" target="_blank" class="btn">
            <div class="flex nowrap flexcenter">
              <div class="fcentr">
                <img class="center block inline" src="<?php echo $this->get_asset('images', 'crown-bg.png'); ?>" alt="trash">
              </div>
              <div class="fbcont lh20">
                <span class="fbhead semibold"><?php _e("Get our Premium plugin", 'backup-migration'); ?></span>
                <?php _e("…to benefit from many cool features & support.", 'backup-migration'); ?>
              </div>
            </div>

          </a>
        </div>
      </div>

      <div class="mb f28 secondary center semibold">
        <?php _e("Thank you!!", 'backup-migration'); ?>
      </div>

      <div class="center mbl">
        <a href="#" class="btn width50 f22 inline grey bold nodec site-reloader">
          <?php _e("Ok, close", 'backup-migration'); ?>
        </a>
      </div>

      <div class="center f17 mbl">
        <a href="<?php echo get_site_url(); ?>/?backup-migration=PROGRESS_LOGS&progress-id=latest_migration.log&backup-id=current&t=<?php echo time(); ?>"
           download="<?php _e('restore_process_logs', 'backup-migration') ?>">
          <?php _e("Download the log", 'backup-migration'); ?></a> <?php _e("of the restoration process", 'backup-migration'); ?>
      </div>

    </div>
  </div>

</div>
