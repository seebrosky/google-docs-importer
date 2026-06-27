<?php

defined( 'ABSPATH' ) || exit;

class GDI_Google_Auth {

    public function clear_tokens_on_credential_change( $old_value, $new_value ) {
        if ( $old_value !== $new_value ) {
            delete_option( 'gdi_google_tokens' );
        }
    }

    public function is_connected() {
        $tokens = get_option( 'gdi_google_tokens', [] );

        if (
            empty( get_option( 'gdi_client_id', '' ) ) ||
            empty( get_option( 'gdi_client_secret', '' ) ) ||
            empty( $tokens['access_token'] )
        ) {
            return false;
        }

        if ( $this->is_access_token_expired( $tokens ) ) {
            return $this->refresh_access_token();
        }

        return true;
    }

    private function is_access_token_expired( array $tokens ) {
        if ( empty( $tokens['created_at'] ) || empty( $tokens['expires_in'] ) ) {
            return true;
        }

        $expires_at = (int) $tokens['created_at'] + (int) $tokens['expires_in'];

        // Refresh a little early.
        return time() >= ( $expires_at - 60 );
    }

    public function refresh_access_token() {
        $tokens        = get_option( 'gdi_google_tokens', [] );
        $refresh_token = $tokens['refresh_token'] ?? '';

        if ( empty( $refresh_token ) ) {
            delete_option( 'gdi_google_tokens' );
            return false;
        }

        $response = wp_remote_post(
            'https://oauth2.googleapis.com/token',
            [
                'body' => [
                    'client_id'     => get_option( 'gdi_client_id', '' ),
                    'client_secret' => get_option( 'gdi_client_secret', '' ),
                    'refresh_token' => $refresh_token,
                    'grant_type'    => 'refresh_token',
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            delete_option( 'gdi_google_tokens' );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['access_token'] ) ) {
            delete_option( 'gdi_google_tokens' );
            return false;
        }

        $tokens['access_token'] = $body['access_token'];
        $tokens['expires_in']   = $body['expires_in'] ?? 3600;
        $tokens['created_at']   = time();

        update_option( 'gdi_google_tokens', $tokens );

        return true;
    }    

    public function get_access_token() {
        if ( ! $this->is_connected() ) {
            return '';
        }

        $tokens = get_option( 'gdi_google_tokens', [] );

        return $tokens['access_token'] ?? '';
    }

    public function get_last_connected() {
        $tokens = get_option( 'gdi_google_tokens', [] );

        if ( empty( $tokens['created_at'] ) ) {
            return '';
        }

        return wp_date(
            get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
            (int) $tokens['created_at']
        );
    }

    public function get_auth_url() {
        $client_id    = get_option( 'gdi_client_id', '' );
        $redirect_uri = admin_url( 'admin.php?page=docs-importer' );

        $params = [
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => implode(
                ' ',
                [
                    'https://www.googleapis.com/auth/documents.readonly',
                    'https://www.googleapis.com/auth/drive.readonly',
                ]
            ),
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params );
    }

    public function handle_callback() {
        if (
            empty( $_GET['page'] ) ||
            'docs-importer' !== sanitize_text_field( wp_unslash( $_GET['page'] ) )
        ) {
            return;
        }

        if ( empty( $_GET['code'] ) ) {
            return;
        }

        $code          = sanitize_text_field( wp_unslash( $_GET['code'] ) );
        $client_id     = get_option( 'gdi_client_id', '' );
        $client_secret = get_option( 'gdi_client_secret', '' );
        $redirect_uri  = admin_url( 'admin.php?page=docs-importer' );

        $response = wp_remote_post(
            'https://oauth2.googleapis.com/token',
            [
                'body' => [
                    'code'          => $code,
                    'client_id'     => $client_id,
                    'client_secret' => $client_secret,
                    'redirect_uri'  => $redirect_uri,
                    'grant_type'    => 'authorization_code',
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'tab'       => 'settings',
                        'gdi_error' => 'token_request_failed',
                    ],
                    admin_url( 'tools.php?page=docs-importer' )
                )
            );
            exit;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['access_token'] ) ) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'tab'       => 'settings',
                        'gdi_error' => 'missing_access_token',
                    ],
                    admin_url( 'tools.php?page=docs-importer' )
                )
            );
            exit;
        }

        $body['created_at'] = time();

        update_option( 'gdi_google_tokens', $body );

        wp_safe_redirect(
            add_query_arg(
                [
                    'tab'       => 'settings',
                    'connected' => 1,
                ],
                admin_url( 'tools.php?page=docs-importer' )
            )
        );
        exit;
    }
}