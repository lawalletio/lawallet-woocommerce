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

        <h2><?=__('Info Page', 'lawallet-woocommerce')?></h2>
        <table class="form-table" role="presentation">
          <tbody>
            <? foreach ($info as $key => $value): ?>
            <tr>
              <th scope="row"><?=$key?></th>
              <td>
                <input type="text" id="host" class="wc_lnd_setting wc_lnd_field_long " value="<?=gettype($value) === 'string' ? $value : htmlspecialchars(json_encode($value)) ?>" disabled="disabled">
                <!-- <div class="wc_lnd_settings_hint">Dirección del host de LND, puede usarse 127.0.0.1, como localhost</div> -->
              </td>
            </tr>
          <? endforeach; ?>
          </tbody>
        </table>

    </div>
</div>
