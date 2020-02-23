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

        <input type="hidden" name="current_tab" value="<?=$current_tab; ?>" />
        <? settings_fields(static::$prefix . '-' . $current_tab); ?>
        <? do_settings_sections(static::$prefix . '-' . str_replace('_', '-', $current_tab)); ?>
        <? submit_button(); ?>

    </div>
</div>
