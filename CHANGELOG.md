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