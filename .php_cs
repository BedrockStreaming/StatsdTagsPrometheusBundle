<?php

$config = new M6Web\CS\Config\Php71;

$config->getFinder()
    ->in([
        __DIR__
    ])
    ->exclude('Tests/Symfony/');

return $config;
