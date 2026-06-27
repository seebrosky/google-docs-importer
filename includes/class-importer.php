<?php

defined( 'ABSPATH' ) || exit;

class GDI_Importer {

    const GOOGLE_DOC_ID_META_KEY = '_gdi_google_doc_id';
    const LAST_IMPORTED_META_KEY = '_gdi_last_imported_at';

    public function import( $document_id ) {
        $google_docs = new GDI_Google_Docs();
        $doc         = $google_docs->fetch( $document_id );

        if ( is_wp_error( $doc ) ) {
            return $doc;
        }

        $converter = new GDI_Gutenberg_Converter();
        $content   = $converter->convert( $doc );

        $existing_post_id = $this->get_post_id_by_document_id( $document_id );

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

            update_post_meta( $post_id, self::LAST_IMPORTED_META_KEY, time() );

            return [
                'post_id' => absint( $post_id ),
                'action'  => 'updated',
            ];
        }

        $post_id = wp_insert_post(
            [
                'post_title'   => sanitize_text_field( $doc['title'] ?? 'Imported Google Doc' ),
                'post_content' => $content,
                'post_status'  => 'draft',
                'post_type'    => 'post',
            ],
            true
        );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        update_post_meta( $post_id, self::GOOGLE_DOC_ID_META_KEY, sanitize_text_field( $document_id ) );
        update_post_meta( $post_id, self::LAST_IMPORTED_META_KEY, time() );

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
}