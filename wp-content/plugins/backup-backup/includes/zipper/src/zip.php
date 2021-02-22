<?php

namespace BMI\Plugin\Zipper;

use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\Dashboard as Dashboard;
use BMI\Plugin\Database\BMI_Database as Database;
use BMI\Plugin\Progress\BMI_ZipProgress as Progress;
use BMI\Plugin\Heart\BMI_Backup_Heart as Bypasser;

class Zip {
  protected $lib;
  protected $org_files;
  protected $new_file_path;
  protected $new_file_name;
  protected $backupname;
  protected $zip_progress;

  protected $extr_file;
  protected $extr_dirc;
  protected $start_zip;

  public function __construct() {
    $this->lib = 0;
    $this->extr_file = 0;
    $this->new_file_path = 0;
    $this->org_files = [];
  }

  public function zip_start($file_path, $files = [], $name = '', &$zip_progress = null, $start = null) {

    // save the new file path
    $this->new_file_path = $file_path;
    $this->backupname = $name;
    $this->zip_progress = $zip_progress;
    $this->start_zip = $start;

    if (sizeof($files) > 0) {
      $this->org_files = $files;
    }

    // Some php installations doesn't have the ZipArchive
    // So in this case we'll use another lib called PclZip
    if (class_exists("ZipArchive")) {
      $this->lib = 1;
    } else {
      $this->lib = 2;
    }

    return true;

  }

  public function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = substr($val, 0, -1);

    switch ($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
            // no break
        case 'm':
            $val *= 1024;
            // no break
        case 'k':
            $val *= 1024;
    }

