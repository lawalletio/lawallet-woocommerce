<h2><?=__('Payment completed successfully', 'lawallet-woocommerce')?></h2>
<ul class="order_details">
  <?/*
  <li>
    <?=__('Payment completed at', 'lawallet-woocommerce')?>: <strong><?=date('r', $invoiceRep->settle_date) ?></strong>
  </li>
  */
  ?>
  <li>
    <?=__('Lightning hash', 'lawallet-woocommerce')?>: <strong><?=$payHash ?></strong>
  </li>
  <li>
    <?=__('Invoice amount', 'lawallet-woocommerce')?>: <strong><?=self::format_msat($sats) ?></strong>
  </li>
</ul>
