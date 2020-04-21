<link rel="stylesheet" href="<? echo plugins_url('assets/css/payment.css', dirname(__FILE__))?>" type="text/css">
<noscript><style>.yesscript{display:none}</style></noscript>

<div class="ln-pay">
  <h1><?=__('Pay with Lightning', 'lnd-woocommerce')?></h1>
  <h3>
    <? if ($order->get_currency() !== 'BTC'): ?> <? echo $order->get_total() ?> <?=$currency ?> = <? endif ?>
    <? echo self::format_msat($sats) ?>
  </h3>
  <h4>
    <b><?=__('Rate', 'lnd-woocommerce')?></b>: <?=$currency . ' ' . $rate . ' ' . __('taken from', 'lnd-woocommerce') . ' ' . $exchange?>
  </h4>
  <div class="qr_container">
    <svg style="display: none" xmlns="http://www.w3.org/2000/svg" class="check" width="250" height="250" viewBox="-40 -10 246 180.9"><path d="M0.3 96l62.4 54.1L165.6 0.3"/></svg>
    <img class="qr" src="<? echo $qr_uri ?>">
  </div>
  <code class="payreq"><? echo $payReq ?></code>
  <p>
    <noscript>Your browser has JavaScript turned off. Please refresh the page manually after making the payment.</noscript>
    <span class="yesscript"><img src="<? echo plugins_url( '../assets/img/loader.gif', __FILE__ ) ?>" class="loader" alt="loading">
      <span id="invoice_expires_label">
        <?=__('Awaiting payment', 'lnd-woocommerce')?>.
        <?=__('The invoice expires', 'lnd-woocommerce')?> <span id="expiry-timer" title="<?=$expiry ?>"><?=$expiry ?></span>.
      </span>
    </span>
  </p>
  <a class="checkout-button button alt btn btn-default" href="lightning:<?=$payReq; ?>"><?=__('Pay with Lightning', 'lnd-woocommerce')?></a>
</div>

<script>
(function($, ajax_url, invoice_id, redir_url, expires_at){
  var first_check = false;
  $(function poll() {
    $.post(ajax_url, { action: 'ln_wait_invoice', invoice_id: invoice_id })
      .success((code, state, res) => {
        first_check = true;
        if (res.responseJSON === true) {
          playPayedAnimation();
          return document.location = redir_url;
        }

        setTimeout(poll, 10000);
        throw new Error('succesful response, but not the expected one')
      })
      .fail(res => {
        // 402 Payment Required: timeout reached without payment, invoice is still payable
        if (res.status === 402) return poll()
        // 410 Gone: invoice expired and can not longer be paid
        if (res.status === 410) return location.reload()

        throw new Error('unexpected status code '+res.status)
      })
  })

  ;(function updateExpiry() {
    var left = expires_at - (+new Date()/1000|0)
    if (left <= 0) {
      if (first_check) return location.reload();
      $('#invoice_expires_label').text('<?=__('Generating new invoice', 'lnd-woocommerce')?>...');
    } else {
      $('#expiry-timer').text('<?=__('in', 'lnd-woocommerce')?> '+formatDur(left))
    }
    setTimeout(updateExpiry, 1000)
  })()

  function playPayedAnimation() {
    $('.ln-pay .check').css({
      display: 'block'
    });
    $('.ln-pay .qr').css({
      opacity: 0.3
    });
  }

  function formatDur(x) {
    var h=x/3600|0, m=x%3600/60|0, s=x%60
    return ''+(h>0?h+':':'')+(m<10&&h>0?'0':'')+m+':'+(s<10?'0':'')+s
  }
})(jQuery, <? echo json_encode(admin_url( 'admin-ajax.php' )) ?>, <? echo json_encode($order->get_id()) ?>,
           <? echo json_encode($order->get_checkout_order_received_url()) ?>, <?=$expiry ?>)

</script>
