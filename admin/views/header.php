<?

/**
 * View for Settings page header (tabs)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<h2 style="padding: 0; margin: 0; height: 0;">
    <!-- Fix for WordPress notices jumping in between header and settings area -->
</h2>

<div class="wrap">
    <h1><?=__( $this->title, WC_LND_NAME ); ?></h1>

    <h2 class="wc_lnd_tabs_container nav-tab-wrapper">
        <? foreach (self::get_structure() as $tab_key => $tab): ?>
            <? if (self::tab_has_settings($tab)): ?>
                <a class="nav-tab <?=($tab_key == $current_tab ? 'nav-tab-active' : ''); ?>" href="admin.php?page=<?=static::$prefix?>&tab=<?=$tab_key; ?>"><?=$tab['title']; ?></a>
            <? endif; ?>
        <? endforeach; ?>
    </h2>

</div>
