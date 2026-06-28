<?php

defined( 'ABSPATH' ) || exit;

class GDI_Google_Docs {

    public function search( $query ) {
        $auth         = new GDI_Google_Auth();
        $access_token = $auth->get_access_token();

        if ( empty( $access_token ) ) {
            return new WP_Error( 'not_connected', 'Google is not connected.' );
        }

        $drive_query = sprintf(
            "mimeType='application/vnd.google-apps.document' and name contains '%s' and trashed=false",
            str_replace( "'", "\\'", $query )
        );

        $url = add_query_arg(
            [
                'q'        => $drive_query,
                'fields'   => 'files(id,name,webViewLink,modifiedTime)',
                'pageSize' => 10,
            ],
            'https://www.googleapis.com/drive/v3/files'
        );

        $response = wp_remote_get(
            $url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return ! empty( $body['files'] ) ? $body['files'] : [];
    }

    public function fetch( $document_id ) {
        $auth         = new GDI_Google_Auth();
        $access_token = $auth->get_access_token();

        if ( empty( $access_token ) ) {
            return new WP_Error( 'not_connected', 'Google is not connected.' );
        }

        $response = wp_remote_get(
            add_query_arg(
                [
                    'fields' => implode(
                        ',',
                        [
                            'title',
                            'body/content/paragraph/elements/textRun/content',
                            'body/content/paragraph/elements/textRun/textStyle',
                            'body/content/paragraph/elements/inlineObjectElement/inlineObjectId',
                            'body/content/paragraph/elements/horizontalRule',
                            'body/content/paragraph/paragraphStyle/namedStyleType',
                            'body/content/paragraph/paragraphStyle/indentStart',
                            'body/content/paragraph/bullet',
                            'body/content/paragraph/bullet/nestingLevel',
                            'lists',
                            'body/content/table/tableRows/tableCells/content/paragraph/elements/textRun/content',
                            'body/content/table/tableRows/tableCells/content/paragraph/elements/textRun/textStyle',
                            'inlineObjects',
                        ]
                    ),
                ],
                sprintf(
                    'https://docs.googleapis.com/v1/documents/%s',
                    rawurlencode( $document_id )
                )
            ),
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['body']['content'] ) ) {
            return new WP_Error( 'empty_doc', 'No document content found.' );
        }

        return $body;
    }
}