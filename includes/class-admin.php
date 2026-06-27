<?php

defined( 'ABSPATH' ) || exit;

class GDI_Admin {

    public function __construct() {

        $auth = new GDI_Google_Auth();

        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_init', [ $auth, 'handle_callback' ] );
        add_action( 'admin_init', [ $this, 'handle_form_actions' ] );

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        add_action( 'update_option_gdi_client_id', [ $auth, 'clear_tokens_on_credential_change' ], 10, 2 );
        add_action( 'update_option_gdi_client_secret', [ $auth, 'clear_tokens_on_credential_change' ], 10, 2 );
    }

    public function enqueue_assets( $hook ) {

        if ( 'tools_page_docs-importer' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'gdi-admin',
            GDI_URL . 'assets/css/admin.css',
            [],
            GDI_VERSION
        );
    }    

    public function register_menu() {
        add_management_page(
            'Google Docs Importer',
            'Google Docs Importer',
            'manage_options',
            'docs-importer',
            [ $this, 'render_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'gdi_settings', 'gdi_client_id' );
        register_setting( 'gdi_settings', 'gdi_client_secret' );
    }

    public function handle_form_actions() {
        if (
            empty( $_GET['page'] ) ||
            'docs-importer' !== sanitize_text_field( wp_unslash( $_GET['page'] ) )
        ) {
            return;
        }

        $auth = new GDI_Google_Auth();

        if ( ! $auth->is_connected() ) {
            return;
        }

        if (
            isset( $_POST['gdi_doc_search'] ) &&
            check_admin_referer( 'gdi_search_docs', 'gdi_search_nonce' )
        ) {
            $search_query = sanitize_text_field( wp_unslash( $_POST['gdi_doc_search'] ) );

            wp_safe_redirect(
                add_query_arg(
                    [
                        'tab'        => 'import',
                        'gdi_search' => rawurlencode( $search_query ),
                    ],
                    admin_url( 'tools.php?page=docs-importer' )
                )
            );
            exit;
        }

        if (
            isset( $_POST['gdi_import_doc_id'] ) &&
            check_admin_referer( 'gdi_import_doc', 'gdi_import_nonce' )
        ) {
            $document_id   = sanitize_text_field( wp_unslash( $_POST['gdi_import_doc_id'] ) );
            $return_search = isset( $_POST['gdi_return_search'] )
                ? sanitize_text_field( wp_unslash( $_POST['gdi_return_search'] ) )
                : '';

            $importer = new GDI_Importer();
            $post_id  = $importer->import( $document_id );

            if ( is_wp_error( $post_id ) ) {
                wp_safe_redirect(
                    add_query_arg(
                        [
                            'tab'              => 'import',
                            'gdi_import_error' => rawurlencode( $post_id->get_error_message() ),
                            'gdi_search'       => rawurlencode( $return_search ),
                        ],
                        admin_url( 'tools.php?page=docs-importer' )
                    )
                );
                exit;
            }

            wp_safe_redirect(
                add_query_arg(
                    [
                        'tab'              => 'import',
                        'imported_post_id' => absint( $post_id ),
                        'gdi_search'       => rawurlencode( $return_search ),
                    ],
                    admin_url( 'tools.php?page=docs-importer' )
                )
            );
            exit;
        }
    }

    public function render_page() {
        $auth = new GDI_Google_Auth();

        $active_tab = isset( $_GET['tab'] )
            ? sanitize_key( wp_unslash( $_GET['tab'] ) )
            : 'import';

        if ( ! in_array( $active_tab, [ 'import', 'settings' ], true ) ) {
            $active_tab = 'import';
        }

        $client_id      = get_option( 'gdi_client_id', '' );
        $client_secret  = get_option( 'gdi_client_secret', '' );
        $can_connect    = ! empty( $client_id ) && ! empty( $client_secret );
        $is_connected   = $auth->is_connected();
        $last_connected = $auth->get_last_connected();
        $search_results = [];
        $search_error   = '';
        $search_query   = '';
        $has_searched   = false;

        if (
            isset( $_GET['gdi_search'] ) &&
            '' !== sanitize_text_field( wp_unslash( $_GET['gdi_search'] ) )
        ) {
            $search_query = sanitize_text_field( wp_unslash( $_GET['gdi_search'] ) );
        }

        if ( $is_connected && ! empty( $search_query ) ) {
            $has_searched = true;
            $google_docs  = new GDI_Google_Docs();
            $results      = $google_docs->search( $search_query );

            if ( is_wp_error( $results ) ) {
                $search_error = $results->get_error_message();
            } else {
                $search_results = $results;
            }
        }

        $import_tab_url   = add_query_arg( [ 'page' => 'docs-importer', 'tab' => 'import' ], admin_url( 'tools.php' ) );
        $settings_tab_url = add_query_arg( [ 'page' => 'docs-importer', 'tab' => 'settings' ], admin_url( 'tools.php' ) );

        $status_color = $is_connected ? '#00a32a' : '#d63638';
        $status_text  = $is_connected ? 'Connected to Google' : 'Disconnected from Google';

        require GDI_PATH . 'templates/admin-page.php';
    }
}