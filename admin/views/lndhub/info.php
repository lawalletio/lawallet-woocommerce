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
                <textarea><?=json_encode($value)?></textarea>
              </td>
            </tr>
          <? endforeach; ?>
          </tbody>
        </table>

    </div>
</div>
