<?php

  // Namespace
  namespace BMI\Plugin\Zipper;

  // Use
  use BMI\Plugin\Backup_Migration_Plugin as BMP;
  use BMI\Plugin\BMI_Logger as Logger;
  use BMI\Plugin\Progress\BMI_ZipProgress as Progress;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  /**
   * BMI_Zipper
   */
  class BMI_Zipper {
    public function makeZIP($files, $output, $name, &$zip_progress, $cron = false) {

      // Verbose
      Logger::log(__("Creating backup ", 'backup-migration'));
      Logger::log(__("Found ", 'backup-migration') . sizeof($files) . __(" files to backup.", 'backup-migration'));

      // Require Universal Zip Library
      require_once BMI_INCLUDES . '/zipper/src/zip.php';

      // Start microtime for ZIP Process
      $start = microtime(true);

      // Logs
      $zip_progress->log(__("Preparing map of files...", 'backup-migration'), 'step');

      // Try to catch error
      try {

        // Create new ZIP
        $zip = new Zip();
        $zip->zip_start($output, $files, $name, $zip_progress, $start);

        // Logs
        $zip_progress->log(__("Files prepared.", 'backup-migration'), 'success');
        $zip_progress->log(__("Starting compression process...", 'backup-migration'), 'info');

        // Close ZIP and Save
        $lala = $zip->zip_end(2, $cron);
        if (!$lala) {
          $zip_progress->log(__("Something went wrong (pclzip) – removing backup files...", 'backup-migration'), 'error');

          return false;
        }

        return true;
      } catch (\Throwable $e) {

        // Error print
        $zip_progress->log(__("Reverting backup, removing file...", 'backup-migration'), 'step');
        $zip_progress->log(__("There was an error during backup...", 'backup-migration'), 'error');
        $zip_progress->log($e->getMessage(), 'error');

        return false;
      } catch (\Exception $e) {

        // Error print
        $zip_progress->log(__("Reverting backup, removing file...", 'backup-migration'), 'step');
        $zip_progress->log(__("There was an error during backup...", 'backup-migration'), 'error');
        $zip_progress->log($e->getMessage(), 'error');

        return false;
      }

      return true;
    }

    public function getZipFileContent($zipname, $filename) {
      if (class_exists('ZipArchive')) {
        $zip = new \ZipArchive();

        if ($zip->open($zipname) === true) {
          if ($content = $zip->getFromName($filename)) {
            return json_decode($content);
          } else {
            return false;
          }
        } else {
          return false;
        }
      } else {
        if (!class_exists('PclZip')) {
          if (!defined('PCLZIP_TEMPORARY_DIR')) {
            $bmi_tmp_dir = BMI_ROOT_DIR . '/tmp';
            if (!file_exists($bmi_tmp_dir)) {
              @mkdir($bmi_tmp_dir, 0775, true);
            }
            define('PCLZIP_TEMPORARY_DIR', $bmi_tmp_dir . '/bmi-');
          }
          if (defined('BMI_PRO_PCLZIP') && file_exists(BMI_PRO_PCLZIP)) {
            require_once BMI_PRO_PCLZIP;
          } else {
            require_once trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip.php';
          }
        }
        $lib = new \PclZip($zipname);

        $content = $lib->extract(PCLZIP_OPT_BY_NAME, $filename, PCLZIP_OPT_EXTRACT_AS_STRING);
        if (sizeof($content) > 0) {
          return json_decode($content[0]['content']);
        } else {
          return false;
        }
      }
    }

    public function getZipFileContentPlain($zipname, $filename) {
      if (class_exists('ZipArchive')) {
        $zip = new \ZipArchive();

        if ($zip->open($zipname) === true) {
          if ($content = $zip->getFromName($filename)) {
            return $content;
          } else {
            return false;
          }
        } else {
          return false;
        }
      } else {
        if (!class_exists('PclZip')) {
          if (!defined('PCLZIP_TEMPORARY_DIR')) {
            $bmi_tmp_dir = BMI_ROOT_DIR . '/tmp';
            if (!file_exists($bmi_tmp_dir)) {
              @mkdir($bmi_tmp_dir, 0775, true);
            }
            define('PCLZIP_TEMPORARY_DIR', $bmi_tmp_dir . '/bmi-');
          }
          if (defined('BMI_PRO_PCLZIP') && file_exists(BMI_PRO_PCLZIP)) {
            require_once BMI_PRO_PCLZIP;
          } else {
            require_once trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip.php';
          }
        }
        $lib = new \PclZip($zipname);

        $content = $lib->extract(PCLZIP_OPT_BY_NAME, $filename, PCLZIP_OPT_EXTRACT_AS_STRING);
        if (sizeof($content) > 0) {
          return $content[0]['content'];
        } else {
          return false;
        }
      }
    }

    public function lock_zip($zippath, $unlock = false) {

      // Require Universal Zip Library
      require_once BMI_INCLUDES . '/zipper/src/zip.php';

      try {

        // Path to lock file
        $filename = '.lock';

        // Load lib
        if (!class_exists('PclZip')) {
          if (!defined('PCLZIP_TEMPORARY_DIR')) {
            $bmi_tmp_dir = BMI_ROOT_DIR . '/tmp';
            if (!file_exists($bmi_tmp_dir)) {
              @mkdir($bmi_tmp_dir, 0775, true);
            }
            define('PCLZIP_TEMPORARY_DIR', $bmi_tmp_dir . '/bmi-');
          }
          if (defined('BMI_PRO_PCLZIP') && file_exists(BMI_PRO_PCLZIP)) {
            require_once BMI_PRO_PCLZIP;
          } else {
            require_once trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip.php';
          }
        }
        $lib = new \PclZip($zippath);

        // Unlocking case
        if ($unlock) {
          if ($this->is_locked_zip($zippath)) {
            $lib->delete(PCLZIP_OPT_BY_NAME, $filename);
          } else {
            return true;
          }
        } else {
          if (!$this->is_locked_zip($zippath)) {

            // Locking case
            $content = json_encode(['locked' => 'true']);
            $lib->add([[PCLZIP_ATT_FILE_NAME => $filename, PCLZIP_ATT_FILE_CONTENT => $content]]);
          }
        }

        return true;
      } catch (\Exception $e) {
        Logger::error($e);

        return false;
      } catch (\Throwable $e) {
        Logger::error($e);

        return false;
      }
    }

    public function is_locked_zip($zippath) {
      $lock = $this->getZipFileContent($zippath, '.lock');
      if ($lock) {
        if ($lock->locked == 'true') {
          return true;
        } else {
          return false;
        }
      } else {
        return false;
      }
    }
  }
