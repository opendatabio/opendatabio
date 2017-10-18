<?php

$header = <<<EOF
This file is part of the OpenDataBio app.
(c) OpenDataBio development team https://github.com/opendatabio
EOF;

$fixers = [
    '@PSR1' => true,
    '@PSR2' => true,
    '@Symfony' => true,
    'header_comment' => [ 'header' => $header ],
    'no_extra_consecutive_blank_lines' => true,
    'no_unused_imports' => true,
    'standardize_not_equals' => true,
    'ternary_operator_spaces' => true,
    'trailing_comma_in_multiline_array' => true,
];
 
$finder = PhpCsFixer\Finder::create()
    ->notName('*.blade.php')
    ->exclude('vendor')
    ->in([
        __DIR__.'/app/',
        __DIR__.'/config/',
        __DIR__.'/database/',
        __DIR__.'/routes/',
        __DIR__.'/tests/',
    ]);
 
return PhpCsFixer\Config::create()
    ->setRules($fixers)
    ->setUsingCache(true)
    ->setFinder($finder);
