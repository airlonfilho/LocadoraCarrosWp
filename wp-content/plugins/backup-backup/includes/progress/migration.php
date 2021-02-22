<?php

  // Namespace
  namespace BMI\Plugin\Progress;

  // Use
  use BMI\Plugin\BMI_Logger AS Logger;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  /**
   * Main File Scanner Logic
   */
  class BMI_MigrationProgress {

    public function __construct($continue = false) {

      if (!file_exists(BMI_BACKUPS)) mkdir(BMI_BACKUPS, 755, true);

      $this->latest = BMI_BACKUPS . '/latest_migration.log';
      $this->progress = BMI_BACKUPS . '/latest_migration_progress.log';

      if (file_exists($this->latest) && !$continue) unlink($this->latest);

    }

    public function start($muted = false) {

      $this->muted = $muted;
      $this->file = fopen($this->latest, 'a') or die(__("Unable to open file!", 'backup-migration'));

    }

    public function progress($progress = '0') {

      file_put_contents($this->progress, $progress);

    }

    public function log($log = '', $level = 'INFO') {

      if (!$this->muted)
        fwrite($this->file, '[' . strtoupper($level) . '] [' . date('Y-m-d H:i:s') . '] ' . $log . "\n");

    }

    public function end() {

      fclose($this->file);

    }

  }
