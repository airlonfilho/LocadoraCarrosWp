<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

?>

<tr>
  <th>
    <label for="backups-select-all">
      <input id="backups-select-all" type="checkbox">
      <div class="inline tooltip" tooltip="<?php _e('When your backup was created, in server time', 'backup-migration') ?>" data-top="5">
        <?php _e('Backup date', 'backup-migration') ?>
      </div>
    </label>
  </th>
  <th>
    <div class="inline tooltip" tooltip="<?php _e('The name of your backup. To see the logic which default names your backups get, go to chapter &quot;How backups will be stored&quot;', 'backup-migration') ?>" data-top="5">
      <?php _e('Filename', 'backup-migration') ?>
    </div>
  </th>
  <th>
    <div class="inline tooltip" tooltip="<?php _e('Size of your backup file', 'backup-migration') ?>" data-top="5">
      <?php _e('Size', 'backup-migration') ?>
    </div>
  </th>
  <th style="text-align: center;">
    <div class="tooltip inline" tooltip="<?php _e('Locked backups can only be deleted manually. Unlocked backups get deleted automatically according to the deletion processes which you defined at the top of the &quot;Create backups&quot; - tab. Click on the icon(s) to change the lock status.', 'backup-migration') ?>" data-top="5">
      <?php _e('Locked?', 'backup-migration') ?>
    </div>
  </th>
  <th>
    <div class="inline tooltip" tooltip="<?php _e('Move over the icons to see what you can do with the backup(s)', 'backup-migration') ?>" data-top="5">
      <?php _e('Actions', 'backup-migration') ?>
    </div>
  </th>
  <th></th>
  <th></th>
</tr>
