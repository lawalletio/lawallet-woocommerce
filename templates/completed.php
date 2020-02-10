<h2><?=__('Payment completed successfully', 'lnd-woocommerce')?></h2>
<?
  $payHash = get_post_meta( $order->get_id(), 'LN_HASH', true );
  $invoiceRep = $this->lndCon->getInvoiceInfoFromHash( bin2hex(base64_decode($payHash)) );
?>
<ul class="order_details">
  <li>
    <?=__('Payment completed at', 'lnd-woocommerce')?>: <strong><? echo date('r', $invoiceRep->settle_date) ?></strong>
  </li>
  <li>
    <?=__('Lightning rhash', 'lnd-woocommerce')?>: <strong><? echo $invoiceRep->r_hash ?></strong>
  </li>
  <li>
    <?=__('Invoice amount', 'lnd-woocommerce')?>: <strong><? echo self::format_msat($invoiceRep->value) ?></strong>
  </li>
</ul>
