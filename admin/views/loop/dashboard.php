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
        <h2><?=__('Summary', 'lnd-woocommerce')?></h2>
        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th scope="row"><?=__('Inbound capacity', 'lnd-woocommerce')?></th>
              <td>
                <b><?=number_format($balance->remote_balance)?></b> sats
                <div class="wc_lnd_settings_hint"><?=__('Total satoshis you are able to receive via Lightning', 'lnd-woocommerce')?></div>
              </td>
            </tr>
            <tr>
              <th scope="row"><?=__('Outbound capacity', 'lnd-woocommerce')?></th>
              <td>
                <b><?=number_format($balance->local_balance)?></b> sats
                <div class="wc_lnd_settings_hint"><?=__('Total satoshis you are able to send via Lightning', 'lnd-woocommerce')?></div>
              </td>
            </tr>
            <tr>
              <th scope="row"><?=__('Capacity available', 'lnd-woocommerce')?></th>
              <td>
                <b><?=$ratio*100?>%</b>
                <div class="wc_lnd_settings_hint"><?=__('Total inbound capacity related to total channel balance', 'lnd-woocommerce')?></div>
              </td>
            </tr>
            <tr>
              <th scope="row"><?=__('Total Channels', 'lnd-woocommerce')?></th>
              <td>
                <b><?=$balance->open_channels?></b> open channels
                <div class="wc_lnd_settings_hint"><?=__('Currently open channels', 'lnd-woocommerce')?></div>
              </td>
            </tr>
            <tr>
              <th scope="row"><?=__('Swap min amount', 'lnd-woocommerce')?></th>
              <td>
                <b><?=number_format($terms->min_swap_amount)?></b> sats
                <div class="wc_lnd_settings_hint"><?=__('Minimum amount of satoshis for swaping ', 'lnd-woocommerce')?></div>
              </td>
            </tr>
            <tr>
              <th scope="row"><?=__('Swap max amount', 'lnd-woocommerce')?></th>
              <td>
                <b><?=number_format($terms->max_swap_amount)?></b> sats
                <div class="wc_lnd_settings_hint"><?=__('Maximum amount of satoshis for swaping ', 'lnd-woocommerce')?></div>
              </td>
            </tr>
          </tbody>
        </table>

        <h2><?=__('Swap Capacity (Loop Out)', 'lnd-woocommerce')?></h2>
        <form method="post" name="data">
          <input type="hidden" name="method" value="loop_out" />
          <table class="form-table" role="presentation">
            <tbody>
              <tr>
                <th scope="row"><?=__('Total satoshis', 'lnd-woocommerce')?></th>
                <td>
                  <input type="text" name="amt" class="wc_lnd_setting wc_lnd_field_long " value="<?=min(max($balance->local_balance, $terms->min_swap_amount), $terms->max_swap_amount)?>" />
                  <div class="wc_lnd_settings_hint"><?=__('Total satoshis to loop out and get on-chain transaction', 'lnd-woocommerce')?></div>
                </td>
              </tr>
              <tr>
                <th scope="row"><?=__('Bitcoin Address', 'lnd-woocommerce')?></th>
                <td>
                  <input type="text" name="address" class="wc_lnd_setting wc_lnd_field_long " value="tb1qudu050q3e6v70fd48z4982qsxqedw9mudkxr4c" />
                  <div class="wc_lnd_settings_hint"><?=__('Bitcoin address to get the transaction', 'lnd-woocommerce')?></div>
                </td>
              </tr>
            </tbody>
          </table>
          <? submit_button(__('Execute Swap', 'lnd-woocommerce')); ?>
        </form>

    </div>
</div>
