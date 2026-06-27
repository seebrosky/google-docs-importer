<?php

defined( 'ABSPATH' ) || exit;

?>

<div class="wrap">
    <h1>Google Docs Importer</h1>

    <h2 class="nav-tab-wrapper">
        <a href="<?php echo esc_url( $import_tab_url ); ?>" class="nav-tab <?php echo 'import' === $active_tab ? 'nav-tab-active' : ''; ?>">Import</a>
        <a href="<?php echo esc_url( $settings_tab_url ); ?>" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">Settings</a>
    </h2>

    <?php if ( isset( $_GET['connected'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p>Successfully connected to Google.</p></div>
    <?php endif; ?>

    <?php if ( ! empty( $_GET['imported_post_id'] ) ) : ?>
        <?php
        $imported_post_id    = absint( $_GET['imported_post_id'] );
        $imported_post_title = get_the_title( $imported_post_id );
        $edit_link           = get_edit_post_link( $imported_post_id, 'raw' );
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                $gdi_action = isset( $_GET['gdi_action'] )
                    ? sanitize_key( wp_unslash( $_GET['gdi_action'] ) )
                    : 'imported';

                $message = 'updated' === $gdi_action
                    ? sprintf( 'Updated "%s".', $imported_post_title )
                    : sprintf( 'Imported "%s" as a draft.', $imported_post_title );

                echo esc_html( $message );
                ?>
                <?php if ( ! empty( $edit_link ) ) : ?>
                    <a href="<?php echo esc_url( $edit_link ); ?>" target="_blank" rel="noopener noreferrer">Edit Post</a>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $_GET['gdi_import_error'] ) ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['gdi_import_error'] ) ) ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( 'import' === $active_tab ) : ?>

        <div class="gdi-status-panel">
            <p>
                <span class="gdi-status-dot" style="background:<?php echo esc_attr( $status_color ); ?>;"></span>
                <strong><?php echo esc_html( $status_text ); ?></strong>
            </p>

            <?php if ( $is_connected && ! empty( $last_connected ) ) : ?>
                <p><strong>Last connected:</strong> <?php echo esc_html( $last_connected ); ?></p>
            <?php endif; ?>
        </div>

        <?php if ( ! $is_connected ) : ?>

            <p>Connect your Google account in the Settings tab before importing documents.</p>
            <p><a href="<?php echo esc_url( $settings_tab_url ); ?>" class="button button-primary">Go to Settings</a></p>

        <?php else : ?>

            <hr>

            <h2>Import from Google Docs</h2>

            <form method="post">
                <?php wp_nonce_field( 'gdi_search_docs', 'gdi_search_nonce' ); ?>

                <p><label for="gdi_doc_search">Search by document title</label></p>

                <div class="gdi-search-form">
                    <input
                        type="text"
                        id="gdi_doc_search"
                        name="gdi_doc_search"
                        class="regular-text"
                        placeholder="Example: sample article"
                        value="<?php echo esc_attr( $search_query ); ?>"
                    >
                    <?php
                        submit_button(
                            'Search Docs',
                            'primary',
                            'submit',
                            false
                        );
                    ?>

                    <?php if ( ! empty( $search_query ) ) : ?>
                        <a href="<?php echo esc_url( $import_tab_url ); ?>" class="button gdi-button-danger">
                            Clear Search
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ( ! empty( $search_error ) ) : ?>
                <div class="notice notice-error"><p><?php echo esc_html( $search_error ); ?></p></div>
            <?php endif; ?>

            <?php if ( $has_searched && empty( $search_error ) && empty( $search_results ) ) : ?>
                <p>No documents found.</p>
            <?php endif; ?>

            <?php if ( ! empty( $search_results ) ) : ?>
                <h3>Results</h3>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Modified</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $importer = new GDI_Importer();
                        ?>

                        <?php foreach ( $search_results as $doc ) : ?>
                            <?php
                            $existing_post_id = ! empty( $doc['id'] )
                                ? $importer->get_post_id_by_document_id( $doc['id'] )
                                : 0;

                            $edit_post_link = $existing_post_id
                                ? get_edit_post_link( $existing_post_id, 'raw' )
                                : '';
                            ?>
                            <tr>
                                <td><?php echo esc_html( $doc['name'] ?? '' ); ?></td>
                                <td>
                                    <?php
                                    if ( ! empty( $doc['modifiedTime'] ) ) {
                                        echo esc_html(
                                            wp_date(
                                                get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                                                strtotime( $doc['modifiedTime'] )
                                            )
                                        );
                                    }
                                    ?>
                                </td>

                                <td>
                                    <?php if ( $existing_post_id ) : ?>
                                        <span class="gdi-status-imported">✓ Imported</span>
                                    <?php else : ?>
                                        <span class="gdi-status-new">New</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <form method="post" class="gdi-inline-form">
                                        <?php wp_nonce_field( 'gdi_import_doc', 'gdi_import_nonce' ); ?>

                                        <input type="hidden" name="gdi_import_doc_id" value="<?php echo esc_attr( $doc['id'] ?? '' ); ?>">
                                        <input type="hidden" name="gdi_return_search" value="<?php echo esc_attr( $search_query ); ?>">

                                        <button type="submit" class="button button-primary">
                                            <?php echo $existing_post_id ? 'Update' : 'Import'; ?>
                                        </button>
                                    </form>

                                    <?php if ( ! empty( $edit_post_link ) ) : ?>
                                        <a href="<?php echo esc_url( $edit_post_link ); ?>" target="_blank" rel="noopener noreferrer" class="button">
                                            Edit Post
                                        </a>
                                    <?php endif; ?>

                                    <?php if ( ! empty( $doc['webViewLink'] ) ) : ?>
                                        <a href="<?php echo esc_url( $doc['webViewLink'] ); ?>" target="_blank" rel="noopener noreferrer" class="button">
                                            Open Doc
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php endif; ?>

    <?php endif; ?>

    <?php if ( 'settings' === $active_tab ) : ?>

        <div class="gdi-status-panel">
            <h2>Settings</h2>

            <form method="post" action="options.php">
                <?php settings_fields( 'gdi_settings' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="gdi_client_id">Google Client ID</label></th>
                        <td>
                            <input type="text" id="gdi_client_id" name="gdi_client_id" value="<?php echo esc_attr( $client_id ); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="gdi_client_secret">Google Client Secret</label></th>
                        <td>
                            <input type="password" id="gdi_client_secret" name="gdi_client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>

                <?php submit_button( 'Save Credentials' ); ?>
            </form>

            <hr>

            <h2>Google Connection</h2>

            <p>
                <span class="gdi-status-dot" style="background:<?php echo esc_attr( $status_color ); ?>;"></span>
                <strong><?php echo esc_html( $status_text ); ?></strong>
            </p>

            <?php if ( $is_connected && ! empty( $last_connected ) ) : ?>
                <p><strong>Last connected:</strong> <?php echo esc_html( $last_connected ); ?></p>
            <?php endif; ?>

            <?php if ( ! $can_connect ) : ?>

                <p>Please save both your Google Client ID and Client Secret before connecting.</p>

                <button type="button" class="button button-primary" disabled aria-disabled="true">
                    Connect to Google
                </button>

            <?php else : ?>

                <p>
                    <a class="button button-primary" href="<?php echo esc_url( $auth->get_auth_url() ); ?>">
                        <?php echo $is_connected ? 'Reconnect to Google' : 'Connect to Google'; ?>
                    </a>
                </p>

            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>