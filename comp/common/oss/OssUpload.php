<?php

namespace OSS;

use BadMethodCallException;
use Imee\Comp\Common\Log\LoggerProxy;
use Phalcon\Http\Request\File;
use OSS\Core\OssException;
use Config\ConfigAliyunOss;

class OssUpload
{
    private $_bucket;
    private $_endpoint;

    private $_options = array(
        'checkmd5' => true,
    );
    //测试
    const BUCKET_DEV = 'apc-admin-test';
    const BUCKET_DEV_PS = 'bb-admin-test';
    const RBP_DEV = 'dev-rbp';
    const SLP_DEV = 'slp-ops';

    const PT_IMAGE = 'ptm-public';
    const PT_XS_IMAGE = 'ptm-public';
    const PT_PROXY = 'ptm-public';
    const PT_CN_PROXY = 'ptm-public';
    const PT_VOICE = 'ptm-voice-public';//KTV

    const PA_IMAGE = 'pam-public';
    const PA_VOICE = 'pam-voice-public';//KTV

    const PS_IMAGE = 'partystar-app-public';
    const PS_XS_IMAGE = 'partystar-app-public';
    const PS_POINT = 'partystar-app-public';
    const PS_SUPPORT_NEW = 'partystar-support-new';//超管做的一个图片截帧图片
    const PS_DEV_IMAGE = 'partystar-app-dev';

    const PG_IMAGE = 'pg-app-public';

    const VEEKA_IMAGE = 'veeka-internal';//视频直播
    const VEEKA_XS_IMAGE = 'veeka-app-public';
    const VEEKA_PROXY = 'veeka-app-public';
    const VEEKA_VOICE = 'veeka-voice';//veeka ktv

    const UTLAS_XS_IMAGE = 'utlas';//北美
    const UTLAS_ME_XS_IMAGE = 'utlas-me';//中东

    const MIXER_XS_IMAGE = 'mixer-us';

    const WOWCHAT_APP_PUBLIC = 'wowchat-app-public';//德国法兰克福

    //录屏使用
    const APC_ROOM_RECORD = 'apc-room-record';
    const APC_ROOM_RECORD_DOMAIN = 'oss-apc-room-record.aopacloud.net';

    //国内在用
    const IM_CN_LOG_DEV = 'bc-im-cn-dev';//im-cn-log-oss-dev.aopacloud.net
    const IM_CN_LOG = 'bc-im-cn-log';//im-cn-log-oss.aopacloud.net
    const SG_EMR = 'sg-emr-data';//海外数据存放新加坡
    const DATA_EMR = 'data-emr-01';//海外数据存放杭州
    const CHANNEL_BILL = 'zengzhang2022';
    const SLP_IMAGE = 'slp-image';
    const RBP_IMAGE = 'rbp-image';
    const SLP_IMAGE_PROXY = 'slp-image';
    const RBP_IMAGE_PROXY = 'rbp-image';
    const APC_ADMIN_DATA = 'apc-admin-data';//杭州
    const APSG_ADMIN_DATA = 'apsg-admin-data';//新加坡
    const ZH_EMR = 'zh-emr-data';
    const ALLO_IMAGE = 'allo-image';//推荐组
    const WHO_IMAGE = 'who-xs-image';//https://xs-image.hubeixinxingwang.com
    const WSP_IMAGE = 'apc-ai-image';

    const ENDPOINT_OUT = 'oss-cn-hangzhou.aliyuncs.com';//杭州外网
    const ENDPOINT_INTERNAL = 'oss-cn-hangzhou-internal.aliyuncs.com';//杭州内网
    const ENDPOINT_SH_OUT = 'oss-cn-shanghai.aliyuncs.com';//shanghai
    const ENDPOINT_SH_INTERNAL = 'oss-cn-shanghai-internal.aliyuncs.com';//shanghai

    const ENDPOINT_AP_OUT = 'oss-ap-southeast-1.aliyuncs.com';//新加坡外网
    const ENDPOINT_AP_INTERNAL = 'oss-ap-southeast-1-internal.aliyuncs.com';//新加坡内网

