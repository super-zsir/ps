<?php

namespace OSS;

use AsyncAws\S3\Input\GetObjectRequest;
use AsyncAws\S3\S3Client;
use DateTimeImmutable;
use AsyncAws\S3\Exception\NoSuchKeyException;
use Imee\Comp\Common\Log\LoggerProxy;
use Config\ConfigAliyunOss;

class S3Upload
{
    private $_bucket;
    private $_region;
    private $_endpoint;
    private $_s3Client;
    private $_options;

    const TOP_DEV_IMAGE = 'top-dev-image';
    const TOP_IMAGE = 'top-image';
    const TOP_ALLO_IMAGE = 'top-allo-image';

    public static $buckets = [
        self::TOP_IMAGE,
        self::TOP_ALLO_IMAGE,
        self::TOP_DEV_IMAGE
    ];

    // region
    const SGP_REGION = 'ap-southeast-1';

    // endpoint
    const SGP_ENDPOINT_OUT = 'https://s3-ap-southeast-1.amazonaws.com';
    const SGP_ENDPOINT_INTERNAL = 'https://s3.ap-southeast-1.amazonaws.com';


    public function __construct($bucket, $endpoint = null)
    {
        $this->_bucket = $bucket;
        [$region, $endpoint] = $this->getRegionAndEndpoint($endpoint);
        $this->_region = $region;
        $this->_endpoint = $endpoint;

        $this->_options = [
            'CacheControl' => 'max-age=31536000'
        ];

        // 创建S3客户端
        $this->_s3Client = $this->client();
    }

    public function getRegionAndEndpoint($endpoint = null): array
    {
        switch ($this->_bucket) {
            case self::TOP_IMAGE:
            case self::TOP_ALLO_IMAGE:
            case self::TOP_DEV_IMAGE:
                $region = self::SGP_REGION;
                $endpointNew = self::SGP_ENDPOINT_OUT;
                break;
            default:
                $region = self::SGP_REGION;
                $endpointNew = self::SGP_ENDPOINT_INTERNAL;
        }

        if (!$endpoint) {
            $endpoint = $endpointNew;
        }

        return [$region, $endpoint];
    }

    public function moveFile($localFile, $ext = null, $addIfExist = true)
    {
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

    public function moveFileTo($localFilePath, $remoteFileName)
    {
        try {
            /** PutObjectOutput $resp */
            $resp = $this->_s3Client->putObject([
                'Bucket'      => $this->_bucket,
                'Key'         => $remoteFileName,
                'Body'        => file_get_contents($localFilePath),
                'ContentType' => mime_content_type($localFilePath),
                'Metadata'    => $this->_options
            ]);
            return $resp->info();
        } catch (\Exception $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage() . "::" . $this->_bucket . "::" . $remoteFileName . "::" . $localFilePath);
            return false;
        }
    }

