<?php


namespace Imee\Service\Domain\Service\Csms\Exception;


use Imee\Service\Domain\Service\Csms\Exception\Saas\BaseException;

class CsmsToolException extends BaseException
{


    const TASK_RETRY_ERROR = ['20', '参数错误'];
    const TASK_RETRY_NULL = ['21', '无对应任务记录'];
    const TASK_RETRY_CHECKNULL = ['22', '任务源数据为空'];
    const TASK_RETRY_FAILED = ['23', '任务重入失败'];

    const REVIEW_RETRY_ERROR = ['40', '参数错误'];
    const REVIEW_RETRY_NULL = ['41', '无对应外显记录'];
    const REVIEW_RETRY_REVIEWNULL = ['42', '无任务源数据'];
    const REVIEW_RETRY_FAILED = ['43', '外显任务重复入队失败'];



}