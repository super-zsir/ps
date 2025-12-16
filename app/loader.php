<?php

use Imee\Comp\Common\Fixed\Loader;

$loader = new Loader();
$loader->registerNamespaces(array(
    "Imee\Controller" => "app/controller/",
    "Imee\Libs"       => "app/libs/",
    "Imee\Exception"  => "app/exception/",
    "Imee\Service"    => "app/service/",
    "Config"          => CONFIG . "/",
    "Imee\Helper"     => "app/helper/",
    "Imee\Export"     => "app/export/",
    "Imee\Models"     => "app/models/",
    "Imee\Comp"       => "comp/",
    "OSS"             => "comp/common/oss/",
));

//注册组件里命名空间
require_once ROOT . '/comp/loader.php';

return $loader;
