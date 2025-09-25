<?php

declare(strict_types=1);

use OdinDev\CharWash\Exceptions\CharWashException;

describe('CharWashException', function () {
    it('creates invalid configuration exception', function () {
        $exception = CharWashException::invalidConfiguration('test error');

        expect($exception)->toBeInstanceOf(CharWashException::class);
        expect($exception->getMessage())->toBe('Invalid CharWash configuration: test error');
    });

    it('creates processor error exception', function () {
        $exception = CharWashException::processorError('HtmlProcessor', 'parsing failed');

        expect($exception)->toBeInstanceOf(CharWashException::class);
        expect($exception->getMessage())->toBe('Error in HtmlProcessor processor: parsing failed');
    });

    it('creates encoding error exception', function () {
        $exception = CharWashException::encodingError('invalid UTF-8');

        expect($exception)->toBeInstanceOf(CharWashException::class);
        expect($exception->getMessage())->toBe('Encoding error: invalid UTF-8');
    });

    it('creates missing dependency exception', function () {
        $exception = CharWashException::missingDependency('ext-intl');

        expect($exception)->toBeInstanceOf(CharWashException::class);
        expect($exception->getMessage())->toBe('Missing required dependency: ext-intl. Please install it via Composer.');
    });
});