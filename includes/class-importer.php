<?php

defined( 'ABSPATH' ) || exit;

class GDI_Importer {

    const GOOGLE_DOC_ID_META_KEY = '_gdi_google_doc_id';
    const LAST_IMPORTED_META_KEY = '_gdi_last_imported_at';
    const CONTENT_HASH_META_KEY  = '_gdi_content_hash';

    public function import( $document_id, $post_type = 'post', $category_id = 0 ) {
        $google_docs = new GDI_Google_Docs();
        $doc         = $google_docs->fetch( $document_id );

        if ( is_wp_error( $doc ) ) {
            return $doc;
        }

        $existing_post_id = $this->get_post_id_by_document_id( $document_id );

        /*
         * Posts use the featured image in the hero, so skip the first
         * inline image in the content. Pages keep the first image.
         */
        $import_post_type = $existing_post_id
            ? get_post_type( $existing_post_id )
            : $post_type;

        $skip_first_image = 'post' === $import_post_type;

        $converter         = new GDI_Gutenberg_Converter();
        $content           = $converter->convert( $doc, $existing_post_id, $skip_first_image );
        $content_hash      = md5( $content );
        $featured_image_id = $converter->get_first_image_id();

        if ( $existing_post_id ) {
            $post_id = wp_update_post(
                [
                    'ID'           => $existing_post_id,
                    'post_title'   => sanitize_text_field( $doc['title'] ?? 'Imported Google Doc' ),
                    'post_content' => $content,
                ],
                true
            );

            if ( is_wp_error( $post_id ) ) {
                return $post_id;
            }

            $this->maybe_set_featured_image( $post_id, $featured_image_id );
            $this->maybe_set_post_category( $post_id, $category_id );

            update_post_meta( $post_id, self::LAST_IMPORTED_META_KEY, time() );
            update_post_meta( $post_id, self::CONTENT_HASH_META_KEY, $content_hash );

            return [
                'post_id' => absint( $post_id ),
                'action'  => 'updated',
            ];
        }

        $post_type = in_array( $post_type, [ 'post', 'page' ], true ) ? $post_type : 'post';

        $post_id = wp_insert_post(
            [
                'post_title'   => sanitize_text_field( $doc['title'] ?? 'Imported Google Doc' ),
                'post_content' => $content,
                'post_status'  => 'draft',
                'post_type'    => $post_type,
            ],
            true
        );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        $this->maybe_set_featured_image( $post_id, $featured_image_id );
        $this->maybe_set_post_category( $post_id, $category_id );

        update_post_meta( $post_id, self::GOOGLE_DOC_ID_META_KEY, sanitize_text_field( $document_id ) );
        update_post_meta( $post_id, self::LAST_IMPORTED_META_KEY, time() );
        update_post_meta( $post_id, self::CONTENT_HASH_META_KEY, $content_hash );

        return [
            'post_id' => absint( $post_id ),
            'action'  => 'imported',
        ];
    }

    public function get_post_id_by_document_id( $document_id ) {
        $query = new WP_Query(
            [
                'post_type'      => 'any',
                'post_status'    => [ 'draft', 'publish', 'pending', 'future', 'private' ],
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => [
                    [
                        'key'   => self::GOOGLE_DOC_ID_META_KEY,
                        'value' => sanitize_text_field( $document_id ),
                    ],
                ],
            ]
        );

        return ! empty( $query->posts ) ? absint( $query->posts[0] ) : 0;
    }

    public function get_last_imported_at( $post_id ) {
        return (int) get_post_meta( $post_id, self::LAST_IMPORTED_META_KEY, true );
    }

    public function is_update_available( $post_id, $document_id ) {
        if ( empty( $post_id ) || empty( $document_id ) ) {
            return false;
        }

        $saved_hash = get_post_meta( $post_id, self::CONTENT_HASH_META_KEY, true );

        if ( empty( $saved_hash ) ) {
            return true;
        }

        $google_docs = new GDI_Google_Docs();
        $doc         = $google_docs->fetch( $document_id );

        if ( is_wp_error( $doc ) ) {
            return false;
        }

        $import_post_type = get_post_type( $post_id );
        $skip_first_image = 'post' === $import_post_type;

        $converter    = new GDI_Gutenberg_Converter();
        $content      = $converter->convert( $doc, $post_id, $skip_first_image );
        $current_hash = md5( $content );

        return $current_hash !== $saved_hash;
    }

    private function maybe_set_featured_image( $post_id, $attachment_id ) {
        if ( empty( $post_id ) || empty( $attachment_id ) ) {
            return;
        }

        if ( has_post_thumbnail( $post_id ) ) {
            return;
        }

        set_post_thumbnail( $post_id, $attachment_id );
    }

    private function maybe_set_post_category( $post_id, $category_id ) {
        if ( empty( $post_id ) || empty( $category_id ) ) {
            return;
        }

        if ( 'post' !== get_post_type( $post_id ) ) {
            return;
        }

        wp_set_post_categories( $post_id, [ absint( $category_id ) ] );
    }
}