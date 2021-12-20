<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
;

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'none'],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'new_with_braces' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'no_empty_comment' => true,
        'no_leading_import_slash' => true,
        'no_superfluous_phpdoc_tags' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
        'phpdoc_align' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_to_comment' => true,
        'phpdoc_summary' => true,
        'psr_autoloading' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'single_blank_line_before_namespace' => true,
        'single_quote' => true,
        'space_after_semicolon' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder($finder)
;

