# 上传接口

# 配置oss

## config_define.php里配置

## 依赖getid3包获取音频/视频时长

```PHP
// OSS
define('BUCKET_DEV', 'bb-admin-test');//测试bucket
define('BUCKET_ONLINE', 'xs-image');//线上bucket
define('OSS_IMAGE_URL_WEB', 'http://xs-image.starifymusic.com');//线上外网域名
define('OSS_IMAGE_URL_LOCAL', 'https://xs-starify.oss-ap-southeast-1-internal.aliyuncs.com');//线上内网域名
define('OSS_IMAGE_URL_TEST', 'http://bb-admin-test.oss-cn-hangzhou.aliyuncs.com');//测试域名


   Imee\Service\Helper.php
   /**
     * 组装oss访问url
     * @param $url
     * @param bool $isLocal 是否使用内网地址
     * @param string $bucket
     * @return mixed|string
     */
    public static function getHeadUrl($url, bool $isLocal = false, $bucket = '')
    {
    
    }
```
# 使用示范例

## 上传音频获取时间
/api/common/upload/voice?type=getDuration

## 指定上传路径
/api/common/upload/voice?path=file/

## 上传图片
/api/common/upload/image?allowFileSize=2048&allowExt=jpg,png,gif

## 指定上传bucket
/api/common/upload/voice?bucket=xs-flock

## 支持自定义方法组装上传路径 (通过helper/traits/UploadFileTrait在项目里自定义方法处理)
## 调用UploadHelperTrait里commodity方法
/api/common/upload/file?type=commodity

## 指定图片比例
/api/common/upload/image?ratio=16:9

## 指定图片宽度高度
/api/common/upload/image?width=210&height=210