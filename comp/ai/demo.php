<?php

require_once __DIR__ . '/../app/index.php'; // 假设已经加载了框架环境
// 或者如果是在框架内运行，无需 require

use Imee\Comp\Ai\CodeGenerator;

// 1. 初始化生成器 (此处传入 null 使用模拟生成，实际可传入实现了 complete 方法的对象)
$generator = new CodeGenerator();

try {
    // 2. 调用生成
    // 参数: 模块名 (如 ka), 业务名 (如 user, 对应 XsUserProfile), 需求描述
    $code = $generator->generateListProcess(
        'ka', 
        'user', 
        '查询用户列表，包含VIP等级和注册时间', 
        true // true 表示直接写入文件
    );
    
    echo "代码生成成功！\n";
    echo $code;
} catch (Exception $e) {
    echo "生成失败: " . $e->getMessage();
}
