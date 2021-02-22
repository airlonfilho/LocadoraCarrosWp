<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

?>

<div class="mtll exclude-row exclusion_template">
  <span><?php _e("Exclude if string", 'backup-migration'); ?></span>
  <input class="exclusion_txt" type="text">
  <span class="orr"><?php _e("appears", 'backup-migration'); ?></span>
  <div class="exclusion_position inline">
    <select>
      <option value="1"><?php _e("anywhere in", 'backup-migration'); ?></option>
      <option value="2"><?php _e("at the beginning of", 'backup-migration'); ?></option>
      <option value="3"><?php _e("at the end of", 'backup-migration'); ?></option>
    </select>
  </div>
  <span class="oll orr"><?php _e("the", 'backup-migration'); ?></span>
  <div class="exclusion_where inline">
    <select>
      <option value="1"><?php _e("file name", 'backup-migration'); ?></option>
      <option value="2"><?php _e("folder name", 'backup-migration'); ?></option>
    </select>
  </div>
  <span class="oll kill-exclusion-rule">
    <img src="<?php echo $this->get_asset('images', 'red-close-min.svg') ?>" alt="close-img" class="hoverable" height="15px">
  </span>
</div>
