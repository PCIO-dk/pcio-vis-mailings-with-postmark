<?php
/*
Plugin Name:  PCIO VIS Mailings with Postmark
Plugin URI:   https://www.pcio.dk/
Description:  Adds Postmark bulk-mail sending to PCIO VIS Member Event. When active, the Mails page sends via the Postmark API. When inactive, PCIO VIS Member Event falls back to wp_mail().
Version:      1.0.0
Author:       PCIO
Author URI:   https://www.pcio.dk
Requires at least: 6.2
Requires PHP: 8.1
Requires Plugins: pcio-vis-member-event
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  pcio-vis-mailings-with-postmark
Domain Path:  /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PCIO_VIS_Postmark {

    public static function register(): void {
        add_action( 'admin_menu',                  [ __CLASS__, 'add_postmark_menu' ], 20 );
        add_filter( 'pcio_me_mailer_send',         [ __CLASS__, 'send_via_postmark' ], 10, 4 );
    }

    // ── Admin submenu ─────────────────────────────────────────────

    /**
     * Adds a "Postmark" submenu under the parent "Vis" menu registered by
     * pcio-vis-member-event. Must fire after pcio-vis-member-event's admin_menu callback
     * (default priority 10 is fine — WordPress collects all admin_menu
     * callbacks before rendering the menu).
     */
    public static function add_postmark_menu(): void {
        add_submenu_page(
            'pcio-me-settings',
            __( 'Postmark', 'pcio-vis-mailings-with-postmark' ),
            __( 'Postmark', 'pcio-vis-mailings-with-postmark' ),
            'manage_options',
            'pcio-me-pm-settings',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    // ── Settings page ─────────────────────────────────────────────

    public static function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'pcio-vis-mailings-with-postmark' ) );
        }

        // Handle save.
        if (
            isset( $_POST['pcio_me_pm_nonce'] ) &&
            wp_verify_nonce( sanitize_key( $_POST['pcio_me_pm_nonce'] ), 'pcio_me_pm_save' )
        ) {
            update_option( 'pcio_me_postmark_token',    sanitize_text_field( wp_unslash( $_POST['postmark_token']    ?? '' ) ) );
            update_option( 'pcio_me_postmark_from',     sanitize_email(      wp_unslash( $_POST['postmark_from']     ?? '' ) ) );
            update_option( 'pcio_me_postmark_fromname', sanitize_text_field( wp_unslash( $_POST['postmark_fromname'] ?? '' ) ) );
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html__( 'Postmark settings saved.', 'pcio-vis-mailings-with-postmark' ) . '</p></div>';
        }

        $token    = get_option( 'pcio_me_postmark_token',    '' );
        $from     = get_option( 'pcio_me_postmark_from',     '' );
        $fromname = get_option( 'pcio_me_postmark_fromname', '' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Vis — Postmark Settings', 'pcio-vis-mailings-with-postmark' ); ?></h1>

            <p class="description">
                <?php esc_html_e( 'Used for bulk-sending mails to all members via the Postmark API. When these settings are blank the Mails page falls back to the standard WordPress mailer (wp_mail).', 'pcio-vis-mailings-with-postmark' ); ?>
                <?php
                echo wp_kses(
                    sprintf(
                        /* translators: %s: link to postmarkapp.com */
                        esc_html__( 'Get your server API token from %s.', 'pcio-vis-mailings-with-postmark' ),
                        '<a href="' . esc_url( 'https://postmarkapp.com' ) . '" target="_blank" rel="noopener">postmarkapp.com</a>'
                    ),
                    [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ]
                );
                ?>
            </p>

            <form method="post" action="">
                <?php wp_nonce_field( 'pcio_me_pm_save', 'pcio_me_pm_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="postmark_token"><?php esc_html_e( 'Server API Token', 'pcio-vis-mailings-with-postmark' ); ?></label>
                        </th>
                        <td>
                            <input name="postmark_token" id="postmark_token" type="password"
                                   class="regular-text" autocomplete="off"
                                   value="<?php echo esc_attr( $token ); ?>">
                            <p class="description"><?php esc_html_e( 'Stored in the database. Never share this token.', 'pcio-vis-mailings-with-postmark' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="postmark_from"><?php esc_html_e( 'From Email', 'pcio-vis-mailings-with-postmark' ); ?></label>
                        </th>
                        <td>
                            <input name="postmark_from" id="postmark_from" type="email"
                                   class="regular-text"
                                   value="<?php echo esc_attr( $from ); ?>"
                                   placeholder="noreply@example.com">
                            <p class="description"><?php esc_html_e( 'Must be a verified sender address in your Postmark account.', 'pcio-vis-mailings-with-postmark' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="postmark_fromname"><?php esc_html_e( 'From Name', 'pcio-vis-mailings-with-postmark' ); ?></label>
                        </th>
                        <td>
                            <input name="postmark_fromname" id="postmark_fromname" type="text"
                                   class="regular-text"
                                   placeholder="<?php echo esc_attr__( 'Organisation name', 'pcio-vis-mailings-with-postmark' ); ?>"
                                   value="<?php echo esc_attr( $fromname ); ?>">
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Postmark settings', 'pcio-vis-mailings-with-postmark' ) ); ?>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Current status', 'pcio-vis-mailings-with-postmark' ); ?></h2>
            <?php if ( $token && $from ) : ?>
                <p>
                    <span style="color:#00a32a;font-weight:600;">&#10003;</span>
                    <?php esc_html_e( 'Postmark is configured. Mails will be sent via the Postmark API.', 'pcio-vis-mailings-with-postmark' ); ?>
                </p>
            <?php else : ?>
                <p>
                    <span style="color:#d63638;font-weight:600;">&#9888;</span>
                    <?php esc_html_e( 'Postmark is not fully configured. Mails will fall back to wp_mail().', 'pcio-vis-mailings-with-postmark' ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    // ── Mailer filter implementation ─────────────────────────────

    /**
     * Hooked on 'pcio_me_mailer_send'.
     *
     * @param array|null|\WP_Error $result      Previous filter value (null on first call).
     * @param array                $mail        Row from me_mails: ['subject'=>…,'content'=>…].
     * @param array                $recipients  Array of ['email'=>…] rows.
     * @param array                $attachments Array of ['name','path','mime'] resolved rows.
     * @return array|null|\WP_Error
     *   – array ['sent'=>int,'failed'=>int]  on success
     *   – WP_Error                           when token is set but the API call fails
     *   – null                               when Postmark is not configured (allows fallback)
     */
    public static function send_via_postmark( $result, array $mail, array $recipients, array $attachments = [] ) {
        $token    = get_option( 'pcio_me_postmark_token',    '' );
        $from     = get_option( 'pcio_me_postmark_from',     '' );
        $fromname = get_option( 'pcio_me_postmark_fromname', '' );

        // Not configured — return null so core falls back to wp_mail.
        if ( ! $token || ! $from ) {
            return null;
        }

        $from_header = $fromname ? $fromname . ' <' . $from . '>' : $from;

        // Read and base64-encode each attachment file ONCE (shared across all recipients).
        global $wp_filesystem;
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        $pm_attachments = [];
        foreach ( $attachments as $att ) {
            if ( empty( $att['path'] ) || ! file_exists( $att['path'] ) ) {
                continue;
            }
            $content = $wp_filesystem ? $wp_filesystem->get_contents( $att['path'] ) : false;
            if ( $content === false ) {
                continue; // unreadable — skip rather than send a zero-byte attachment
            }
            // Postmark's JSON API requires attachment bytes to be base64-encoded for transport.
            $pm_attachments[] = [
                'Name'        => $att['name'],
                'Content'     => base64_encode( $content ),
                'ContentType' => $att['mime'] ?: 'application/octet-stream',
            ];
        }

        // Postmark batch API limit: ~50 MB per request.
        // When attachments are present the base64 content is duplicated into every message
        // object, so the payload grows as (attachment_bytes × recipient_count).
        // We calculate a safe chunk size to stay under 40 MB (80 % of the limit).
        $chunk_size = 500; // default — no attachments
        if ( $pm_attachments ) {
            $att_bytes = array_sum( array_map( static fn( $a ) => strlen( $a['Content'] ), $pm_attachments ) );
            if ( $att_bytes > 0 ) {
                $chunk_size = max( 1, (int) floor( 40 * 1024 * 1024 / $att_bytes ) );
            }
        }

        // Build per-recipient message objects.
        $messages = array_map( static function ( $r ) use ( $from_header, $mail, $pm_attachments ) {
            $msg = [
                'From'          => $from_header,
                'To'            => $r['email'],
                'Subject'       => $mail['subject'],
                'HtmlBody'      => $mail['content'],
                'MessageStream' => 'broadcast',
            ];
            if ( $pm_attachments ) {
                $msg['Attachments'] = $pm_attachments;
            }
            return $msg;
        }, $recipients );

        $sent       = 0;
        $failed     = 0;
        $last_error = null;

        foreach ( array_chunk( $messages, $chunk_size ) as $chunk ) {
            $response = wp_remote_post(
                'https://api.postmarkapp.com/email/batch',
                [
                    'timeout' => 60,
                    'headers' => [
                        'Accept'                  => 'application/json',
                        'Content-Type'            => 'application/json',
                        'X-Postmark-Server-Token' => $token,
                    ],
                    'body' => wp_json_encode( $chunk ),
                ]
            );

            if ( is_wp_error( $response ) ) {
                $failed    += count( $chunk );
                $last_error = $response->get_error_message();
                continue;
            }

            $code = wp_remote_retrieve_response_code( $response );
            if ( $code !== 200 ) {
                $body       = json_decode( wp_remote_retrieve_body( $response ), true );
                $last_error = $body['Message'] ?? ( 'Postmark HTTP ' . $code );
                $failed    += count( $chunk );
                continue;
            }

            $results = json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];
            foreach ( $results as $r ) {
                if ( ( $r['ErrorCode'] ?? 0 ) !== 0 ) {
                    ++$failed;
                } else {
                    ++$sent;
                }
            }
        }

        // Surface the last error only if every single message failed.
        if ( $sent === 0 && $failed > 0 && $last_error ) {
            return new WP_Error( 'postmark_error', $last_error );
        }

        return compact( 'sent', 'failed' );
    }
}

add_action( 'plugins_loaded', static function (): void {
    // Bail if the core plugin is not active.
    if ( ! class_exists( 'PCIO_VIS_Plugin' ) ) {
        return;
    }
    PCIO_VIS_Postmark::register();
} );
