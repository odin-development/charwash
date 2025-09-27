# CharWash

[![Latest Version on Packagist](https://img.shields.io/packagist/v/odindev/charwash.svg?style=flat-square)](https://packagist.org/packages/odindev/charwash)
[![Total Downloads](https://img.shields.io/packagist/dt/odindev/charwash.svg?style=flat-square)](https://packagist.org/packages/odindev/charwash)
[![License](https://img.shields.io/packagist/l/odindev/charwash.svg?style=flat-square)](LICENSE)
[![Tests](https://github.com/odin-development/charwash/actions/workflows/tests.yml/badge.svg)](https://github.com/odin-development/charwash/actions/workflows/tests.yml)
[![Lint](https://github.com/odin-development/charwash/actions/workflows/lint.yml/badge.svg)](https://github.com/odin-development/charwash/actions/workflows/lint.yml)

**CharWash** cleans messy text before it ever reaches your database.
It fixes broken characters, strips unsafe HTML, and removes the junk Word, Outlook, and web copy-pastes sneak in.
A standalone PHP library that works with any PHP project or framework.

---

## Quick Start (Lite Version)

### Install

```bash
composer require odindev/charwash
```

### Quick Example

```php
use OdinDev\CharWash\CharWash;

$clean = CharWash::sanitize($rawInput);
```

### Features

- Clean everything in one call, or target specific issues
- Normalize Unicode and remove invisible characters
- Sanitize and simplify HTML
- Strip Word/Outlook junk and fix encoding problems
- Flatten curly quotes, dashes, and ellipses
- Works with or without frameworks

### Why Use It?

- Stops messy copy-paste text from polluting your database
- Protects against unsafe HTML and hidden characters
- Keeps your content clean and consistent

---

## Table of Contents

- [CharWash](#charwash)
  - [Quick Start (Lite Version)](#quick-start-lite-version)
    - [Install](#install)
    - [Quick Example](#quick-example)
    - [Features](#features)
    - [Why Use It?](#why-use-it)
  - [Table of Contents](#table-of-contents)
- [Full Documentation](#full-documentation)
  - [Features](#features-1)
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [Basic Usage](#basic-usage)
  - [Plain PHP Example](#plain-php-example)
  - [Modular Processing](#modular-processing)
  - [Direct Processor Access](#direct-processor-access)
  - [Configuration](#configuration)
  - [Processor Details](#processor-details)
    - [UnicodeProcessor](#unicodeprocessor)
    - [HtmlProcessor](#htmlprocessor)
    - [OfficeProcessor](#officeprocessor)
    - [PunctuationProcessor](#punctuationprocessor)
  - [Common Use Cases](#common-use-cases)
    - [Product Descriptions](#product-descriptions)
    - [User Content](#user-content)
    - [Word/Email Pastes](#wordemail-pastes)
    - [API Data](#api-data)
  - [What CharWash Solves](#what-charwash-solves)
  - [Performance](#performance)
  - [Testing](#testing)
  - [Contributing](#contributing)
  - [License](#license)
  - [Author](#author)

---

# Full Documentation

## Features

- **All-in-one cleaning**: Run a full cleanup in one call
- **Targeted processors**: Use only the parts you need (HTML, Unicode, Office, punctuation)
- **Unicode fixes**: Normalize encodings and remove invisible characters
- **HTML cleanup**: Sanitize tags, remove empties, and enforce safe attributes
- **Office/email cleanup**: Strip Word/Outlook artifacts, conditional comments, and mojibake
- **Punctuation fixes**: Straighten curly quotes, flatten dashes, clean ellipses
- **Configurable**: Control processors, allowed tags, and cache paths
- **Efficient**: Handles large text without heavy overhead
- **Framework-agnostic**: Works with any PHP project or framework

## Requirements

- PHP 8.3 or higher
- PHP Extensions:
  - `ext-intl`
  - `ext-mbstring`
  - `ext-iconv`
- Dependency:
  - `ezyang/htmlpurifier: ^4.16`

## Installation

```bash
composer require odindev/charwash
```

## Basic Usage

```php
use OdinDev\CharWash\CharWash;

// Clean everything (recommended for user input)
$clean = CharWash::sanitize($messyText);

// Target specific issues
$clean = CharWash::sanitizeHtml($htmlContent);
$clean = CharWash::sanitizeUnicode($text);
$clean = CharWash::sanitizeOffice($pastedFromWord);
```

## Plain PHP Example

CharWash works in any PHP project, no framework required:

```php
require __DIR__ . '/vendor/autoload.php';

use OdinDev\CharWash\CharWash;

$raw = file_get_contents('user_input.txt');

// Clean the text before saving it
$clean = CharWash::sanitize($raw);

// Save to database
$pdo->prepare("INSERT INTO comments (content) VALUES (:content)")
    ->execute(['content' => $clean]);
```

## Modular Processing

Each processor can be called directly:

```php
// HTML cleanup
$clean = CharWash::sanitizeHtml($htmlContent);
// - Purifies HTML
// - Removes empty tags
// - Adds rel="noopener noreferrer"
// - Converts H1 to H2

// Unicode fixes
$clean = CharWash::sanitizeUnicode($text);
// - NFC normalization
// - Replaces non-breaking spaces with regular spaces
// - Replaces line breaks (CRLF, LF, CR) with spaces
// - Removes BOM, ZWSP, ZWNJ, ZWJ
// - Strips soft hyphens

// Office/email cleanup
$clean = CharWash::sanitizeOffice($pastedText);
// - Removes mso-* styles
// - Strips conditional comments
// - Removes _x000D_ markers
// - Fixes CP1252/mojibake
```

## Direct Processor Access

For advanced cases, you can use processors directly:

```php
use OdinDev\CharWash\Processors\HtmlProcessor;
use OdinDev\CharWash\Processors\UnicodeProcessor;
use OdinDev\CharWash\Processors\OfficeProcessor;
use OdinDev\CharWash\Processors\PunctuationProcessor;

$htmlProcessor = new HtmlProcessor();
$clean = $htmlProcessor->process($htmlContent);

$unicodeProcessor = new UnicodeProcessor();
$clean = $unicodeProcessor->process($text);
```

## Configuration

```php
use OdinDev\CharWash\Config\CharWashConfig;

// Set HTMLPurifier cache path
CharWashConfig::setHtmlPurifierCachePath('/custom/cache/path');

// Set allowed HTML tags
CharWashConfig::setAllowedHtmlTags(['p', 'a', 'strong', 'em']);

// Or load full config
CharWashConfig::loadFromArray([
    'cache_path' => '/custom/cache/path',
    'allowed_tags' => ['p', 'a', 'strong', 'em'],
    'processors' => [
        'unicode' => [
            'removeInvisible' => true,
            'normalizeNFC' => true,
        ],
    ],
]);
```

## Processor Details

### UnicodeProcessor
- NFC normalization for consistent encoding
- Replace non-breaking spaces (U+00A0) with regular spaces
- Replace line breaks (CRLF, LF, CR) with spaces
- Remove invisible characters (BOM, ZWSP, ZWNJ, ZWJ)
- Strip soft hyphens and control characters

### HtmlProcessor
- Uses HTMLPurifier with HTML5 support
- Removes empty tags
- Adds security attributes (noopener, noreferrer)
- Converts H1 to H2 for SEO sanity

### OfficeProcessor
- Removes Word/Outlook artifacts (mso-* styles, _x000D_ markers)
- Strips conditional comments
- Fixes CP1252 encoding issues
- Cleans mojibake from pasted emails

### PunctuationProcessor
- Straightens curly quotes
- Converts em/en dashes to standard dashes
- Replaces ellipsis with three dots
- Normalizes ligatures

## Common Use Cases

### Product Descriptions
```php
$cleanDescription = CharWash::sanitize($rawDescription);
```

### User Content
```php
$cleanComment = CharWash::sanitizeHtml($userComment);
```

### Word/Email Pastes
```php
$clean = CharWash::sanitizeOffice($pastedContent);
```

### API Data
```php
$clean = CharWash::sanitizeUnicode($apiResponse['description']);
```

## What CharWash Solves

- Word/Outlook artifacts (mso-* styles, conditional comments, markers)
- Encoding problems (mojibake, double UTF-8, CP1252 leftovers)
- Invisible characters (zero-width spaces, BOM, soft hyphens)
- Punctuation quirks (curly quotes, em/en dashes, ellipses, ligatures)
- Security issues (unsafe HTML, missing rel attributes)
- SEO issues (multiple H1s, empty tags)
- Copy-paste mess (hidden formatting, special characters)

## Performance

- Processors run in a smart order for speed
- HTMLPurifier supports caching
- Handles multi-megabyte text safely
- Minimal memory overhead

## Testing

CharWash uses [Pest PHP](https://pestphp.com/) for testing.

```bash
# Run all tests
composer test

# Run tests in parallel (faster)
composer test:parallel

# Run with coverage report
composer test:coverage

# Run only unit tests
composer test:unit

# Run only feature tests
composer test:feature

# Run specific test
./vendor/bin/pest --filter "sanitizes HTML"
```

## Contributing

PRs welcome.

## License

MIT

## Author

**Odin Development**
- Email: gary@odindev.com
- Package: odindev/charwash
