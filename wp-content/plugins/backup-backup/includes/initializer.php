<?php

  // Namespace
  namespace BMI\Plugin;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Require classes
  require_once BMI_INCLUDES . '/logger.php';

  // Alias for classes
  use BMI\Plugin\BMI_Logger as Logger;
  use BMI\Plugin\CRON\BMI_Crons as Crons;
  use BMI\Plugin\Dashboard as Dashboard;
  use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
  use BMI\Plugin\Zipper\BMI_Zipper as Zipper;

  // Uninstallator
  if (!function_exists('bmi_uninstall_handler')) {
    function bmi_uninstall_handler() {
      require_once BMI_ROOT_DIR . '/uninstall.php';
    }
  }

  /**
   * Backup Migration Main Class
   */
  class Backup_Migration_Plugin {
    public function initialize() {

      // Hooks
      register_deactivation_hook(BMI_ROOT_FILE, [&$this, 'deactivation']);
      register_uninstall_hook(BMI_ROOT_FILE, 'bmi_uninstall_handler');

      // File downloading
      add_action('init', [&$this, 'handle_downloading']);

      // Handle CRONs
      add_action('bmi_do_backup_right_now', [&$this, 'handle_cron_backup']);
      add_action('bmi_handle_cron_check', [&$this, 'handle_cron_check']);
      add_action('init', [&$this, 'handle_crons']);

      // Return if CRON time
      if (function_exists('wp_doing_cron') && wp_doing_cron()) return;

      // Check user permissions
      $user = get_userdata(get_current_user_id());
      if (!$user || !$user->roles) return;
      if (!current_user_can('do_backups') && !in_array('administrator', (array) $user->roles)) return;

      // Include our cool banner
      include_once BMI_INCLUDES . '/banner/misc.php';

      // POST Logic
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Register AJAX Handler
        add_action('wp_ajax_backup_migration', [&$this, 'ajax']);

        // Stop GET Registration
        return;

      }

      // Actions
      add_action('admin_init', [&$this, 'admin_init_hook']);
      add_action('admin_menu', [&$this, 'submenu']);
      add_action('admin_notices', [&$this, 'admin_notices']);

      // Settings action
      add_filter('plugin_action_links_' . plugin_basename(BMI_ROOT_FILE), [&$this, 'settings_action']);

      // Ignore below actions if those true
      if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
        return;
      }

      // Styles & scripts
      add_action('admin_head', [&$this, 'enqueue_styles']);
      add_action('admin_footer', [&$this, 'enqueue_scripts']);

    }

    public function ajax() {
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        if (isset($_POST['token']) && $_POST['token'] == 'bmi' && isset($_POST['f']) && is_admin()) {
          try {

            // Extend execution time
            // $exectime = intval(ini_get('max_execution_time'));
            // if ($exectime < 16000 && $exectime != 0) set_time_limit(16000);
            @ignore_user_abort(true);
            @set_time_limit(16000);
            @ini_set('max_execution_time', '259200');
            @ini_set('max_input_time', '259200');
            @ini_set('session.gc_maxlifetime', '1200');

            // May cause issues with auto login
            // if (strlen(session_id()) > 0) session_write_close();

            register_shutdown_function([$this, 'execution_shutdown']);

            // Require AJAX Handler
            require_once BMI_INCLUDES . '/ajax.php';
            $handler = new BMI_Ajax();

          } catch (\Exception $e) {

            Logger::error('POST error:');
            Logger::error($e);
            if ($_POST['f'] == 'create-backup') {
              $progress = &$GLOBALS['bmi_backup_progress'];
              $this->handleErrorDuringBackup($e->getMessage(), $e->getFile(), $e->getLine(), $progress);
            }
              if ($_POST['f'] == 'restore-backup') {
              $progress = &$GLOBALS['bmi_migration_progress'];
              $this->handleErrorDuringRestore($e->getMessage(), $e->getFile(), $e->getLine(), $progress);
            }

            $this->res(['status' => 'error', 'error' => $e]);
            exit;

          } catch (\Throwable $e) {

            Logger::error('POST error:');
            Logger::error($e);
            if ($_POST['f'] == 'create-backup') {
              $progress = &$GLOBALS['bmi_backup_progress'];
              $this->handleErrorDuringBackup($e->getMessage(), $e->getFile(), $e->getLine(), $progress);
            }
              if ($_POST['f'] == 'restore-backup') {
              $progress = &$GLOBALS['bmi_migration_progress'];
              $this->handleErrorDuringRestore($e->getMessage(), $e->getFile(), $e->getLine(), $progress);
            }

            $this->res(['status' => 'error', 'error' => $e]);
            exit;

          }
        }
      }
    }

    public function execution_shutdown() {
      $err = error_get_last();

      if ($err != null) {
        Logger::error(__('Shuted down', 'backup-migration'));
        Logger::error(print_r($err, true));

        $msg = $err['message'];
        $file = $err['file'];
        $line = $err['line'];
        $type = $err['type'];

        if ($type != '1' && ($type != E_ERROR && $type != E_CORE_ERROR && $type != E_COMPILE_ERROR && $type != E_USER_ERROR && $type != E_RECOVERABLE_ERROR)) {
          Logger::error(__('There was an error before request shutdown (but it was not logged to backup/restore log)', 'backup-migration'));
          Logger::error(__('Error message: ', 'backup-migration') . $msg);
          Logger::error(__('Error file/line: ', 'backup-migration') . $file . '|' . $line);
          return;
        }

        if ($GLOBALS['bmi_error_handled']) return;
        if ($_POST['f'] == 'create-backup') {
          Logger::error(__('There was an error during backup', 'backup-migration'));
          Logger::error(__('Error message: ', 'backup-migration') . $msg);
          Logger::error(__('Error file/line: ', 'backup-migration') . $file . '|' . $line);
          $progress = &$GLOBALS['bmi_backup_progress'];
          $progress->log(__('Error message: ', 'backup-migration') . $msg, 'error');
          $progress->log(__('You can get more pieces of information in troubleshooting log file.', 'backup-migration'), 'error');
          $this->handleErrorDuringBackup($msg, $file, $line, $progress);

          $fullPath = BMI_ROOT_DIR . '/tmp' . '/';
          array_map('unlink', glob($fullPath . '*.tmp'));
          array_map('unlink', glob($fullPath . '*.gz'));
        }

        if ($_POST['f'] == 'restore-backup') {
          Logger::error(__('There was an error during restore process', 'backup-migration'));
          Logger::error(__('Error message: ', 'backup-migration') . $msg);
          Logger::error(__('Error file/line: ', 'backup-migration') . $file . '|' . $line);
          $progress = &$GLOBALS['bmi_migration_progress'];
          $progress->log(__('Error message: ', 'backup-migration') . $msg, 'error');
          $progress->log(__('You can get more pieces of information in troubleshooting log file.', 'backup-migration'), 'error');
          $this->handleErrorDuringRestore($msg, $file, $line, $progress);
        }

        $this->res(['status' => 'error', 'error' => $err]);
        exit;
      }
    }

    public function handleErrorDuringBackup($msg, $file, $line, &$progress) {
      $backup = $GLOBALS['bmi_current_backup_name'];

      Logger::log('Due to fatal error backup handled correctly (closed and removed).');
      $progress->log(__('Something bad happened on PHP side.', 'backup-migration'), 'error');
      $progress->log(__('Unfortunately we had to remove the backup (if partly created).', 'backup-migration'), 'error');
      $progress->log(__('Error message: ', 'backup-migration') . $msg, 'error');
      $progress->log(__('Error file/line: ', 'backup-migration') . $file . '|' . $line, 'error');
      if (strpos($msg, 'execution time') !== false) {
        $progress->log(__('Probably we could not increase the execution time, please edit your php.ini manually', 'backup-migration'), 'error');
      }

      $backup_path = BMI_BACKUPS . DIRECTORY_SEPARATOR . $backup;
      if (file_exists($backup_path)) @unlink($backup_path);
      if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running');
      if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.abort')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.abort');

      $progress->log(__("Aborting backup...", 'backup-migration'), 'step');
      $progress->end();
    }

    public function handleErrorDuringRestore($msg, $file, $line, &$progress) {
      Logger::log('There was fatal error during restore.');
      $progress->log(__('Something bad happened on PHP side.', 'backup-migration'), 'error');
      $progress->log(__('Error message: ', 'backup-migration') . $msg, 'error');
      $progress->log(__('Error file/line: ', 'backup-migration') . $file . '|' . $line, 'error');
      if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock');
      $progress->log(__("Aborting & unlocking restore process...", 'backup-migration'), 'step');
      $progress->end();

      $lock = BMI_BACKUPS . '/.migration_lock';
      if (file_exists($lock)) @unlink($lock);
    }

    public function submenu() {

      // Menu icon
      $icon_url = $this->get_asset('images', 'logo-min.png');

      // Main menu slug
      $parentSlug = 'backup-migration';

      // Content
      $content = [$this, 'settings_page'];

      // Main menu hook
      add_menu_page('Backup Migration', '<span id="bmi-menu">Backup Migration</span>', 'read', $parentSlug, $content, $icon_url, $position = 98);

      // Remove default submenu by menu
      remove_submenu_page($parentSlug, $parentSlug);

    }

    public function settings_action($links) {
      $text = __('Manage', 'backup-migration');
      $links['bmi-settings-link'] = '<a href="' . admin_url('/admin.php?page=backup-migration') . '">' . $text . '</a>';

      return $links;
    }

    public function settings_page() {

      // Set email if does not exist
      if (!Dashboard\bmi_get_config('OTHER:EMAIL')) {
        Dashboard\bmi_set_config('OTHER:EMAIL', get_bloginfo('admin_email'));
      }

      // Require The HTML
      require_once BMI_INCLUDES . '/dashboard/settings.php';
    }

    public function admin_init_hook() {
      if (get_option('_bmi_redirect', false)) {
        $this->fixLitespeed();
        delete_option('_bmi_redirect');
        wp_safe_redirect(admin_url('admin.php?page=backup-migration'));
      }
    }

    public function admin_notices() {
      if (get_current_screen()->id != 'toplevel_page_backup-migration' && get_option('bmi_display_email_issues', false)) {
        ?>
        <div class="notice notice-warning">
          <p>
            <?php _e('There was an error during automated backup, please', 'backup-migration'); ?>
            <?php echo '<a href="' . admin_url('/admin.php?page=backup-migration') . '">' . __('check that.', 'backup-migration') . '</a>'; ?>
          </p>
        </div>
        <?php
      }
    }

    public function handle_crons() {
      $time = get_option('bmi_backup_check', 0);
      if ((time() - $time) > 60) {
        update_option('bmi_backup_check', time());

        if (Dashboard\bmi_get_config('CRON:ENABLED') !== true) {
          return;
        }
        do_action('bmi_handle_cron_check');
      }
    }

    public function email_error($msg) {
      Logger::log('Displaying some issues about email sending...');
      update_option('bmi_display_email_issues', $msg);
    }

    public function backup_inproper_time($should_time) {
      Logger::log('Sending notification about backup being late');
      $email = Dashboard\bmi_get_config('OTHER:EMAIL') != false ? Dashboard\bmi_get_config('OTHER:EMAIL') : get_bloginfo('admin_email');
      $subject = Dashboard\bmi_get_config('OTHER:EMAIL:TITLE');
      $message = __("Automatic backup was not on time because there was no traffic on the site.", 'backup-migration') . "\n";
      $message .= __("Backup was made on: ", 'backup-migration') . date('Y-m-d H:i:s') . __(', but should be on: ', 'backup-migration') . date('Y-m-d H:i:s', $should_time);
      $message .= ' ' . __("(server time)", 'backup-migration');

      Logger::debug($message);
      if (!$this->send_notification_mail($email, $subject, $message)) {
        $issue = __("Couldn't send mail to you, please check server configuration.", 'backup-migration') . '<br>';
        $issue .= '<b>' . __("Message you missed because of this: ", 'backup-migration') . '</b>' . $message;
        $this->email_error($issue);
      }
    }

    public function handle_cron_check() {
      $now = time();
      if (file_exists(BMI_INCLUDES . '/htaccess/.last')) {
        $last = @file_get_contents(BMI_INCLUDES . '/htaccess/.last');
        $last_status = explode('.', $last)[0];
        $last_time = intval(explode('.', $last)[1]);
      } else {
        $last_time = 0;
        $last_status = 0;
      }

      $plan = intval(@file_get_contents(BMI_INCLUDES . '/htaccess/.plan'));
      if ($last_time < $plan && ((time() - $plan) > 55)) {
        if ($last_status !== '0') {
          $this->backup_inproper_time($plan);
          if (!wp_next_scheduled('bmi_do_backup_right_now')) {
            wp_schedule_single_event(time(), 'bmi_do_backup_right_now');
          }
        }
      }
    }

    public function get_next_cron($curr = false) {
      if ($curr === false) {
        $curr = time();
      }

      $time = Crons::calculate_date([
        'type' => Dashboard\bmi_get_config('CRON:TYPE'),
        'week' => Dashboard\bmi_get_config('CRON:WEEK'),
        'day' => Dashboard\bmi_get_config('CRON:DAY'),
        'hour' => Dashboard\bmi_get_config('CRON:HOUR'),
        'minute' => Dashboard\bmi_get_config('CRON:MINUTE')
      ], $curr);

      return $time;
    }

    public function handle_cron_error($e) {
      Logger::error(__("Automatic backup failed at time: ", 'backup-migration') . date('Y-m-d, H:i:s'));
      if (is_object($e) || is_array($e)) {
        Logger::error('Error: ' . $e->getMessage());
      } else {
        Logger::error('Error: ' . $e);
      }

      $notis = Dashboard\bmi_get_config('OTHER:EMAIL:NOTIS');
      if (in_array($notis, [true, 'true'])) {
        $email = Dashboard\bmi_get_config('OTHER:EMAIL') != false ? Dashboard\bmi_get_config('OTHER:EMAIL') : get_bloginfo('admin_email');
        $subject = Dashboard\bmi_get_config('OTHER:EMAIL:TITLE');
        $message = __("There was an error during automatic backup, please check the logs.", 'backup-migration');
        if (is_string($e)) {
          $message .= "\nError: " . $e;
        }

        $this->send_notification_mail($email, $subject, $message);
      }

      @unlink(BMI_BACKUPS . '/.cron');
    }

    public function send_notification_mail($email, $subject, $message) {
      $email_fail = __("Could not send the email notification about that fail", 'backup-migration');

      try {
        if (wp_mail($email, $subject, $message)) {
          Logger::info(__("Sent email notification to: ", 'backup-migration') . $email);

          return true;
        } else {
          Logger::error($email_fail);
          $this->email_error(__("Couldn't send notification via email, please check the email and your server settings.", 'backup-migration'));

          return false;
        }
      } catch (\Exception $e) {
        Logger::error($email_fail);
        $this->email_error(__("Couldn't send notification via email due to error, please check plugin logs for more details.", 'backup-migration'));

        return false;
      } catch (\Throwable $e) {
        Logger::error($email_fail);
        $this->email_error(__("Couldn't send notification via email due to error, please check plugin logs for more details.", 'backup-migration'));

        return false;
      }
    }

    public function handle_after_cron() {
      require_once BMI_INCLUDES . '/scanner/backups.php';
      $backups = new Backups();
      $list = $backups->getAvailableBackups();

      $cron_list = [];
      $cron_dates = [];
      foreach ($list as $key => $value) {
        if ($list[$key][6] == true) {
          if ($list[$key][5] == 'unlocked') {
            $cron_list[$list[$key][1]] = $list[$key][0];
            $cron_dates[] = $list[$key][1];
          }
        }
      }

      usort($cron_dates, function ($a, $b) {
        return (strtotime($a) < strtotime($b)) ? -1 : 1;
      });

      $cron_dates = array_slice($cron_dates, 0, -(intval(Dashboard\bmi_get_config('CRON:KEEP'))));
      foreach ($cron_dates as $key => $value) {
        $name = $cron_list[$cron_dates[$key]];
        Logger::log(__("Removing backup due to keep rules: ", 'backup-migration') . $name);
        @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . $name);
      }
    }

    public function set_last_cron($status, $time) {
      $file = BMI_INCLUDES . '/htaccess/.last';
      file_put_contents($file, $status . '.' . $time);
    }

    public function handle_cron_backup() {

      // Planned time
      $plan = intval(@file_get_contents(BMI_INCLUDES . '/htaccess/.plan'));

      // Check difference
      if ((time() - $plan) > 45) {
        Logger::log('Backup failed to run on proper time, but running now.');
        Logger::log('Planned time: ' . date('Y-m-d H:i:s', $plan));
        $this->backup_inproper_time($plan);
      }

      // Now
      $now = time();
      $this->set_last_cron('0', $now);

      // Extend execution time
      @ignore_user_abort(true);
      @set_time_limit(16000);
      @ini_set('max_execution_time', '259200');
      @ini_set('max_input_time', '259200');
      @ini_set('session.gc_maxlifetime', '1200');
      if (strlen(session_id()) > 0) session_write_close();

      if (Dashboard\bmi_get_config('CRON:ENABLED') !== true) return;
      Logger::log(__("Automatic backup called at time: ", 'backup-migration') . date('Y-m-d, H:i:s'));

      try {
        require_once BMI_INCLUDES . '/ajax.php';
        $isBackup = (file_exists(BMI_BACKUPS . '/.running') && (time() - filemtime(BMI_BACKUPS . '/.running')) <= 65) ? true : false;
        $isCron = (file_exists(BMI_BACKUPS . '/.cron') && (time() - filemtime(BMI_BACKUPS . '/.cron')) <= 65) ? true : false;
        if ($isCron) {
          return;
        }

        if ($isBackup) {
          $this->handle_cron_error(__("Could not make the backup: Backup already running, please wait till it complete.", 'backup-migration'));
          $this->set_last_cron('2', $now);
        } else {
          touch(BMI_BACKUPS . '/.cron');

          $handler = new BMI_Ajax();
          $handler->resetLatestLogs();
          $backup = $handler->prepareAndMakeBackup(true);

          if ($backup['status'] == 'success') {
            Logger::log(__("Automatic backup successed: ", 'backup-migration') . $backup['filename']);
            $this->handle_after_cron();
            $this->set_last_cron('1', $now);
          } elseif ($backup['status'] == 'msg') {
            $this->handle_cron_error($backup['why']);
            $this->set_last_cron('3', $now);
          } else {
            $this->handle_cron_error(__("Could not make the backup due to internal server error.", 'backup-migration'));
            $this->set_last_cron('4', $now);
          }
        }
      } catch (\Exception $e) {
        $this->handle_cron_error($e);
        $this->set_last_cron('5', $now);
      } catch (\Throwable $e) {
        $this->handle_cron_error($e);
        $this->set_last_cron('5', $now);
      }

      @unlink(BMI_BACKUPS . '/.cron');
      require_once BMI_INCLUDES . '/cron/handler.php';
      $time = $this->get_next_cron();

      wp_clear_scheduled_hook('bmi_do_backup_right_now');
      wp_schedule_single_event($time, 'bmi_do_backup_right_now');

      $file = BMI_INCLUDES . '/htaccess/.plan';
      file_put_contents($file, $time);
    }

    public function enqueue_scripts() {

      // Global
      if (in_array(get_current_screen()->id, ['toplevel_page_backup-migration', 'plugins'])) { ?>
      <script type="text/javascript">
        let stars = '<?php echo plugin_dir_url(BMI_ROOT_FILE); ?>' + 'admin/images/stars.gif';
        let css_star = "background:url('" + stars + "')";
        jQuery('[data-slug="backup-migration-pro"]').find('strong').html('<span>Backup Migration <b style="color: orange; ' + css_star + '">Pro</b></span>');
        jQuery('[data-slug="backup-backup-pro"]').find('strong').html('<span>Backup Migration <b style="color: orange; ' + css_star + '">Pro</b></span>');
      </script>
      <?php }

      // Only for BM Settings
      if (get_current_screen()->id != 'toplevel_page_backup-migration') {
        return;
      }
      wp_enqueue_script('backup-migration-script', $this->get_asset('js', 'backup-migration.min.js'), ['jquery'], BMI_VERSION, true);
    }

    public function enqueue_styles() {

      // Global styles
      wp_enqueue_style('backup-migration-style-icon', $this->get_asset('css', 'bmi-plugin-icon.min.css'), [], BMI_VERSION);

      // Only for BM Settings
      if (get_current_screen()->id != 'toplevel_page_backup-migration') return;

      // Enqueue the style
      wp_enqueue_style('backup-migration-style', $this->get_asset('css', 'bmi-plugin.min.css'), [], BMI_VERSION);

    }

    public function handle_downloading() {
      @error_reporting(0);
      $allowed = ['BMI_BACKUP', 'BMI_BACKUP_LOGS', 'PROGRESS_LOGS'];
      $get_bmi = !empty($_GET['backup-migration']) ? sanitize_text_field($_GET['backup-migration']) : false;
      $get_bid = !empty($_GET['backup-id']) ? sanitize_text_field($_GET['backup-id']) : false;
      $get_pid = !empty($_GET['progress-id']) ? sanitize_text_field($_GET['progress-id']) : false;

      if (isset($get_bmi) && in_array($get_bmi, $allowed)) {
        if (isset($get_bid) && strlen($get_bid) > 0) {
          $type = $get_bmi;
          if ($type == 'BMI_BACKUP') {
            if (Dashboard\bmi_get_config('STORAGE::DIRECT::URL') === 'true' || current_user_can('administrator')) {
              $backupname = $get_bid;
              $file = $this->fixSlashes(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupname);

              // Prevent parent directory downloading
              if (file_exists($file) && $this->fixSlashes(dirname($file)) == $this->fixSlashes(BMI_BACKUPS)) {
                ob_clean();

                @ignore_user_abort(true);
                @set_time_limit(16000);
                @ini_set('max_execution_time', '259200');
                @ini_set('max_input_time', '259200');
                @ini_set('session.gc_maxlifetime', '1200');
                if (strlen(session_id()) > 0) session_write_close();

                if (@ini_get('zlib.output_compression')) @ini_set('zlib.output_compression', 'Off');
                $fp = @fopen($file, 'rb');

                // header('X-Sendfile: ' . $file);
                // header('X-Sendfile-Type: X-Accel-Redirect');
                // header('X-Accel-Redirect: ' . $file);
                // header('X-Accel-Buffering: yes');
                header('Expires: 0');
                header('Pragma: public');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Content-Disposition: attachment; filename="' . $backupname . '"');
                header('Content-Type: application/octet-stream');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . filesize($file));
                header('Content-Description: File Transfer');
                http_response_code(200);

                if (ob_get_level()) ob_end_clean();

                fpassthru($fp);
                fclose($fp);
                exit;
              }
            } else {
              ob_clean();
              header('HTTP/1.0 423 Locked');
              if (ob_get_level()) ob_end_clean();
              echo __("Backup download is restricted (allowed for admins only).", 'backup-migration');
              exit;
            }
          } elseif ($type == 'BMI_BACKUP_LOGS') {
            ob_clean();
            $backupname = $get_bid;
            $file = $this->fixSlashes(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupname);

            // Prevent parent directory downloading
            if (file_exists($file) && $this->fixSlashes(dirname($file)) == $this->fixSlashes(BMI_BACKUPS)) {
              require_once BMI_INCLUDES . '/zipper/zipping.php';

              $zipper = new Zipper();
              $logs = $zipper->getZipFileContentPlain($file, 'bmi_logs_this_backup.log');
              header('Content-Type: text/plain');

              if ($logs) {
                header('Content-Disposition: attachment; filename="' . substr($backupname, 0, -4) . '.log"');
                http_response_code(200);
                if (ob_get_level()) ob_end_clean();
                echo $logs;
                exit;
              } else {
                if (ob_get_level()) ob_end_clean();
                header('HTTP/1.0 404 Not found');
                echo __("There was an error during getting logs, this file is not right log file.", 'backup-migration');
                exit;
              }
            }
          } elseif ($type == 'PROGRESS_LOGS') {
            $allowed_progress = ['latest_full.log', 'latest.log', 'latest_progress.log', 'latest_migration_progress.log', 'latest_migration.log', 'complete_logs.log', 'latest_migration_full.log'];
            if (isset($get_pid) && in_array($get_pid, $allowed_progress)) {
              header('Content-Type: text/plain');
              header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
              http_response_code(200);
              ob_clean();
              if ($get_pid == 'complete_logs.log') {
                $file = BMI_CONFIG_DIR . DIRECTORY_SEPARATOR . 'complete_logs.log';
                if (ob_get_level()) ob_end_clean();
                readfile($file);
                exit;
              } else if ($get_pid == 'latest_full.log') {
                $progress = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest_progress.log';
                $logs = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest.log';
                if ((file_exists($progress) && (time() - filemtime($progress)) < (60 * 5)) || current_user_can('administrator')) {
                  if (ob_get_level()) ob_end_clean();
                  readfile($progress);
                  echo "\n";
                  readfile($logs);
                  exit;
                } else {
                  if (file_exists($progress) && !(time() - filemtime($progress)) < (60 * 5)) {
                    if (ob_get_level()) ob_end_clean();
                    echo __("Due to security reasons access to this file is disabled at this moment.", 'backup-migration') . "\n";
                    echo __("Human readable: file expired.", 'backup-migration');
                    exit;
                  } else {
                    if (ob_get_level()) ob_end_clean();
                    echo '';
                    exit;
                  }
                }
              } else if ($get_pid == 'latest_migration_full.log') {
                $progress = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest_migration_progress.log';
                $logs = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest_migration.log';
                if ((file_exists($progress) && (time() - filemtime($progress)) < (60 * 5)) || current_user_can('administrator')) {
                  if (ob_get_level()) ob_end_clean();
                  readfile($progress);
                  echo "\n";
                  readfile($logs);
                  exit;
                } else {
                  if (file_exists($progress) && !(time() - filemtime($progress)) < (60 * 5)) {
                    if (ob_get_level()) ob_end_clean();
                    echo __("Due to security reasons access to this file is disabled at this moment.", 'backup-migration') . "\n";
                    echo __("Human readable: file expired.", 'backup-migration');
                    exit;
                  } else {
                    if (ob_get_level()) ob_end_clean();
                    echo '';
                    exit;
                  }
                }
              } else {
                $file = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $get_pid;
                if ((file_exists($file) && (time() - filemtime($file)) < (60 * 5)) || current_user_can('administrator')) {
                  if (ob_get_level()) ob_end_clean();
                  readfile($file);
                  echo "\n";
                  if ($get_pid == 'latest.log') $file = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest_progress.log';
                  if ($get_pid == 'latest_migration.log') $file = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest_migration_progress.log';
                  echo __("[DOWNLOAD GENERATED] File downloaded on (server time): ", 'backup-migration') . date('Y-m-d H:i:s') . "\n";
                  echo __("[DOWNLOAD GENERATED] Last update (seconds): ", 'backup-migration') . (time() - filemtime($file)) . __(" seconds ago ", 'backup-migration') . "\n";
                  echo __("[DOWNLOAD GENERATED] Last update (date): ", 'backup-migration') . date('Y-m-d H:i:s', filemtime($file)) . " \n";
                  exit;
                } else {
                  if (file_exists($file) && !(time() - filemtime($file)) < (60 * 5)) {
                    if (ob_get_level()) ob_end_clean();
                    echo __("Due to security reasons access to this file is disabled at this moment.", 'backup-migration') . "\n";
                    echo __("Human readable: file expired.", 'backup-migration');
                    exit;
                  } else {
                    if (ob_get_level()) ob_end_clean();
                    echo '';
                    exit;
                  }
                }
              }
              exit;
            }
          }
        }
      }
    }

    public function deactivation() {
      Logger::log(__("Plugin has been deactivated", 'backup-migration'));
      $this->revertLitespeed();
    }

    public static function res($array) {
      echo json_encode(Backup_Migration_Plugin::sanitize($array));
    }

    public static function sanitize($data = []) {
      $array = [];

      if (is_array($data) || is_object($data)) {
        foreach ($data as $key => $value) {
          $key = ((is_numeric($key))?intval($key):sanitize_text_field($key));

          if (is_array($value) || is_object($value)) {
            $array[$key] = Backup_Migration_Plugin::sanitize($value);
          } else {
            $array[$key] = sanitize_text_field($value);
          }
        }
      } elseif (is_string($data)) {
        return sanitize_text_field($data);
      } elseif (is_bool($data)) {
        return $data;
      } elseif (is_null($data)) {
        return 'false';
      } else {
        Logger::log(__("Unknow AJAX Sanitize Type: ", 'backup-migration') . gettype($data));
        wp_die();
      }

      return $array;
    }

    public static function fixLitespeed() {
      $litepath = BMI_INCLUDES . DIRECTORY_SEPARATOR . 'htaccess' . DIRECTORY_SEPARATOR . '.litespeed';
      $htpath = ABSPATH . DIRECTORY_SEPARATOR . '.htaccess';
      if (!is_writable($htpath)) return ['status' => 'success'];
      if (file_exists($htpath)) {
        Backup_Migration_Plugin::revertLitespeed();
        $litespeed = @file_get_contents($litepath);
        $htaccess = @file_get_contents($htpath);
        $htaccess = explode("\n", $htaccess);
        $litespeed = explode("\n", $litespeed);

        $hasAlready = false;
        for ($i = 0; $i < sizeof($htaccess); ++$i) {
          if (strpos($htaccess[$i], 'Backup Migration') !== false) {
            $hasAlready = true;

            break;
          }
        }

        if ($hasAlready) {
          return ['status' => 'success'];
        }
        $htaccess[] = '';
        for ($i = 0; $i < sizeof($litespeed); ++$i) {
          $htaccess[] = $litespeed[$i];
        }

        file_put_contents($htpath, implode("\n", $htaccess));
      } else {
        copy($litepath, $htpath);
      }

      return ['status' => 'success'];
    }

    public static function revertLitespeed() {
      $htpath = ABSPATH . DIRECTORY_SEPARATOR . '.htaccess';
      $addline = true;

      if (!is_writable($htpath)) return ['status' => 'success'];
      $htaccess = @file_get_contents($htpath);
      $htaccess = explode("\n", $htaccess);
      $htFilter = [];

      for ($i = 0; $i < sizeof($htaccess); ++$i) {
        if (strpos($htaccess[$i], 'Backup Migration START')) {
          $addline = false;

          continue;
        } elseif (strpos($htaccess[$i], 'Backup Migration END')) {
          $addline = true;

          continue;
        } else {
          if ($addline == true) {
            $htFilter[] = $htaccess[$i];
          }
        }
      }

      file_put_contents($htpath, trim(implode("\n", $htFilter)));

      return ['status' => 'success'];
    }

    public static function humanSize($bytes) {
      $label = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
      for ($i = 0; $bytes >= 1024 && $i < (count($label) - 1); $bytes /= 1024, $i++);

      return (round($bytes, 2) . " " . $label[$i]);
    }

    public static function fixSlashes($str) {
      $str = str_replace('\\\\', DIRECTORY_SEPARATOR, $str);
      $str = str_replace('\\', DIRECTORY_SEPARATOR, $str);
      $str = str_replace('\/', DIRECTORY_SEPARATOR, $str);
      $str = str_replace('/', DIRECTORY_SEPARATOR, $str);

      if ($str[strlen($str) - 1] == DIRECTORY_SEPARATOR) {
        $str = substr($str, 0, -1);
      }

      return $str;
    }

    public static function merge_arrays(&$array1, &$array2) {
      for ($i = 0; $i < sizeof($array2); ++$i) {
        $array1[] = $array2[$i];
      }
    }

    private function get_asset($base = '', $asset = '') {
      return BMI_ASSETS . '/' . $base . '/' . $asset;
    }
  }
