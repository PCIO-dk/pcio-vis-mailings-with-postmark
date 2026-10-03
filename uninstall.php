<?php
/**
 * Uninstall handler for PCIO VIS Mailings with Postmark.
 *
 * Runs only when the plugin is deleted from the WordPress admin. Removes the
 * options this plugin creates so no orphaned data is left behind.
 *
 * @package PCIO_VIS_Postmark
 */

// Bail if WordPress did not invoke this file via the uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$pcio_vis_pm_options = [
    'pcio_me_postmark_token',
    'pcio_me_postmark_from',
    'pcio_me_postmark_fromname',
];

foreach ( $pcio_vis_pm_options as $pcio_vis_pm_option ) {
    delete_option( $pcio_vis_pm_option );
}
