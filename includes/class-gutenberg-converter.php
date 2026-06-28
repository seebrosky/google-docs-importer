<?php

defined( 'ABSPATH' ) || exit;

class GDI_Gutenberg_Converter {

    private $first_image_id = 0;

    public function convert( array $doc, $post_id = 0, $skip_first_image = false ) {
        $this->first_image_id = 0;

        return $this->convert_doc_to_blocks( $doc, $post_id, $skip_first_image );
    }

    public function get_first_image_id() {
        return absint( $this->first_image_id );
    }

    private function convert_doc_to_blocks( array $doc, $post_id = 0, $skip_first_image = false ) {
        $blocks           = '';
        $list_items       = [];
        $in_list          = false;
        $list_definitions = $doc['lists'] ?? [];
        $image_importer   = new GDI_Image_Importer();

        foreach ( $doc['body']['content'] as $item ) {
            if ( ! empty( $item['table'] ) ) {
                if ( $in_list && ! empty( $list_items ) ) {
                    $blocks .= $this->get_list_block( $list_items );

                    $list_items = [];
                    $in_list    = false;
                }

                $blocks .= $this->get_table_block( $item['table'] );
                continue;
            }

            if ( empty( $item['paragraph']['elements'] ) ) {
                continue;
            }

            $paragraph    = $item['paragraph'];
            $text         = '';
            $image_blocks = '';

            foreach ( $paragraph['elements'] as $element ) {
                if ( ! empty( $element['horizontalRule'] ) ) {
                    $text .= '__GDI_HORIZONTAL_RULE__';
                    continue;
                }

                if ( ! empty( $element['textRun']['content'] ) ) {
                    $text .= $this->get_text_run_html( $element['textRun'] );
                }

                if ( ! empty( $element['inlineObjectElement']['inlineObjectId'] ) ) {
                    $inline_object_id = $element['inlineObjectElement']['inlineObjectId'];
                    $image_url        = $this->get_inline_image_url( $doc, $inline_object_id );

                    if ( ! empty( $image_url ) ) {
                        $attachment_id = $image_importer->import_from_url( $image_url, $post_id, $inline_object_id );

                        if ( ! is_wp_error( $attachment_id ) ) {
                            $is_first_image = empty( $this->first_image_id );

                            if ( $is_first_image ) {
                                $this->first_image_id = absint( $attachment_id );
                            }

                            if ( $is_first_image && $skip_first_image ) {
                                continue;
                            }

                            $image_blocks .= $image_importer->get_image_block( $attachment_id );
                        }
                    }
                }
            }

            $text       = trim( $text );
            $plain_text = trim( wp_strip_all_tags( $text ) );

            if ( '__GDI_HORIZONTAL_RULE__' === $plain_text ) {

                if ( $in_list && ! empty( $list_items ) ) {
                    $blocks .= $this->get_list_block( $list_items );

                    $list_items = [];
                    $in_list    = false;
                }

                $blocks .= "<!-- wp:separator -->\n";
                $blocks .= "<hr class=\"wp-block-separator has-alpha-channel-opacity\" />\n";
                $blocks .= "<!-- /wp:separator -->\n\n";

                continue;
            }

            if ( $in_list && ! empty( $list_items ) && ! empty( $image_blocks ) ) {
                $blocks .= $this->get_list_block( $list_items );

                $list_items = [];
                $in_list    = false;
            }

            if ( empty( $plain_text ) && ! empty( $image_blocks ) ) {
                $blocks .= $image_blocks;
                continue;
            }

            if ( empty( $plain_text ) ) {
                continue;
            }

            if ( ! empty( $paragraph['bullet'] ) ) {
                $list_level = $this->get_list_level( $paragraph );
                $list_type  = $this->get_list_type( $paragraph, $list_definitions, $list_level );

                $list_items[] = [
                    'content' => '<li>' . wp_kses_post( $text ),
                    'level'   => $list_level,
                    'type'    => $list_type,
                ];

                $in_list = true;

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
                    wp_kses_post( $text ),
                    $level
                );

                $blocks .= $image_blocks;

                continue;
            }

            $blocks .= "<!-- wp:paragraph -->\n";
            $blocks .= '<p>' . wp_kses_post( $text ) . "</p>\n";
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

    private function get_text_run_html( array $text_run ) {
        $content = $text_run['content'] ?? '';

        if ( '' === $content ) {
            return '';
        }

        $content    = esc_html( $content );
        $text_style = $text_run['textStyle'] ?? [];

        $content = $this->apply_text_styles( $content, $text_style );

        $url = $text_style['link']['url'] ?? '';

        if ( ! empty( $url ) ) {
            return sprintf(
                '<a href="%s">%s</a>',
                esc_url( $url ),
                $content
            );
        }

        return $content;
    }

    private function apply_text_styles( $content, array $text_style ) {
        if ( '' === $content ) {
            return '';
        }

        if ( ! empty( $text_style['bold'] ) ) {
            $content = '<strong>' . $content . '</strong>';
        }

        if ( ! empty( $text_style['italic'] ) ) {
            $content = '<em>' . $content . '</em>';
        }

        if ( ! empty( $text_style['underline'] ) ) {
            $content = '<u>' . $content . '</u>';
        }

        if ( ! empty( $text_style['strikethrough'] ) ) {
            $content = '<s>' . $content . '</s>';
        }

        return $content;
    }

    private function get_table_block( array $table ) {
        if ( empty( $table['tableRows'] ) ) {
            return '';
        }

        $rows           = $table['tableRows'];
        $has_header_row = $this->is_table_header_row( $rows[0] );

        $html  = "<!-- wp:table -->\n";
        $html .= "<figure class=\"wp-block-table\"><table>\n";

        if ( $has_header_row ) {
            $html .= "<thead>\n";
            $html .= $this->get_table_row_html( $rows[0], 'th' );
            $html .= "</thead>\n";

            $rows = array_slice( $rows, 1 );
        }

        $html .= "<tbody>\n";

        foreach ( $rows as $row ) {
            $html .= $this->get_table_row_html( $row, 'td' );
        }

        $html .= "</tbody></table></figure>\n";
        $html .= "<!-- /wp:table -->\n\n";

        return $html;
    }

    private function get_table_row_html( array $row, $cell_tag = 'td' ) {
        $cell_tag = in_array( $cell_tag, [ 'td', 'th' ], true ) ? $cell_tag : 'td';

        $html = "<tr>\n";

        foreach ( $row['tableCells'] ?? [] as $cell ) {
            $is_header = 'th' === $cell_tag;
            $cell_text = $this->get_table_cell_html( $cell, $is_header );

            $html .= '<' . $cell_tag . '>' . wp_kses_post( $cell_text ) . '</' . $cell_tag . ">\n";
        }

        $html .= "</tr>\n";

        return $html;
    }

    private function is_table_header_row( array $row ) {
        $cells = $row['tableCells'] ?? [];

        if ( empty( $cells ) ) {
            return false;
        }

        foreach ( $cells as $cell ) {
            if ( ! $this->cell_has_bold_text( $cell ) ) {
                return false;
            }
        }

        return true;
    }

    private function cell_has_bold_text( array $cell ) {
        foreach ( $cell['content'] ?? [] as $item ) {
            foreach ( $item['paragraph']['elements'] ?? [] as $element ) {
                if ( ! empty( $element['textRun']['content'] ) ) {
                    return ! empty( $element['textRun']['textStyle']['bold'] );
                }
            }
        }

        return false;
    }    

    private function get_table_cell_html( array $cell, $is_header = false ) {
        $content = '';

        foreach ( $cell['content'] ?? [] as $item ) {
            if ( empty( $item['paragraph']['elements'] ) ) {
                continue;
            }

            $text = '';

            foreach ( $item['paragraph']['elements'] as $element ) {
                if ( ! empty( $element['textRun']['content'] ) ) {
                    $text .= $this->get_text_run_html( $element['textRun'] );
                }
            }

            $text = trim( $text );

            if ( '' !== $text ) {
                $content .= $is_header
                    ? wp_kses_post( $text )
                    : '<p>' . wp_kses_post( $text ) . '</p>';
            }
        }

        return $content;
    }

    private function get_list_block( array $list_items ) {
        if ( empty( $list_items ) ) {
            return '';
        }

        $root_type = $list_items[0]['type'] ?? 'ul';
        $root_type = in_array( $root_type, [ 'ul', 'ol' ], true ) ? $root_type : 'ul';

        $index = 0;

        $block  = '<!-- wp:list';
        $block .= 'ol' === $root_type ? ' {"ordered":true}' : '';
        $block .= " -->\n";
        $block .= $this->build_nested_list_html( $list_items, $index, 0, $root_type );
        $block .= "<!-- /wp:list -->\n\n";

        return $block;
    }

    private function build_nested_list_html( array $list_items, &$index, $level, $list_type ) {
        $list_type = in_array( $list_type, [ 'ul', 'ol' ], true ) ? $list_type : 'ul';

        $html = '<' . $list_type . ">\n";

        while ( $index < count( $list_items ) ) {
            $item_level = (int) ( $list_items[ $index ]['level'] ?? 0 );

            if ( $item_level < $level ) {
                break;
            }

            if ( $item_level > $level ) {
                break;
            }

            $content = $list_items[ $index ]['content'] ?? '<li>';
            $html   .= $content;

            $index++;

            while ( $index < count( $list_items ) && (int) ( $list_items[ $index ]['level'] ?? 0 ) > $level ) {
                $child_level = (int) ( $list_items[ $index ]['level'] ?? 0 );
                $child_type  = $list_items[ $index ]['type'] ?? 'ul';

                $html .= "\n" . $this->build_nested_list_html( $list_items, $index, $child_level, $child_type );
            }

            $html .= "</li>\n";
        }

        $html .= '</' . $list_type . ">\n";

        return $html;
    }

    private function get_list_level( array $paragraph ) {
        return isset( $paragraph['bullet']['nestingLevel'] )
            ? absint( $paragraph['bullet']['nestingLevel'] )
            : 0;
    }

    private function get_list_type( array $paragraph, array $list_definitions, $level = 0 ) {
        $list_id = $paragraph['bullet']['listId'] ?? '';

        if ( empty( $list_id ) || empty( $list_definitions[ $list_id ] ) ) {
            return 'ul';
        }

        $nesting_levels = $list_definitions[ $list_id ]['listProperties']['nestingLevels'] ?? [];
        $glyph          = $nesting_levels[ $level ]['glyphType'] ?? '';

        $ordered_glyphs = [
            'DECIMAL',
            'ZERO_DECIMAL',
            'UPPER_ALPHA',
            'LOWER_ALPHA',
            'UPPER_ROMAN',
            'LOWER_ROMAN',
        ];

        if ( in_array( $glyph, $ordered_glyphs, true ) ) {
            return 'ol';
        }

        $parent_glyph = $nesting_levels[0]['glyphType'] ?? '';

        return in_array( $parent_glyph, $ordered_glyphs, true ) ? 'ol' : 'ul';
    }
}