<?php

declare(strict_types=1);

// Code style for PHP classes and scripts. Templates in views/ are left as written.
$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/config', __DIR__ . '/database/migrations', __DIR__ . '/database/seeds'])
    ->append([__DIR__ . '/public/index.php', __DIR__ . '/phinx.php']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