    const ENDPOINT_US_OUT = 'oss-us-west-1.aliyuncs.com';//美国外网
    const ENDPOINT_US_INTERNAL = 'oss-us-west-1-internal.aliyuncs.com';//美国内网

    const ENDPOINT_ME_OUT = 'oss-me-east-1.aliyuncs.com';//中东外网
    const ENDPOINT_ME_INTERNAL = 'oss-me-east-1-internal.aliyuncs.com';//中东内网

    const ENDPOINT_EU_OUT = 'oss-eu-central-1.aliyuncs.com';//法兰克福外网
    const ENDPOINT_EU_INTERNAL = 'oss-eu-central-1-internal.aliyuncs.com';//法兰克福内网

    public $s3Instance = null; // 用于保存 S3Upload 实例

    public function __construct($bucket = self::BUCKET_DEV, $endpoint = null)
    {
        //兼容支持aws s3
        if (in_array($bucket, S3Upload::$buckets)) {
            $this->s3Instance = new S3Upload($bucket, $endpoint);
            return;
        }

        $this->_bucket = $bucket;

        $this->_endpoint = $this->setEndpoint($endpoint);

        $this->_options[OssClient::OSS_HEADERS] = array(
            'Cache-Control' => 'max-age=31536000',
        );
    }

    //对于一些跨境的，传endpoint设置
    public function setEndpoint($endpoint)
    {
        //有传递且部署默认endpoint的直接使用
        if ($endpoint && $endpoint != self::ENDPOINT_INTERNAL) {
            return $endpoint;
        }

        //根据bucket设置默认的endpoint
        switch ($this->_bucket) {
            case self::PT_VOICE:
            case self::PA_VOICE:
            case self::VEEKA_IMAGE:
            case self::VEEKA_VOICE:
            case self::PS_IMAGE:
            case self::PS_XS_IMAGE:
            case self::PS_DEV_IMAGE:
            case self::PS_POINT:
            case self::PS_SUPPORT_NEW:
            case self::SG_EMR:
                $endpoint = self::ENDPOINT_AP_OUT;
                break;
            case self::UTLAS_XS_IMAGE:
            case self::MIXER_XS_IMAGE:
                $endpoint = self::ENDPOINT_US_INTERNAL;
                break;
            case self::UTLAS_ME_XS_IMAGE:
                $endpoint = self::ENDPOINT_ME_OUT;
                break;
            case self::PT_IMAGE:
            case self::PA_IMAGE:
            case self::PT_XS_IMAGE:
            case self::PT_PROXY:
            case self::VEEKA_XS_IMAGE:
            case self::PG_IMAGE:
            case self::APSG_ADMIN_DATA:
                $endpoint = self::ENDPOINT_AP_INTERNAL;
                break;
            case self::BUCKET_DEV:
            case self::BUCKET_DEV_PS:
            case self::SLP_DEV:
            case self::RBP_DEV:
            case self::DATA_EMR:
            case self::WSP_IMAGE:
                $endpoint = self::ENDPOINT_OUT;
                break;
            case self::APC_ROOM_RECORD:
                $endpoint = self::ENDPOINT_SH_OUT;
                break;
            case self::WOWCHAT_APP_PUBLIC:
                $endpoint = self::ENDPOINT_EU_INTERNAL;
                break;
            default:
                $endpoint = self::ENDPOINT_INTERNAL;
        }

        return $endpoint;
    }

    public function getEndPoint()
    {
        if ($this->s3Instance) {
            return $this->s3Instance->getEndPoint();
        }
        return $this->_endpoint;
    }

    public function getBucket()
    {
        if ($this->s3Instance) {
            return $this->s3Instance->getBucket();
        }
        return $this->_bucket;
    }

    public function modifyMetaForObject($object, $contentType = 'image/gif', $maxAge = 31536000)
    {
        if ($this->s3Instance) {
            return $this->s3Instance->modifyMetaForObject($object, $contentType, $maxAge);
        }
        $copyOptions = array(
            OssClient::OSS_HEADERS => array(
                'Cache-Control' => 'max-age=' . $maxAge,
                'Content-Type'  => $contentType,
            ),
        );
        $client = $this->client();
        if (!$client) return false;
        try {
            $client->copyObject($this->_bucket, $object, $this->_bucket, $object, $copyOptions);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return false;
        }
        return true;
    }