    return $val;
  }

  public function zip_failed($error) {
    Logger::error(__("There was an error during backup (packing)...", 'backup-migration'));
    Logger::error($error);

    if ($this->zip_progress != null) {
      $this->zip_progress->log(__("Issues during backup (packing)...", 'backup-migration'), 'ERROR');
      $this->zip_progress->log($error, 'ERROR');
    }
  }

  public function restore_failed($error) {
    Logger::error(__("There was an error during restore process (extracting)...", 'backup-migration'));
    Logger::error($error);

    if ($this->zip_progress != null) {
      $this->zip_progress->log(__("Issues during restore process (extracting)...", 'backup-migration'), 'ERROR');
      $this->zip_progress->log($error, 'ERROR');
    }
  }

  public function zip_add($in) {

    // Just to make sure.. if the user haven't called the earlier method
    if ($this->lib === 0 || $this->new_file_path === 0) {
      throw new \Exception("PHP-ZIP: must call zip_start before zip_add");
    }

    // Push file
    array_push($this->org_files, $in);

    // Return
    return true;
  }

  public function zip_end($force_lib = false, $cron = false) {

    // Try to set limit
    $this->zip_progress->log(__("Smart memory calculation...", 'backup-migration'), 'STEP');
    if ((intval($this->return_bytes(ini_get('memory_limit'))) / 1024 / 1024) < 384) @ini_set('memory_limit', '384M');
    if (defined('WP_MAX_MEMORY_LIMIT')) $maxwp = WP_MAX_MEMORY_LIMIT;
    else $maxwp = '1M';

    $memory_limit = (intval($this->return_bytes(ini_get('memory_limit'))) / 1024 / 1024);
    $maxwp = (intval($this->return_bytes($maxwp)) / 1024 / 1024);

    if ($maxwp > $memory_limit) $memory_limit = $maxwp;
    $this->zip_progress->log(str_replace('%s', $memory_limit, __("There is %s MBs of memory to use", 'backup-migration')), 'INFO');
    $this->zip_progress->log(str_replace('%s', $maxwp, __("WordPress memory limit: %s MBs", 'backup-migration')), 'INFO');
    $safe_limit = intval($memory_limit / 4);
    if ($safe_limit > 64) $safe_limit = 64;
    if ($memory_limit === 384) $safe_limit = 96;
    if ($memory_limit >= 512) $safe_limit = 128;
    if ($memory_limit >= 1024) $safe_limit = 256;

    // $real_memory = intval(memory_get_usage() * 0.9 / 1024 / 1024);
    // if ($real_memory < $safe_limit) $safe_limit = $real_memory;
    $safe_limit = intval($safe_limit * 0.9);

    $this->zip_progress->log(str_replace('%s', $safe_limit, __("Setting the safe limit to %s MB", 'backup-migration')), 'SUCCESS');

    $abs = BMP::fixSlashes(ABSPATH) . DIRECTORY_SEPARATOR;

    $dbbackupname = 'bmi_database_backup.sql';
    $database_file = BMP::fixSlashes(BMI_INCLUDES . DIRECTORY_SEPARATOR . 'htaccess' . DIRECTORY_SEPARATOR . $dbbackupname);
    $database_file_dir = BMP::fixSlashes((dirname($database_file))) . DIRECTORY_SEPARATOR;

    if (Dashboard\bmi_get_config('BACKUP:DATABASE') == 'true') {

      // Require Database Manager
      require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'manager.php';

      // Get database dump
      $this->zip_progress->log(__("Making database backup", 'backup-migration'), 'STEP');
      $this->zip_progress->log(__("Iterating database...", 'backup-migration'), 'INFO');
      $databaser = new Database(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
      $databaser->exportDatabase($dbbackupname);
      $this->zip_progress->log(__("Database size: ", 'backup-migration') . BMP::humanSize(filesize($database_file)), 'INFO');
      $this->zip_progress->log(__("Database backup finished", 'backup-migration'), 'SUCCESS');
    } else {
      $this->zip_progress->log(__("Omitting database backup (due to settings)...", 'backup-migration'), 'WARN');
      $database_file = false;
    }

    // force usage of specific lib (for testing purposes)
    if ($force_lib === 2) {
      $this->lib = 2;
    } elseif ($force_lib === 1) {
      $this->lib = 1;
    }

    // just to make sure.. if the user haven't called the earlier method
    if ($this->lib === 0 || $this->new_file_path === 0) {
      throw new \Exception('PHP-ZIP: zip_start and zip_add haven\'t been called yet');
    }

    // All files
    $max = sizeof($this->org_files);
    $this->zip_progress->log(__("Making archive", 'backup-migration'), 'STEP');
    $this->zip_progress->log(__("Compressing...", 'backup-migration'), 'INFO');

    // using zipArchive class
    if ($this->lib === 1) {

      // Verbose
      $this->zip_progress->log(__("Using Zlib to create Backup", 'backup-migration'));

      $lib = new \ZipArchive();
      if (!$lib->open($this->new_file_path, \ZipArchive::CREATE)) {
        throw new \Exception('PHP-ZIP: Permission Denied or zlib can\'t be found');
      }

      // Add each file
      for ($i = 0; $i < $max; $i++) {
        $file = $this->org_files[$i];
        $zippath = substr($file, strlen($abs));
        $lib->addFile($file, 'wordpress' . DIRECTORY_SEPARATOR . $zippath);

        if ($i % 100 === 0) {
          if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.abort')) {
            break;
          }
          $this->zip_progress->progress($i + 1 . '/' . $max);
        }

        if (($i + 1) % 500 === 0 || $i == 0) {
          if (($i + 1) < $max) {
            $this->zip_progress->log((__("Milestone: ", 'backup-migration') . ($i + 1) . '/' . $max), 'info');
          }
        }
      }

      if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.abort')) {

        // close the archive
        $lib->close();
      } else {
        $this->zip_progress->log((__("Milestone: ", 'backup-migration') . $max . '/' . $max), 'info');
        $this->zip_progress->log(__("Compressed ", 'backup-migration') . $max . __(" files", 'backup-migration'), 'SUCCESS');

        // Log time of ZIP Process
        $this->zip_progress->log(__("Archiving of ", 'backup-migration') . $max . __(" files took: ", 'backup-migration') . (microtime(true) - $this->start_zip) . 's');

        $this->zip_progress->log(__("Finalizing backup", 'backup-migration'), 'STEP');
        $this->zip_progress->log(__("Adding manifest...", 'backup-migration'), 'INFO');
        $this->zip_progress->log(__("Closing files and archives", 'backup-migration'), 'STEP');

        $this->zip_progress->end();
        $logs = file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . 'latest.log');
        $this->zip_progress->start(true);

        if ($database_file !== false) {
          $lib->addFile($database_file, 'bmi_database_backup.sql');
        }

        $lib->addFromString('bmi_backup_manifest.json', $this->zip_progress->createManifest());
        $lib->addFromString('bmi_logs_this_backup.log', $logs);
        $this->zip_progress->progress($max . '/' . $max);

        // close the archive
        $lib->close();
      }
    }

    // using PclZip
    if ($this->lib === 2) {

      // Verbose
      $legacy = BMI_LEGACY_VERSION;
      if ($legacy) $legacy = BMI_LEGACY_HARD_VERSION;
      $this->zip_progress->log(__("Using PclZip module to create the backup", 'backup-migration'), 'INFO');
      if (!BMI_LEGACY_VERSION) {
        $this->zip_progress->log(__("Legacy setting: Using server-sided script and cURL based loop for better capabilities", 'backup-migration'), 'INFO');
      } elseif (!BMI_LEGACY_HARD_VERSION) {
        $this->zip_progress->log(__("Legacy setting: Using user browser as middleware for full capabilities", 'backup-migration'), 'INFO');
      } else {
        $this->zip_progress->log(__("Legacy setting: Using default modules depending on user server", 'backup-migration'), 'INFO');
      }

      // Run the backup in background
      if (($legacy === false || BMI_CLI_ENABLED === true) && sizeof($this->org_files) > 10) {
        file_put_contents($database_file_dir . 'bmi_backup_manifest.json', $this->zip_progress->createManifest());
        $url = plugins_url(null) . '/backup-backup/includes/backup-heart.php';
        $identy = 'BMI-' . rand(10000000, 999999999);
        $remote_settings = [
          'identy' => $identy,
          'manifest' => $database_file_dir . 'bmi_backup_manifest.json',
          'backupname' => $this->backupname,
          'safelimit' => $safe_limit,
          'rev' => BMI_REV,
          'total_files' => sizeof($this->org_files),
          'filessofar' => 0,
          'start' => microtime(true),
          'config_dir' => BMI_CONFIG_DIR,
          'content_dir' => trailingslashit(WP_CONTENT_DIR),
          'backup_dir' => BMI_BACKUPS,
          'abs_dir' => trailingslashit(ABSPATH),
          'root_dir' => plugin_dir_path(BMI_ROOT_FILE),
          'browser' => false,
          'url' => $url
        ];

        $fix = true;
        $Xfiles = glob(BMI_INCLUDES . '/htaccess' . '/.BMI-*');
        foreach ($Xfiles as $xfile) if (is_file($xfile)) unlink($xfile);
        touch(BMI_INCLUDES . '/htaccess' . '/.' . $identy);

        if (BMI_CLI_ENABLED === true && BMI_FUNCTION_NORMAL === true) {
          file_put_contents($database_file_dir . 'bmi_cli_data.json', json_encode($remote_settings));
          $this->zip_progress->log(__("Running PHP CLI process - it should be confirmed with next messages", 'backup-migration'), 'STEP');
          $fix = false;

          // ignore_user_abort(true);
          // ob_start();
          // session_write_close();
          // header('Content-Length: ' . ob_get_length());
          // header('Connection: close');
          // ob_end_flush();
          // flush();
          // ob_start();

          $output = @shell_exec('php -f ' . realpath(BMI_INCLUDES . '/backup-cli.php'));

          if ($output === '010011010101' || $output === '010011010111') {
            $this->zip_progress->log(__('CLI Failed, trying to save the backup using alternative approaches.', 'backup-migration'), 'WARN');
            $fix = true;
          }
        }

        if ($fix === true) {
          if (BMI_LEGACY_HARD_VERSION === false && $cron === false) {
            $remote_settings['browser'] = true;
            $this->zip_progress->log(__("Sending backup settings and identy to the browser", 'backup-migration'), 'INFO');
            BMP::res(['status' => 'background_hard', 'filename' => $this->backupname, 'settings' => $remote_settings, 'url' => $url]);
            exit;
          } else {
            $this->zip_progress->log(__('Starting background process on server-side...', 'backup-migration'), 'INFO');
            require_once BMI_INCLUDES . '/bypasser.php';
            $request = new Bypasser(false, BMI_CONFIG_DIR, trailingslashit(WP_CONTENT_DIR), BMI_BACKUPS, trailingslashit(ABSPATH), plugin_dir_path(BMI_ROOT_FILE), $url, $remote_settings);
            $request->send_beat(true, $this->zip_progress);
          }
        }

        sleep(2);
        if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running')) {
          if (file_exists(BMI_INCLUDES . '/htaccess' . '/.' . $identy . '-running')) {
            // $this->zip_progress->log(__('Request received correctly – backup is running.', 'backup-migration'), 'SUCCESS');
            BMP::res(['status' => 'background', 'filename' => $this->backupname]);
            exit;
          } else {
            $this->zip_progress->log(__('Could not find any response from the server, trying again in 3 seconds.', 'backup-migration'), 'WARN');
            sleep(3);
            if (file_exists(BMI_INCLUDES . '/htaccess' . '/.' . $identy . '-running')) {
              // $this->zip_progress->log(__('Request received correctly – backup is running.', 'backup-migration'), 'SUCCESS');
              BMP::res(['status' => 'background', 'filename' => $this->backupname]);
              exit;
            } else {
              $this->zip_progress->log(__('Still nothing backup probably is not running.', 'backup-migration'), 'WARN');
              if (file_exists(BMI_INCLUDES . '/htaccess' . '/.' . $identy . '-running')) @unlink(BMI_INCLUDES . '/htaccess' . '/.' . $identy . '-running');
              if (file_exists(BMI_INCLUDES . '/htaccess' . '/.' . $identy)) @unlink(BMI_INCLUDES . '/htaccess' . '/.' . $identy);
              throw new \Exception('Backup could not run on your server, please check global logs.');
            }
          }
        } else {
          BMP::res(['status' => 'background', 'filename' => $this->backupname]);
          exit;
        }

        // ob_end_clean();
        exit;
      } else {
        $this->zip_progress->log(__("Backup will run as single-request...", 'backup-migration'), 'INFO');
      }

      // require the lib
      if (!class_exists('PclZip')) {
        if (!defined('PCLZIP_TEMPORARY_DIR')) {
          $bmi_tmp_dir = BMI_ROOT_DIR . '/tmp';
          if (!file_exists($bmi_tmp_dir)) {
            @mkdir($bmi_tmp_dir, 0775, true);
          }
          define('PCLZIP_TEMPORARY_DIR', $bmi_tmp_dir . '/bmi-');
        }
        if (defined('BMI_PRO_PCLZIP') && file_exists(BMI_PRO_PCLZIP)) {
          $this->zip_progress->log(__('Using dedicated PclZIP for Premium Users.', 'backup-migration'), 'INFO');
          require_once BMI_PRO_PCLZIP;
        } else {
          require_once trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip.php';
        }
      }
      $common = $this->org_files;

      if (!$lib = new \PclZip($this->new_file_path)) {
        throw new \Exception('PHP-ZIP: Permission Denied or zlib can\'t be found');
      }

      try {
        $splitby = 200; $milestoneby = 500;
        $filestotal = sizeof($this->org_files);
        if ($filestotal < 3000) { $splitby = 250; $milestoneby = 500; }
        if ($filestotal > 5000) { $splitby = 500; $milestoneby = 500; }
        if ($filestotal > 10000) { $splitby = 1000; $milestoneby = 1000; }
        if ($filestotal > 15000) { $splitby = 2000; $milestoneby = 2000; }
        if ($filestotal > 20000) { $splitby = 4000; $milestoneby = 4000; }
        if ($filestotal > 25000) { $splitby = 6000; $milestoneby = 6000; }
        if ($filestotal > 30000) { $splitby = 8000; $milestoneby = 8000; }
        if ($filestotal > 32000) { $splitby = 10000; $milestoneby = 10000; }

        $this->zip_progress->log(__("Chunks contain ", 'backup-migration') . $splitby . __(" files.", 'backup-migration'));

        $chunks = array_chunk($this->org_files, $splitby);
        $chunkslen = sizeof($chunks);
        if ($chunkslen > 0) {
          $sizeoflast = sizeof($chunks[$chunkslen - 1]);
          if ($chunkslen > 1 && $sizeoflast == 1) {
            $buffer = array_slice($chunks[$chunkslen - 2], -1);
            $chunks[$chunkslen - 2] = array_slice($chunks[$chunkslen - 2], 0, -1);
            $chunks[$chunkslen - 1][] = $buffer[0];
          }
        }

        for ($i = 0; $i < $chunkslen; ++$i) {

          // Abort if user wants it (check every 100 files)
          if (file_exists(BMI_BACKUPS . '/.abort')) {
            break;
          }

          $chunk = $chunks[$i];
          $back = $lib->add($chunk, PCLZIP_OPT_REMOVE_PATH, $abs, PCLZIP_OPT_ADD_PATH, 'wordpress' . DIRECTORY_SEPARATOR/*, PCLZIP_OPT_ADD_TEMP_FILE_ON*/, PCLZIP_OPT_TEMP_FILE_THRESHOLD, $safe_limit);
          if ($back == 0) {
            $this->zip_failed($lib->errorInfo(true));
            return false;
          }

          $curfile = (($i * $splitby) + $splitby);
          $this->zip_progress->progress($curfile . '/' . $max);
          if ($curfile % $milestoneby === 0 && $curfile < $max) {
            $this->zip_progress->log(__("Milestone: ", 'backup-migration') . ($curfile . '/' . $max), 'info');
          }
        }
      } catch (\Exception $e) {
        $this->zip_failed($e->getMessage());

        return false;
      } catch (\Throwable $e) {
        $this->zip_failed($e->getMessage());

        return false;
      }

      if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.abort')) {

        if (file_exists($database_file_dir . 'bmi_backup_manifest.json')) {
          @unlink($database_file_dir . 'bmi_backup_manifest.json');
        }
        if (file_exists($database_file_dir . 'bmi_logs_this_backup.log')) {
          @unlink($database_file_dir . 'bmi_logs_this_backup.log');
        }

      } else {

        // End
        $this->zip_progress->log(__("Milestone: ", 'backup-migration') . ($max . '/' . $max), 'info');
        $this->zip_progress->log(__("Compressed ", 'backup-migration') . $max . __(" files", 'backup-migration'), 'SUCCESS');

        // Log time of ZIP Process
        $this->zip_progress->log(__("Archiving of ", 'backup-migration') . $max . __(" files took: ", 'backup-migration') . (microtime(true) - $this->start_zip) . 's');

        $this->zip_progress->log(__("Finalizing backup", 'backup-migration'), 'STEP');
        $this->zip_progress->log(__("Adding manifest...", 'backup-migration'), 'INFO');
        $this->zip_progress->log(__("Closing files and archives", 'backup-migration'), 'STEP');

        $this->zip_progress->end();

        file_put_contents($database_file_dir . 'bmi_backup_manifest.json', $this->zip_progress->createManifest());
        file_put_contents($database_file_dir . 'bmi_logs_this_backup.log', file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . 'latest.log'));

        $this->zip_progress->start(true);

        $files = [$database_file_dir . 'bmi_backup_manifest.json', $database_file_dir . 'bmi_logs_this_backup.log'];
        if ($database_file !== false) {
          $files[] = $database_file;
        }
        $lib->add($files, PCLZIP_OPT_REMOVE_PATH, $database_file_dir);

        if (file_exists($database_file_dir . 'bmi_backup_manifest.json')) {
          @unlink($database_file_dir . 'bmi_backup_manifest.json');
        }
        if (file_exists($database_file_dir . 'bmi_logs_this_backup.log')) {
          @unlink($database_file_dir . 'bmi_logs_this_backup.log');
        }

        $this->zip_progress->progress($max . '/' . $max);

      }
    }

    if (file_exists($database_file)) @unlink($database_file);
    if (!file_exists($this->new_file_path)) {
      throw new \Exception('PHP-ZIP: After doing the zipping file can not be found');
    }
    if (filesize($this->new_file_path) === 0) {
      throw new \Exception('PHP-ZIP: After doing the zipping file size is still 0 bytes');
    }

    // empty the array
    $this->org_files = [];

    return true;
  }

  public function zip_files($files, $to) {
    $this->zip_start($to);
    $this->zip_add($files);

    return $this->zip_end();
  }

  public function unzip_file($file_path, $target_dir = null, &$zip_progress = null) {

    // Progress
    $this->zip_progress = $zip_progress;

    // if it doesn't exist
    if (!file_exists($file_path)) {
      throw new \Exception("PHP-ZIP: File doesn't Exist");
    }

    $this->extr_file = $file_path;

    // if (class_exists("ZipArchive")) $this->lib = 1;
    // else $this->lib = 2;
    $this->lib = 2;

    if ($target_dir !== null) {
      return $this->unzip_to($target_dir);
    } else {
      return true;
    }
  }

  public function unzip_to($target_dir) {

        // validations -- start //
    if ($this->lib === 0 && $this->extr_file === 0) {
      throw new \Exception("PHP-ZIP: unzip_file hasn't been called");
    }
    // it exists, but it's not a directory
    if (file_exists($target_dir) && (!is_dir($target_dir))) {
      throw new \Exception("PHP-ZIP: Target directory exists as a file not a directory");
    }
    // it doesn't exist
    if (!file_exists($target_dir)) {
      if (!mkdir($target_dir)) {
        throw new \Exception("PHP-ZIP: Directory not found, and unable to create it");
      }
    }
    // validations -- end //

    // Target Directory
    $this->extr_dirc = $target_dir;

    // Smart memory -- start //
    if ($this->zip_progress != null) {
      $this->zip_progress->log(__("Smart memory calculation...", 'backup-migration'), 'STEP');
    }

    if ((intval($this->return_bytes(ini_get('memory_limit'))) / 1024 / 1024) < 384) {
      @ini_set('memory_limit', '384M');
    }

    $memory_limit = (intval($this->return_bytes(ini_get('memory_limit'))) / 1024 / 1024);
    if ($this->zip_progress != null) {
      $this->zip_progress->log(str_replace('%s', $memory_limit, __("There is %s MBs of memory to use", 'backup-migration')), 'INFO');
    }

    $safe_limit = intval($memory_limit / 4);
    if ($safe_limit > 64) $safe_limit = 64;
    if ($memory_limit === 384) $safe_limit = 78;
    if ($memory_limit >= 512) $safe_limit = 104;
    if ($memory_limit >= 1024) $safe_limit = 228;
    if ($memory_limit >= 2048) $safe_limit = 428;

    $real_memory = intval(memory_get_usage() * 0.9 / 1024 / 1024);
    if ($real_memory < $safe_limit) $safe_limit = $real_memory;

    if ($this->zip_progress != null) {
      $this->zip_progress->log(str_replace('%s', $safe_limit, __("Setting the safe limit to %s MB", 'backup-migration')), 'SUCCESS');
    }
    // Smart memory -- end //

    // Extract msg
    $this->zip_progress->log(__('Extracting files (this process can take some time)...', 'backup_migration'), 'STEP');

    // Force PCL Zip
    $this->lib = 2;

    // extract using ZipArchive
    // if($this->lib === 1) {
    // 	$lib = new \ZipArchive;
    // 	if(!$lib->open($this->extr_file)) throw new \Exception("PHP-ZIP: Unable to open the zip file");
    // 	if(!$lib->extractTo($this->extr_dirc)) throw new \Exception("PHP-ZIP: Unable to extract files");
    // 	$lib->close();
    // }

    // extarct using PclZip
    if ($this->lib === 2) {
      if (!class_exists('PclZip')) {
        if (!defined('PCLZIP_TEMPORARY_DIR')) {
          $bmi_tmp_dir = BMI_ROOT_DIR . '/tmp';
          if (!file_exists($bmi_tmp_dir)) {
            @mkdir($bmi_tmp_dir, 0775, true);
          }
          define('PCLZIP_TEMPORARY_DIR', $bmi_tmp_dir . '/bmi-');
        }
        Logger::log(BMI_PRO_PCLZIP);
        if (defined('BMI_PRO_PCLZIP') && file_exists(BMI_PRO_PCLZIP)) {
          require_once BMI_PRO_PCLZIP;
          $this->zip_progress->log(__('Using dedicated PclZIP for Premium Users.', 'backup-migration'), 'INFO');
        } else {
          require_once trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip.php';
        }
      }
      $lib = new \PclZip($this->extr_file);
      $restor = $lib->extract(PCLZIP_OPT_PATH, $this->extr_dirc, PCLZIP_OPT_TEMP_FILE_THRESHOLD, $safe_limit);
      if ($restor == 0) {
        $this->restore_failed($lib->errorInfo(true));

        return false;
      }
    }

    return true;
  }

  private function dir_to_assoc_arr(DirectoryIterator $dir) {
    $data = [];
    foreach ($dir as $node) {
      if ($node->isDir() && !$node->isDot()) {
        $data[$node->getFilename()] = $this->dir_to_assoc_arr(new DirectoryIterator($node->getPathname()));
      } elseif ($node->isFile()) {
        $data[] = $node->getFilename();
      }
    }

    return $data;
  }

  private function path() {
    return join(DIRECTORY_SEPARATOR, func_get_args());
  }

  private function commonPath($files, $remove = true) {
    foreach ($files as $index => $filesStr) {
      $files[$index] = explode(DIRECTORY_SEPARATOR, $filesStr);
    }
    $toDiff = $files;
    foreach ($toDiff as $arr_i => $arr) {
      foreach ($arr as $name_i => $name) {
        $toDiff[$arr_i][$name_i] = $name . "___" . $name_i;
      }
    }
    $diff = call_user_func_array("array_diff", $toDiff);
    reset($diff);
    $i = key($diff) - 1;
    if ($remove) {
      foreach ($files as $index => $arr) {
        $files[$index] = implode(DIRECTORY_SEPARATOR, array_slice($files[$index], $i));
      }
    } else {
      foreach ($files as $index => $arr) {
        $files[$index] = implode(DIRECTORY_SEPARATOR, array_slice($files[$index], 0, $i));
      }
    }

    return $files;
  }
}
