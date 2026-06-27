<?php

defined( 'ABSPATH' ) || exit;

class GDI_Importer {

    public function import( $document_id ) {
        $google_docs = new GDI_Google_Docs();
        $doc         = $google_docs->fetch( $document_id );

        if ( is_wp_error( $doc ) ) {
            return $doc;
        }

        $converter = new GDI_Gutenberg_Converter();
        $content   = $converter->convert( $doc );

        return wp_insert_post(
            [
                'post_title'   => sanitize_text_field( $doc['title'] ?? 'Imported Google Doc' ),
                'post_content' => $content,
                'post_status'  => 'draft',
                'post_type'    => 'post',
            ],
            true
        );
    }
}