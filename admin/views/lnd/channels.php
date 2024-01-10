<?php

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

        <h2><?=__('Summary', 'lawallet-woocommerce')?></h2>

        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th scope="row"><?=__('Inbound capacity', 'lawallet-woocommerce')?></th>
              <td>
                <b><?=$balance->remote_balance?></b> sats <i>(<?=LND_WC_Helpers::convertSats($balance->remote_balance, $ticker)?>)</i>
                <div class="wc_lnd_settings_hint"><?=__('Total satoshis you are able to receive via Lightning', 'lawallet-woocommerce')?></div>
              </td>
            </tr>
            <tr>
              <th scope="row"><?=__('Outbound capacity', 'lawallet-woocommerce')?></th>
              <td>
                <b><?=$balance->local_balance?></b> sats <i>(<?=LND_WC_Helpers::convertSats($balance->local_balance, $ticker)?>)</i>
                <div class="wc_lnd_settings_hint"><?=__('Total satoshis you are able to send via Lightning', 'lawallet-woocommerce')?></div>
              </td>
            </tr>
          </tbody>
        </table>

        <h2><?=__('Open Channels', 'lawallet-woocommerce')?></h2>

        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th><?=__('Channel ID', 'lawallet-woocommerce')?></th>
              <th><?=__('Capacity', 'lawallet-woocommerce')?></th>
              <th><?=__('Inbound', 'lawallet-woocommerce')?></th>
              <th><?=__('Outbound', 'lawallet-woocommerce')?></th>
              <th><?=__('Sent', 'lawallet-woocommerce')?></th>
              <th><?=__('Received', 'lawallet-woocommerce')?></th>
            </tr>
            <? foreach ($channels as $channel): ?>
            <tr>
              <td><?=$channel->chan_id?></td>
              <td><?=$channel->capacity?></td>
              <td><?=$channel->remote_balance?></td>
              <td><?=$channel->local_balance?></td>
              <td><?=$channel->total_satoshis_sent?></td>
              <td><?=$channel->total_satoshis_received?></td>
            </tr>
          <? endforeach; ?>
          </tbody>
        </table>

    </div>
</div>
