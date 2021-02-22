<?php

  // Namespace
  namespace BMI\Plugin;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  // Uses
  use BMI\Plugin\Backup_Migration_Plugin as BMP;
  use BMI\Plugin\BMI_Logger as Logger;
  use BMI\Plugin\Checker\BMI_Checker as Checker;
  use BMI\Plugin\Checker\System_Info as SI;
  use BMI\Plugin\CRON\BMI_Crons as Crons;
  use BMI\Plugin\Dashboard as Dashboard;
  use BMI\Plugin\Extracter\BMI_Extracter as Extracter;
  use BMI\Plugin\Progress\BMI_MigrationProgress as MigrationProgress;
  use BMI\Plugin\Progress\BMI_ZipProgress as Progress;
  use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
  use BMI\Plugin\Scanner\BMI_FileScanner as Scanner;
  use BMI\Plugin\Zipper\BMI_Zipper as Zipper;

  /**
   * Ajax Handler for BMI
   */
  class BMI_Ajax {
    public function __construct() {

      // Return if it's not post
      if (empty($_POST)) {
        return;
      }

      // Sanitize User Input
      $this->post = BMP::sanitize($_POST);

      // Log Handler Call (Verbose)
      Logger::debug(__("Running POST Function: ", 'backup-migration') . $this->post['f']);

      // Create backup folder
      if (!file_exists(BMI_BACKUPS)) {
        mkdir(BMI_BACKUPS, 0755, true);
      }

      // Handle User Request If Known And Sanitize Response
      if ($this->post['f'] == 'scan-directory') {
        BMP::res($this->dirSize());
      } elseif ($this->post['f'] == 'create-backup') {
        BMP::res($this->prepareAndMakeBackup());
      } elseif ($this->post['f'] == 'reset-latest') {
        BMP::res($this->resetLatestLogs());
      } elseif ($this->post['f'] == 'get-current-backups') {
        BMP::res($this->getBackupsList());
      } elseif ($this->post['f'] == 'restore-backup') {
        BMP::res($this->restoreBackup());
      } elseif ($this->post['f'] == 'is-running-backup') {
        BMP::res($this->isRunningBackup());
      } elseif ($this->post['f'] == 'stop-backup') {
        BMP::res($this->stopBackup());
      } elseif ($this->post['f'] == 'download-backup') {
        BMP::res($this->handleQuickMigration());
      } elseif ($this->post['f'] == 'migration-locked') {
        BMP::res($this->isMigrationLocked());
      } elseif ($this->post['f'] == 'upload-backup') {
        BMP::res($this->handleChunkUpload());
      } elseif ($this->post['f'] == 'delete-backup') {
        BMP::res($this->removeBackupFile());
      } elseif ($this->post['f'] == 'save-storage') {
        BMP::res($this->saveStorageConfig());
      } elseif ($this->post['f'] == 'save-file-config') {
        BMP::res($this->saveFilesConfig());
      } elseif ($this->post['f'] == 'save-other-options') {
        BMP::res($this->saveOtherOptions());
      } elseif ($this->post['f'] == 'store-config') {
        BMP::res($this->saveStorageTypeConfig());
      } elseif ($this->post['f'] == 'unlock-backup') {
        BMP::res($this->toggleBackupLock(true));
      } elseif ($this->post['f'] == 'lock-backup') {
        BMP::res($this->toggleBackupLock(false));
      } elseif ($this->post['f'] == 'get-dynamic-names') {
        BMP::res($this->getDynamicNames());
      } elseif ($this->post['f'] == 'reset-configuration') {
        BMP::res($this->resetConfiguration());
      } elseif ($this->post['f'] == 'get-site-data') {
        BMP::res($this->getSiteData());
      } elseif ($this->post['f'] == 'send-test-mail') {
        BMP::res($this->sendTestMail());
      } elseif ($this->post['f'] == 'calculate-cron') {
        BMP::res($this->calculateCron());
      } elseif ($this->post['f'] == 'dismiss-error-notice') {
        BMP::res($this->dismissErrorNotice());
      } elseif ($this->post['f'] == 'fix_uname_issues') {
        BMP::res($this->fixUnameFunction());
      } elseif ($this->post['f'] == 'revert_uname_issues') {
        BMP::res($this->revertUnameProcess());
      } elseif ($this->post['f'] == 'htaccess-litespeed') {
        BMP::res($this->fixLitespeed());
      } elseif ($this->post['f'] == 'debugging') {
        BMP::res($this->debugging());
      } else {
        do_action('bmi_premium_ajax', $this->post);
      }
    }

    public function siteURL() {
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      $domainName = $_SERVER['HTTP_HOST'];

      return $protocol . $domainName;
    }

    public function isShellExecEnabled() {

      // This way we can run the backup as system process omitting web server
      $disabled = explode(',', ini_get('disable_functions'));
      if (is_array($disabled) && (in_array('shell_exec', $disabled) || in_array('exec', $disabled) || in_array('system', $disabled))) {
        return false;
      } else {
        if (function_exists('shell_exec') && is_callable('shell_exec')) return true;
        else return false;
      }

    }

    public function checkIfPHPCliExist() {
      if ($this->isShellExecEnabled()) {

        try {

          $return = shell_exec('which php');
          return !empty($return);

        } catch (\Error $e) {

          return false;

        } catch (\Exception $e) {

          return false;

        } catch (\Throwable $e) {

          return false;

        }

      } else return false;
    }

    public function dirSize() {

      // Require File Scanner
      require_once BMI_INCLUDES . '/scanner/files.php';

      // Folder
      $f = $this->post['folder'];

      // Bytes
      $bytes = 0;
      $bm = BMP::fixSlashes(BMI_BACKUPS);

      if ($f == 'plugins') {
        $bytes = Scanner::scanFilesWithIgnore(BMP::fixSlashes(WP_PLUGIN_DIR), ['backup-backup', 'backup-backup-pro'], $bm);
      } elseif ($f == 'uploads') {
        $bytes = Scanner::scanFiles(BMP::fixSlashes(WP_CONTENT_DIR) . DIRECTORY_SEPARATOR . 'uploads', $bm);
      } elseif ($f == 'themes') {
        $bytes = Scanner::scanFiles(BMP::fixSlashes(WP_CONTENT_DIR) . DIRECTORY_SEPARATOR . 'themes', $bm);
      } elseif ($f == 'contents_others') {
        $bytes = Scanner::scanFilesWithIgnore(BMP::fixSlashes(WP_CONTENT_DIR), ['uploads', 'themes', 'plugins'], $bm);
      } elseif ($f == 'wordpress') {
        $bytes = Scanner::scanFilesWithIgnore(BMP::fixSlashes(ABSPATH), [BMP::fixSlashes(WP_CONTENT_DIR)], $bm);
      }

      return [
        'bytes' => $bytes,
        'readable' => BMP::humanSize($bytes)
      ];
    }

    public function backupErrorHandler() {
      set_error_handler(function ($errno, $errstr, $errfile, $errline) {

        if (strpos($errstr, 'deprecated') !== false) return;
        if (strpos($errstr, 'php_uname') !== false) return;
        Logger::error(__("There was an error during backup:", 'backup-migration'));
        Logger::error(__("Message: ", 'backup-migration') . $errstr);
        Logger::error(__("File/line: ", 'backup-migration') . $errfile . '|' . $errline);

        if ($errno != E_ERROR && $errno != E_CORE_ERROR && $errno != E_COMPILE_ERROR && $errno != E_USER_ERROR && $errno != E_RECOVERABLE_ERROR) {
          Logger::error(__('There was an error before request shutdown (but it was not logged to backup log)', 'backup-migration'));
          Logger::error(__('Error message: ', 'backup-migration') . $errstr);
          Logger::error(__('Error file/line: ', 'backup-migration') . $errfile . '|' . $errline);
          return;
        }
        if (strpos($errfile, 'backup-backup') === false) {
          Logger::error(__("Restore process was not aborted because this error is not related to Backup Migration.", 'backup-migration'));
          $this->migration_progress->log(__("There was an error not related to Backup Migration Plugin.", 'backup-migration'), 'warn');
          $this->migration_progress->log(__("Message: ", 'backup-migration') . $errstr, 'warn');
          $this->migration_progress->log(__("Backup will not be aborted because of this.", 'backup-migration'), 'warn');
          return;
        }
        if (strpos($errstr, 'unlink(') !== false) {
          Logger::error(__("Restore process was not aborted due to this error.", 'backup-migration'));
          Logger::error($errstr);
          return;
        }
        if (strpos($errfile, 'pclzip') !== false) {
          Logger::error(__("Restore process was not aborted due to this error.", 'backup-migration'));
          Logger::error($errstr);
          return;
        }
        if (strpos($errstr, 'rename(') !== false) {
          Logger::error(__("Restore process was not aborted due to this error.", 'backup-migration'));
          Logger::error($errstr);
          $this->migration_progress->log(__("Cannot move: ", 'backup-migration') . $errstr, 'warn');
          return;
        }

        $this->zip_progress->log(__("There was an error during backup:", 'backup-migration'), 'error');
        $this->zip_progress->log(__("Message: ", 'backup-migration') . $errstr, 'error');
        $this->zip_progress->log(__("File/line: ", 'backup-migration') . $errfile . '|' . $errline, 'error');
        $this->zip_progress->log(__('Unfortunately we had to remove the backup (if partly created).', 'backup-migration'), 'error');

        $backup = $GLOBALS['bmi_current_backup_name'];
        $backup_path = BMI_BACKUPS . DIRECTORY_SEPARATOR . $backup;
        if (file_exists($backup_path)) @unlink($backup_path);
        if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running');
        if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.abort')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.abort');

        $this->zip_progress->log(__("Aborting backup...", 'backup-migration'), 'step');
        $this->zip_progress->end();

        $GLOBALS['bmi_error_handled'] = true;
        BMP::res(['status' => 'error', 'error' => $errstr]);
        exit;

      }, E_ALL);
    }

    public function migrationErrorHandler() {
      set_exception_handler(function ($exception) {
        $this->migration_progress->log(__("Restore exception: ", 'backup-migration') . $exception->getMessage(), 'warn');
        Logger::log(__("Restore exception: ", 'backup-migration') . $exception->getMessage());
      });
    }

    public function migrationExceptionHandler() {
      set_error_handler(function ($errno, $errstr, $errfile, $errline) {

        if (strpos($errstr, 'deprecated') !== false) return;
        if (strpos($errstr, 'php_uname') !== false) return;
        if ($errno != E_ERROR && $errno != E_CORE_ERROR && $errno != E_COMPILE_ERROR && $errno != E_USER_ERROR && $errno != E_RECOVERABLE_ERROR) {
          Logger::error(__('There was an error before request shutdown (but it was not logged to restore log)', 'backup-migration'));
          Logger::error(__('Error message: ', 'backup-migration') . $errstr);
          Logger::error(__('Error file/line: ', 'backup-migration') . $errfile . '|' . $errline);
          return;
        }

        Logger::error(__("There was an error/warning during restore process:", 'backup-migration'));
        Logger::error(__("Message: ", 'backup-migration') . $errstr);
        Logger::error(__("File/line: ", 'backup-migration') . $errfile . '|' . $errline);
        if (strpos($errfile, 'backup-backup') === false) {
          Logger::error(__("Restore process was not aborted because this error is not related to Backup Migration.", 'backup-migration'));
          $this->migration_progress->log(__("There was an error not related to Backup Migration Plugin.", 'backup-migration'), 'warn');
          $this->migration_progress->log(__("Message: ", 'backup-migration') . $errstr, 'warn');
          $this->migration_progress->log(__("Backup will not be aborted because of this.", 'backup-migration'), 'warn');
          return;
        }
        if (strpos($errstr, 'unlink(') !== false) {
          Logger::error(__("Restore process was not aborted due to this error.", 'backup-migration'));
          Logger::error($errstr);
          return;
        }
        if (strpos($errfile, 'pclzip') !== false) {
          Logger::error(__("Restore process was not aborted due to this error.", 'backup-migration'));
          Logger::error($errstr);
          return;
        }
        if (strpos($errstr, 'rename(') !== false) {
          Logger::error(__("Restore process was not aborted due to this error.", 'backup-migration'));
          Logger::error($errstr);
          $this->migration_progress->log(__("Cannot move: ", 'backup-migration') . $errstr, 'warn');
          return;
        }

        $this->migration_progress->log(__("There was an error during restore process:", 'backup-migration'), 'error');
        $this->migration_progress->log(__("Message: ", 'backup-migration') . $errstr, 'error');
        $this->migration_progress->log(__("File/line: ", 'backup-migration') . $errfile . '|' . $errline, 'error');

        if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock');

        $this->migration_progress->log(__("Aborting restore process...", 'backup-migration'), 'step');
        $this->migration_progress->end();

        $GLOBALS['bmi_error_handled'] = true;
        BMP::res(['status' => 'error', 'error' => $errstr]);
        exit;

      }, E_ALL);
    }

    public function backupExceptionHandler() {
      set_exception_handler(function ($exception) {
        $this->zip_progress->log(__("Exception: ", 'backup-migration') . $exception->getMessage(), 'warn');
        Logger::log(__("Exception: ", 'backup-migration') . $exception->getMessage());
      });
    }

    public function resetLatestLogs() {

      // Restore htaccess
      BMP::fixLitespeed();
      BMP::revertLitespeed();

      // Check time if not bugged
      if (file_exists(BMI_BACKUPS . '/.running') && (time() - filemtime(BMI_BACKUPS . '/.running')) > 65) {
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');
      }

      // Check if backup is not in progress
      if (file_exists(BMI_BACKUPS . '/.running')) {
        return ['status' => 'msg', 'why' => __('Backup process already running, please wait till it complete.', 'backup-migration'), 'level' => 'warning'];
      }

      // Require logs
      require_once BMI_INCLUDES . '/progress/zip.php';

      // Write initial
      $zip_progress = new Progress('', 0);
      $zip_progress->start();
      $zip_progress->log(__("Initializing backup...", 'backup-migration'), 'step');
      $zip_progress->progress('0/100');
      $zip_progress->end();

      // Return done
      return ['status' => 'success'];
    }

    public function makeBackupName() {
      $name = Dashboard\bmi_get_config('BACKUP:NAME');

      $hash = rand(1000, 9999);
      $name = str_replace('%hash', $hash, $name);
      $name = str_replace('%Y', date('Y'), $name);
      $name = str_replace('%M', date('M'), $name);
      $name = str_replace('%D', date('D'), $name);
      $name = str_replace('%d', date('d'), $name);
      $name = str_replace('%j', date('j'), $name);
      $name = str_replace('%m', date('m'), $name);
      $name = str_replace('%n', date('n'), $name);
      $name = str_replace('%Y', date('Y'), $name);
      $name = str_replace('%y', date('y'), $name);
      $name = str_replace('%a', date('a'), $name);
      $name = str_replace('%A', date('A'), $name);
      $name = str_replace('%B', date('B'), $name);
      $name = str_replace('%g', date('g'), $name);
      $name = str_replace('%G', date('G'), $name);
      $name = str_replace('%h', date('h'), $name);
      $name = str_replace('%H', date('H'), $name);
      $name = str_replace('%i', date('i'), $name);
      $name = str_replace('%s', date('s'), $name);
      $name = str_replace('%s', date('s'), $name);

      $i = 2;
      $tmpname = $name;

      while (file_exists($tmpname . '.zip')) {
        $tmpname = $name . '_' . $i;
        $i++;
      }

      $name = $tmpname . '.zip';

      $GLOBALS['bmi_current_backup_name'] = $name;
      return $name;
    }

    public function fixUnameFunction() {
      $file = trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip.php';
      $backup = trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip-backup.php';

      // Make backup
      if (!file_exists($backup)) {
        @copy($file, $backup);
      }

      // Replace deprecated php_uname function which is mostly disabled and cause errors
      $replace = file_get_contents($file);
      $replace = str_replace('php_uname()', '(DIRECTORY_SEPARATOR === "/" ? "linux" : "windows")', $replace);
      file_put_contents($file, $replace);
      return ['status' => 'success'];
    }

    public function revertUnameProcess() {
      $file = trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip.php';
      $backup = trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip-backup.php';
      if (file_exists($backup)) {
        if (file_exists($file)) @unlink($file);
        @copy($backup, $file);
      }
      return ['status' => 'success'];
    }

    public function prepareAndMakeBackup($cron = false) {

      // Require File Scanner
      require_once BMI_INCLUDES . '/progress/zip.php';
      require_once BMI_INCLUDES . '/check/checker.php';

      // Backup name
      $name = $this->makeBackupName();

      // Progress & Logs
      $zip_progress = new Progress($name, 100, 0, $cron);
      $zip_progress->start();

      // Just in case (e.g. syntax error, we can close the file correctly)
      $GLOBALS['bmi_backup_progress'] = &$zip_progress;

      // Logs
      $zip_progress->log(__("Initializing backup...", 'backup-migration'), 'step');
      $zip_progress->log((__("Backup & Migration version: ", 'backup-migration') . BMI_VERSION), 'info');
      // $zip_progress->log(__("Site which will be backed up: ", 'backup-migration') . $this->siteURL(), 'info');
      $zip_progress->log(__("Site which will be backed up: ", 'backup-migration') . site_url(), 'info');
      $zip_progress->log(__("PHP Version: ", 'backup-migration') . PHP_VERSION, 'info');
      if (isset($_SERVER['SERVER_SOFTWARE'])) {
        $zip_progress->log(__("Web server: ", 'backup-migration') . $_SERVER['SERVER_SOFTWARE'], 'info');
      } else {
        $zip_progress->log(__("Web server: Not available", 'backup-migration'), 'info');
      }
      $zip_progress->log(__("Max execution time (in seconds): ", 'backup-migration') . @ini_get('max_execution_time'), 'info');
      $zip_progress->log(__("Checking if backup dir is writable...", 'backup-migration'), 'info');
      $zip_progress->log(__("Initializing custom error handler", 'backup-migration'), 'info');
      if (defined('BMI_BACKUP_PRO')) {
        if (BMI_BACKUP_PRO == 1) {
          $zip_progress->log(__("Premium plugin is enabled and activated", 'backup-migration'), 'info');
        } else {
          $zip_progress->log(__("Premium version is enabled but not active, using free plugin.", 'backup-migration'), 'warn');
        }
      }

      // Error handler
      $this->zip_progress = &$zip_progress;
      $this->backupErrorHandler();
      $this->backupExceptionHandler();

      // Checker
      $checker = new Checker($zip_progress);

      if (!is_writable(dirname(BMI_BACKUPS))) {

        // Abort backup
        $zip_progress->log(__("Backup directory is not writable...", 'backup-migration'), 'error');
        $zip_progress->log(__("Path: ", 'backup-migration') . BMI_BACKUPS, 'error');

        // Close backup
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');

        // Log and close log
        $zip_progress->end();

        // Return error
        return ['status' => 'error'];
      } else {
        $zip_progress->log(__("Yup it is writable...", 'backup-migration'), 'success');
      }

      if (!file_exists(BMI_BACKUPS)) {
        @mkdir(BMI_BACKUPS, true);
      }

      // PHP CLI Check
      if ($this->checkIfPHPCliExist()) {
        $zip_progress->log(__('PHP CLI is available – plugin will try to run server script.', 'backup-migration'), 'info');
        define('BMI_CLI_ENABLED', true);
      } else {
        $zip_progress->log(__('PHP CLI is not available – plugin will use web server proxy.', 'backup-migration'), 'info');
        define('BMI_CLI_ENABLED', false);
      }

      // Get file names (huge list mostly)
      if ($fgwp = Dashboard\bmi_get_config('BACKUP:FILES') == 'true') {
        $zip_progress->log(__("Scanning files...", 'backup-migration'), 'step');
        $files = $this->scanFilesForBackup($zip_progress);
        $files = $this->parseFilesForBackup($files, $zip_progress, $cron);
      } else {
        $zip_progress->log(__("Omitting files (due to settings)...", 'backup-migration'), 'warn');
        $files = [];
      }

      // If only database backup
      if (!isset($this->total_size_for_backup)) $this->total_size_for_backup = 0;
      if (!isset($this->total_size_for_backup_in_mb)) $this->total_size_for_backup_in_mb = 0;

      // Check if there is enough space
      $bytes = intval($this->total_size_for_backup * 1.2);
      $zip_progress->log(__("Checking free space, reserving...", 'backup-migration'), 'step');
      if ($this->total_size_for_backup_in_mb >= BMI_REV * 1000) {

        // Abort backup
        $zip_progress->log(__("Aborting backup...", 'backup-migration'), 'step');
        $zip_progress->log(str_replace('%s', BMI_REV, __("Site weights more than %s GB.", 'backup-migration')), 'error');

        // Close backup
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');

        // Log and close log
        $zip_progress->end();

        // Return error
        return ['status' => 'error', 'bfs' => true];
      }

      if (!$checker->check_free_space($bytes)) {

        // Abort backup
        $zip_progress->log(__("Aborting backup...", 'backup-migration'), 'step');
        $zip_progress->log(__("There is no space for that backup, checked: ", 'backup-migration') . ($bytes) . __(" bytes", 'backup-migration'), 'error');

        // Close backup
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');

        // Log and close log
        $zip_progress->end();

        // Return error
        return ['status' => 'error'];
      } else {
        $zip_progress->log(__("Confirmed, there is more than enough space, checked: ", 'backup-migration') . ($bytes) . __(" bytes", 'backup-migration'), 'success');
        $zip_progress->bytes = $this->total_size_for_backup;
      }

      if (Dashboard\bmi_get_config('BACKUP:DATABASE') != 'true') {
        // Do something if db is not selected
      }

      // Log and set files length
      $zip_progress->log(__("Scanning done - found ", 'backup-migration') . sizeof($files) . __(" files...", 'backup-migration'), 'info');
      $zip_progress->files = sizeof($files);

      // Make Backup
      $zip_progress->log(__("Backup initialized...", 'backup-migration'), 'success');
      $zip_progress->log(__("Initializing archiving system...", 'backup-migration'), 'step');

      return $this->createBackup($files, ABSPATH, $name, $zip_progress, $cron);
    }

    public function fixLitespeed() {
      BMP::fixLitespeed();

      return ['status' => 'success'];
    }

    public function revertLitespeed() {
      BMP::revertLitespeed();

      return ['status' => 'success'];
    }

    public function createBackup($files, $base, $name, &$zip_progress, $cron = false) {

      // Require File Zipper
      require_once BMI_INCLUDES . '/zipper/zipping.php';

      // Backup name
      $backup_path = BMI_BACKUPS . '/' . $name;

      // Check time if not bugged
      if (file_exists(BMI_BACKUPS . '/.running') && (time() - filemtime(BMI_BACKUPS . '/.running')) > 65) {
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');
      }

      // Mark as in progress
      if (!file_exists(BMI_BACKUPS . '/.running')) {
        touch(BMI_BACKUPS . '/.running');
      } else {
        return ['status' => 'msg', 'why' => __('Backup process already running, please wait till it complete.', 'backup-migration'), 'level' => 'warning'];
      }

      // Initialized
      $zip_progress->log(__("Archive system initialized...", 'backup-migration'), 'success');

      // Make ZIP
      $zipper = new Zipper();
      $zippy = $zipper->makeZIP($files, $backup_path, $name, $zip_progress, $cron);
      if (!$zippy) {

        // Make sure it's open
        $zip_progress->start();

        // Abort backup
        $zip_progress->log(__("Aborting backup...", 'backup-migration'), 'step');

        // Close backup
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');

        // Log and close log
        $zip_progress->end();

        // Return error
        if (file_exists($backup_path)) @unlink($backup_path);
        return ['status' => 'error'];
      }

      // Backup aborted
      if (file_exists(BMI_BACKUPS . '/.abort')) {

        // Make sure it's open
        $zip_progress->start();

        if (file_exists($backup_path)) @unlink($backup_path);
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');

        // Log and close log
        $zip_progress->log(__("Backup process aborted.", 'backup-migration'), 'warn');
        $zip_progress->end();

        Logger::log(__("Backup process aborted.", 'backup-migration'));

        return ['status' => 'msg', 'why' => __('Backup process aborted.', 'backup-migration'), 'level' => 'info'];
      }

      if (!file_exists($backup_path)) {

        // Make sure it's open
        $zip_progress->start();

        // Abort backup
        $zip_progress->log(__("Aborting backup...", 'backup-migration'), 'step');
        $zip_progress->log(__("There is no backup file...", 'backup-migration'), 'error');
        $zip_progress->log(__("We could not find backup file when it already should be here.", 'backup-migration'), 'error');
        $zip_progress->log(__("This error may be related to missing space. (filled during backup)", 'backup-migration'), 'error');
        $zip_progress->log(__("Path: ", 'backup-migration') . $backup_path, 'error');

        // Close backup
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');

        // Log and close log
        $zip_progress->end();

        // Return error
        return ['status' => 'error'];
      }

      // End zip log
      $zip_progress->log(__("New backup created and its name is: ", 'backup-migration') . $name, 'success');
      $zip_progress->end();

      // Unlink progress
      if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
      if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');

      // Return
      Logger::log(__("New backup created and its name is: ", 'backup-migration') . $name);

      $GLOBALS['bmi_error_handled'] = true;
      return ['status' => 'success', 'filename' => $name, 'root' => plugin_dir_url(BMI_ROOT_FILE)];
    }

    public function getBackupsList() {

      // Require File Scanner
      require_once BMI_INCLUDES . '/scanner/backups.php';

      // Get backups
      $backups = new Backups();
      $manifests = $backups->getAvailableBackups();

      // Return files
      return ['status' => 'success', 'backups' => $manifests];
    }

    public function sendTestMail() {

      $email = Dashboard\bmi_get_config('OTHER:EMAIL') != false ? Dashboard\bmi_get_config('OTHER:EMAIL') : get_bloginfo('admin_email');
      $subject = __('Backup Migration – Example email', 'backup-migration');
      $message = __('This is a test email sent by the Backup Migration plugin via Troubleshooting options!', 'backup-migration');

      try {

        if (wp_mail($email, $subject, $message)) return [ 'status' => 'success' ];
        else return ['status' => 'error'];

      } catch (\Exception $e) {

        return ['status' => 'error'];

      } catch (\Throwable $e) {

        return ['status' => 'error'];

      }

    }

    public function restoreBackup() {

      // Require File Scanner
      require_once BMI_INCLUDES . '/zipper/zipping.php';
      require_once BMI_INCLUDES . '/extracter/extract.php';
      require_once BMI_INCLUDES . '/progress/migration.php';
      require_once BMI_INCLUDES . '/check/checker.php';

      // Progress & lock file
      $lock = BMI_BACKUPS . '/.migration_lock';
      $progress = BMI_BACKUPS . '/latest_migration_progress.log';

      if (file_exists($lock) && (time() - filemtime($lock)) < 65) {
        return ['status' => 'msg', 'why' => __('Download process is currently running, please wait till it complete.', 'backup-migration'), 'level' => 'warning'];
      }

      // Logs
      $migration = new MigrationProgress($this->post['remote']);
      $migration->start();

      // Just in case (e.g. syntax error, we can close the file correctly)
      $GLOBALS['bmi_migration_progress'] = &$migration;

      // Checker
      $checker = new Checker($migration);
      $zipper = new Zipper();

      // Handle remote
      if ($this->post['file']) {
        $migration->log(__('Restore process responded', 'backup-migration'), 'SUCCESS');
      }

      // Make lock file
      $migration->log(__('Locking migration process', 'backup-migration'), 'SUCCESS');
      touch($lock);

      // Initializing
      $migration->log(__('Initializing restore process', 'backup-migration'), 'STEP');
      $migration->log((__("Backup & Migration version: ", 'backup-migration') . BMI_VERSION), 'info');
      // $migration->log(__("Site which will be restored: ", 'backup-migration') . $this->siteURL(), 'info');
      $migration->log(__("Site which will be restored: ", 'backup-migration') . site_url(), 'info');
      $migration->log(__("PHP Version: ", 'backup-migration') . PHP_VERSION, 'info');
      if (isset($_SERVER['SERVER_SOFTWARE'])) {
        $migration->log(__("Web server: ", 'backup-migration') . $_SERVER['SERVER_SOFTWARE'], 'info');
      } else {
        $migration->log(__("Web server: Not available", 'backup-migration'), 'info');
      }
      $migration->log(__("Max execution time (in seconds): ", 'backup-migration') . @ini_get('max_execution_time'), 'info');
      if (defined('BMI_BACKUP_PRO')) {
        if (BMI_BACKUP_PRO == 1) {
          $migration->log(__("Premium plugin is enabled and activated", 'backup-migration'), 'info');
        } else {
          $migration->log(__("Premium version is enabled but not active, using free plugin.", 'backup-migration'), 'warn');
        }
      }

      // Error handler
      $migration->log(__("Initializing custom error handler", 'backup-migration'), 'info');
      // Error handler
      $this->migration_progress = &$migration;
      $this->migrationErrorHandler();
      $this->migrationExceptionHandler();

      // Check file size
      $zippath = BMP::fixSlashes(BMI_BACKUPS) . DIRECTORY_SEPARATOR . $this->post['file'];
      $manifest = $zipper->getZipFileContent($zippath, 'bmi_backup_manifest.json');
      $migration->log(__('Free space checking...', 'backup-migration'), 'STEP');
      $migration->log(__('Checking if there is enough amount of free space', 'backup-migration'), 'INFO');
      if ($manifest) {
        if (isset($manifest->bytes) && $manifest->bytes) {
          $bytes = intval($manifest->bytes * 1.2);
          if (!$checker->check_free_space($bytes)) {
            $migration->log(__('Cannot start migration process', 'backup-migration'), 'ERROR');
            $migration->log(__('Error: There is not enough space on the server, checked: ' . ($bytes) . ' bytes.', 'backup-migration'), 'ERROR');
            $migration->log(__('Aborting...', 'backup-migration'), 'ERROR');
            $migration->log(__('Unlocking migration', 'backup-migration'), 'INFO');

            if (file_exists($lock)) @unlink($lock);
            $migration->end();

            return ['status' => 'error'];
          } else {
            $migration->log(__('Confirmed, there is enough space on the device, checked: ' . ($bytes) . ' bytes.', 'backup-migration'), 'SUCCESS');
          }
        }
      } else {
        $migration->log(__('Cannot start migration process', 'backup-migration'), 'ERROR');
        $migration->log(__('Error: Could not find manifest in backup, file may be broken', 'backup-migration'), 'ERROR');
        $migration->log(__('Error: Btw. because of this I also cannot check free space', 'backup-migration'), 'ERROR');
        $migration->log(__('Aborting...', 'backup-migration'), 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-migration'), 'INFO');

        if (file_exists($lock)) @unlink($lock);
        $migration->end();

        return ['status' => 'error'];
      }

      // New extracter
      $extracter = new Extracter($this->post['file'], $migration);

      // Extract
      $isFine = $extracter->extractTo();
      if (!$isFine) {
        $migration->log(__('Aborting...', 'backup-migration'), 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-migration'), 'INFO');

        if (file_exists($lock)) @unlink($lock);
        $migration->end();

        return ['status' => 'error'];
      }

      $migration->progress('100');
      $migration->log(__('Restore process completed', 'backup-migration'), 'SUCCESS');
      $migration->log(__('Finalizing restored files', 'backup-migration'), 'STEP');
      $migration->log(__('Unlocking migration', 'backup-migration'), 'INFO');
      if (file_exists($lock)) @unlink($lock);

      $migration->end();

      return ['status' => 'success'];
    }

    public function isRunningBackup() {
      if (file_exists(BMI_BACKUPS . '/.running') && (time() - filemtime(BMI_BACKUPS . '/.running')) <= 65) {
        return ['status' => 'msg', 'why' => __('Backup process already running, please wait till it complete.', 'backup-migration'), 'level' => 'warning'];
      } else {
        return ['status' => 'success'];
      }
    }

    public function stopBackup() {
      if (!file_exists(BMI_BACKUPS . '/.running')) {
        return ['status' => 'msg', 'why' => __('Backup process completed or is not running.', 'backup-migration'), 'level' => 'info'];
      } else {
        if (!file_exists(BMI_BACKUPS . '/.abort')) {
          touch(BMI_BACKUPS . '/.abort');
        }

        return ['status' => 'success'];
      }
    }

    public function isMigrationLocked() {
      $lock = BMI_BACKUPS . '/.migration_lock';
      if (file_exists($lock) && (time() - filemtime($lock)) < 65) {
        return ['status' => 'msg', 'why' => __('Download process is currently running, please wait till it complete.', 'backup-migration'), 'level' => 'warning'];
      } else {
        require_once BMI_INCLUDES . '/progress/migration.php';
        $progress = BMI_BACKUPS . '/latest_migration_progress.log';
        $migration = new MigrationProgress();
        $migration->start();
        $migration->log(__('Initializing restore process...', 'backup-migration'), 'STEP');
        $migration->end();

        file_put_contents($progress, '0');

        return ['status' => 'success'];
      }
    }

    public function downloadFile($url, $dest, $progress, $lock) {
      $current_percentage = 0;
      $fp = fopen($dest, 'w+');

      $progressfile = $progress;
      $lockfile = $lock;

      $ch = curl_init(str_replace(' ', '%20', $url));
      curl_setopt($ch, CURLOPT_TIMEOUT, 0);

      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

      curl_setopt($ch, CURLOPT_NOPROGRESS, false);
      curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $download_size, $downloaded) use (&$current_percentage, &$lockfile, &$progressfile) {
        if ($download_size > 0) {
          $new_percentage = intval(($downloaded / $download_size) * 100);

          if (intval($current_percentage) != intval($new_percentage)) {
            $current_percentage = $new_percentage;
            file_put_contents($progressfile, $current_percentage);
            touch($lockfile);
          }
        }
      });

      curl_exec($ch);
      $this->lastCurlCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      $error_msg = false;
      if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
      }

      curl_close($ch);
      fclose($fp);

      if ($error_msg) {
        return $error_msg;
      } else {
        return false;
      }
    }

    public function handleQuickMigration() {
      $lock = BMI_BACKUPS . '/.migration_lock';
      if (file_exists($lock) && (time() - filemtime($lock)) < 65) {
        return ['status' => 'msg', 'why' => __('Download process is currently running, please wait till it complete.', 'backup-migration'), 'level' => 'warning'];
      }

      require_once BMI_INCLUDES . '/progress/migration.php';
      require_once BMI_INCLUDES . '/zipper/zipping.php';

      $migration = new MigrationProgress(true);
      $migration->start();

      $tmp_name = 'backup_' . time() . '.zip';
      $url = $this->post['url'];
      $dest = BMI_BACKUPS . '/' . $tmp_name;
      $progress = BMI_BACKUPS . '/latest_migration_progress.log';

      $migration->log(__('Creating lock file', 'backup-migration'));
      file_put_contents($lock, '');
      $migration->log(__('Initializing download process', 'backup-migration'), 'STEP');
      $downstart = microtime(true);
      $migration->log(__('Downloading initialized', 'backup-migration'), 'SUCCESS');
      $migration->log(__('Downloading remote file...', 'backup-migration'), 'STEP');
      $fileError = $this->downloadFile($url, $dest, $progress, $lock);
      $migration->log(__('Unlocking migration', 'backup-migration'), 'INFO');
      if (file_exists($lock)) @unlink($lock);

      if ($fileError) {
        $migration->log(__('Removing downloaded file', 'backup-migration'), 'INFO');
        if (file_exists($dest)) @unlink($dest);
        $migration->log(__('Download error', 'backup-migration'), 'ERROR');

        if (strpos($fileError, 'Failed writing body') !== false) {
          $migration->log(__('Error: There is not enough space on the server', 'backup-migration'), 'ERROR');
        } else {
          $migration->log(__('Error', 'backup-migration') . ': ' . $fileError, 'ERROR');
        }

        $migration->end();

        return ['status' => 'error'];
      } else {
        $migration->log(__('Download completed (took: ', 'backup-migration') . (microtime(true) - $downstart) . 's)', 'SUCCESS');
        $migration->log(__('Looking for backup manifest', 'backup-migration'), 'STEP');
        $zipper = new Zipper();
        $content = $zipper->getZipFileContent($dest, 'bmi_backup_manifest.json');
        if ($content) {
          try {
            $i = 1;
            $name = $content->name;
            $prepared_name = $name;
            $migration->log(__('Manifest found remote name: ', 'backup-migration') . $name, 'SUCCESS');

            while (file_exists(BMI_BACKUPS . '/' . $prepared_name)) {
              $prepared_name = substr($name, 0, -4) . '_' . $i . '.zip';
              $i++;
            }

            rename($dest, BMI_BACKUPS . '/' . $prepared_name);
            $migration->log(__('Requesting restore process', 'backup-migration'), 'STEP');

            $migration->end();
            file_put_contents($progress, '0');

            return ['status' => 'success', 'name' => $prepared_name];
          } catch (\Exception $e) {
            $migration->log(__('Error: ', 'backup-migration') . $e, 'ERROR');
            $migration->log(__('Removing downloaded file', 'backup-migration'), 'ERROR');
            if (file_exists($dest)) @unlink($dest);

            $migration->end();

            return ['status' => 'error'];
          } catch (\Throwable $e) {
            $migration->log(__('Error: ', 'backup-migration') . $e, 'ERROR');
            $migration->log(__('Removing downloaded file', 'backup-migration'), 'ERROR');
            if (file_exists($dest)) @unlink($dest);

            $migration->end();

            return ['status' => 'error'];
          }
        } else {
          if ($this->lastCurlCode == '403') {
            $migration->log(__('Backup is not available to download (Error 403).', 'backup-migration'), 'ERROR');
            $migration->log(__('It is restricted by remote server configuration.', 'backup-migration'), 'ERROR');
          } elseif ($this->lastCurlCode == '423') {
            $migration->log(__('Backup is locked on remote site, please unlock remote downloading.', 'backup-migration'), 'ERROR');
            $migration->log(__('You can find the setting in "Where shall the backup(s) be stored?" section.', 'backup-migration'), 'ERROR');
          } elseif ($this->lastCurlCode == '200' || $this->lastCurlCode == '404') {
            $migration->log(__('Backup does not exist under provided URL.', 'backup-migration'), 'ERROR');
            $migration->log(__('Please confirm that you can download the backup file via provided URL.', 'backup-migration'), 'ERROR');
            $migration->log(__('...or the manifest file does not exist in the backup.', 'backup-migration'), 'ERROR');
            $migration->log(__('Missing manifest means that the backup is probably invalid.', 'backup-migration'), 'ERROR');
          } else {
            $migration->log(__('Manifest file does not exist', 'backup-migration'), 'ERROR');
            $migration->log(__('Downloaded backup may be incomplete (missing manifest)', 'backup-migration'), 'ERROR');
            $migration->log(__('...or provided URL is not a direct download of ZIP file.', 'backup-migration'), 'ERROR');
            $migration->log(__('Removing downloaded file', 'backup-migration'), 'ERROR');
          }

          if (file_exists($dest)) @unlink($dest);
          $migration->end();

          return ['status' => 'error'];
        }
      }
    }

    public function handleChunkUpload() {
      require_once BMI_INCLUDES . '/uploader/chunks.php';
    }

    public function removeBackupFile() {
      $files = $this->post['filenames'];

      try {
        if (is_array($files)) {
          for ($i = 0; $i < sizeof($files); $i++) {
            $file = $files[$i];
            $file = preg_replace('/\.\./', '', $file);
            if (file_exists(BMI_BACKUPS . '/' . $file)) {
              unlink(BMI_BACKUPS . '/' . $file);
            }
          }
        }
      } catch (\Exception $e) {
        return ['status' => 'error', 'e' => $e];
      } catch (\Throwable $e) {
        return ['status' => 'error', 'e' => $e];
      }

      return ['status' => 'success'];
    }

    public function saveStorageConfig() {
      $dir_path = $this->post['directory']; // STORAGE::LOCAL::PATH
      $accessible = $this->post['access']; // TORAGE::DIRECT::URL
      $curr_path = Dashboard\bmi_get_config('STORAGE::LOCAL::PATH');

      $error = 0;
      $created = false;

      if (!file_exists($dir_path)) {
        $created = true;
        @mkdir($dir_path, 0755, true);
      }

      if (is_writable($dir_path)) {
        if (!Dashboard\bmi_set_config('STORAGE::DIRECT::URL', $accessible)) {
          $error++;
        }
        if (!Dashboard\bmi_set_config('STORAGE::LOCAL::PATH', $dir_path)) {
          $error++;
        } else {
          $cur_dir = BMP::fixSlashes($curr_path . DIRECTORY_SEPARATOR . 'backups');
          $new_dir = BMP::fixSlashes($dir_path . DIRECTORY_SEPARATOR . 'backups');

          if ($cur_dir != $new_dir) {
            $scanned_directory = array_diff(scandir($cur_dir), ['..', '.']);
            if (!file_exists($new_dir)) {
              @mkdir($new_dir, 0755, true);
            }
            foreach ($scanned_directory as $i => $file) {
              rename($cur_dir . DIRECTORY_SEPARATOR . $file, $new_dir . DIRECTORY_SEPARATOR . $file);
            }

            @rmdir($cur_dir);
            @rmdir(dirname($cur_dir));
          }
        }
      } else {
        if ($created === true) {
          if (file_exists($dir_path)) @unlink($dir_path);
        }

        return ['status' => 'msg', 'why' => __('Entered path is not writable, cannot be used.', 'backup-migration'), 'level' => 'warning'];
      }

      return ['status' => 'success', 'errors' => $error];
    }

    public function saveOtherOptions() {

      // Errors
      $invalid_email = __('Provided email addess is not valid.', 'backup-migration');
      $title_long = __('Your email title is too long, please change the title (max 64 chars).', 'backup-migration');
      $title_short = __('Your email title is too short, please use longer one (at least 3 chars).', 'backup-migration');
      $title_empty = __('Title field is required, please fill it.', 'backup-migration');
      $email_empty = __('Email field cannot be empty, please fill it.', 'backup-migration');

      $email = trim($this->post['email']); // OTHER:EMAIL
      $email_title = trim($this->post['email_title']); // OTHER:EMAIL:TITLE
      $schedule_issues = $this->post['schedule_issues'] === 'true' ? true : false; // OTHER:EMAIL:NOTIS
      $experiment_timeout = $this->post['experiment_timeout'] === 'true' ? true : false; // OTHER:EXPERIMENT:TIMEOUT
      $experiment_timeout_hard = $this->post['experimental_hard_timeout'] === 'true' ? true : false; // OTHER:EXPERIMENT:TIMEOUT:HARD
      $normal_timeout = $this->post['normal_timeout'] === 'true' ? true : false; // OTHER:USE:TIMEOUT:NORMAL
      $uninstall_config = $this->post['uninstall_config'] === 'true' ? true : false; // OTHER:UNINSTALL:CONFIGS
      $uninstall_backups = $this->post['uninstall_backups'] === 'true' ? true : false; // OTHER:UNINSTALL:BACKUPS

      if ($experiment_timeout_hard === true) {
        $experiment_timeout = false;
      }

      if ($normal_timeout === true) {
        $experiment_timeout = false;
        $experiment_timeout_hard = false;
      }

      if (strlen($email) <= 0) {
        return ['status' => 'msg', 'why' => $email_empty, 'level' => 'warning'];
      }
      if (strlen($email_title) <= 0) {
        return ['status' => 'msg', 'why' => $title_empty, 'level' => 'warning'];
      }
      if (strlen($email_title) > 64) {
        return ['status' => 'msg', 'why' => $title_long, 'level' => 'warning'];
      }
      if (strlen($email_title) < 3) {
        return ['status' => 'msg', 'why' => $title_short, 'level' => 'warning'];
      }
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'msg', 'why' => $invalid_email, 'level' => 'warning'];
      }

      $error = 0;
      if (!Dashboard\bmi_set_config('OTHER:EMAIL', $email)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:EMAIL:TITLE', $email_title)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:EMAIL:NOTIS', $schedule_issues)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:EXPERIMENT:TIMEOUT', $experiment_timeout)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:EXPERIMENT:TIMEOUT:HARD', $experiment_timeout_hard)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:USE:TIMEOUT:NORMAL', $normal_timeout)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:UNINSTALL:CONFIGS', $uninstall_config)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:UNINSTALL:BACKUPS', $uninstall_backups)) {
        $error++;
      }

      return ['status' => 'success', 'errors' => $error];
    }

    public function saveStorageTypeConfig() {

      // Errors
      $name_empty = __('Name is required, please fill the input.', 'backup-migration');
      $name_long = __('Your name is too long, please change the name.', 'backup-migration');
      $name_short = __('Your name is too short, please create longer one.', 'backup-migration');
      $name_space = __('Please, do not use spaces in file name.', 'backup-migration');
      $name_forbidden = __('Your name contains character(s) that are not allowed in file names: ', 'backup-migration');

      $forbidden_chars = ['/', '\\', '<', '>', ':', '"', "'", '|', '?', '*', '.', ';', '@', '!', '~', '`', ',', '#', '$', '&', '=', '+'];
      $name = trim($this->post['name']); // BACKUP:NAME

      if (strlen($name) == 0) {
        return ['status' => 'msg', 'why' => $name_empty, 'level' => 'warning'];
      }
      if (strlen($name) > 40) {
        return ['status' => 'msg', 'why' => $name_long, 'level' => 'warning'];
      }
      if (strlen($name) < 3) {
        return ['status' => 'msg', 'why' => $name_short, 'level' => 'warning'];
      }
      if (strpos($name, ' ') !== false) {
        return ['status' => 'msg', 'why' => $name_space, 'level' => 'warning'];
      }

      for ($i = 0; $i < sizeof($forbidden_chars); ++$i) {
        $char = $forbidden_chars[$i];
        if (strpos($name, $char) !== false) {
          return ['status' => 'msg', 'why' => $name_forbidden . $char, 'level' => 'warning'];
        }
      }

      $error = 0;
      if (!Dashboard\bmi_set_config('BACKUP:NAME', $name)) {
        $error++;
      }

      return ['status' => 'success', 'errors' => $error];
    }

    public function saveFilesConfig() {
      $db_group = $this->post['database_group']; // BACKUP:DATABASE
      $files_group = $this->post['files_group']; // BACKUP:FILES

      $fgp = $this->post['files-group-plugins']; // BACKUP:FILES::PLUGINS
      $fgu = $this->post['files-group-uploads']; // BACKUP:FILES::UPLOADS
      $fgt = $this->post['files-group-themes']; // BACKUP:FILES::THEMES
      $fgoc = $this->post['files-group-other-contents']; // BACKUP:FILES::OTHERS
      $fgwp = $this->post['files-group-wp-install']; // BACKUP:FILES::WP

      $file_filters = $this->post['files_by_filters']; // BACKUP:FILES::FILTER
      $ffs = $this->post['ex_b_fs']; // BACKUP:FILES::FILTER:SIZE
      $ffsizemax = $this->post['BFFSIN']; // BACKUP:FILES::FILTER:SIZE:IN
      $ffn = $this->post['ex_b_names']; // BACKUP:FILES::FILTER:NAMES
      $ffp = $this->post['ex_b_fpaths']; // BACKUP:FILES::FILTER:FPATHS
      $ffd = $this->post['ex_b_dpaths']; // BACKUP:FILES::FILTER:DPATHS

      $existant = [];
      $parsed = [];
      $ffnames = $this->post['dynamic-names']; // BACKUP:FILES::FILTER:NAMES:IN
      $ffpnames = array_unique($this->post['dynamic-fpaths-names']); // BACKUP:FILES::FILTER:FPATHS:IN
      $ffdnames = array_unique($this->post['dynamic-dpaths-names']); // BACKUP:FILES::FILTER:DPATHS:IN


      $max = sizeof($ffpnames);
      for ($i = 0; $i < $max; ++$i) {
        if (!is_string($ffpnames[$i]) || trim(strlen($ffpnames[$i])) <= 1) {
          array_splice($ffpnames, $i, 1);
          $i--;
          $max--;
        }
      }

      $max = sizeof($ffdnames);
      for ($i = 0; $i < $max; ++$i) {
        if (!is_string($ffdnames[$i]) || trim(strlen($ffdnames[$i])) <= 1) {
          array_splice($ffdnames, $i, 1);
          $i--;
          $max--;
        }
      }

      for ($i = 0; $i < sizeof($ffnames); ++$i) {
        $row = $ffnames[$i];
        $txt = array_key_exists('txt', $row) ? "" . $row['txt'] . "" : false;
        $pos = array_key_exists('pos', $row) ? $row['pos'] : false;
        $whr = array_key_exists('whr', $row) ? $row['whr'] : false;

        if ($txt === false || $pos === false || $whr === false) {
          continue;
        }
        if (trim(strlen($txt)) <= 0) {
          continue;
        }
        if (!in_array($pos, ["1", "2", "3"])) {
          continue;
        }
        if (!in_array($whr, ["1", "2"])) {
          continue;
        }
        if (in_array($txt . $pos . $whr, $existant)) {
          continue;
        } else {
          $existant[] = $txt . $pos . $whr;
        }

        $parsed[] = ['txt' => $txt, 'pos' => $pos, 'whr' => $whr];
      }

      if ($ffs == 'true' && !is_numeric($ffsizemax)) {
        return ['status' => 'msg', 'why' => __('Entred file size limit, is not correct number.', 'backup-migration'), 'level' => 'warning'];
      }

      $error = 0;
      if (!Dashboard\bmi_set_config('BACKUP:DATABASE', $db_group)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES', $files_group)) {
        $error++;
      }

      if (!Dashboard\bmi_set_config('BACKUP:FILES::PLUGINS', $fgp)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::UPLOADS', $fgu)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::THEMES', $fgt)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::OTHERS', $fgoc)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::WP', $fgwp)) {
        $error++;
      }

      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER', $file_filters)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:SIZE', $ffs)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:NAMES', $ffn)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:FPATHS', $ffp)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:DPATHS', $ffd)) {
        $error++;
      }

      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:SIZE:IN', $ffsizemax)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:NAMES:IN', $parsed)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:FPATHS:IN', $ffpnames)) {
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:DPATHS:IN', $ffdnames)) {
        $error++;
      }

      // return array('status' => 'msg', 'why' => __('Entred path is not writable or does not exist.', 'backup-migration'), 'level' => 'warning');

      return ['status' => 'success', 'errors' => $error];
    }

    public function scanFilesForBackup(&$progress) {
      require_once BMI_INCLUDES . '/scanner/files.php';

      // Use filters?
      $is = Dashboard\bmi_get_config('BACKUP:FILES::FILTER') === 'true' ? true : false;

      // Get settings form config
      $fgp = Dashboard\bmi_get_config('BACKUP:FILES::PLUGINS');
      $fgt = Dashboard\bmi_get_config('BACKUP:FILES::THEMES');
      $fgu = Dashboard\bmi_get_config('BACKUP:FILES::UPLOADS');
      $fgoc = Dashboard\bmi_get_config('BACKUP:FILES::OTHERS');
      $fgwp = Dashboard\bmi_get_config('BACKUP:FILES::WP');
      $dpathsis = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:DPATHS') === 'true' ? true : false;
      $dpaths = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:DPATHS:IN');
      $dynamesis = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:NAMES') === 'true' ? true : false;
      $dynames = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:NAMES:IN');
      $dynparsed = [];

      // Filter dynames to for smaller size
      if ($is && $dynamesis) {
        for ($i = 0; $i < sizeof($dynames); ++$i) {
          $s = $dynames[$i];
          if ($s->whr == '2') {
            $dynparsed[] = ['s' => $s->txt, 'w' => $s->pos, 'z' => strlen($s->txt)];
          }
        }
      }

      // Set exclusion rules
      $ignored_folders_default = [];
      if ($is && $dynamesis) {
        BMP::merge_arrays($ignored_folders_default, $dynparsed);
      }
      $ignored_folders = $ignored_folders_default;
      $ignored_paths_default = [BMI_CONFIG_DIR, BMI_BACKUPS, BMI_ROOT_DIR];
      if (defined('BMI_PRO_ROOT_DIR')) $ignored_paths_default[] = BMI_PRO_ROOT_DIR;
      if ($is && $dpathsis) {
        BMP::merge_arrays($ignored_paths_default, $dpaths);
      }
      $ignored_paths = $ignored_paths_default;

      // Fix slashes for current system
      for ($i = 0; $i < sizeof($ignored_paths); ++$i) {
        $ignored_paths[$i] = BMP::fixSlashes($ignored_paths[$i]);
      }

      // WordPress Paths
      $plugins_path = BMP::fixSlashes(WP_PLUGIN_DIR);
      $themes_path = BMP::fixSlashes(dirname(get_template_directory()));
      $uploads_path = BMP::fixSlashes(wp_upload_dir()['basedir']);
      $wp_contents = BMP::fixSlashes(WP_CONTENT_DIR);
      $wp_install = BMP::fixSlashes(ABSPATH);

      // Getting plugins
      $sfgp = Scanner::equalFolderByPath($wp_install, $plugins_path, $ignored_folders);
      if ($fgp == 'true' && !$sfgp) {
        $plugins_path_files = Scanner::scanFilesGetNamesWithIgnoreFBC($plugins_path, $ignored_folders, $ignored_paths);
      }

      // Getting themes
      $sfgt = Scanner::equalFolderByPath($wp_install, $themes_path, $ignored_folders);
      if ($fgt == 'true' && !$sfgt) {
        $themes_path_files = Scanner::scanFilesGetNamesWithIgnoreFBC($themes_path, $ignored_folders, $ignored_paths);
      }

      // Getting uploads
      $sfgu = Scanner::equalFolderByPath($wp_install, $uploads_path, $ignored_folders);
      if ($fgu == 'true' && !$sfgu) {
        $uploads_path_files = Scanner::scanFilesGetNamesWithIgnoreFBC($uploads_path, $ignored_folders, $ignored_paths);
      }

      // Ignore above paths
      $sfgoc = Scanner::equalFolderByPath($wp_install, $wp_contents, $ignored_folders);
      if ($fgoc == 'true' && !$sfgoc) {

        // Ignore common folders (already scanned)
        $content_folders = [$plugins_path, $themes_path, $uploads_path];
        BMP::merge_arrays($content_folders, $ignored_paths);

        // Getting other contents
        $wp_contents_files = Scanner::scanFilesGetNamesWithIgnoreFBC($wp_contents, $ignored_folders, $content_folders);
      }

      // Ignore contents path
      if ($fgwp == 'true') {

        // Ignore contents file
        $ignored_paths[] = $wp_contents;

        // Getting WP Installation
        $wp_install_files = Scanner::scanFilesGetNamesWithIgnoreFBC($wp_install, $ignored_folders, $ignored_paths);
      }

      // Concat all file paths
      $all_files = [];
      if ($fgp == 'true' && !$sfgp) {
        BMP::merge_arrays($all_files, $plugins_path_files);
        unset($plugins_path_files);
      }

      if ($fgt == 'true' && !$sfgt) {
        BMP::merge_arrays($all_files, $themes_path_files);
        unset($themes_path_files);
      }

      if ($fgu == 'true' && !$sfgu) {
        BMP::merge_arrays($all_files, $uploads_path_files);
        unset($uploads_path_files);
      }

      if ($fgoc == 'true' && !$sfgoc) {
        BMP::merge_arrays($all_files, $wp_contents_files);
        unset($wp_contents_files);
      }

      if ($fgwp == 'true') {
        BMP::merge_arrays($all_files, $wp_install_files);
        unset($wp_install_files);
      }

      return $all_files;
    }

    public function parseFilesForBackup(&$files, &$progress, $cron = false) {
      $is = Dashboard\bmi_get_config('BACKUP:FILES::FILTER') === 'true' ? true : false;
      $acis = (Dashboard\bmi_get_config('BACKUP:FILES::FILTER:FPATHS') === 'true' && $is) ? true : false;
      $ac = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:FPATHS:IN');

      $abis = (Dashboard\bmi_get_config('BACKUP:FILES::FILTER:NAMES') === 'true' && $is) ? true : false;
      $ab = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:NAMES:IN');
      $abres = [];
      $acres = new \stdClass();

      if ($is && $acis) {
        foreach ($ac as $key => $value) {
          $value = BMP::fixSlashes($value);
          $acres->{$value} = 1;
        }
      }

      if ($is && $abis) {
        for ($i = 0; $i < sizeof($ab); ++$i) {
          $s = $ab[$i];
          if ($s->whr == '1') {
            $abres[] = ['s' => $s->txt, 'w' => $s->pos, 'z' => strlen($s->txt)];
          }
        }
      }

      $limitcrl = 60;
      if (BMI_CLI_ENABLED === true) $limitcrl = 512;
      $first_big = false;
      $sizemax = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:SIZE:IN');
      $usesize = (Dashboard\bmi_get_config('BACKUP:FILES::FILTER:SIZE') === 'true' && $is) ? true : false;
      if (!is_numeric($sizemax)) {
        $usesize = false;
        $sizemax = 99999;
      } else {
        intval($sizemax);
      }

      // If legacy === false it will use background process to bypass the timeout
      $legacy = BMI_LEGACY_VERSION;
      if ($legacy && !BMI_LEGACY_HARD_VERSION) $legacy = BMI_LEGACY_HARD_VERSION;
      if (BMI_CLI_ENABLED === true && BMI_FUNCTION_NORMAL === true) $legacy = false;

      $total_size = 0;
      $max = $sizemax * (1024 * 1024);
      $maxfor = sizeof($files);

      // Non-legacy variables
      if ($legacy === false) {
        $Hx = trailingslashit(WP_CONTENT_DIR);
        $Hz = trailingslashit(ABSPATH);
        $Hxs = strlen($Hx);
        $Hzs = strlen($Hz);
      }

      // Sort it by size
      if ($legacy === false) {
        usort($files, function ($a, $b) {
          $a = explode(',', $a);
          $last = sizeof($a) - 1;
          $sizea = intval($a[$last]);

          $b = explode(',', $b);
          $last = sizeof($b) - 1;
          $sizeb = intval($b[$last]);

          if ($sizea == $sizeb) return 0;
          if ($sizea < $sizeb) return -1;
          else return 1;
        });
      }

      // Process due to rules
      for ($i = 0; $i < $maxfor; ++$i) {

        // Remove size from path and get the size
        $files[$i] = explode(',', $files[$i]);
        $last = sizeof($files[$i]) - 1;
        $size = intval($files[$i][$last]);
        unset($files[$i][$last]);
        $files[$i] = implode(',', $files[$i]);

        if ($usesize && Scanner::fileTooLarge($size, $max)) {
          $progress->log(__("Removing file from backup (too large) ", 'backup-migration') . $files[$i] . ' (' . number_format(($size / 1024 / 1024), 2) . ' MB)', 'WARN');
          array_splice($files, $i, 1);
          $maxfor--;
          $i--;

          continue;
        }

        if ($abis && Scanner::equalFolder(basename($files[$i]), $abres)) {
          $progress->log(__("Removing file from backup (due to exclude rules): ", 'backup-migration') . $files[$i], 'WARN');
          array_splice($files, $i, 1);
          $maxfor--;
          $i--;

          continue;
        }

        if ($acis && property_exists($acres, $files[$i])) {
          $progress->log(__("Removing file from backup (due to path rules): ", 'backup-migration') . $files[$i], 'WARN');
          array_splice($files, $i, 1);
          $maxfor--;
          $i--;

          continue;
        }

        if ($size === 0) {
          array_splice($files, $i, 1);
          $maxfor--;
          $i--;

          continue;
        }

        if (strpos($files[$i], 'bmi-pclzip-') !== false) {
          array_splice($files, $i, 1);
          $maxfor--;
          $i--;

          continue;
        }

        if ($size > ($limitcrl * (1024 * 1024))) {
          if ($first_big === false) $first_big = $i;
          $progress->log(__("This file is quite big consider to exclude it, if backup fails: ", 'backup-migration') . $files[$i] . ' (' . BMP::humanSize($size) . ')', 'WARN');
        }

        if ($legacy === false && (BMI_FUNCTION_NORMAL === false || (BMI_FUNCTION_NORMAL === true && BMI_CLI_ENABLED === true))) {
          $fx = strpos($files[$i], $Hx);
          $fz = strpos($files[$i], $Hz);

          if ($fx !== false) $files[$i] = substr_replace($files[$i], '@1@', $fx, $Hxs);
          else if ($fz !== false) $files[$i] = substr_replace($files[$i], '@2@', $fz, $Hzs);

          $files[$i] .= ',' . $size;
        }
        $total_size += $size;
      }

      if ($legacy === false) {
        $list_file = BMI_INCLUDES . '/htaccess/files_latest.list';
        if (file_exists($list_file)) @unlink($list_file);
        $files_list = fopen($list_file, 'a');
        if ($first_big === false) fwrite($files_list, sizeof($files) . "_-1\r\n");
        else fwrite($files_list, sizeof($files) . '_' . $first_big . "\r\n");
        for ($i = 0; $i < sizeof($files); ++$i) {
          fwrite($files_list, $files[$i] . "\r\n");
        }
        fclose($files_list);
        $this->first_big = $first_big;
      }

      $this->total_size_for_backup = $total_size;
      $this->total_size_for_backup_in_mb = ($total_size / 1024 / 1024);

      return $files;
    }

    public function toggleBackupLock($unlock = false) {

      // Require lib
      require_once BMI_INCLUDES . '/zipper/zipping.php';

      // Backup name
      $filename = $this->post['filename'];

      // Init Zipper
      $zipper = new Zipper();

      // Path to Backup
      $path = BMI_BACKUPS . DIRECTORY_SEPARATOR . $filename;
      $path_dir = BMP::fixSlashes(dirname($path));

      // Check if file exists
      if (!file_exists($path)) {
        return ['status' => 'fail'];
      }

      // Check if directory is correct
      if ($path_dir != BMP::fixSlashes(BMI_BACKUPS)) {
        return ['status' => 'fail'];
      }

      // Toggle the lock
      $status = $zipper->lock_zip($path, $unlock);

      // Return the status
      return ['status' => ($status ? 'success' : 'fail')];
    }

    public function getDynamicNames() {
      $data = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:NAMES:IN');
      $fpdata = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:FPATHS:IN');
      $fddata = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:DPATHS:IN');

      for ($i = 0; $i < sizeof($fpdata); ++$i) {
        $fpdata[$i] = BMP::fixSlashes($fpdata[$i]);
      }

      for ($i = 0; $i < sizeof($fddata); ++$i) {
        $fddata[$i] = BMP::fixSlashes($fddata[$i]);
      }

      return [
        'status' => 'success',
        'dynamic-fpaths-names' => $fpdata,
        'dynamic-dpaths-names' => $fddata,
        'data' => $data
      ];
    }

    public function resetConfiguration() {
      if (file_exists(BMI_CONFIG_PATH)) {
        unlink(BMI_CONFIG_PATH);
      }

      return ['status' => 'success'];
    }

    public function getSiteData() {
      require_once BMI_INCLUDES . '/check/system_info.php';
      $bmi = new SI();
      $bmi = $bmi->to_array();

      return ['status' => 'success', 'data' => $bmi];
    }

    public function calculateCron() {
      require_once BMI_INCLUDES . '/cron/handler.php';

      $minutes = [];
      $keeps = [];
      $days = [];
      $weeks = [];
      $hours = [];

      for ($i = 1; $i <= 28; ++$i) {
        $days[] = substr('0' . $i, -2);
      }
      for ($i = 1; $i <= 7; ++$i) {
        $weeks[] = $i . '';
      }
      for ($i = 0; $i <= 23; ++$i) {
        $hours[] = substr('0' . $i, -2);
      }
      for ($i = 0; $i <= 55; $i += 5) {
        $minutes[] = substr('0' . $i, -2);
      }
      for ($i = 1; $i <= 20; ++$i) {
        $keeps[] = $i . '';
      }

      $errors = 0;
      if (in_array($this->post['type'], ['month', 'week', 'day'])) {
        if (!Dashboard\bmi_set_config('CRON:TYPE', $this->post['type'])) {
          $errors++;
        }
      }
      if (in_array($this->post['day'], $days)) {
        if (!Dashboard\bmi_set_config('CRON:DAY', $this->post['day'])) {
          $errors++;
        }
      }
      if (in_array($this->post['week'], $weeks)) {
        if (!Dashboard\bmi_set_config('CRON:WEEK', $this->post['week'])) {
          $errors++;
        }
      }
      if (in_array($this->post['hour'], $hours)) {
        if (!Dashboard\bmi_set_config('CRON:HOUR', $this->post['hour'])) {
          $errors++;
        }
      }
      if (in_array($this->post['minute'], $minutes)) {
        if (!Dashboard\bmi_set_config('CRON:MINUTE', $this->post['minute'])) {
          $errors++;
        }
      }
      if (in_array($this->post['keep'], $keeps)) {
        if (!Dashboard\bmi_set_config('CRON:KEEP', $this->post['keep'])) {
          $errors++;
        }
      }

      if ($this->post['enabled'] === 'true') {
        $this->post['enabled'] = true;
      } else {
        $this->post['enabled'] = false;
      }

      if (!Dashboard\bmi_set_config('CRON:ENABLED', $this->post['enabled'])) {
        $errors++;
      }

      if ($errors === 0) {
        $time = Crons::calculate_date([
          'type' => $this->post['type'],
          'week' => $this->post['week'],
          'day' => $this->post['day'],
          'hour' => $this->post['hour'],
          'minute' => $this->post['minute']
        ], time());

        $file = BMI_INCLUDES . '/htaccess/.plan';
        if (file_exists($file)) {
          $earlier = intval(file_get_contents($file));
        } else {
          $earlier = 0;
        }

        if (!wp_next_scheduled('bmi_do_backup_right_now') || $earlier === 0 || (abs($time - $earlier) >= 15)) {
          wp_clear_scheduled_hook('bmi_do_backup_right_now');
          if ($this->post['enabled'] === true) {
            wp_schedule_single_event($time, 'bmi_do_backup_right_now');
            file_put_contents($file, $time);
          }
        }

        return [
          'status' => 'success',
          'data' => date('Y-m-d H:i:s', $time),
          'currdata' => date('Y-m-d H:i:s')
        ];
      } else {
        return ['status' => 'error'];
      }
    }

    public function dismissErrorNotice() {
      delete_option('bmi_display_email_issues');
    }

    public function debugging() {
    }
  }
