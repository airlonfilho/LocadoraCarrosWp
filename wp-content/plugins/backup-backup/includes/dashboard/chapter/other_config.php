<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Tooltips
  $deinstalled_info = __('This will be triggered on plugin removal via WordPress plugins tab', 'backup-migration');
  $experimental_info = __('It will change some fundamental logics of the plugin', 'backup-migration');
  $experimental_info_1 = __('Use this option if you have full access to your server and you know how to make basic configuration of the server. Wrong configuration may give you hick-ups without error due to e.g. web server server timeout (for small sites below 300 MB this is the best option).', 'backup-migration');
  $experimental_info_2 = __('Use this option before the third one, it should work fine on SSD/NVMe hostings even for huge backups - but still may timeout if you are running on slow drive high I/O.', 'backup-migration');
  $experimental_info_3 = __('This option will require you to not close the backup window since it will use your connection to keep the backup in parts, it will disable automatic backups. Use this only if all of the above does not work. Recommended for huge sites +100k files / 5+ GB.', 'backup-migration');

?>

<div class="mt mb f18 lh30">

  <!--  -->
  <div class="mm cf mbl">
    <div class="f20 bold mbll">
      <?php _e('Email notifications', 'backup-migration'); ?>
    </div>

    <div class="left mw250 lh65">
      <?php _e('Email address:', 'backup-migration'); ?>
    </div>
    <div class="left">
      <div class="">
        <?php
          $ee = sanitize_text_field(bmi_get_config('OTHER:EMAIL'));
          if (strlen($ee) <= 1) {
            $ee = get_bloginfo('admin_email');
          }
        ?>
        <input type="text" id="email-for-notices" class="bmi-text-input small" value="<?php echo $ee; ?>" />
      </div>
      <div class="f16">
        <?php _e('This is where the log files will be sent to. You can enter several email addresses, separated by comma.', 'backup-migration'); ?>
      </div>
    </div>
  </div>

  <!--  -->
  <div class="mm cf mbl">
    <div class="left mw250 lh65">
      <?php _e('From field:', 'backup-migration'); ?>
    </div>
    <div class="left">
      <div class="">
        <input type="text" id="email-title-for-notices" class="bmi-text-input small" value="<?php echo sanitize_text_field(bmi_get_config('OTHER:EMAIL:TITLE')); ?>" />
      </div>
      <div class="f16">
        <?php _e('This will show up as sender of the emails', 'backup-migration'); ?>
      </div>
    </div>
  </div>

  <!--  -->
  <div class="mm cf mbl">

    <div class="left mw250 lh50" style="line-height: 145px;">
      <?php _e("You'll get an email if...", 'backup-migration'); ?>
    </div>

    <div class="left lh40">
      <table>
        <tbody>
          <tr>
            <td>
              <label class="premium-wrapper">
                <input type="checkbox" disabled>
                <span><?php _e("Backups was created successfully", 'backup-migration'); ?></span>
                <span class="premium premium-img premium-ntt"></span>
              </label>
            </td>
            <td>
              <label class="premium-wrapper">
                <input type="checkbox" disabled>
                <span><?php _e("Backup creation failed", 'backup-migration'); ?></span>
                <span class="premium premium-img premium-ntt"></span>
              </label>
            </td>
          </tr>
          <tr>
            <td>
              <label class="premium-wrapper">
                <input type="checkbox" disabled>
                <span><?php _e("Restore succeeded", 'backup-migration'); ?></span>
                <span class="premium premium-img premium-ntt"></span>
              </label>
            </td>
            <td>
              <label class="premium-wrapper">
                <input type="checkbox" disabled>
                <span><?php _e("Restore failed", 'backup-migration'); ?></span>
                <span class="premium premium-img premium-ntt"></span>
              </label>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <label for="scheduled-issues">
                <input type="checkbox" id="scheduled-issues"<?php bmi_try_checked('OTHER:EMAIL:NOTIS'); ?>>
                <span>
                  <?php _e("There are (new) issues with scheduling (creating automatic backups)", 'backup-migration'); ?><br>
                  <span class="f14">
                    <?php _e("(Make sure that your hosting does not block mail functions, otherwise you have to configure SMTP mail.)", 'backup-migration'); ?>
                  </span>
                </span>
              </label>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>

  <!--  -->
  <div class="mm mbl">
    <div class="cf">

      <div class="left mr20">
        <?php _e("Add logs to emails?", 'backup-migration'); ?>
      </div>

      <div class="left">
        <div class="left d-flex mr60 ia-center">
          <label class="container-radio">
            <?php _e("No", 'backup-migration'); ?>
            <input type="radio" checked name="add_logs_email">
            <span class="checkmark-radio"></span>
          </label>
          <div class="inline premium-wrapper cf">
            <label class="left container-radio ml25 not-allowed">
              <?php _e("Yes", 'backup-migration'); ?>
              <input type="radio" disabled name="add_logs_email">
              <span class="checkmark-radio"></span>
            </label>
            <span class="left premium premium-img premium-nt mtf3"></span>
          </div>
        </div>
      </div>

    </div>

    <div class="f16 mtll">
      <?php _e("If you want to also receive the backup file as attachment of the email (for backup notifications), please set this in chapter Where will backups be stored?.", 'backup-migration'); ?>
    </div>
  </div>

  <!--  -->
  <div class="mm">
    <div class="f20 bold">
      <?php _e("Backup triggers", 'backup-migration'); ?>
    </div>

    <div class="f16 mtll">
      <?php _e('At the top of the plugin you can create a backup instantly ("Create backup now" - button), or schedule them. Here are more options which trigger the backup creation:', 'backup-migration'); ?>
    </div>
  </div>

  <!--  -->
  <div class="mbl mtl overlayed">

    <?php include BMI_INCLUDES . '/dashboard/templates/premium-overlay.php'; ?>

    <div class="mm">
      <div class="cf">
        <div class="left">
          <div class="f20 bold mr20 premium-wrapper">
            <?php _e("Before updates", 'backup-migration'); ?>
            <span class="premium premium-img premium-ntt"></span>
          </div>
        </div>
        <div class="left">
          <label for="before-updates-switch" class="bmi-switch">
            <input type="checkbox" disabled checked id="before-updates-switch">
            <div class="bmi-switch-slider round">
              <span class="on"><?php _e("On", 'backup-migration'); ?></span>
              <span class="off"><?php _e("Off", 'backup-migration'); ?></span>
            </div>
          </label>
        </div>
      </div>
    </div>
    <div class="mm">
      <div class="mtll f16">
        <?php _e("Activate this so that a backup is created before there are automatic WordPress updates (WordPress core, plugins, themes, or language files).", 'backup-migration'); ?>
      </div>
    </div>

  </div>

  <!--  -->
  <div class="overlayed">

    <?php include BMI_INCLUDES . '/dashboard/templates/premium-overlay.php'; ?>

    <table class="mm">
      <tbody>
        <tr>
          <td style="vertical-align: top;">
            <div class="f20 bold mw250 lh65 premium-wrapper">
              <?php _e("Trigger by URI", 'backup-migration'); ?>
              <span class="premium premium-img premium-ntt"></span>
            </div>
          </td>
          <td>
            <div class="">
              <div class="cf">
                <div class="left mr20">
                  <input type="text" class="bmi-text-input small" id="trigger-input1" />
                </div>
                <div class="left">
                  <a href="#" class="btn inline btn-with-img btn-img-low-pad btn-pad left bmi-copper othersec mm30" data-copy="trigger-input1">
                    <div class="text">
                      <img src="<?php echo $this->get_asset('images', 'copy-icon.png'); ?>" alt="copy-img">
                      <div class="f18 semibold"><?php _e('Copy', 'backup-migration') ?></div>
                    </div>
                  </a>
                </div>
              </div>
              <div class="f16 mtlll">
                <?php _e("Copy & paste this url into a browser and press enter to trigger the backup creation.", 'backup-migration'); ?><br>
                <?php _e("Make sure you keep this url a secret. For safety reasons this only works once per hour & you’ll get emailed when it used.", 'backup-migration'); ?>
              </div>
              <div class="mtll cf">
                <div class="left lh60 mr20"><?php _e("Key:", 'backup-migration'); ?></div>
                <div class="left mr20">
                  <input type="text" class="bmi-text-input small" />
                </div>
                <div class="left">
                  <a href="#" class="btn mm30 othersec"><?php _e("Save", 'backup-migration'); ?></a>
                </div>
              </div>
              <div class="f16 mtlll">
                <?php _e("Change the key (which is part of above url) if you suspect an unauthorized person got access to it.", 'backup-migration'); ?>
              </div>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

  </div>

  <!--  -->
  <div class="mbl mtl overlayed" style="display: none;">

    <?php include BMI_INCLUDES . '/dashboard/templates/premium-overlay.php'; ?>

    <div class="mm f20 bold premium-wrapper">
      <?php _e("WP CLI", 'backup-migration'); ?>
      <span class="premium premium-img premium-ntt"></span>
    </div>
    <div class="mm mtll f16">
      <?php _e('Trigger backups via WP CLI.', 'backup-migration'); ?>
    </div>
    <div class="mm mtll">
      <?php _e('If you selected the "schedule backups" - option at the top of the plugin, and backups are not created, then please check out the Cron settings. Or just  ask us in the forum.', 'backup-migration'); ?>
    </div>
  </div>

  <!--  -->
  <div class="mm mbl mtl">
    <div class="f20 bold">
      <?php _e("Change functionality of the plugin", 'backup-migration'); ?>
    </div>
    <div class="mtll">
      <span class="relative">
        <?php _e("Some", 'backup-migration'); ?> <b><?php _e("experimental", 'backup-migration'); ?></b> <?php _e("features", 'backup-migration'); ?>:
        &nbsp;<span class="bmi-info-icon tooltip" tooltip="<?php echo $experimental_info; ?>"></span>
      </span>
    </div>

    <div class="lh40">
      <label for="normal-timeout" class="container-radio">
        <input type="radio" name="experimental_features" id="normal-timeout"<?php bmi_try_checked('OTHER:USE:TIMEOUT:NORMAL'); ?> />
        <span class="f18">
          <?php _e("Do not change the default plugin functions - it may require to adjust your server for stable backup", 'backup-migration'); ?>
          &nbsp;<span class="bmi-info-icon tooltip" tooltip="<?php echo $experimental_info_1; ?>"></span>
        </span>
        <span class="checkmark-radio" style="margin-top: 2px;"></span>
      </label>
    </div>
    <div class="lh40">
      <label for="experimental-timeout" class="container-radio">
        <input type="radio" name="experimental_features" id="experimental-timeout"<?php bmi_try_checked('OTHER:EXPERIMENT:TIMEOUT'); ?> />
        <span class="f18">
          <?php _e("Bypass web server timeout directive - backup process may be slower", 'backup-migration'); ?>
          &nbsp;<span class="bmi-info-icon tooltip" tooltip="<?php echo $experimental_info_2; ?>"></span>
        </span>
        <span class="checkmark-radio" style="margin-top: 2px;"></span>
      </label>
    </div>
    <div class="lh40">
      <label for="experimental-hard-timeout" class="container-radio">
        <input type="radio" name="experimental_features" id="experimental-hard-timeout"<?php bmi_try_checked('OTHER:EXPERIMENT:TIMEOUT:HARD'); ?> />
        <span class="f18">
          <?php _e("Bypass web server limits - it will disable automatic backup and possibility to run it in the background", 'backup-migration'); ?>
          &nbsp;<span class="bmi-info-icon tooltip" tooltip="<?php echo $experimental_info_3; ?>"></span>
        </span>
        <span class="checkmark-radio" style="margin-top: 2px;"></span>
      </label>
    </div>
  </div>

  <!--  -->
  <div class="mm mbl">
    <div class="f20 bold">
      <?php _e("Clean-ups", 'backup-migration'); ?>
    </div>
    <div class="mtll">
      <span class="relative">
        <?php _e("When this plugins is", 'backup-migration'); ?> <b><?php _e("de-installed:", 'backup-migration'); ?></b>
        &nbsp;<span class="bmi-info-icon tooltip" tooltip="<?php echo $deinstalled_info; ?>"></span>
      </span>
    </div>

    <div class="lh40">
      <label for="uninstalling-configs">
        <input type="checkbox" id="uninstalling-configs"<?php bmi_try_checked('OTHER:UNINSTALL:CONFIGS'); ?> />
        <span><?php _e("Delete all plugins settings (this means if you install it again, you have to configure it again)", 'backup-migration'); ?></span>
      </label>
    </div>
    <div class="lh40">
      <label for="uninstalling-backups">
        <input type="checkbox" id="uninstalling-backups"<?php bmi_try_checked('OTHER:UNINSTALL:BACKUPS'); ?> />
        <span><?php _e("Delete all backups (created by this plugin)", 'backup-migration'); ?></span>
      </label>
    </div>
  </div>

  <!--  -->
  <div class="mm mtll">
    <?php _e("If you're looking for other options not listed above, check out the", 'backup-migration'); ?> <a href="#" class="secondary hoverable nodec collapser-openner" data-el="#troubleshooting-chapter"><?php _e("troubleshooting", 'backup-migration'); ?></a> <?php _e("chapter as they might be there.", 'backup-migration'); ?>
  </div>

</div>

<?php include BMI_INCLUDES . '/dashboard/chapter/save-button.php'; ?>
