<?php
namespace Imee\Controller\Cs\Setting;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Cs\Setting\Channel\ChannelCreateValidation;
use Imee\Controller\Validation\Cs\Setting\Channel\ChannelModifyValidation;
use Imee\Service\Domain\Service\Cs\Setting\ChannelService;

class ChannelController extends BaseController
{
    /**
     * @page setting.channel
     * @name 客服系统-客服中心设置-客服通道设置
     * @point 列表
     */
    public function indexAction()
    {
        $service = new ChannelService();
        return $this->outputSuccess($service->getList());
    }

    /**
     * @page setting.channel
     * @point 增加
     */
    public function createAction()
    {
        ChannelCreateValidation::make()->validators($this->request->getPost());
        $service = new ChannelService();
        $service->create($this->request->getPost());
        return $this->outputSuccess();
    }

	/**
	 * @page setting.channel
	 * @point 编辑
	 */
    public function modifyAction()
	{
		ChannelModifyValidation::make()->validators($this->request->getPost());
		$service = new ChannelService();
		$service->modify($this->request->getPost());
		return $this->outputSuccess();
	}

	/**
	 * @page setting.channel
	 * @point 配置
	 */
	public function configAction()
	{
		$service = new ChannelService();
		return $this->outputSuccess($service->config());
	}
}
