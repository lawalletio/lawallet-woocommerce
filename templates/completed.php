<h2><?=__('Payment completed successfully', 'lnd-woocommerce')?></h2>
<ul class="order_details">
  <?/*
  <li>
    <?=__('Payment completed at', 'lnd-woocommerce')?>: <strong><?=date('r', $invoiceRep->settle_date) ?></strong>
  </li>
  */
  ?>
  <li>
    <?=__('Lightning hash', 'lnd-woocommerce')?>: <strong><?=$payHash ?></strong>
  </li>
  <li>
    <?=__('Invoice amount', 'lnd-woocommerce')?>: <strong><?=self::format_msat($sats) ?></strong>
  </li>
</ul>
