# CharWash

CharWash is a comprehensive text sanitization package for Laravel and Magento applications, providing robust Unicode normalization, HTML purification, and Office/email paste cleanup.

## Features

- **Complete Sanitization**: All-in-one text cleaning with multiple processors
- **Modular Processing**: Use specific processors for targeted cleanup
- **Unicode Normalization**: NFC normalization and invisible character removal
- **HTML Purification**: Security-focused HTML cleaning with HTMLPurifier
- **Office Cleanup**: Remove Word/Outlook artifacts and fix mojibake
- **Smart Typography**: Normalize quotes, dashes, and special characters
- **Configurable**: Flexible configuration for Laravel and Magento
- **Performance**: Optimized for large content processing
- **Enterprise Ready**: Production-tested for e-commerce platforms

## Requirements

- PHP 8.1 or higher
- PHP Extensions:
  - `ext-intl`
  - `ext-mbstring`
  - `ext-iconv`
- Dependencies:
  - `ezyang/htmlpurifier: ^4.16`

## Installation

```bash
composer require odindev/charwash
```

## Basic Usage

```php
use OdinDev\CharWash\CharWash;

// Complete sanitization (recommended for user input)
$clean = CharWash::sanitize($messyText);

// Target specific issues
$clean = CharWash::sanitizeHtml($htmlContent);
$clean = CharWash::sanitizeUnicode($text);
$clean = CharWash::sanitizeOffice($pastedFromWord);
```

### Modular Processing

Use specific processors for targeted cleanup:

```php
// HTML-specific sanitization
$clean = CharWash::sanitizeHtml($htmlContent);
// - Purifies HTML using HTMLPurifier
// - Removes empty tags
// - Enforces rel="noopener noreferrer"
// - Converts H1 to H2

// Unicode normalization
$clean = CharWash::sanitizeUnicode($text);
// - Applies NFC normalization
// - Removes BOM, ZWSP, ZWNJ, ZWJ
// - Strips soft hyphens

// Office/email cleanup
$clean = CharWash::sanitizeOffice($pastedText);
// - Removes mso-* styles
// - Strips conditional comments
// - Removes *x000D* markers
// - Fixes CP1252/mojibake issues
```

### Direct Processor Access

For advanced usage, access processors directly:

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

### Configuration

```php
use OdinDev\CharWash\Config\CharWashConfig;

// Set HTMLPurifier cache path
CharWashConfig::setHtmlPurifierCachePath('/custom/cache/path');

// Set allowed HTML tags
CharWashConfig::setAllowedHtmlTags(['p', 'a', 'strong', 'em']);

// Or load from config array (Laravel/Magento)
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
- NFC normalization for consistent byte representation
- Remove invisible characters (BOM, ZWSP, ZWNJ, ZWJ)
- Strip soft hyphens and control characters

### HtmlProcessor
- HTMLPurifier integration with HTML5 support
- Empty tag removal
- Security hardening (noopener noreferrer)
- SEO discipline (H1 to H2 conversion)

### OfficeProcessor
- Remove Word/Outlook artifacts (*x000D*, mso-* styles)
- Strip conditional comments
- Fix CP1252 encoding issues
- Clean up mojibake from email pastes

### PunctuationProcessor
- Flatten smart quotes to straight quotes
- Convert em/en dashes to standard dashes
- Replace ellipsis characters with three dots
- Normalize ligatures to standard characters

## Laravel Integration

CharWash auto-registers via Laravel's package discovery.

### Configuration

Publish the config file:

```bash
php artisan vendor:publish --provider="OdinDev\CharWash\LaravelServiceProvider"
```

### Usage in Laravel

```php
use OdinDev\CharWash\CharWash;

// In a controller
public function store(Request $request)
{
    $clean = CharWash::sanitize($request->input('content'));
    // ...
}

// In a model mutator
public function setDescriptionAttribute($value)
{
    $this->attributes['description'] = CharWash::sanitizeOffice($value);
}

// In a blade component
@php
$cleanHtml = CharWash::sanitizeHtml($userContent);
@endphp
{!! $cleanHtml !!}
```

## Magento 2 Integration

Register as a Magento module:

### Module Structure

```
app/code/YourVendor/CharWash/
├── etc/
│   ├── module.xml
│   └── di.xml
├── Helper/
│   └── CharWash.php
└── registration.php
```

### Helper Class

```php
namespace YourVendor\CharWash\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use OdinDev\CharWash\CharWash as CharWashLib;

class CharWash extends AbstractHelper
{
    public function sanitize($text)
    {
        return CharWashLib::sanitize($text);
    }

    public function sanitizeHtml($html)
    {
        return CharWashLib::sanitizeHtml($html);
    }
}
```

### Usage in Magento

```php
// In a Block or Model
public function __construct(
    \YourVendor\CharWash\Helper\CharWash $charWash
) {
    $this->charWash = $charWash;
}

public function getCleanDescription()
{
    return $this->charWash->sanitize($this->getDescription());
}
```

## Common Use Cases

### E-commerce Product Descriptions

```php
// Clean product descriptions from various sources
$cleanDescription = CharWash::sanitize($rawDescription);
// Automatically handles Word artifacts, smart quotes, invisible chars, etc.
```

### User-Generated Content

```php
// Sanitize user comments/reviews
$cleanComment = CharWash::sanitizeHtml($userComment);
// Safe HTML output with XSS protection
```

### Email/Office Paste Cleanup

```php
// Fix content pasted from Word/Outlook
$clean = CharWash::sanitizeOffice($pastedContent);
// Removes MSO styles, conditional comments, hex markers, fixes mojibake
```

### API Data Sanitization

```php
// Ensure consistent data from external APIs
$clean = CharWash::sanitizeUnicode($apiResponse['description']);
// NFC normalization, remove invisible chars, ensure consistent encoding
```

## What CharWash Solves

### Common Text Issues
- **Word/Outlook Artifacts**: MSO styles, conditional comments, hex markers
- **Encoding Problems**: CP1252 mojibake, double-encoded UTF-8
- **Invisible Characters**: Zero-width spaces, BOM, soft hyphens
- **Smart Typography**: Curly quotes, em/en dashes, ellipsis
- **Security Issues**: XSS attacks, unsafe HTML, missing rel attributes
- **SEO Problems**: Multiple H1 tags, empty tags
- **Unicode Issues**: Inconsistent normalization, control characters
- **Copy-Paste Problems**: Hidden formatting, special characters

## Performance

- **Optimized Processing**: Processors run in optimal order
- **HTMLPurifier Caching**: Configurable cache path for performance
- **Large Content Ready**: Tested with multi-megabyte documents
- **Memory Efficient**: Minimal memory overhead

## Testing

```bash
vendor/bin/phpunit
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

MIT

## Author

**Odindev**
- Email: dev@odindev.com
- Package: odindev/charwash