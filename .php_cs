<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude('spec')
    ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()
    ->finder($finder)
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
;