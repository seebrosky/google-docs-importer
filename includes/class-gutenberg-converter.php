<?php

defined( 'ABSPATH' ) || exit;

class GDI_Gutenberg_Converter {

    public function convert( array $doc ) {
        return $this->convert_doc_to_blocks( $doc );
    }

    private function convert_doc_to_blocks( array $doc ) {
        $blocks     = '';
        $list_items = [];
        $in_list    = false;

        foreach ( $doc['body']['content'] as $item ) {
            if ( empty( $item['paragraph']['elements'] ) ) {
                continue;
            }

            $paragraph = $item['paragraph'];
            $text      = '';

            foreach ( $paragraph['elements'] as $element ) {
                if ( ! empty( $element['textRun']['content'] ) ) {
                    $text .= $element['textRun']['content'];
                }
            }

            $text = trim( $text );

            if ( empty( $text ) ) {
                continue;
            }

            if ( ! empty( $paragraph['bullet'] ) ) {
                $list_items[] = '<li>' . esc_html( $text ) . '</li>';
                $in_list      = true;
                continue;
            }

            if ( $in_list && ! empty( $list_items ) ) {
                $blocks .= "<!-- wp:list -->\n";
                $blocks .= "<ul>\n" . implode( "\n", $list_items ) . "\n</ul>\n";
                $blocks .= "<!-- /wp:list -->\n\n";

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

                continue;
            }

            $blocks .= "<!-- wp:paragraph -->\n";
            $blocks .= '<p>' . esc_html( $text ) . "</p>\n";
            $blocks .= "<!-- /wp:paragraph -->\n\n";
        }

        if ( $in_list && ! empty( $list_items ) ) {
            $blocks .= "<!-- wp:list -->\n";
            $blocks .= "<ul>\n" . implode( "\n", $list_items ) . "\n</ul>\n";
            $blocks .= "<!-- /wp:list -->\n\n";
        }

        return $blocks;
    }
}