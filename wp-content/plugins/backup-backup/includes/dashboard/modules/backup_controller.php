<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  // ThMaker
  function __th($n) {

    $ths = [__("st", 'backup-migration'), __("nd", 'backup-migration'), __("rd", 'backup-migration'), __("th", 'backup-migration')];
    $n = intval($n);
    $nah = [11, 12, 13];

    if (in_array($n, $nah)) return $ths[3];
    else {

      if (substr(''.$n, -1) == '1') return $ths[0];
      elseif (substr(''.$n, -1) == '2') return $ths[1];
      elseif (substr(''.$n, -1) == '3') return $ths[2];
      else return $ths[3];

    }

  }

?>

<div class="backup-creator cf section-bmi" id="bmi-section--cron">
  <div class="left">
    <div class="create-now pointer bmi-backup-btn one shadow" id="i-backup-creator">
      <div class="insider"></div>
      <div class="insider-2"></div>
      <div class="vcenter">
        <img src="<?php echo $this->get_asset('images', 'backup-min.svg'); ?>" alt="server-img" height="50px" class="img-now">
        <div class="text">
          <span class="medium pointer">
            <?php _e('Create backup', 'backup-migration') ?> <span class="bold"><?php _e('now!', 'backup-migration') ?></span>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="left cron-backups-wrap" id="i-backup-cron">
    <div class="cron-backups shadow relative<?php echo ((bmi_get_config('CRON:ENABLED') === false) ? ' disabled' : '') ?>">
      <div class="turned-off pointer transition"<?php echo ((bmi_get_config('CRON:ENABLED') === true) ? ' style="display: none"' : '') ?>>
        <div class="vcenter">
          <div class="fullwidth">
            <div class="cf inline lh28">
              <div class="left">
                <img src="<?php echo $this->get_asset('images', 'timemachine.svg'); ?>" alt="cron-icon" height="30px;">&nbsp;&nbsp;
              </div>
              <div class="left">
                <span class="f20 regular">
                  <?php _e('... or have', 'backup-migration') ?>
                  <span class="semibold"><?php _e('backups created automatically', 'backup-migration') ?></span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="turned-on f18"<?php echo ((bmi_get_config('CRON:ENABLED') === false) ? ' style="display: none"' : '') ?>>

        <div class="cron-a cf">
          <div class="left semibold relative">
            <?php _e("Automatic backups creation", 'backup-migration'); ?>&nbsp;
            <span class="bmi-info-icon tooltip cron-time-server" tooltip=""></span>
          </div>
          <div class="right">

            <label for="cron-btn-toggle" class="bmi-switch">
              <input type="checkbox" id="cron-btn-toggle"<?php echo ((bmi_get_config('CRON:ENABLED') === false) ? ' checked' : '') ?>>
              <div class="bmi-switch-slider round">
                <span class="on"><?php _e("On", 'backup-migration'); ?></span>
                <span class="off"><?php _e("Off", 'backup-migration'); ?></span>
              </div>
            </label>

          </div>
        </div>

        <div class="cron-bc">
          <div class="cron-b bg-second cf">
            <table class="ooo-to-pad">
              <tbody>
                <tr>
                  <td class="orr" style="min-width: 76px;"><?php _e("Create a backup every", 'backup-migration'); ?></td>
                  <td>
                    <select id="cron-period" data-parent="#bmi-section--cron" data-classes="orr" data-def="<?php echo sanitize_text_field(bmi_get_config('CRON:TYPE')); ?>">
                      <option value="month"><?php _e("Month", 'backup-migration'); ?></option>
                      <option value="week"><?php _e("Week", 'backup-migration'); ?></option>
                      <option value="day"><?php _e("Day", 'backup-migration'); ?></option>
                    </select>
                  </td>
                  <td id="cron-on-word"><?php _e("on", 'backup-migration'); ?></td>
                  <td class="cron-the"><?php _e("the", 'backup-migration'); ?></td>
                  <td>
                    <select id="cron-day" data-parent="#bmi-section--cron" data-classes="left orr oll" data-def="<?php echo sanitize_text_field(bmi_get_config('CRON:DAY')); ?>">
                      <?php for ($i = 0; $i < 28; ++$i): ?>
                        <?php $d = ($i+1); ?>
                        <option value="<?php echo $d; ?>"><?php echo $d . __th($d); ?></option>
                      <?php endfor; ?>
                    </select>
                  </td>
                  <td>
                    <select id="cron-week" data-parent="#bmi-section--cron" data-classes="orr oll" data-hide="true" data-def="<?php echo sanitize_text_field(bmi_get_config('CRON:WEEK')); ?>">
                      <option value="1"><?php _e("Monday", 'backup-migration'); ?></option>
                      <option value="2"><?php _e("Tuesday", 'backup-migration'); ?></option>
                      <option value="3"><?php _e("Wednesday", 'backup-migration'); ?></option>
                      <option value="4"><?php _e("Thursday", 'backup-migration'); ?></option>
                      <option value="5"><?php _e("Friday", 'backup-migration'); ?></option>
                      <option value="6"><?php _e("Saturday", 'backup-migration'); ?></option>
                      <option value="7"><?php _e("Sunday", 'backup-migration'); ?></option>
                    </select>
                  </td>
                  <td class="orr"><?php _e("at", 'backup-migration'); ?></td>
                  <td>
                    <select id="cron-hour" data-parent="#bmi-section--cron" data-def="<?php echo sanitize_text_field(bmi_get_config('CRON:HOUR')); ?>">
                      <?php for ($i = 0; $i < 24; ++$i): ?>
                        <?php $d = substr('0' . ($i), -2); ?>
                        <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                      <?php endfor; ?>
                    </select>
                  </td>
                  <td class="orr oll"><?php _e("hours and", 'backup-migration'); ?></td>
                  <td>
                    <select id="cron-minute" data-parent="#bmi-section--cron" data-def="<?php echo sanitize_text_field(bmi_get_config('CRON:MINUTE')); ?>">
                      <?php for ($i = 0; $i < 60; $i += 5): ?>
                        <?php $d = substr('0' . ($i), -2); ?>
                        <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                      <?php endfor; ?>
                    </select>
                  </td>
                  <td class="orr"><?php _e("minutes", 'backup-migration'); ?></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="cron-c cf">
            <table class="left ooo-to-pad" style="max-width: calc(100% - 110px);">
              <tbody>
                <tr>
                  <td class="orr oll">
                    <?php _e("...and keep the last", 'backup-migration'); ?>
                  </td>
                  <td>
                    <select id="cron-keep-backups" data-parent="#bmi-section--cron" data-def="<?php echo sanitize_text_field(bmi_get_config('CRON:KEEP')); ?>">
                      <?php for ($i = 0; $i < 20; ++$i): ?>
                        <option value="<?php echo ($i+1); ?>"><?php echo ($i+1); ?></option>
                      <?php endfor; ?>
                    </select>
                  </td>
                  <td class="orr oll">
                    <?php _e("backups that have been created automatically.", 'backup-migration'); ?>
                  </td>
                </tr>
              </tbody>
            </table>
            <div class="lrn-mr-btn hoverable secondary right">
              <?php _e('Learn more', 'backup-migration'); ?>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<div class="mbl learn_more_about_cron" style="display: none;">
  <ol style="list-style: outside;">
    <li>
      <?php _e("Above times are", 'backup-migration'); ?>
      <b><?php _e("server times", 'backup-migration'); ?></b>
      (<?php _e("time now:", 'backup-migration'); ?> <span id="server-time-auto" data-time="<?php echo time(); ?>"></span>)
    </li>
    <li>
      <?php _e("There needs to be", 'backup-migration'); ?>
      <b><?php _e("at least one visitor", 'backup-migration'); ?></b>
      <?php _e("so that the backup process gets triggered", 'backup-migration'); ?>
    </li>
    <li>
      <?php _e("We suggest to", 'backup-migration'); ?>
      <b><?php _e("only keep 2 or 3 backups", 'backup-migration'); ?></b>
      <?php _e("otherwise you may run out of space.", 'backup-migration'); ?>
    </li>
    <li>
      <b><?php _e("Locked backups will <u>not</u> be deleted", 'backup-migration'); ?></b>
      <?php _e("automatically. Those are indicated by a lock sign", 'backup-migration'); ?>
      <img src="<?php echo $this->get_asset('images', 'lock-min.svg'); ?>" alt="lock" class="inline" height="18px">.
      <?php _e('Manually created backups (i.e. those after click on "Create backup now!") are permanently locked, while automatically created backups are by default unlocked.', 'backup-migration'); ?>
      <?php _e("You can change their lock status on the", 'backup-migration'); ?>
      <span class="secondary hoverable go-to-marbs"><?php _e("Manage & Restore Backup(s)", 'backup-migration'); ?></span>
      <?php _e("tab", 'backup-migration'); ?>.
    </li>
    <li>
      <?php _e("For", 'backup-migration'); ?>
      <b><?php _e("other triggers", 'backup-migration'); ?></b>
      <?php _e("when your backups are created, please go", 'backup-migration'); ?>
      <span class="secondary hoverable collapser-openner" data-el="#other-options"><?php _e("here", 'backup-migration'); ?></span>.
    </li>
  </ol>
  <div class="right-align hoverable secondary closer-learn-more">
    <?php _e("Collapse", 'backup-migration'); ?>
  </div>
</div>