    public function copyObject($from, $to)
    {
        if ($this->s3Instance) {
            return $this->s3Instance->copyObject($from, $to);
        }
        $copyOptions = [];
        $client = $this->client();
        if (!$client) return false;
        try {
            $client->copyObject($this->_bucket, $from, $this->_bucket, $to, $copyOptions);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return false;
        }
        return true;
    }

    public function putObject($object, $content)
    {
        if ($this->s3Instance) {
            return $this->s3Instance->putObject($object, $content);
        }
        $options = [];
        $client = $this->client();
        if (!$client) return false;
        try {
            $client->putObject($this->_bucket, $object, $content, $options);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return false;
        }
        return true;
    }

    public function downloadToLocal($key, $localFile)
    {
        if ($this->s3Instance) {
            return $this->s3Instance->downloadToLocal($key, $localFile);
        }
        $options = [
            OssClient::OSS_FILE_DOWNLOAD => $localFile,
        ];
        $client = $this->client();
        if (!$client) return false;
        try {
            $client->getObject($this->_bucket, $key, $options);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return false;
        }
        return true;
    }

    public function getObject($object, $options = null)
    {
        if ($this->s3Instance) {
            return $this->s3Instance->getObject($object, $options);
        }
        $client = $this->client();
        if (!$client) return false;
        try {
            return $client->getObject($this->_bucket, $object, $options);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return '';
        }
    }

    //删除oss里的某张图片
    public function delete($object)
    {
        if ($this->s3Instance) {
            return $this->s3Instance->delete($object);
        }
        $client = $this->client();
        $options = [];
        if (!$client) return false;
        try {
            $client->deleteObject($this->_bucket, $object, $options);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return false;
        }
        return true;
    }

    public function listObjects($options = null)
    {
        if ($this->s3Instance) {
            return $this->s3Instance->listObjects($options);
        }
        $client = $this->client();
        if (!$client) return false;
        try {
            return $client->listObjects($this->_bucket, $options);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return false;
        }
    }

    public function setOptions(array $options)
    {
        if ($this->s3Instance) {
            $this->s3Instance->setOptions($options);
        }
        $this->_options = $options;
    }

    public function moveFile($localFile, $ext = null, $addIfExist = true)
    {
        if ($this->s3Instance) {
            return $this->s3Instance->moveFile($localFile, $ext);
        }

        if (!$ext) {
            $ext = strstr($localFile, '.');
        }

        if (!strstr($ext, '.')) {
            $ext = '.' . $ext;
        }

        $dir = date('Ym/d/');
        $prefix = ip2long($_SERVER['SERVER_ADDR']);
        $remoteName = $dir . uniqid($prefix, true) . $ext;

        if (false === $this->moveFileTo($localFile, $remoteName)) {
            return false;
        }
        return $remoteName;
    }

