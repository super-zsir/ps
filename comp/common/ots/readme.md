# 配置

## 支持aws dynamodb 需要安装
```
composer require async-aws/dynamo-db
```
### dynamodb示例
```PHP
<?php

namespace Imee\Models\Ots;

use Imee\Comp\Common\Ots\DynamoDbBase;

class OTSXsCircle extends DynamoDbBase
{
    const SCHEMA = 'xs-friend-circle';
    
    public function getList($request)
    {
        return $this->query($request);
    }
}
```

## 支持 aliyun tablestore 需要安装
```
composer require aliyun/aliyun-tablestore-sdk-php
```
### tablestore示例
```PHP
<?php

namespace Imee\Models\Ots;

use Imee\Comp\Common\Ots\OTSBase;

// 用于朋友圈相关内容
class OTSXsCircle extends OTSBase
{
    const SCHEMA = 'xs-friend-circle';
    
    public function getList($request)
    {
        return $this->query($request);
    }
}
```
