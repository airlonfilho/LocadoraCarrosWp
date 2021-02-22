<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

?>

<div class="translations">
  <div id="premium-tooltip">
    <?php if (defined('BMI_PREMIUM_TOOLTIP')): ?>
    <?php echo BMI_PREMIUM_TOOLTIP; ?>
    <?php endif; ?>
  </div>
  <div id="bmi-success-copy">
    <?php _e('Text copied successfully', 'backup-migration') ?>
  </div>
  <div id="bmi-received-hard">
    <?php _e('Browser successfully received backup settings.', 'backup-migration') ?>
  </div>
  <div id="bmi-failed-copy">
    <?php _e('Your browser does not support copying, please copy by hand', 'backup-migration') ?>
  </div>
  <div id="bmi-abort-soon">
    <?php _e('Backup will be aborted in few seconds.', 'backup-migration') ?>
  </div>
  <div id="bmi-aborted-al">
    <?php _e('Backup process aborted.', 'backup-migration') ?>
  </div>
  <div id="bmi-downloading-remote">
    <?php _e('Downloading backup file...', 'backup-migration') ?>
  </div>
  <div id="bmi-restoring-prepare">
    <?php _e('Preparing restore process...', 'backup-migration') ?>
  </div>
  <div id="bmi-restore-require-checkmark">
    <?php _e('You have to confirm that you understand the risk.', 'backup-migration') ?>
  </div>
  <div id="bmi-upload-start">
    <?php _e('File upload started.', 'backup-migration') ?>
  </div>
  <div id="bmi-upload-error">
    <?php _e('There was an error during file upload.', 'backup-migration') ?>
  </div>
  <div id="bmi-upload-end">
    <?php _e('File has been uploaded successfully.', 'backup-migration') ?>
  </div>
  <div id="bmi-upload-wrong">
    <?php _e('File has wrong type.', 'backup-migration') ?>
  </div>
  <div id="bmi-upload-exists">
    <?php _e('File already exist in backup directory.', 'backup-migration') ?>
  </div>
  <div id="bmi-remove-success">
    <?php _e('Backup(s) removed successfully.', 'backup-migration') ?>
  </div>
  <div id="bmi-remove-error">
    <?php _e('Cannot remove backup(s) file(s) due to unknown error.', 'backup-migration') ?>
  </div>
  <div id="bmi-save-success">
    <?php _e('Configuration saved successfully.', 'backup-migration') ?>
  </div>
  <div id="bmi-save-issues">
    <?php _e('There was an issue during saving, some settings may stay unchanged.', 'backup-migration') ?>
  </div>
  <div id="bmi-no-file">
    <?php _e('Could not find this backup, it may be deleted or there was an error with getting the name.', 'backup-migration') ?>
  </div>
  <div id="bmi-unlock-success">
    <?php _e('File unlocked successfully.', 'backup-migration') ?>
  </div>
  <div id="bmi-unlock-error">
    <?php _e('Could not unlock this backup due to unknown error, please reload and try again.', 'backup-migration') ?>
  </div>
  <div id="bmi-lock-success">
    <?php _e('File locked successfully.', 'backup-migration') ?>
  </div>
  <div id="bmi-lock-error">
    <?php _e('Could not lock this backup due to unknown error, please reload and try again.', 'backup-migration') ?>
  </div>
  <div id="bmi-download-should-start">
    <?php _e('Download process should start.', 'backup-migration') ?>
  </div>
  <div id="bmi-preb-processing">
    <?php _e('We are processing your files, please wait till it complete. You can check the progress in the "What will be backed up?" tab.', 'backup-migration') ?>
  </div>
  <div id="bmi-no-selected">
    <?php _e('There is nothing to backup. Please select database and / or files to backup.', 'backup-migration') ?>
  </div>
  <div id="bmi-invalid-url">
    <?php _e('The URL you provided does not seems to be correct.', 'backup-migration') ?>
  </div>
  <div id="bmi-bc-ended">
    <?php _e('Backup process ended, we triggered backup list reload for your.', 'backup-migration') ?>
  </div>
  <div id="bmi-current-time">
    <?php _e('Current server time: ', 'backup-migration') ?>
  </div>
  <div id="bmi-next-cron">
    <?php _e('Next backup planned: ', 'backup-migration') ?>
  </div>
  <div id="bmi-cron-updated">
    <?php _e('Settings updated successfully', 'backup-migration') ?>
  </div>
  <div id="bmi-cron-updated-fail">
    <?php _e('Could not update CRON setting now, please check the logs.', 'backup-migration') ?>
  </div>
  <div id="bmi-making-archive">
    <?php _e("Making archive", 'backup-migration') ?>
  </div>
  <div id="bmi-email-success">
    <?php _e('Email send successfully, check mailbox.', 'backup-migration') ?>
  </div>
  <div id="bmi-email-fail">
    <?php _e("There was an error sending the email, please use additional plugins to debug it or ask your hosting administrator for help.", 'backup-migration') ?>
  </div>
  <div id="bmi-manual-locked">
    <?php _e("Manually created backups are always locked.", 'backup-migration') ?>
  </div>
  <div id="bmi-default-success">
    <?php _e("Operation finished with success.", 'backup-migration') ?>
  </div>
  <div id="bmi-default-fail">
    <?php _e("Operation failed, please try again.", 'backup-migration') ?>
  </div>
  <div id="bmi-loading-translation">
    <?php _e("Loading...", 'backup-migration') ?>
  </div>
  <div id="BMI_URL_ROOT"><?php echo plugin_dir_url(BMI_ROOT_FILE); ?></div>
  <div id="BMI_BLOG_URL"><?php echo get_site_url(); ?></div>
  <div id="BMI_REV"><?php echo BMI_REV; ?></div>
  <div><input type="text" id="bmi-support-url-translation" value="<?php echo BMI_CHAT_SUPPORT_URL ?>" hidden></div>
</div>
