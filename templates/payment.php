
<link rel="stylesheet" href="<?=plugins_url('assets/css/payment.css', dirname(__FILE__))?>" type="text/css" />
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<noscript><style>.yesscript{display:none}</style></noscript>

<div class="ln-pay">
  <h1><?=__('Pay with Lightning', 'lawallet-woocommerce')?></h1>
  <h3>
    <?php if ($order->get_currency() !== 'BTC'): ?> <?php echo $order->get_total() ?> <?=$currency ?> = <?php endif ?>
    <?php echo $sats ?> sats
  </h3>
  <h4>
    <b><?=__('Rate')?></b>: 1 SAT = <?=$currency . ' ' . $rate . ' ' . '<i>' . __('from') . ' ' . $exchange . '</i>'?>
  </h4>
  <div class="qr_container">
    <div id="qr"></div>
    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 326 326" id="check-svg">
      <circle cx="163" cy="163" r="150"/>
      <polyline points="100 170 150 210 230 120" />
    </svg>
  </div>
  <code class="payreq"><?php echo $payReq ?></code>
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
    const lud16 = <?=json_encode($lud16) ?>;

    async function poll() {
      return $.post(ajax_url, { action: 'ln_wait_invoice', invoice_id: invoice_id })
        .success((code, state, res) => {
          if (res.responseJSON === true) {
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
    
    async function updateExpiry() {
      var left = expires_at - (+new Date()/1000|0)
      if (left <= 0) {
        await poll();
        if (first_check) return location.reload();
        $('#invoice_expires_label').text('<?=__('Generating new invoice', 'lawallet-woocommerce')?>...');
        return;
      } else {
        $('#expiry-timer').text('<?=__('in', 'lawallet-woocommerce')?> '+left)
      }
      setTimeout(updateExpiry, 1000)
    }

    function playPayedAnimation() {
      $('#qr').addClass("active");
      $("#check-svg").addClass("active");
    }

    $(function () {
      const zapListener = new ZapListener(lud16.relays[0], lud16.nostrPubkey, lud16.orderKey, (event) => {
        console.dir(event);
        playPayedAnimation();
        poll();
      });
      zapListener.connect();


      new QRCode(document.getElementById("qr"), {
          text: "<?=$payReq ?>",
          width: 300,
          height: 300,
          colorDark : "#000000",
          colorLight : "#ffffff",
          // correctLevel : QRCode.CorrectLevel.H
      })
    })
    
    updateExpiry();
  })(jQuery, <?=json_encode(admin_url( 'admin-ajax.php' )) ?>, <?=json_encode($order->get_id()) ?>,
            <?=json_encode($order->get_checkout_order_received_url()) ?>, <?=$expiry ?>)

</script>
