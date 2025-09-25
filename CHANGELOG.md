# Changelog

All notable changes to CharWash will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-09-25

### Added
- Initial release of CharWash text sanitization package
- UnicodeProcessor for NFC normalization and invisible character removal
- HtmlProcessor with HTMLPurifier integration for security-focused HTML cleaning
- OfficeProcessor for cleaning Word/Outlook artifacts and fixing encoding issues
- PunctuationProcessor for normalizing smart quotes, dashes, and ligatures
- Configurable processor system with CharWashConfig
- Laravel integration with auto-discovery support
- Magento 2 integration examples
- Comprehensive test suite with 35 tests
- Full documentation and usage examples

### Features
- Complete text sanitization with multiple processors
- Modular processing for targeted cleanup
- Unicode normalization (NFC) and invisible character removal
- HTML purification with XSS protection
- Office/email paste cleanup (MSO styles, conditional comments, mojibake)
- Smart typography normalization
- Configurable for Laravel and Magento platforms
- Performance optimized for large content processing

[Unreleased]: https://github.com/odindev/charwash/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/odindev/charwash/releases/tag/v1.0.0