    public function delete($objectKey)
    {
        try {
            return $this->_s3Client->deleteObject([
                'Bucket' => $this->_bucket,
                'Key'    => $objectKey
            ]);
        } catch (\Exception $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage() . "::" . $this->_bucket . "::" . $objectKey);
            return false;
        }
    }

    public function setOptions(array $options)
    {
        $this->_options = $options;
    }

    public function listObjects($options = [])
    {
        try {
            $params = ['Bucket' => $this->_bucket];
            if (isset($options['prefix'])) {
                $params['Prefix'] = $options['prefix'];
            }

            $result = $this->_s3Client->listObjectsV2($params);
            return $result['Contents'];
        } catch (\Exception $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage() . "::" . $this->_bucket);
            return false;
        }
    }

    public function putObject($object, $content)
    {
        try {
            return $this->_s3Client->putObject([
                'Bucket'   => $this->_bucket,
                'Key'      => $object,
                'Body'     => $content,
                'Metadata' => $this->_options
            ]);
        } catch (\Exception $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage() . "::" . $this->_bucket . "::" . $object);
            return false;
        }
    }

    private function getAkSk()
    {
        //自动匹配aksk
        $config = $this->getConfigAkSk($this->_bucket);
        if ($config) {
            return $config;
        }

        switch ($this->_bucket) {
            case self::TOP_IMAGE:
                $AccessKeyId = ConfigAliyunOss::TopImageAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::TopImageAccessKeySecret;
                break;
            case self::TOP_ALLO_IMAGE:
                $AccessKeyId = ConfigAliyunOss::TopAlloImageAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::TopAlloImageAccessKeySecret;
                break;
            case self::TOP_DEV_IMAGE:
                $AccessKeyId = ConfigAliyunOss::TopDevImageAccessKeyId;
                $AccessKeySecret = ConfigAliyunOss::TopDevImageAccessKeySecret;
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
        [$AccessKeyId, $AccessKeySecret] = self::getAkSk();

        return new S3Client([
            'region'          => $this->_region,
            'accessKeyId'     => $AccessKeyId,
            'accessKeySecret' => $AccessKeySecret,
            'endpoint'        => $this->_endpoint,
        ]);
    }

    public function ossSign($fileMaxSize, $dir, $expire = 3600, $endPoint = '')
    {
        [$AccessKeyId, $AccessKeySecret] = $this->getAkSk();

        $host = 'https://' . $this->_bucket . '.s3.amazonaws.com';
        if ($endPoint) {
            $host = $endPoint;
        }

        $expiration = time() + $expire;

        $conditions = [];

        if (!$endPoint) {
            $conditions[] = ['starts-with', '$key', $dir];
        }

        if ($fileMaxSize) {
            $conditions[] = ['content-length-range', 0, $fileMaxSize];
        }

        $policyJson = json_encode([
            'expiration' => gmdate('Y-m-d\TH:i:s\Z', $expiration),
            'conditions' => $conditions,
        ]);

        $base64Policy = base64_encode($policyJson);
        $signature = base64_encode(hash_hmac('sha1', $base64Policy, $AccessKeySecret, true));

        return [
            'accessid'  => $AccessKeyId,
            'host'      => $host,
            'policy'    => $base64Policy,
            'signature' => $signature,
            'expire'    => $expiration,
            'dir'       => $dir
        ];
    }

    public function signUrl($object, $timeout = 60, $method = 'GET', $options = [])
    {
        try {
            $expiresAt = (new DateTimeImmutable())->modify('+' . $timeout . ' seconds');

            $getObjectRequest = new GetObjectRequest();
            $getObjectRequest->setBucket($this->_bucket);
            $getObjectRequest->setKey($object);

            return $this->_s3Client->presign($getObjectRequest, $expiresAt);
        } catch (\Exception $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage() . "::" . $this->_bucket . "::" . $object);
            return '';
        }
    }

    public function doesObjectExist($object)
    {
        try {
            $input = [
                'Bucket' => $this->_bucket,
                'Key'    => $object
            ];
            $this->_s3Client->headObject($input);
            return true;
        } catch (NoSuchKeyException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getEndPoint()
    {
        return $this->_endpoint;
    }

    public function getBucket()
    {
        return $this->_bucket;
    }

    public function getObjectMeta($object)
    {
        // 获取对象的元信息
        $result = $this->_s3Client->headObject([
            'Bucket' => $this->_bucket,
            'Key'    => $object,
        ]);

        // 提取元信息数据
        return $result->getMetadata();
    }

    public function modifyMetaForObject($object, $contentType = 'image/gif', $maxAge = 31536000)
    {
        $options = [
            'Bucket'                      => $this->_bucket,
            'Key'                         => $object,
            'CopySource'                  => "{$this->_bucket}/{$object}",
            'MetadataDirective'           => 'REPLACE',
            'CopySourceIfModifiedSince'   => null,
            'CopySourceIfMatch'           => null,
            'CopySourceIfNoneMatch'       => null,
            'CopySourceIfUnmodifiedSince' => null,
            'ContentType'                 => $contentType,
            'CacheControl'                => 'max-age=' . $maxAge,
        ];

        try {
            $this->_s3Client->copyObject($options);
        } catch (\Exception $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return false;
        }

        return true;
    }

    public function copyObject($from, $to)
    {
        try {
            $this->_s3Client->copyObject([
                'Bucket'     => $this->_bucket,  // 替换为源和目标对象所在的存储桶名称
                'Key'        => $to,  // 目标对象的 Key（路径）
                'CopySource' => "{$this->_bucket}/{$from}",  // 源对象的 Bucket 和 Key 组合
            ]);

            return true;
        } catch (\Exception $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return false;
        }
    }

    public function downloadToLocal($object, $localFile)
    {
        $args = [
            'Bucket'              => $this->_bucket,
            'Key'                 => $object,
            'ResponseContentType' => 'application/octet-stream',
            'SaveAs'              => $localFile
        ];

        try {
            $this->_s3Client->getObject($args);
            return true;
        } catch (NoSuchKeyException $e) {
            return false;
        } catch (\Exception $e) {
            LoggerProxy::instance()->error($e->getFile() . $e->getLine() . $e->getMessage());
            return false;
        }
    }

    public function getObject($object, $options = null)
    {
        $args = [
            'Bucket' => $this->_bucket,
            'Key'    => $object,
        ];

        // 处理可选参数
        if ($options !== null) {
            // 下载到指定路径
            if (isset($options['fileDownload'])) {
                $distPath = $options['fileDownload'];
                $args['ResponseContentType'] = 'application/octet-stream';
                $args['SaveAs'] = $distPath;
            }
        }

        return $this->_s3Client->getObject($args)->getBody();
    }
}