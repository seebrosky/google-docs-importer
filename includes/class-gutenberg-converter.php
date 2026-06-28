<?php

defined( 'ABSPATH' ) || exit;

class GDI_Gutenberg_Converter {

    private $first_image_id = 0;

    public function convert( array $doc, $post_id = 0 ) {
        $this->first_image_id = 0;

        return $this->convert_doc_to_blocks( $doc, $post_id );
    }

    public function get_first_image_id() {
        return absint( $this->first_image_id );
    }

    private function convert_doc_to_blocks( array $doc, $post_id = 0 ) {
        $blocks         = '';
        $list_items     = [];
        $in_list        = false;
        $image_importer = new GDI_Image_Importer();

        foreach ( $doc['body']['content'] as $item ) {
            if ( empty( $item['paragraph']['elements'] ) ) {
                continue;
            }

            $paragraph    = $item['paragraph'];
            $text         = '';
            $image_blocks = '';

            foreach ( $paragraph['elements'] as $element ) {
                if ( ! empty( $element['textRun']['content'] ) ) {
                    $text .= $element['textRun']['content'];
                }

                if ( ! empty( $element['inlineObjectElement']['inlineObjectId'] ) ) {
                    $inline_object_id = $element['inlineObjectElement']['inlineObjectId'];
                    $image_url        = $this->get_inline_image_url( $doc, $inline_object_id );

                    if ( ! empty( $image_url ) ) {
                        $attachment_id = $image_importer->import_from_url( $image_url, $post_id, $inline_object_id );

                        if ( ! is_wp_error( $attachment_id ) ) {
                            if ( empty( $this->first_image_id ) ) {
                                $this->first_image_id = absint( $attachment_id );
                            }

                            $image_blocks .= $image_importer->get_image_block( $attachment_id );
                        }
                    }
                }
            }

            $text = trim( $text );

            if ( $in_list && ! empty( $list_items ) && ! empty( $image_blocks ) ) {
                $blocks .= $this->get_list_block( $list_items );

                $list_items = [];
                $in_list    = false;
            }

            if ( empty( $text ) && ! empty( $image_blocks ) ) {
                $blocks .= $image_blocks;
                continue;
            }

            if ( empty( $text ) ) {
                continue;
            }

            if ( ! empty( $paragraph['bullet'] ) ) {
                $list_items[] = '<li>' . esc_html( $text ) . '</li>';
                $in_list      = true;

                if ( ! empty( $image_blocks ) ) {
                    $blocks .= $this->get_list_block( $list_items );
                    $blocks .= $image_blocks;

                    $list_items = [];
                    $in_list    = false;
                }

                continue;
            }

            if ( $in_list && ! empty( $list_items ) ) {
                $blocks .= $this->get_list_block( $list_items );

                $list_items = [];
                $in_list    = false;
            }

            $style = $paragraph['paragraphStyle']['namedStyleType'] ?? '';

            if ( in_array( $style, [ 'HEADING_1', 'HEADING_2', 'HEADING_3', 'HEADING_4', 'HEADING_5', 'HEADING_6' ], true ) ) {
                $level = (int) str_replace( 'HEADING_', '', $style );

                $blocks .= sprintf(
                    "<!-- wp:heading {\"level\":%d} -->\n<h%d class=\"wp-block-heading\">%s</h%d>\n<!-- /wp:heading -->\n\n",
                    $level,
                    $level,
                    esc_html( $text ),
                    $level
                );

                $blocks .= $image_blocks;

                continue;
            }

            $blocks .= "<!-- wp:paragraph -->\n";
            $blocks .= '<p>' . esc_html( $text ) . "</p>\n";
            $blocks .= "<!-- /wp:paragraph -->\n\n";

            $blocks .= $image_blocks;
        }

        if ( $in_list && ! empty( $list_items ) ) {
            $blocks .= $this->get_list_block( $list_items );
        }

        return $blocks;
    }

    private function get_inline_image_url( array $doc, $inline_object_id ) {
        return $doc['inlineObjects'][ $inline_object_id ]['inlineObjectProperties']['embeddedObject']['imageProperties']['contentUri'] ?? '';
    }

    private function get_list_block( array $list_items ) {
        $block  = "<!-- wp:list -->\n";
        $block .= "<ul>\n" . implode( "\n", $list_items ) . "\n</ul>\n";
        $block .= "<!-- /wp:list -->\n\n";

        return $block;
    }
}