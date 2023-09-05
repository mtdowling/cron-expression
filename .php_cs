<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__);

$config = PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'native_function_invocation' => [
            'include' => ['@compiler_optimized'],
            'scope' => 'namespaced',
        ],
        'phpdoc_align' => [
            'align' => 'left',
        ],
        'blank_line_before_statement' => true,
        'align_multiline_comment' => false,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'array_indentation' => true,
        'no_extra_blank_lines' => [
            'break',
            'case',
            'continue',
            'curly_brace_block',
            'default',
            'extra',
            'parenthesis_brace_block',
            'return',
            'square_brace_block',
            'switch',
            'throw',
            'use',
            'useTrait',
            'use_trait',
        ],
        'compact_nullable_typehint' => true,
        'escape_implicit_backslashes' => true,
        'explicit_indirect_variable' => true,
        'explicit_string_variable' => true,
        'final_internal_class' => true,
        'fully_qualified_strict_types' => true,
        'function_to_constant' => [
            'functions' => [
                'get_class',
                'get_called_class',
                'php_sapi_name',
                'phpversion',
                'pi',
            ],
        ],
        'list_syntax' => [
            'syntax' => 'short',
        ],
        'logical_operators' => true,
        'no_alternative_syntax' => true,
        'no_null_property_initialization' => true,
        'no_short_echo_tag' => true,
        'no_superfluous_elseif' => false,
        'no_unreachable_default_argument_value' => true,
        'no_unset_on_property' => false,
        'no_useless_else' => false,
        'ordered_imports' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types_order' => true,
        'return_assignment' => true,
        'string_line_ending' => true,
        'strict_param' => true,
        'strict_comparison' => true,
    ])
    ->setFinder($finder);

return $config;