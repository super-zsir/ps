# 模块组件库

## 这个目录下的文件通过脚本更新

## 安装/更新 common-message模块
## php comp.php update common-message

## 删除 common-message模块
## php comp.php delete common-message

## 项目与命名空间
```
1.基础框架 bms-comp-framework
创建新项目直接copy此框架代码即可

2.业务模块
根据业务线创建独立的组件git仓库存放。
公会 bms-comp-broker
Imee\Comp\Broker

运营 bms-comp-operate
Imee\Comp\Operate

低代码 bms-lesscode-api
Imee\Comp\Lesscode

3.公共模块 bms-comp-common
放置一些公共工具类和函数，方便业务使用。
Imee\Comp\Common\Support
Imee\Comp\Common\Rpc
```