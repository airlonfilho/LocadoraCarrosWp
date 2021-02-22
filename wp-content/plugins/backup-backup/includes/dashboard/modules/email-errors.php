<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;
  use BMI\Plugin\Backup_Migration_Plugin AS BMP;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

?>

<?php if (get_option('bmi_display_email_issues', false)): ?>

<div class="error-noticer">
  <div class="error-header">
    <div class="cf">
      <div class="left">
        <?php _e('We have some notices regarding most recent automated backup.', 'backup-migration'); ?>
      </div>
      <div class="right hoverable">
        <span id="bmi-error-toggle" data-expand="<?php _e('Expand', 'backup-migration'); ?>" data-collapse="<?php _e('Collapse', 'backup-migration'); ?>">
          <?php _e('Expand', 'backup-migration'); ?>
        </span> |
        <span id="bmi-error-dismiss">
          <?php _e('Dismiss', 'backup-migration'); ?>
        </span>
      </div>
    </div>
  </div>
  <div class="error-body">
    <?php echo get_option('bmi_display_email_issues', false); ?>
  </div>
</div>

<?php endif; ?>
