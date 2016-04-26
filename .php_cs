<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__);

$fixers = array(
    '-psr0',
    'long_array_syntax',
);

return Symfony\CS\Config\Config::create()
    ->finder($finder)
    ->fixers($fixers)
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->setUsingCache(true);
