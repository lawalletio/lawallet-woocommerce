
<link rel="stylesheet" href="<?=plugins_url('assets/css/payment.css', dirname(__FILE__))?>" type="text/css" />
<noscript><style>.yesscript{display:none}</style></noscript>

<div class="ln-pay">
  <h1><?=__('Pay with Lightning', 'lawallet-woocommerce')?></h1>
  <h3>
    <?php if ($order->get_currency() !== 'BTC'): ?> <?php echo $order->get_total() ?> <?=$currency ?> = <?php endif ?>
    <?php echo $sats ?> sats
  </h3>
  <h4>
    <b><?=__('Rate')?></b>: <?=$currency . ' ' . $rate . ' ' . __('from') . ' ' . $exchange?>
  </h4>
  <img class="qr" src="<?php echo $qr_uri ?>">
  <code class="payreq"><?php echo $payReq ?></code>
  <hr />
  <p>
    <code><?php echo json_encode($lud16) ?></code>
  </p>
  <p>
    <noscript>Your browser has JavaScript turned off. Please refresh the page manually after making the payment.</noscript>
    <span class="yesscript"><img src="<?=plugins_url( '../assets/img/loader.gif', __FILE__ ) ?>" class="loader" alt="loading">
      <span id="invoice_expires_label">
        <?=__('Awaiting payment', 'lawallet-woocommerce')?>.
        <?=__('The invoice expires', 'lawallet-woocommerce')?> <span id="expiry-timer" title="<?=$expiry ?>"><?=$expiry ?></span>.
      </span>
    </span>
  </p>
  <a class="checkout-button button alt btn btn-default" href="lightning:<?=$payReq; ?>"><?=__('Pay with Lightning', 'lawallet-woocommerce')?></a>
</div>

<script type="text/javascript">
  (function($, ajax_url, invoice_id, redir_url, expires_at){
    var first_check = false;

    var intervalId = null;

    function poll() {
      $.post(ajax_url, { action: 'ln_wait_invoice', invoice_id: invoice_id })
        .success((code, state, res) => {
          first_check = true;
          if (res.responseJSON === true) {
            playPayedAnimation();
            intervalId && clearInterval(intervalId);
            return document.location = redir_url;
          }
          throw new Error('succesful response, but not the expected one')
        })
        .fail(res => {
          // 402 Payment Required: timeout reached without payment, invoice is still payable
          // if (res.status === 402) return poll()
          // 410 Gone: invoice expired and can not longer be paid
          if (res.status === 410) return location.reload()

          throw new Error('unexpected status code '+res.status)
        })
    };
    
    function updateExpiry() {
      var left = expires_at - (+new Date()/1000|0)
      if (left <= 0) {
        alert("RESTARTING");
        if (first_check) return location.reload();
        $('#invoice_expires_label').text('<?=__('Generating new invoice', 'lawallet-woocommerce')?>...');
      } else {
        $('#expiry-timer').text('<?=__('in', 'lawallet-woocommerce')?> '+left)
      }
      setTimeout(updateExpiry, 1000)
    }

    function playPayedAnimation() {
      $('.ln-pay .check').css({
        display: 'block'
      });
      $('.ln-pay .qr').css({
        opacity: 0.3
      });
    }

    $(function () {
      intervalId = setInterval(() => {
        poll();
      }, 2000);
    })
    

  })(jQuery, <?=json_encode(admin_url( 'admin-ajax.php' )) ?>, <?=json_encode($order->get_id()) ?>,
            <?=json_encode($order->get_checkout_order_received_url()) ?>, <?=$expiry ?>)

</script>
