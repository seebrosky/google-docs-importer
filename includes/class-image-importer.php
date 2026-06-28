<?php

defined( 'ABSPATH' ) || exit;

class GDI_Image_Importer {

    const IMAGE_KEY_META_KEY        = '_gdi_google_image_key';
    const IMAGE_HASH_META_KEY       = '_gdi_google_image_hash';
    const IMAGE_SOURCE_URL_META_KEY = '_gdi_google_image_source_url';
    const IMAGE_FILE_HASH_META_KEY  = '_gdi_google_image_file_hash';

    public function import_from_url( $image_url, $post_id = 0, $image_id = '' ) {
        if ( empty( $image_url ) ) {
            return new WP_Error( 'missing_image_url', 'Missing image URL.' );
        }

        $image_url  = esc_url_raw( $image_url );
        $image_hash = md5( $image_url );
        $image_key  = ! empty( $image_id ) ? sanitize_text_field( $image_id ) : $image_hash;

        $existing_attachment_id = $this->get_attachment_id_by_image_key( $image_key );

        if ( $existing_attachment_id ) {
            return $existing_attachment_id;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url( $image_url );

        if ( is_wp_error( $tmp ) ) {
            return $tmp;
        }

        $file_hash = file_exists( $tmp ) ? hash_file( 'sha256', $tmp ) : '';

        $existing_attachment_id = $this->get_attachment_id_by_file_hash( $file_hash );

        if ( $existing_attachment_id ) {
            update_post_meta( $existing_attachment_id, self::IMAGE_KEY_META_KEY, $image_key );
            update_post_meta( $existing_attachment_id, self::IMAGE_HASH_META_KEY, $image_hash );
            update_post_meta( $existing_attachment_id, self::IMAGE_FILE_HASH_META_KEY, $file_hash );
            update_post_meta( $existing_attachment_id, self::IMAGE_SOURCE_URL_META_KEY, $image_url );

            @unlink( $tmp );

            return $existing_attachment_id;
        }

        $file_array = [
            'name'     => $this->get_filename(),
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload( $file_array, absint( $post_id ) );

        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            return $attachment_id;
        }

        $webp_attachment_id = $this->convert_attachment_to_webp( $attachment_id );

        if ( ! is_wp_error( $webp_attachment_id ) && ! empty( $webp_attachment_id ) ) {
            $attachment_id = $webp_attachment_id;
        }

        update_post_meta( $attachment_id, self::IMAGE_KEY_META_KEY, $image_key );
        update_post_meta( $attachment_id, self::IMAGE_HASH_META_KEY, $image_hash );
        update_post_meta( $attachment_id, self::IMAGE_FILE_HASH_META_KEY, $file_hash );
        update_post_meta( $attachment_id, self::IMAGE_SOURCE_URL_META_KEY, $image_url );

        return absint( $attachment_id );
    }

    public function get_image_block( $attachment_id ) {
        if ( empty( $attachment_id ) ) {
            return '';
        }

        $image_url = wp_get_attachment_url( $attachment_id );

        if ( empty( $image_url ) ) {
            return '';
        }

        $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

        return sprintf(
            "<!-- wp:image {\"id\":%d,\"sizeSlug\":\"large\",\"linkDestination\":\"none\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"%s\" alt=\"%s\" class=\"wp-image-%d\"/></figure>\n<!-- /wp:image -->\n\n",
            absint( $attachment_id ),
            esc_url( $image_url ),
            esc_attr( $alt ),
            absint( $attachment_id )
        );
    }

    private function convert_attachment_to_webp( $attachment_id ) {
        $file_path = get_attached_file( $attachment_id );

        if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
            return new WP_Error( 'missing_attachment_file', 'Attachment file not found.' );
        }

        $mime_type = get_post_mime_type( $attachment_id );

        if ( 'image/webp' === $mime_type ) {
            return $attachment_id;
        }

        if ( ! in_array( $mime_type, [ 'image/jpeg', 'image/png' ], true ) ) {
            return $attachment_id;
        }

        $editor = wp_get_image_editor( $file_path );

        if ( is_wp_error( $editor ) ) {
            return $editor;
        }

        $editor->set_quality( 82 );

        $size = $editor->get_size();

        if ( ! empty( $size['width'] ) && (int) $size['width'] > 2000 ) {
            $editor->resize( 2000, null, false );
        }

        $webp_path = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file_path );

        if ( empty( $webp_path ) || $webp_path === $file_path ) {
            return new WP_Error( 'invalid_webp_path', 'Could not generate WebP file path.' );
        }

        $saved = $editor->save( $webp_path, 'image/webp' );

        if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
            return is_wp_error( $saved ) ? $saved : new WP_Error( 'webp_save_failed', 'Could not save WebP image.' );
        }

        $upload_dir = wp_get_upload_dir();
        $relative_path = str_replace( trailingslashit( $upload_dir['basedir'] ), '', $saved['path'] );

        update_attached_file( $attachment_id, $relative_path );

        wp_update_post(
            [
                'ID'             => $attachment_id,
                'post_mime_type' => 'image/webp',
                'guid'           => trailingslashit( $upload_dir['baseurl'] ) . $relative_path,
            ]
        );

        $metadata = wp_generate_attachment_metadata( $attachment_id, $saved['path'] );
        wp_update_attachment_metadata( $attachment_id, $metadata );

        if ( file_exists( $file_path ) ) {
            @unlink( $file_path );
        }

        return $attachment_id;
    }

    private function get_attachment_id_by_image_key( $image_key ) {
        if ( empty( $image_key ) ) {
            return 0;
        }

        $query = new WP_Query(
            [
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => [
                    [
                        'key'   => self::IMAGE_KEY_META_KEY,
                        'value' => sanitize_text_field( $image_key ),
                    ],
                ],
            ]
        );

        return ! empty( $query->posts ) ? absint( $query->posts[0] ) : 0;
    }

    private function get_attachment_id_by_file_hash( $file_hash ) {
        if ( empty( $file_hash ) ) {
            return 0;
        }

        $query = new WP_Query(
            [
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => [
                    [
                        'key'   => self::IMAGE_FILE_HASH_META_KEY,
                        'value' => sanitize_text_field( $file_hash ),
                    ],
                ],
            ]
        );

        return ! empty( $query->posts ) ? absint( $query->posts[0] ) : 0;
    }

    private function get_filename() {
        return sprintf(
            'google-doc-image-%s.jpg',
            time()
        );
    }
}