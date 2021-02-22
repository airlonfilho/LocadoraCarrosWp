<?php

  // Namespace
  namespace BMI\Plugin\Extracter;

  // Use
  use BMI\Plugin\BMI_Logger as Logger;
  use BMI\Plugin\Dashboard as Dashboard;
  use BMI\Plugin\Database\BMI_Database as Database;
  use BMI\Plugin\Progress\BMI_ZipProgress as Progress;
  use BMI\Plugin\Backup_Migration_Plugin as BMP;
  use BMI\Plugin\Zipper\Zip as Zip;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  /**
   * BMI_Extracter
   */
  class BMI_Extracter {
    public function __construct($backup, &$migration) {

      // Globals
      global $table_prefix;

      // Requirements
      require_once BMI_INCLUDES . '/database/manager.php';

      // Backup name
      $this->backup_name = $backup;

      // Logger
      $this->migration = $migration;

      // Temp name
      $this->tmptime = time();
      $this->tmp = ABSPATH . '/backup-migration_' . $this->tmptime;

      // Prepare database connection
      $this->db = new Database(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

      // Save current wp-config to replace (only those required)
      $this->DB_NAME = DB_NAME;
      $this->DB_USER = DB_USER;
      $this->DB_PASSWORD = DB_PASSWORD;
      $this->DB_HOST = DB_HOST;
      $this->DB_CHARSET = DB_CHARSET;
      $this->DB_COLLATE = DB_COLLATE;

      $this->AUTH_KEY = AUTH_KEY;
      $this->SECURE_AUTH_KEY = SECURE_AUTH_KEY;
      $this->LOGGED_IN_KEY = LOGGED_IN_KEY;
      $this->NONCE_KEY = NONCE_KEY;
      $this->AUTH_SALT = AUTH_SALT;
      $this->SECURE_AUTH_SALT = SECURE_AUTH_SALT;
      $this->LOGGED_IN_SALT = LOGGED_IN_SALT;
      $this->NONCE_SALT = NONCE_SALT;

      $this->ABSPATH = ABSPATH;
      $this->WP_CONTENT_DIR = WP_CONTENT_DIR;

      $this->WP_DEBUG_LOG = WP_DEBUG_LOG;
      $this->table_prefix = $table_prefix;
      $this->code = get_option('z__bmi_xhria', false);

      $this->siteurl = get_option('siteurl');
      $this->home = get_option('home');

      // Make temp dir
      $this->migration->log(__('Making temporary directory', 'backup-migration'), 'INFO');
      mkdir($this->tmp);

      // Deny read of this folder
      copy(BMI_INCLUDES . '/htaccess/.htaccess', $this->tmp . '/.htaccess');
      touch($this->tmp . '/index.html');
      touch($this->tmp . '/index.php');
    }

    public function replacePath($path, $sub, $content) {
      $path .= DIRECTORY_SEPARATOR . 'wordpress' . $sub;

      // Handle only database backup
      if (!file_exists($path)) return;

      $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

      $clent = strlen($content);
      $sublen = strlen($path);
      $files = [];
      $dirs = [];

      foreach ($rii as $file) {
        if (!$file->isDir()) {
          $files[] = substr($file->getPathname(), $sublen);
        } else {
          $dirs[] = substr($file->getPathname(), $sublen);
        }
      }

      for ($i = 0; $i < sizeof($dirs); ++$i) {
        $src = $path . $dirs[$i];
        if (strpos($src, $content) !== false) {
          $dest = $this->WP_CONTENT_DIR . $sub . substr($dirs[$i], $clent);
        } else {
          $dest = $this->ABSPATH . $sub . $dirs[$i];
        }

        if (!file_exists($dest)) {
          @mkdir($dest, 0755, true);
        }
      }

      for ($i = 0; $i < sizeof($files); ++$i) {
        if (strpos($files[$i], 'debug.log') !== false) {
          array_splice($files, $i, 1);

          break;
        }
      }

      $max = sizeof($files);
      for ($i = 0; $i < $max; ++$i) {
        $src = $path . $files[$i];
        if (strpos($src, $content) !== false) {
          $dest = $this->WP_CONTENT_DIR . $sub . substr($files[$i], $clent);
        } else {
          $dest = $this->ABSPATH . $sub . $files[$i];
        }

        if (file_exists($src)) rename($src, $dest);

        if ($i % 100 === 0) {
          $this->migration->progress(intval((($i / $max) * 100) / 2));
        }
      }
    }

    public function replaceAll($content) {
      $this->replacePath($this->tmp, DIRECTORY_SEPARATOR, $content);
    }

    public function cleanup() {
      $dir = $this->tmp;
      $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
      $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

      $this->migration->log(__('Removing ', 'backup-migration') . iterator_count($files) . __(' files', 'backup-migration'), 'INFO');
      foreach ($files as $file) {
        if ($file->isDir()) {
          @rmdir($file->getRealPath());
        } else {
          @unlink($file->getRealPath());
        }
      }

      @rmdir($dir);
    }

    public function extractTo() {
      try {

        // Require Universal Zip Library
        require_once BMI_INCLUDES . '/zipper/src/zip.php';

        // Verbose
        Logger::log('Restoring site...');

        // Time start
        $start = microtime(true);
        $this->migration->log(__('Scanning archive...', 'backup-migration'), 'STEP');

        // Source
        $src = BMI_BACKUPS . '/' . $this->backup_name;

        // Extract
        $this->zip = new Zip();
        $isOk = $this->zip->unzip_file($src, $this->tmp, $this->migration);
        if (!$isOk) {
          $this->cleanup();

          return false;
        }

        $this->migration->log(__('Files extracted...', 'backup-migration'), 'SUCCESS');

        // WP Config backup
        $this->migration->log(__('Saving wp-config file...', 'backup-migration'), 'STEP');
        copy(ABSPATH . '/wp-config.php', ABSPATH . '/wp-config.' . $this->tmptime . '.php');
        $this->migration->log(__('File wp-config saved', 'backup-migration'), 'SUCCESS');

        $this->migration->log(__('Getting backup manifest...', 'backup-migration'), 'STEP');
        $manifest = json_decode(file_get_contents($this->tmp . '/bmi_backup_manifest.json'));
        $this->migration->log(__('Manifest loaded', 'backup-migration'), 'SUCCESS');

        $this->migration->log(__('Restoring files...', 'backup-migration'), 'STEP');
        $contentDirectory = $this->WP_CONTENT_DIR;
        $pathtowp = DIRECTORY_SEPARATOR . 'wp-content';
        if (isset($manifest->config->WP_CONTENT_DIR) && isset($manifest->config->ABSPATH)) {
          $absi = $manifest->config->ABSPATH;
          $cotsi = $manifest->config->WP_CONTENT_DIR;
          if (strlen($absi) <= strlen($cotsi) && substr($cotsi, 0, strlen($absi)) == $absi) {
            $inside = true;
            $pathtowp = substr($cotsi, strlen($absi));
          } else {
            $inside = false;
            $pathtowp = $cotsi;
          }
        }

        $this->replaceAll($pathtowp);
        $this->migration->log(__('Files restored', 'backup-migration'), 'SUCCESS');

        $this->migration->log(__('Restoring database...', 'backup-migration'), 'STEP');
        if (file_exists($this->tmp . '/bmi_database_backup.sql')) {
          $this->migration->log(__('Database size: ' . BMP::humanSize(filesize($this->tmp . '/bmi_database_backup.sql')), 'backup-migration'), 'INFO');
          $old_domain = $manifest->dbdomain;
          $new_domain = $this->siteurl; // parse_url(home_url())['host'];

          $abs = BMP::fixSlashes($manifest->config->ABSPATH);
          $newabs = BMP::fixSlashes(ABSPATH);
          $file = $this->tmp . '/bmi_database_backup.sql';
          $this->db->importDatabase($file, $old_domain, $new_domain, $abs, $newabs, $manifest->config->table_prefix, $this->siteurl, $this->home);
          $this->migration->log(__('Database restored', 'backup-migration'), 'SUCCESS');
        } else {
          $this->migration->log(__('There is no Database file, omitting...', 'backup-migration'), 'INFO');
        }

        // Restore WP Config ** It allows to recover session after restore no matter what
        $curr_prefix = $this->table_prefix;
        $new_prefix = $manifest->config->table_prefix;
        $this->migration->log(__('Restoring wp-config file...', 'backup-migration'), 'STEP');
        $file = file(ABSPATH . '/wp-config.' . $this->tmptime . '.php');
        rename(ABSPATH . '/wp-config.' . $this->tmptime . '.php', ABSPATH . '/wp-config.php');
        $wpconfig = file_get_contents(ABSPATH . '/wp-config.php');
        if (strpos($wpconfig, '"' . $curr_prefix . '";') !== false) {
          $wpconfig = str_replace('"' . $curr_prefix . '";', '"' . $new_prefix . '";', $wpconfig);
        } elseif (strpos($wpconfig, "'" . $curr_prefix . "';") !== false) {
          $wpconfig = str_replace("'" . $curr_prefix . "';", "'" . $new_prefix . "';", $wpconfig);
        }
        file_put_contents(ABSPATH . '/wp-config.php', $wpconfig);
        $this->migration->log(__('WP-Config restored', 'backup-migration'), 'SUCCESS');

        wp_load_alloptions(true);
        if (file_exists($this->tmp . '/bmi_database_backup.sql')) {
          $this->migration->log(__('Making new login session', 'backup-migration'), 'STEP');
          if ($manifest->cron === true || $manifest->cron === 'true' || $manifest->uid === 0 || $manifest->uid === '0') {
            $manifest->uid = 1;
          }
          if (is_numeric($manifest->uid)) {
            $existant = (bool) get_users(['include' => $manifest->uid, 'fields' => 'ID']);
            if ($existant) {
              $user = get_user_by('id', $manifest->uid);
            } else {
              $existant = (bool) get_users(['include' => 1, 'fields' => 'ID']);
              if ($existant) {
                $user = get_user_by('id', 1);
              }
            }
          }

          if (isset($user) && is_object($user) && property_exists($user, 'ID')) {
            clean_user_cache(get_current_user_id());
            clean_user_cache($user->ID);
            wp_clear_auth_cookie();
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID, 1, is_ssl());
            do_action('wp_login', $user->user_login, $user);
            update_user_caches($user);
          }
          $this->migration->log(__('User should be logged in', 'backup-migration'), 'SUCCESS');
        }

        // delete_post_meta_by_key('_elementor_css');
        // delete_post_meta_by_key('_elementor_inline_svg');
        // delete_option('_elementor_global_css');
        // delete_option('elementor-custom-breakpoints-files');

        if ($this->code && is_string($this->code) && strlen($this->code) > 0) update_option('z__bmi_xhria', $this->code);
        else delete_option('z__bmi_xhria');

        $file = trailingslashit(wp_upload_dir()['basedir']) . 'elementor';
        if (file_exists($file) && is_dir($file)) {
          $this->migration->log(__('Clearing elementor template cache...', 'backup-migration'), 'STEP');
          $path = $file . '/*';
          foreach (glob($path) as $file_path) if (!is_dir($file_path)) @unlink($file_path);
          $this->migration->log(__('Elementor cache cleared!', 'backup-migration'), 'SUCCESS');
        }

        $this->migration->log(__('Cleaning temporary files...', 'backup-migration'), 'STEP');
        $this->cleanup();
        $this->migration->log(__('Temporary files cleaned', 'backup-migration'), 'SUCCESS');
        $this->migration->log(__('Restore process took: ', 'backup-migration') . (microtime(true) - $start) . 's', 'INFO');
        Logger::log('Site restored...');

        return true;
      } catch (\Exception $e) {

        // On this tragedy at least remove tmp files
        $this->migration->log(__('Something bad happened...', 'backup-migration'), 'ERROR');
        $this->migration->log($e->getMessage(), 'ERROR');
        $this->cleanup();

        return false;
      } catch (\Throwable $e) {

        // On this tragedy at least remove tmp files
        $this->migration->log(__('Something bad happened...', 'backup-migration'), 'ERROR');
        $this->migration->log($e->getMessage(), 'ERROR');
        $this->cleanup();

        return false;
      }
    }
  }
