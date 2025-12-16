<?php


namespace Imee\Service\Domain\Service\Csms\Context\Audio;

use Imee\Service\Domain\Context\BaseContext;

class UploadAudioContext extends BaseContext
{

	public $choice;

	public $type = 'url';

	public $taskid;

	public $dataid;

	public $content;

}