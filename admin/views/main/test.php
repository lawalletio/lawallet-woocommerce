<?

/**
 * View for Settings page fields
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wc_lnd_settings">
    <div class="wc_lnd_settings_container">

        <h2><?=__('Testing payment process', 'lawallet-woocommerce')?></h2>
        <ul class="wc_lnd_settings_list">
          <? foreach ($results as $item): ?>
          <li>
            <b><?=$item->title;?></b> :
            <span class="text-<?=$item->success ? 'success' : 'error';?>">
              <?=isset($item->message) ? $item->message : ($item->success ? __('OK', 'lawallet-woocommerce') : __('Error', 'lawallet-woocommerce')) ?>
            </span>
            <span class="dashicons dashicons-<?=$item->success ? 'yes' : 'no'?>"></span>
          </li>
          <? endforeach; ?>
        </ul>


    </div>
</div>