    /*
    * $localFile 本地的绝对路径
    * $remoteName 访问请求名，不带域名，不以 / 开始
    */
    public function moveFileTo($localFile, $remoteName)
    {
        if (!file_exists($localFile)) {
            throw new \Exception("Local file does not exist or is empty.");
        }

        if ($this->s3Instance) {
            return $this->s3Instance->moveFileTo($localFile, $remoteName);
        }

        $client = $this->client();
        if (!$client) return false;
        try {
            $client->multiuploadFile($this->_bucket, $remoteName, $localFile, $this->_options);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage() . "::" . $this->_bucket . "::" . $remoteName . "::" . $localFile);
            return false;
        }
        return true;
    }

    private function getAkSk()
    {
        //自动匹配aksk
        $config = $this->getConfigAkSk($this->_bucket);
        if ($config) {
            return $config;
        }

        switch ($this->_bucket) {
            case self::UTLAS_XS_IMAGE:
                $AccessKeyId = ConfigAliyunOss::UtlasAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::UtlasAccessKeySecret;
                break;
            case self::MIXER_XS_IMAGE:
                $AccessKeyId = ConfigAliyunOss::MixerAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::MixerAccessKeySecret;
                break;
            case self::PT_IMAGE:
            case self::PA_IMAGE:
            case self::PT_XS_IMAGE:
            case self::PT_PROXY:
            case self::PT_CN_PROXY:
            case self::PT_VOICE:
            case self::PA_VOICE:
            case self::VEEKA_XS_IMAGE:
            case self::VEEKA_PROXY:
            case self::VEEKA_VOICE:
            case self::PG_IMAGE:
            case self::PS_IMAGE:
            case self::PS_POINT:
            case self::PS_XS_IMAGE:
                $AccessKeyId = ConfigAliyunOss::OverseaAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::OverseaAccessKeySecret;
                break;
            case self::PS_SUPPORT_NEW:
                $AccessKeyId = ConfigAliyunOss::PsSupportNewAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::PsSupportNewAccessKeySecret;
                break;
            case self::VEEKA_IMAGE:
                $AccessKeyId = ConfigAliyunOss::VeekaInternalAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::VeekaInternalAccessKeySecret;
                break;
            case self::APC_ROOM_RECORD:
                $AccessKeyId = ConfigAliyunOss::ApcRoomRecordAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::ApcRoomRecordAccessKeySecret;
                break;
            case self::BUCKET_DEV_PS:
                $AccessKeyId = ConfigAliyunOss::PsAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::PsAccessKeySecret;
                break;
            case self::RBP_IMAGE:
            case self::RBP_DEV:
                $AccessKeyId = ConfigAliyunOss::RbpAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::RbpAccessKeySecret;
                break;
            case self::IM_CN_LOG:
            case self::IM_CN_LOG_DEV:
                $AccessKeyId = ConfigAliyunOss::BcImCnLogAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::BcImCnLogAccessKeySecret;
                break;
            case self::SLP_DEV:
                $AccessKeyId = ConfigAliyunOss::SlpAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::SlpAccessKeySecret;
                break;
            case self::WHO_IMAGE:
                $AccessKeyId = ConfigAliyunOss::WhoAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::WhoAccessKeySecret;
                break;
            case self::APC_ADMIN_DATA:
                $AccessKeyId = ConfigAliyunOss::ApcAdminDataAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::ApcAdminDataAccessKeyIdSecret;
                break;
            case self::APSG_ADMIN_DATA:
                $AccessKeyId = ConfigAliyunOss::ApsgAdminDataAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::ApsgAdminDataAccessKeySecret;
                break;
            case self::CHANNEL_BILL:
                $AccessKeyId = ConfigAliyunOss::ZengzhangAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::ZengzhangAccessKeySecret;
                break;
            case self::ALLO_IMAGE:
                $AccessKeyId = ConfigAliyunOss::AlloImgAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::AlloImgAccessKeySecret;
                break;
            case self::ZH_EMR:
                $AccessKeyId = ConfigAliyunOss::ZhEmrAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::ZhEmrAccessKeySecret;
                break;
            case self::PS_DEV_IMAGE:
                $AccessKeyId = ConfigAliyunOss::PsDevAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::PsDevAccessKeySecret;
                break;
            default:
                $AccessKeyId = ConfigAliyunOss::AccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::AccessKeySecret;
        }
        return [$AccessKeyId, $AccessKeySecret];
    }

    /**
     * Bucket + AccessKeyID组合
     * 示例：
     * const DataEmr01AccessKeyId
     * @return array
     */
    private function getConfigAkSk($bucket)
    {
        $key = str_replace(' ', '', ucwords(str_replace('-', ' ', $bucket)));
        $id = $key . 'AccessKeyId';
        $secret = $key . 'AccessKeySecret';
        $class = new \ReflectionClass('Config\ConfigAliyunOss');
        if ($class->hasConstant($id) && $class->hasConstant($secret)) {
            return [$class->getConstant($id), $class->getConstant($secret)];
        }
        return [];
    }

    public function client()
    {
        [$AccessKeyId, $AccessKeySecret] = $this->getAkSk();
        try {
            $ossClient = new OssClient($AccessKeyId, $AccessKeySecret, $this->_endpoint, false);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return null;
        }
        return $ossClient;
    }

    public function moveObject($from, $to)
    {
        if ($this->s3Instance) {
            return $this->s3Instance->moveFileTo($from, $to);
        }

        $copyOptions = [];
        $client = $this->client();
        if (!$client) return false;
        try {
            $client->copyObject($this->_bucket, $from, $this->_bucket, $to, $copyOptions);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return false;
        }
        try {
            $client->deleteObject($this->_bucket, $from);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
        }
        return true;
    }

    public function doesObjectExist($object)
    {
        if ($this->s3Instance) {
            return $this->s3Instance->doesObjectExist($object);
        }

        $client = $this->client();
        if (!$client) return false;
        try {
            return $client->doesObjectExist($this->_bucket, $object);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return false;
        }
    }

    public function getObjectMeta($object, $options = null)
    {
        if ($this->s3Instance) {
            return $this->s3Instance->getObjectMeta($object);
        }
        $client = $this->client();
        if (!$client) return false;
        try {
            return $client->getObjectMeta($this->_bucket, $object, $options);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return false;
        }
    }

    public function forbidOverwrite($forbidOverwrite = false)
    {
        if ($forbidOverwrite) {
            $this->_options[OssClient::OSS_HEADERS] += array(
                'x-oss-forbid-overwrite' => 'true'
            );
        }
    }

    /**
     * @param int $fileMaxSize 文件大小限制  0不限制
     * @param string $dir 用户上传文件时指定的前缀。
     * @param int $expire 设置该policy超时时间是$expire s. 即这个policy过了这个有效时间，将不能访问
     * @param string $endPoint
     * @return array
     * @throws \Exception
     */
    public function ossSign($fileMaxSize, $dir, $expire = 3600, $endPoint = '')
    {
        if ($this->s3Instance) {
            return $this->s3Instance->ossSign($fileMaxSize, $dir, $expire, $endPoint);
        }

        [$ak, $sk] = $this->getAkSk();

        $host = 'https://' . $this->_bucket . '.' . $this->_endpoint;
        if ($endPoint) {
            $host = 'https://' . $this->_bucket . '.' . $endPoint;
        }

        $end = time() + $expire;
        $expiration = $this->gmtIso8601($end);

        $conditions = [];

        if (!$endPoint) {
            // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
            $conditions[] = ['starts-with', '$key', $dir];
        }

        //最大文件大小.用户可以自己设置
        if ($fileMaxSize) {
            $conditions[] = ['content-length-range', 0, $fileMaxSize];
        }

        $base64Policy = base64_encode(json_encode(['expiration' => $expiration, 'conditions' => $conditions]));

        return [
            'accessid'  => $ak,
            'host'      => $host,
            'policy'    => $base64Policy,
            'signature' => base64_encode(hash_hmac('sha1', $base64Policy, $sk, true)),
            'expire'    => $end,
            'dir'       => $dir // 这个参数是设置用户上传文件时指定的前缀。
        ];
    }

    protected function gmtIso8601($time)
    {
        $dtStr = date('c', $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ATOM); // \DateTime::ISO8601
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration . 'Z';
    }

    public function signUrl($object, $timeout = 60, $method = 'GET', $options = null)
    {
        if ($this->s3Instance) {
            return $this->s3Instance->signUrl($object, $timeout, $method, $options);
        }

        $client = $this->client();
        if (!$client) return '';
        try {
            return $client->signUrl($this->_bucket, $object, $timeout, $method, $options);
        } catch (OssException $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return '';
        }
    }

    // 使用 __call 魔术方法代理其他未定义的方法
    public function __call($name, $arguments)
    {
        if ($this->s3Instance && method_exists($this->s3Instance, $name)) {
            return call_user_func_array([$this->s3Instance, $name], $arguments);
        }
        throw new BadMethodCallException("Method $name does not exist.");
    }
}
