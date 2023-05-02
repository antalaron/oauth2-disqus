<?php

/*
 * This file is part of Disqus Oaut2 client
 *
 * (c) EDIMA.email Kft. <admin24@edima.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$header = <<<'EOF'
This file is part of Disqus Oaut2 client

(c) EDIMA.email Kft. <admin24@edima.hu>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_before_statement' => ['statements' => ['break', 'case', 'continue', 'declare', 'default', 'exit', 'goto', 'include', 'include_once', 'phpdoc', 'require', 'require_once', 'return', 'throw', 'yield', 'yield_from']],
        'header_comment' => ['header' => $header],
        'heredoc_to_nowdoc' => true,
        'linebreak_after_opening_tag' => true,
        'modernize_strpos' => false,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
        'no_extra_blank_lines' => ['tokens' => ['break', 'continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block']],
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_imports' => true,
        'psr_autoloading' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'yoda_style' => ['equal' => true, 'identical' => true, 'less_and_greater' => true, 'always_move_variable' => true],
    ])
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in(__DIR__)
            ->append([__FILE__])
            ->exclude([
                'vendor',
            ])
    )
;
