<?php

namespace Imee\Comp\Nocode\Service\Exception;

class ModelException extends BaseException
{
    protected $serviceCode = '12';

    const MODEL_NOT_FOUND = ['01', '模型不能为空'];   // 10111201
}
