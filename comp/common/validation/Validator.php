<?php

namespace Imee\Comp\Common\Validation;

use Imee\Exception\ApiException;
use Illuminate\Validation\Factory;
use Illuminate\Translation\Translator;
use Illuminate\Translation\FileLoader;
use Illuminate\Filesystem\Filesystem;

abstract class Validator
{
    private static $factory;

    /**
     * 规则
     */
    abstract protected function rules();

    /**
     * 属性
     */
    abstract protected function attributes();

    /**
     * 返回数据结构
     */
    abstract protected function response();

    /**
     * 提示信息
     */
    abstract protected function messages();

    public static function make(): Validator
    {
        if (self::$factory === null) {
            $translationPath = __DIR__.'/lang';
            
            $translationFileLoader = new FileLoader(
                new Filesystem(),
                $translationPath
            );
            $translator = new Translator($translationFileLoader, VALIDATION_LANG);
            self::$factory = new Factory($translator);
        }
        return new static();
    }

    /**
     * @param array $data 验证数据
     * @return bool
     * @throws ApiException
     */
    public function validators(array $data): bool
    {
        $validator = self::$factory->make(
            $data,
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );

        if ($validator->fails()) {
            throw new ApiException(ApiException::VALIDATION_ERROR, $validator->errors()->first());
        }

        return true;
    }
}
