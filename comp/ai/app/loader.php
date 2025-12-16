<?php

$loader = new \Phalcon\Loader();

$loader->registerNamespaces([
    'Imee\Comp\Ai' => __DIR__ . '/../src/',
], true)->register();
