<?php

namespace Imee\Comp\Common\Sdk;

class SdkVideoService extends SdkBase
{
    // 视频服务地址
    const videoScanDomain = 'http://' . Serv_Video_Scan;
    const VIDEO = 'video'; // 视频审核服务接口
    // 接口
    public static $route = [
        self::VIDEO => '/api/green_video',
    ];

    /**
     * @param $urlType
     * @param array $postData
     * @return array
     */
    public function post($urlType, array $postData = []): array
    {
        $url = $this->getUrl($urlType);
        $this->sign($postData);
        return $this->call($url, 'POST', ['json' => $postData]);
    }

    /**
     * @param string $urlType
     * @return string
     */
    private function getUrl(string $urlType): string
    {
        $baseDomain = self::videoScanDomain;
        if (isset(self::$route[$urlType])) {
            return $baseDomain . self::$route[$urlType];
        } else {
            return $baseDomain . "/";
        }
    }

    /**
     * 签名
     * @param array $postData
     * @return void
     */
    private function sign(array &$postData)
    {

    }
}