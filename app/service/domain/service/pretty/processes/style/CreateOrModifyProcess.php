<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\Style;

use Imee\Service\Domain\Context\Pretty\Style\ModifyContext;
use Imee\Service\Helper;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Exception\Operate\PrettyStyleException;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Exception\ApiException;

/**
 * 列表
 */
class CreateOrModifyProcess
{
    /** @var ModifyContext */
    private $context;

    private $model;
    public function __construct(ModifyContext $context)
    {
        $this->context = $context;
    }

    private function verifyRepeatName()
    {
        $existModel = XsCustomizePrettyStyle::findFirst([
            'conditions' => 'name = :name:',
            'bind' => [
                'name' => $this->context->name,
            ],
        ]);
        if (!empty($existModel)) {
            //报错
            PrettyStyleException::throwException(PrettyStyleException::NAME_REPEAT_ERROR);
        }
    }

    private function verify()
    {
        if ($this->context->styleType == XsCustomizePrettyStyle::STYLE_TYPE_ENGLISH_NUMBER_ARABIC) {
            if ($this->context->arShortLimit < 1) {
                throw new ApiException(ApiException::MSG_ERROR, '阿语靓号最短字符 最小为1');
            }
            if ($this->context->arShortLimit > $this->context->arLongLimit) {
                PrettyStyleException::throwException(PrettyStyleException::LIMIT_ERROR);
            }
        } elseif ($this->context->styleType == XsCustomizePrettyStyle::STYLE_TYPE_ENGLISH_NUMBER_TR) {
            if ($this->context->trShortLimit < 1) {
                throw new ApiException(ApiException::MSG_ERROR, '土语靓号最短字符 最小为1');
            }
            if ($this->context->trShortLimit > $this->context->trLongLimit) {
                PrettyStyleException::throwException(PrettyStyleException::LIMIT_ERROR);
            }
        }

        if (empty($this->context->id)) {
            //验证名称是否重复
            $this->verifyRepeatName();
            return;
        }

        $this->model = XsCustomizePrettyStyle::findFirst([
            'conditions' => 'id = :id:',
            'bind' => [
                'id' => $this->context->id,
            ],
        ]);
        
        if (empty($this->model)) {
            //报错
            PrettyStyleException::throwException(PrettyStyleException::DATA_NOEXIST_ERROR);
        }

        //验证是否重复
        if ($this->context->name != $this->model->name) {
            $this->verifyRepeatName();
        }
        return;
    }

    public function handle()
    {
        $beforeJson = '';
        $this->verify();
        if (empty($this->context->id)) {
            $this->model = new XsCustomizePrettyStyle();
        } else {
            $beforeJson = $this->model->toArray();
        }

        $this->model->name = $this->context->name;
        $this->model->short_limit = $this->context->shortLimit;
        $this->model->long_limit = $this->context->longLimit;
        $this->model->repeat_limit = $this->context->repeatLimit;
        $this->model->correct_example_1 = $this->context->correctExample1;
        $this->model->correct_example_2 = $this->context->correctExample2;
        $this->model->incorrect_example_1 = $this->context->incorrectExample1;
        $this->model->incorrect_example_2 = $this->context->incorrectExample2;
        $this->model->remark = $this->context->remark;
        $this->model->style_type = $this->context->styleType;
        $this->model->ar_short_limit = $this->context->arShortLimit;
        $this->model->ar_long_limit = $this->context->arLongLimit;
        $this->model->tr_short_limit = $this->context->trShortLimit;
        $this->model->tr_long_limit = $this->context->trLongLimit;

        // 支持类型为英文&数字 阿语相关字段清空
        if ($this->model->style_type == XsCustomizePrettyStyle::STYLE_TYPE_ENGLISH_NUMBER) {
            $this->model->ar_short_limit = 0;
            $this->model->ar_long_limit = 0;
            $this->model->tr_short_limit = 0;
            $this->model->tr_long_limit = 0;
        } elseif ($this->model->style_type == XsCustomizePrettyStyle::STYLE_TYPE_ENGLISH_NUMBER_ARABIC) {
            $this->model->tr_short_limit = 0;
            $this->model->tr_long_limit = 0;
        } elseif ($this->model->style_type == XsCustomizePrettyStyle::STYLE_TYPE_ENGLISH_NUMBER_TR) {
            $this->model->ar_short_limit = 0;
            $this->model->ar_long_limit = 0;
        }

        $this->model->save();

        OperateLog::addOperateLog([
            'before_json'  => $beforeJson,
            'content'      => $this->context->id ? '修改' : '新增',
            'after_json'   => $this->model->toArray(),
            'type'         => BmsOperateLog::TYPE_OPERATE_LOG,
            'model'        => $this->model->getSource(),
            'model_id'     => $this->model->id,
            'action'       => $this->context->id ? BmsOperateLog::ACTION_UPDATE : BmsOperateLog::ACTION_ADD,
            'operate_id'   => Helper::getSystemUid(),
            
            'operate_name' => Helper::getSystemUserInfo()['user_name'],
        ]);
    }
}
