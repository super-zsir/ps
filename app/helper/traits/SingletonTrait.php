<?php

namespace Imee\Helper\Traits;

trait SingletonTrait
{
	protected static $instance = null;

	public function __construct()
	{
	}

	/**
	 * @return self
	 */
	public static function getInstance($params = null, $forceNew = false)
	{
		if (false !== $forceNew || null === self::$instance) {
			self::$instance = new static($params);
		}
		return self::$instance;
	}

	public static function hasInstance()
	{
		return !is_null(self::$instance);
	}

}