<h2><?=__('Payment completed successfully', 'lawallet-woocommerce')?></h2>
<ul class="order_details">
  <li>
    <?=__('Invoice amount', 'lawallet-woocommerce')?>: <strong><?=self::format_msat($sats) ?></strong>
  </li>
</ul>
