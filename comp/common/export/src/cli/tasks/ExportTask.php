<?php

use Imee\Comp\Common\Export\Models\Redis\ExportTaskRedis;
use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Comp\Common\Export\Models\Xsst\XsstExportTask;
use OSS\OssUpload;
use Phalcon\Di;

class ExportTask extends CliApp
{
    const MAX_RETRY_NUM = 3;

    private $execTaskId = 0;
    private $execTaskLocalFile = null;
    private $isCron = false;

    /** @var OssUpload */
    private $ossUpload;
    private $bucket;

    public function mainAction(array $params = [])
    {
        //推算endpoint
        if (isset($params['endpoint']) && $params['endpoint'] == 'out') {
            $endpoint = OssUpload::ENDPOINT_OUT;
        } else {
            $endpoint = OssUpload::ENDPOINT_INTERNAL;
        }
        $this->bucket = (ENV == 'dev') ? OssUpload::BUCKET_DEV : OssUpload::APC_ADMIN_DATA;
        $this->ossUpload = new OssUpload($this->bucket, $endpoint);

        $this->isCron = $params['cron'] ?? false;
        $this->registerSig();
    }

    protected function console($msg)
    {
        echo sprintf("[%s][%s] %s\n", date('Y-m-d H:i:s'), Di::getDefault()->getShared('uuid'), $msg);
    }

    private function registerSig()
    {
        if (!$this->isCron && extension_loaded('pcntl') && extension_loaded('posix')) {
            pcntl_signal(SIGTERM, [$this, 'sigHandler']);
            pcntl_signal(SIGUSR1, [$this, 'sigHandler']);
            pcntl_signal(SIGHUP, [$this, 'sigHandler']);
            $this->handle(true);
        } else {
            $this->handle();
        }
    }

    private function sigHandler($signo)
    {
        switch ($signo) {
            case SIGTERM:
            case SIGUSR1:
            case SIGHUP:
                $this->clearHandler();
                exit(0);
            default:
                break;
        }
    }

    private function clearHandler()
    {
        if ($this->execTaskId > 0) {
            $info = ExportService::getTaskById($this->execTaskId);
            if (!empty($info)) {
                $info->status = XsstExportTask::STATUS_PENDING;
                $info->completion_at = 0;
                $info->save();
            }
            !empty($this->execTaskLocalFile) && is_file($this->execTaskLocalFile) && unlink($this->execTaskLocalFile);
        }
    }

    private function handle($enable = false)
    {
        $this->console('start!');
        $id = 0;
        $startTime = time();

        while (true) {
            if ($enable) {
                pcntl_signal_dispatch();
            }
            $taskInfo = ExportService::getTask($id);
            if (!empty($taskInfo)) {
                $id = $taskInfo->id;
                $this->console('exec id: ' . $id);

                try {
                    if (!ExportTaskRedis::checkFirstTaskFlag($id)) {
                        sleep(1);
                        continue;
                    }

                    $this->execTaskId = $id;
                    $taskInfo->status = XsstExportTask::STATUS_EXECUTING;
                    $taskInfo->save();

                    RETRY:
                    $exportParams = $taskInfo->getExportParams();
                    $fileName = $id . '-' . $taskInfo->file_name;
                    $fileExportPath = sprintf('%s/%s', EXPORT_DIR, $fileName);
                    call_user_func_array($exportParams['callback'], [$fileExportPath, $exportParams['filter_params']]);
                    $localFile = $fileExportPath;
                    $ossFilePath = sprintf('%s/%s/%s', EXPORT_OSS_DIR, date('Ym'), $fileName);
                    if (!is_file($localFile)) {
                        throw new \LogicException(sprintf('本地文件 %s 不存在', $localFile));
                    }
                    $this->execTaskLocalFile = $localFile;

                    $uploadFlag = 3;
                    $uploadRes = false;
                    while ($uploadFlag) {
                        if ($this->ossUpload->moveFileTo($localFile, $ossFilePath)) {
                            $uploadRes = true;
                            break;
                        }
                        $uploadFlag--;
                    }
                    if (!$uploadRes) {
                        throw new \LogicException('文件上传失败');
                    }

                    //上传到oss
                    $client = $this->ossUpload->client();
                    $client->setUseSSL(true);
                    $ossUrl = $client->signUrl($this->bucket, $ossFilePath, 86400 * 3);
                    $ossUrl = str_replace('-internal', '', $ossUrl);
                    $taskInfo->file_url = $ossUrl;
                    $taskInfo->status = XsstExportTask::STATUS_SUCCESS;
                    $taskInfo->completion_at = time();
                    $taskInfo->save();
                    //删除本地文件
                    unlink($localFile);
                } catch (\Exception $exception) {
                    $retry = ExportTaskRedis::checkRetryTaskFlag($id);
                    if ($retry <= self::MAX_RETRY_NUM) {
                        usleep(500);
                        goto RETRY;
                    }
                    $taskInfo->status = XsstExportTask::STATUS_FAILED;
                    $taskInfo->remark = $exception->getMessage();
                    $taskInfo->save();
                    !empty($localFile) && is_file($localFile) && unlink($localFile);
                }
                //删除标记锁
                ExportTaskRedis::deleteFirstTaskFlag($id);
                ExportTaskRedis::deleteRetryTaskFlag($id);
                $this->console(sprintf('exec id: %d end', $id));
            } else {
                $this->console('sleep!');
                sleep(rand(5, 10));
            }
            if ($this->isCron || time() > ($startTime + 600)) {
                break;
            }
        }
        $this->console('end!');
    }
}
