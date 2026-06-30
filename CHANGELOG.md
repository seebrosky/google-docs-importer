## [1.3.0] - 2026-06-29

### Added
- Select a WordPress category when importing new Posts from Google Docs.
- Display all available WordPress categories in the import workflow.

### Improved
- Automatically assign the selected category during new Post imports and existing Post updates.
- Show the category selector only when importing as a Post.
- Refined the admin import interface with improved form layout and styling.
- Renamed the admin JavaScript bundle to better reflect its expanded functionality.

## [1.2.0] - 2026-06-28

### Added
- Sort Google Docs search results by document title, modified date, status, and post type.
- Added client-side sorting for search results without requiring page reloads.

### Improved
- Refined the admin results table with native sortable column controls.
- Improved sort indicators using custom SVG icons for active and inactive sort states.
- Enhanced the visual presentation of imported Gutenberg tables with improved borders, spacing, and typography.

## [1.1.0] - 2026-06-28

### Added
- Import Google Docs horizontal rules as native Gutenberg Separator blocks.

### Improved
- Standardized imported horizontal rules to match the active theme's design system.
- Preserved table header rows using native Gutenberg `<thead>` and `<th>` markup when a bold header row is detected.
- Improved content fidelity for documents containing visual section dividers.

## [1.0.0] - 2026-06-28

### Added
- Import ordered Google Docs lists as native Gutenberg ordered lists.
- Preserve nested ordered and unordered list structures during import.
- Preserve Google Docs list hierarchy using native nested Gutenberg lists.

### Improved
- Enhanced list conversion to detect Google Docs list types and nesting levels.
- Improved content fidelity for complex documents containing mixed ordered and unordered lists.

## [0.9.0] - 2026-06-28

### Added
- Import Google Docs tables as native Gutenberg Table blocks.
- Preserve rich text formatting and hyperlinks within table cells.

### Improved
- Extended the document converter to support multiple Google Docs content types beyond paragraphs.
- Improved content fidelity for documents containing structured tabular data.

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
- Automatically convert imported images to WebP.
- Resize imported images to a maximum width of 2000px.
- Automatically set the first imported image as the featured image.
- Prevent duplicate hero images for Posts while preserving the first image in Pages.

### Improved
- Enhanced image import performance and optimization.
- Improved media handling during repeated document imports.

## [0.5.0] - 2026-06-27

### Added
- Prevent duplicate media uploads using Google inline object IDs.
- Added SHA-256 hash comparison to detect previously imported images.

### Improved
- Reduced duplicate Media Library uploads across repeated document imports.
- Improved image matching accuracy during document updates.

## [0.4.0] - 2026-06-27

### Added
- Import inline Google Docs images into the WordPress Media Library.
- Preserve image placement within imported Gutenberg content.

### Improved
- Expanded document conversion beyond text-only content.

## [0.3.0] - 2026-06-27

### Added
- Detect document changes before updating existing WordPress content.
- Update previously imported Posts and Pages directly from Google Docs.

### Improved
- Streamlined the document synchronization workflow.

## [0.2.0] - 2026-06-27

### Added
- Search Google Docs directly from the WordPress admin.
- Import Google Docs as WordPress Posts or Pages.
- Automatically refresh expired Google OAuth access tokens.

### Improved
- Enhanced the Google authentication workflow and document import experience.

## [0.1.0] - 2026-06-27

### Added
- Initial release.
- Secure OAuth 2.0 authentication with Google.
- Native WordPress admin interface.
- Google Cloud credential management.
- Import Google Docs as native Gutenberg paragraphs and headings.
- Support for updating existing imported documents.