<?php


namespace Imee\Service\Domain\Service\Csms;


use Imee\Helper\Constant\CsmsConstant;
use Imee\Service\Domain\Service\Csms\Exception\CsmsWorkbenchException;

class CsmsBaseService
{

	private static $module;

	private static $instance = [];

	private static $schemaMap = [
		// csms - audit
		CsmsConstant::CSMS_AUDIT => 'Imee\Service\Domain\Service\Csms\CsmsAuditService',
		CsmsConstant::CSMS_RECHECK => 'Imee\Service\Domain\Service\Csms\CsmsRecheckService',
		CsmsConstant::CSMS_INSPECT => 'Imee\Service\Domain\Service\Csms\CsmsInspectService',
	];


	public static function getInstance($module)
	{
		self::$module = $module;
		return self::getSchema();
	}


	public static function getSchema()
	{
		if (isset(self::$instance[self::$module])) {
			return self::$instance[self::$module];
		}
		if (!self::$schemaMap || !isset(self::$schemaMap[self::$module])) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::MODULE_NOT_EXIST, ['module'=>self::$module]);
		}
		$class = self::$schemaMap[self::$module];
		self::$instance[self::$module] = new $class();
		return self::$instance[self::$module];
	}

}