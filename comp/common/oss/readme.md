# 配置oss

## 支持aws s3 需要安装
```
composer require async-aws/s3 1.14.0
```

## 使用示例
```
//第2个参数可选，根据自己bucket所需使用对应的endpoint，默认null使用国内
$client        = new OssUpload($bucket, OssUpload::ENDPOINT_OUT);
$hasUploadSucc = $client->moveFileTo($fileNamePath, $remoteNamePath);
```

