<?php

namespace Imee\Comp\Common\Fixed;

class ImeeConfig
{
    protected $config;

    public function __construct()
    {
        $env = defined("CONFIG_ENV") ? CONFIG_ENV : ENV;
        $this->config = require_once(APP_PATH . DS . 'config_' . $env . '.php');
    }

    public function __get($prop)
    {
        return $this->config[$prop] ?? null;
    }
}
