# Google Docs Importer

Import Google Docs directly into WordPress as native Gutenberg blocks.

## Features

- Secure OAuth authentication with Google
- Search Google Docs directly from the WordPress admin
- Import documents as Posts or Pages
- Update previously imported documents
- Detect content changes before updating
- Convert Google Docs into native Gutenberg blocks
- Preserve Google Docs headings, paragraphs, bulleted lists, tables, hyperlinks, and rich text formatting
- Preserve bold, italic, underline, and strikethrough formatting
- Import inline images into the WordPress Media Library
- Prevent duplicate media uploads using Google image IDs and SHA-256 file hashes
- Automatically convert imported images to WebP
- Resize imported images to a maximum width of 2000px
- Automatically set the first imported image as the featured image
- Prevent duplicate hero images for Posts while preserving the first image in Pages
- Automatically refresh expired Google access tokens
- Native WordPress admin interface

## Requirements

- WordPress 6.8+
- PHP 8.1+

## Installation

1. Install and activate the plugin.
2. Create a Google Cloud project.
3. Enable the **Google Docs API** and **Google Drive API**.
4. Create OAuth 2.0 credentials.
5. Enter your Client ID and Client Secret in the plugin settings.
6. Connect your Google account.
7. Search for a Google Doc and import it into WordPress.

## Roadmap

Planned features include:

- Ordered list support
- Nested list support
- Blockquote support
- Horizontal rule support
- Image captions and improved alt text support
- Scheduled document synchronization
- Bulk document synchronization

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a complete history of changes.