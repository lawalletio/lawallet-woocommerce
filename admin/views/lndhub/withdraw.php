<?php

/**
 * View for Settings page fields
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<script>
  let ticker = <?=json_encode($ticker)?>;
  let balance = <?=$balance?>;

  (function ($, bolt11) {

    $(() => {
      $('#wc_lnd_pay_req').change(e => {
        let pay_req = e.target.value;

        loadPayRequest(pay_req);
      });

      // Start
      showBalance(balance, ticker);
      $('.wc_lnd_invoice_data').addClass('hidden');
      $('#wc_lnd_submit_wrap input[type=submit]').prop('disabled', true);

    });

    function showBalance(balance, ticker) {
      $('#wc_lnd_balance_amount').html(formatMoney(balance, 0));
      $('#wc_lnd_balance_conversion').html(convertSats(balance, ticker));
    }

    function showAmount(sats, ticker) {
      $('#wc_lnd_amount').html(formatMoney(sats, 0));
      $('#wc_lnd_conversion').html(convertSats(sats, ticker));
    }

    function loadPayRequest(pay_req) {
      try {
        let invoice = bolt11.decode(pay_req);
        showInvoice(invoice);
      } catch (e) {
        showError('El pay_req tiene un formato inválido');
        toggleValidInvoice(false);
      }
    }

    function showInvoice(invoice) {
      toggleValidInvoice(true);
      console.dir(invoice);
      showAmount(invoice.satoshis, ticker);
      $('#wc_lnd_expires').html(invoice.timeExpireDate ? new Date(invoice.timeExpireDate) : 'No date');
      $('#wc_lnd_node_key').html(invoice.payeeNodeKey);
    }

    function showError(error) {
      toggleValidInvoice(false);
      $('#wc_lnd_invoice_error_text').text(error);
    }

    function toggleValidInvoice(valid) {
      if (valid) {
        $('.wc_lnd_invoice_data').removeClass('hidden');
        $('.wc_lnd_invoice_error').addClass('hidden');

      } else {
        $('.wc_lnd_invoice_data').addClass('hidden');
        $('.wc_lnd_invoice_error').removeClass('hidden');
      }
      $('#wc_lnd_submit_wrap input[type=submit]').prop('disabled', !valid);
    }


  })(jQuery, window.bolt11);
</script>

<div class="wc_lnd_settings">
    <div class="wc_lnd_settings_container">

        <h2><?=__('Withdraw', 'lawallet-woocommerce')?></h2>
        <form method="post" name="data">
          <table class="form-table" role="presentation">
            <tbody>
              <tr>
                <th scope="row"><?=__('Available Balance', 'lawallet-woocommerce')?></th>
                <td>
                  <b id="wc_lnd_balance_amount"></b> sats (<i id="wc_lnd_balance_conversion"></i>)
                  <div class="wc_lnd_settings_hint"><?=__('Total satoshis you are able to send via Lightning', 'lawallet-woocommerce')?></div>
                </td>
              </tr>


              <tr>
                <th scope="row"><?=__('Payment Request', 'lawallet-woocommerce')?></th>
                <td>
                  <input id="wc_lnd_pay_req" type="text" name="pay_req" class="wc_lnd_setting wc_lnd_field_long " value="" />
                  <div class="wc_lnd_settings_hint"><?=__('pay_req for the invoice to be paid', 'lawallet-woocommerce')?></div>
                </td>
              </tr>


              <tr class="wc_lnd_invoice_data">
                <th scope="row"><?=__('Amount', 'lawallet-woocommerce')?></th>
                <td>
                  <b id="wc_lnd_amount">12313</b> sats (<i id="wc_lnd_conversion"></i>)
                  <div class="wc_lnd_settings_hint"><?=__('Amount of satoshis to be payed', 'lawallet-woocommerce')?></div>
                </td>
              </tr>
              <tr class="wc_lnd_invoice_data">
                <th scope="row"><?=__('Expires', 'lawallet-woocommerce')?></th>
                <td>
                  <span id="wc_lnd_expires"></span>
                  <div class="wc_lnd_settings_hint"><?=__('Invoice\'s expiration date', 'lawallet-woocommerce')?></div>
                </td>
              </tr>

              <tr class="wc_lnd_invoice_data">
                <th scope="row"><?=__('Node Key', 'lawallet-woocommerce')?></th>
                <td>
                  <span id="wc_lnd_node_key"></span>
                  <div class="wc_lnd_settings_hint"><?=__('Receiver Node key', 'lawallet-woocommerce')?></div>
                </td>
              </tr>

              <tr class="wc_lnd_invoice_error hidden">
                <th scope="row">Error</th>
                <td id="wc_lnd_invoice_error_text"></td>
              </tr>

            </tbody>
          </table>
          <span id="wc_lnd_submit_wrap"><? submit_button(__('Transfer funds', 'lawallet-woocommerce')); ?></span>
        </form>

    </div>
</div>
