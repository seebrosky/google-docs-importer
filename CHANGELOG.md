# Changelog

All notable changes to this project will be documented in this file.

## [0.8.0] - 2026-06-28

### Added
- Preserve rich text formatting from Google Docs, including bold, italic, underline, and strikethrough.
- Import inline hyperlinks as native Gutenberg links.

### Improved
- Refactored inline text processing to support extensible text formatting.
- Added support for Google Docs `textStyle` metadata during document imports.
- Improved content fidelity so imported documents more closely match their Google Docs source.

## [0.7.0] - 2026-06-28

### Added
- Import hyperlinks from Google Docs into native Gutenberg content.
- Preserve inline links during document updates.

### Improved
- Replaced status dots with semantic status icons for improved accessibility and usability.
- Improved admin interface consistency and button focus states.

## [0.6.0] - 2026-06-27

### Added
- Import inline images from Google Docs.
- Automatically convert imported images to WebP.
- Resize imported images to a maximum width of 2000px.
- Automatically set the first imported image as the featured image.
- Prevent duplicate hero images for Posts while preserving the first image in Pages.
- Detect duplicate images using both Google image IDs and SHA-256 file hashes.

### Improved
- Reuse existing media library images during document updates.
- Improve image import reliability when Google changes inline object IDs.
- Optimize imported images for smaller file sizes and better performance.

## [0.5.0]

### Added
- Featured image support.
- Automatic WebP image conversion.

## [0.4.0]

### Added
- Inline image importing.

## [0.3.0]

### Added
- Post/Page import option.
- Content hash update detection.

## [0.2.0]

### Added
- Google Docs search.
- Document import.

## [0.1.0]

### Added
- Initial OAuth authentication.
- Basic Google Docs importer.