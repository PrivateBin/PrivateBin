<?php
/**
 * Configuration file for PHP Coding Standards Fixer (php-cs-fixer).
 *
 * On GitHub: https://github.com/FriendsOfPhp/php-cs-fixer
 * More information: http://cs.sensiolabs.org/
 */

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in('lib')
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers(['concat_with_spaces', 'long_array_syntax', 'standardize_not_equal',
              'operators_spaces', 'duplicate_semicolon',
              'remove_leading_slash_use', 'align_equals',
              'single_array_no_trailing_comma', 'phpdoc_indent', 'phpdoc_scalar',
              'phpdoc_to_comment', 'phpdoc_trim',
              'phpdoc_types', 'print_to_echo', 'self_accessor', 'single_quote',
              'spaces_cast', 'ternary_spaces', 'phpdoc_order'])
    ->finder($finder)
;
