<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

?>

<div class="modal" id="backup-success-modal">

  <div class="modal-wrapper" style="max-width: 900px; max-width: min(900px, 80vw)">
    <div class="modal-content">

      <div class="mms f30 bold center black"><?php _e('Backup successful!', 'backup-migration') ?></div>

      <div class="center mbl mtl">
        <img src="<?php echo $this->get_asset('images', 'happy-smile.png'); ?>" alt="happy-img">
      </div>

      <div id="accessible-at-section">
        <div class="mms mbl">
          <div class="f18 mbll">
            <?php _e('Your backup is now accessible at:', 'backup-migration') ?>
          </div>
          <div class="cf success-copy-input">
            <input type="text" id="text-input-copy" readonly class="left f18">
            <a href="#" class="btn inline btn-with-img btn-img-low-pad btn-pad left bmi-copper" data-copy="text-input-copy">
              <div class="text">
                <img src="<?php echo $this->get_asset('images', 'copy-icon.png'); ?>" alt="copy-img">
                <div class="f18 semibold"><?php _e('Copy', 'backup-migration') ?></div>
              </div>
            </a>
          </div>
        </div>

        <div class="mms f18 mtl lh30">
          <?php _e('To migrate your site, just copy above link, install our plugin on the target site, go to the', 'backup-migration') ?>
          "<a href="#" class="hoverable secondary go-to-marbs"><?php _e('Manage & Restore Backups', 'backup-migration') ?></a>"
          <?php _e('- tab, and paste the link there.', 'backup-migration') ?>
        </div>
      </div>

      <div class="f18 mtl mbl mms lh14">
        <?php _e('You can manage the backup on the', 'backup-migration') ?>
        "<a href="#" class="hoverable secondary go-to-marbs"><?php _e('Manage & Restore Backups', 'backup-migration') ?></a>"
        <?php _e('- tab.', 'backup-migration') ?>
      </div>

      <div class="mms mtl flex-here lh50">
        <div class="f18 align-left">
          <a href="#" class="nlink hoverable" id="download-backup-url" download><?php _e('Download backup', 'backup-migration') ?></a>
        </div>

        <div class="center">
          <a href="#" class="btn inline btn-pad modal-closer grey-btn nplr" data-close="backup-success-modal">
            <div class="text">
              <div class="f18 semibold"><?php _e('Close window', 'backup-migration') ?></div>
            </div>
          </a>
        </div>

        <div class="f18 inline align-right">
          <a href="#" class="nlink hoverable" id="download-backup-log-url" download="<?php _e('backup-logs', 'backup-migration') ?>">
            <?php _e('Download logs', 'backup-migration') ?>
          </a>
        </div>
      </div>

    </div>
  </div>

</div>
