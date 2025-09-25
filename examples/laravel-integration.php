<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OdinDev\CharWash\CharWash;

/**
 * Example Laravel Form Request with text sanitization using CharWash
 */
class UpdateProductRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $this->merge([
            'title' => CharWash::sanitize($this->title ?? ''),
            'sku' => CharWash::sanitizeUnicode($this->sku ?? ''),
            'description' => CharWash::sanitizeHtml($this->description ?? ''),
            'short_description' => CharWash::sanitizeHtml($this->short_description ?? ''),
        ]);
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'sku' => 'required|string|max:64',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
        ];
    }
}

// ---------------------------------------------

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OdinDev\CharWash\CharWash;

/**
 * Example Eloquent Model with CharWash sanitization
 */
class Product extends Model
{
    protected $fillable = ['title', 'sku', 'description'];

    protected static function boot()
    {
        parent::boot();

        // Auto-sanitize on save
        static::saving(function ($product) {
            $product->title = CharWash::sanitize($product->title);
            $product->sku = CharWash::sanitizeUnicode($product->sku);

            if ($product->description) {
                $product->description = CharWash::sanitizeHtml($product->description);
            }
        });
    }
}

// ---------------------------------------------

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OdinDev\CharWash\CharWash;

/**
 * Example Controller using CharWash
 */
class ProductController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        // Sanitize input based on content type
        $product = Product::create([
            'title' => CharWash::sanitize($validated['title']),
            'description' => CharWash::sanitizeHtml($validated['description']),
        ]);

        return redirect()->route('products.show', $product);
    }

    public function import(Request $request)
    {
        $data = $request->input('import_data');

        // Clean data pasted from Word/Excel
        $cleanData = CharWash::sanitizeOffice($data);

        // Process import...
        return response()->json(['cleaned' => $cleanData]);
    }
}

// ---------------------------------------------

namespace App\View\Components;

use Illuminate\View\Component;
use OdinDev\CharWash\CharWash;

/**
 * Example Blade Component with CharWash
 */
class SafeHtml extends Component
{
    public string $content;

    public function __construct(string $content)
    {
        // Sanitize HTML content for safe rendering
        $this->content = CharWash::sanitizeHtml($content);
    }

    public function render()
    {
        return view('components.safe-html');
    }
}

// ---------------------------------------------
// Config file: config/charwash.php

return [
    /**
     * HTMLPurifier cache directory
     */
    'cache_path' => storage_path('app/charwash/cache'),

    /**
     * Allowed HTML tags
     */
    'allowed_tags' => [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u',
        'a', 'img', 'ul', 'ol', 'li',
        'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'pre', 'code',
    ],

    /**
     * Processor-specific configuration
     */
    'processors' => [
        'unicode' => [
            'removeInvisible' => true,
            'removeControl' => true,
            'removeSoftHyphens' => true,
            'normalizeNFC' => true,
        ],
        'html' => [
            'convertH1ToH2' => true,
            'removeEmptyTags' => true,
            'enforceSecureLinks' => true,
        ],
        'office' => [
            'removeMsoStyles' => true,
            'removeConditionalComments' => true,
            'fixMojibake' => true,
            'removeHexMarkers' => true,
        ],
        'punctuation' => [
            'flattenSmartQuotes' => true,
            'normalizeDashes' => true,
            'normalizeEllipsis' => true,
            'normalizeBullets' => true,
            'normalizeLigatures' => true,
        ],
    ],
];