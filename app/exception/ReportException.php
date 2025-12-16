<?php

namespace Imee\Exception;

class ReportException extends \Exception
{
    private $systemCode = '10';
    protected $moduleCode = '00';
    protected $serviceCode = '00';

	const NOT_MSG = '99';
	const NOT_MSG_TIP = '错误提示语未定义';

    const MODULE_AUTH = '00';
    const MODULE_CS = '01';
    const MODULE_COMMON = '04';
    const MODULE_OPERATE = '06';
    const MODULE_AUDIT = '05';
    const MODULE_KA = '07';

    public function getOutCode()
    {
        return sprintf(
            "%02s%02s%02s%02s",
            $this->systemCode,
            $this->moduleCode,
            $this->serviceCode,
            $this->getCode()
        );
    }

	private static $data = [];

	public function getData()
	{
		return self::$data;
	}

	/**
	 * 基类直接抛异常
	 * @param string $exception
	 * @param string $msg
	 */
	public static function throwException($exception = [], $data = [], $msg = '')
	{
		if ($data) {
			self::$data = $data;
		}
		$class = get_called_class();
		if ($exception) {
			list($code, $errmsg) = $exception;
			throw new $class(
				$msg ? $msg : $errmsg,
				$code
			);
		} else {
			throw new $class(
				self::NOT_MSG_TIP,
				self::NOT_MSG
			);
		}
	}
}
