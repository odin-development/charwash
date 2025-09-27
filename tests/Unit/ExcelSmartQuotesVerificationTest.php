<?php

declare(strict_types=1);

use OdinDev\CharWash\CharWash;
use PhpOffice\PhpSpreadsheet\IOFactory;

describe('CharWash correctly processes Excel smart quotes', function () {
    it('converts smart quotes from Excel to straight quotes', function () {
        $excelFile = __DIR__ . '/../Fixtures/test-flex-attribute-cleaning.xlsx';
        expect(file_exists($excelFile))->toBeTrue();

        $spreadsheet = IOFactory::load($excelFile);
        $worksheet = $spreadsheet->getActiveSheet();

        // Test data from row 6 as specified in bug report
        // P6: Flex-Front Lift Height with smart quote
        // Q6: Flex-Rear Lift Height with smart quote
        $p6_raw = (string) $worksheet->getCell('P6')->getValue();
        $q6_raw = (string) $worksheet->getCell('Q6')->getValue();

        // Verify raw data contains smart quotes
        expect(mb_strpos($p6_raw, "\u{201D}"))->not->toBeFalse();
        expect(mb_strpos($q6_raw, "\u{201D}"))->not->toBeFalse();

        // Clean with CharWash
        $p6_cleaned = CharWash::sanitize($p6_raw);
        $q6_cleaned = CharWash::sanitize($q6_raw);

        // Verify smart quotes are removed
        expect(mb_strpos($p6_cleaned, "\u{201D}"))->toBeFalse();
        expect(mb_strpos($q6_cleaned, "\u{201D}"))->toBeFalse();

        // Verify straight quotes are present
        expect(mb_strpos($p6_cleaned, '"'))->not->toBeFalse();
        expect(mb_strpos($q6_cleaned, '"'))->not->toBeFalse();

        // Verify exact output (trailing spaces are now trimmed)
        expect($p6_cleaned)->toBe('4"');
        expect($q6_cleaned)->toBe('4"');
    });

    it('handles all Unicode smart quote types', function () {
        $testCases = [
            "\u{201C}quoted\u{201D}" => '"quoted"',     // Left and right double quotes
            "\u{2018}single\u{2019}" => "'single'",      // Left and right single quotes
            "4\u{201D}" => '4"',                         // Right double quote (Excel case)
            "it\u{2019}s" => "it's",                     // Smart apostrophe
            "\u{201C}\u{201D}" => '""',                  // Adjacent smart quotes
        ];

        foreach ($testCases as $input => $expected) {
            $result = CharWash::sanitize($input);
            expect($result)->toBe($expected);
        }
    });

    it('processes data with sanitizePunctuation method', function () {
        $excelFile = __DIR__ . '/../Fixtures/test-flex-attribute-cleaning.xlsx';
        $spreadsheet = IOFactory::load($excelFile);
        $worksheet = $spreadsheet->getActiveSheet();

        $p6_raw = (string) $worksheet->getCell('P6')->getValue();

        // Test with punctuation-specific method
        $cleaned = CharWash::sanitizePunctuation($p6_raw);

        // Smart quote should be replaced
        expect(mb_strpos($cleaned, "\u{201D}"))->toBeFalse();
        expect(mb_strpos($cleaned, '"'))->not->toBeFalse();
        expect($cleaned)->toBe('4" ');
    });
});