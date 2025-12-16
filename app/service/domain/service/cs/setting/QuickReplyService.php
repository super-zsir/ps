<?php

namespace Imee\Service\Domain\Service\Cs\Setting;

use Imee\Service\Domain\Context\Cs\Setting\QuickReply\CreateContext;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\DelContext;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\GroupCreateContext;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\GroupDelContext;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\GroupModifyContext;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\ListContext;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\ModifyContext;
use Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply\ConfigProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply\CreateProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply\DelProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply\GroupCreateProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply\GroupDelProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply\GroupListProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply\GroupModifyProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply\ListProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply\ModifyProcess;
use Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply\TreeListProcess;

/**
 * 客服快捷回复
 */
class QuickReplyService
{
    /**
     * 列表
     * @param $context
     * @return array
     */
    public function getList($params)
    {
    	$context = new ListContext($params);
        $process = new ListProcess($context);
        return $process->handle();
    }

    public function getTreeList()
	{
		$process = new TreeListProcess();
		return $process->handle();
	}

    /**
     * 新增
     * @return void
     */
    public function create($params)
    {
		$context = new CreateContext($params);
        $process = new CreateProcess($context);
        $process->handle();
    }

    /**
     * 修改
     * @return void
     */
    public function modify($params)
    {
		$context = new ModifyContext($params);
        $process = new ModifyProcess($context);
        $process->handle();
    }

    /**
     * 删除
     * @return void
     */
    public function del($params)
    {
		$context = new DelContext($params);
        $process = new DelProcess($context);
        $process->handle();
    }

	/**
	 * 分组列表
	 */
    public function getGroupList()
	{
		$process = new GroupListProcess();
		return $process->handle();
	}

	/**
	 * 分组新增
	 */
	public function createGroup($params)
	{
		$context = new GroupCreateContext($params);
		$process = new GroupCreateProcess($context);
		$process->handle();
	}

	/**
	 * 分组修改
	 */
	public function modifyGroup($params)
	{
		$context = new GroupModifyContext($params);
		$process = new GroupModifyProcess($context);
		$process->handle();
	}

	/**
	 * 分组删除
	 */
	public function delGroup($params)
	{
		$context = new GroupDelContext($params);
		$process = new GroupDelProcess($context);
		$process->handle();
	}

	public function config()
	{
		$process = new ConfigProcess();
		return $process->handle();
	}
}
