<?php

use Phalcon\Di;
use Phalcon\Translate\Adapter\NativeArray;

if (!function_exists('__T')) {
    //增加一个全局变量用于缓存NativeArray对象
    $__T_Array = [];
    function __T($key, $params = [], $lang = '')
    {
        if (empty($lang) && !IS_CLI) {
            $di = DI::getDefault();
            $request = $di->getRequest();
            if ($request) {
                $headers = $request->getHeaders();
                if (isset($headers['User-Language'])) {
                    $lang = trim(strtolower($headers['User-Language']));
                } else {
                    $lang = strtolower($request->getBestLanguage());
                }
            }
        }

        $langFile = __DIR__ . '/' . $lang . '.php';
        if (!$lang || !file_exists($langFile)) {
            $lang = 'zh_tw';
        }

        global $__T_Array;
        if (!isset($__T_Array[$lang])) {
            if (!file_exists($langFile)) {
                throw new \Exception("$langFile not exist!");
            }
            $messages = require($langFile);//此处不能用require_once,否则变量只会返回一次
            $__T_Array[$lang] = new NativeArray(array(
                'content' => $messages
            ));
        }

        if ($__T_Array[$lang]->exists($key)) {
            return $__T_Array[$lang]->_($key, $params);
        }
        return $key;
    }
}

if (!function_exists('translate_output')) {
    function translate_output($output, $lang)
    {
        if (is_array($output) && !empty($output)) {
            foreach ($output as $key => $item) {
                if (is_array($item)) {
                    $output[$key] = translate_output($item, $lang);
                } elseif (is_string($item)) {
                    $output[$key] = __T($item, [], $lang);
                }
            }
        }
        return $output;
    }
}