# 🛡️ 基于nocode_schema_config表的APIJSON权限控制方案

## 📋 方案概述

通过扩展现有的 `nocode_schema_config` 表，增加字段来控制每个表的 GET、POST、PUT、DELETE 操作权限，实现细粒度的APIJSON权限控制。

---

## 🔧 1. 数据库表结构扩展

### 1.1 新增权限控制字段

```sql
ALTER TABLE `nocode_schema_config` 
ADD COLUMN `allow_get` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否允许GET查询 1=允许 0=禁止',
ADD COLUMN `allow_post` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否允许POST创建 1=允许 0=禁止',
ADD COLUMN `allow_put` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否允许PUT更新 1=允许 0=禁止', 
ADD COLUMN `allow_delete` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否允许DELETE删除 1=允许 0=禁止',
ADD COLUMN `permission_config` json DEFAULT NULL COMMENT 'JSON格式的详细权限配置';
```

### 1.2 permission_config JSON结构设计

```json
{
    "role_based": {
        "admin": ["GET", "POST", "PUT", "DELETE"],
        "user": ["GET", "POST", "PUT"],
        "guest": ["GET"]
    },
    "field_restrictions": {
        "GET": ["password", "salt"],
        "POST": ["system_id", "super"],
        "PUT": ["user_id", "create_time"],
        "DELETE": []
    },
    "custom_rules": {
        "self_only_fields": ["password", "email", "phone"],
        "admin_only_fields": ["super", "system_id"],
        "readonly_fields": ["user_id", "create_time", "modify_time"]
    }
}
```

### 1.3 示例配置数据

```sql
-- 更新CmsUser表的权限配置
UPDATE `nocode_schema_config` SET 
    `allow_get` = 1,
    `allow_post` = 1, 
    `allow_put` = 1,
    `allow_delete` = 0,  -- 默认禁止删除操作，确保安全
    `permission_config` = JSON_OBJECT(
        'role_based', JSON_OBJECT(
            'admin', JSON_ARRAY('GET', 'POST', 'PUT', 'DELETE'),
            'user', JSON_ARRAY('GET', 'POST', 'PUT'),
            'guest', JSON_ARRAY('GET')
        ),
        'field_restrictions', JSON_OBJECT(
            'GET', JSON_ARRAY(),
            'POST', JSON_ARRAY('password', 'salt'),
            'PUT', JSON_ARRAY('password', 'salt', 'system_id'),
            'DELETE', JSON_ARRAY()
        ),
        'custom_rules', JSON_OBJECT(
            'self_only_fields', JSON_ARRAY('password', 'salt', 'wechat_uid'),
            'admin_only_fields', JSON_ARRAY('super', 'system_id'),
            'readonly_fields', JSON_ARRAY('user_id', 'create_time')
        )
    )
WHERE `name` = 'CmsUser';
```

---

## 🏗️ 2. 架构设计

### 2.1 权限检查流程

```
APIJSON请求 → 权限Handle → 表级检查 → 字段级检查 → 自定义规则 → 继续处理/拒绝
```

### 2.2 多层权限控制

#### 层级一：表级权限
- 通过 `allow_get`、`allow_post`、`allow_put`、`allow_delete` 字段控制
- 直接禁止/允许整个表的特定操作
- 最粗粒度的权限控制，优先级最高

#### 层级二：角色权限
- 基于用户角色的操作权限控制
- 支持 admin、user、guest 等角色分级
- 在 `permission_config.role_based` 中配置

#### 层级三：字段权限
- 针对不同操作类型的字段访问控制
- 可以隐藏敏感字段或限制修改
- 在 `permission_config.field_restrictions` 中配置

#### 层级四：自定义规则
- 自访问规则：用户只能访问自己的记录
- 管理员专用字段：某些字段只有管理员能操作
- 只读字段：创建后不允许修改的字段
- 在 `permission_config.custom_rules` 中配置

---

## 🛠️ 3. 技术实现要点

### 3.1 权限Handle设计

```php
class SchemaPermissionHandle extends AbstractHandle
{
    public function handle() {
        // 1. 获取用户角色和表名
        $currentUser = $this->getCurrentUser();
        $userRole = $this->getUserRole($currentUser);
        $tableName = $this->getTableName();
        $method = $this->getHttpMethod();
        
        // 2. 查询表权限配置
        $config = NoCodeSchemaConfig::getConfigByTable($tableName);
        
        // 3. 执行多层权限检查
        $this->checkTablePermission($tableName, $method, $userRole);
        $this->checkFieldPermission($tableName, $method, $userRole);
        $this->applyCustomRules($tableName, $method, $userRole, $currentUser);
        
        // 4. 应用字段过滤
        $this->filterRestrictedFields($tableName, $method, $userRole);
        
        // 5. 记录审计日志
        $this->logPermissionCheck($tableName, $method, $userRole);
    }
}
```

### 3.2 配置模型设计

```php
class NoCodeSchemaConfig extends BaseModel
{
    // 根据表名获取权限配置
    public static function getConfigByTable(string $tableName, int $systemId = 4): ?array
    
    // 检查表操作权限
    public static function isMethodAllowed(string $tableName, string $method, int $systemId = 4): bool
    
    // 检查用户角色权限
    public static function checkUserPermission(string $tableName, string $method, string $role, int $systemId = 4): bool
    
    // 获取字段限制
    public static function getFieldRestrictions(string $tableName, string $method, int $systemId = 4): array
    
    // 获取自定义规则
    public static function getCustomRules(string $tableName, int $systemId = 4): array
    
    // 批量检查权限
    public static function batchCheckPermissions(array $tableNames, string $method, string $role, int $systemId = 4): array
}
```

### 3.3 集成点设计

#### Handle链集成
```php
// 在 Handle.php 的 queryMethodRules 最前面添加权限检查
protected $queryMethodRules = [
    'query' => [
        // 0. Schema权限验证（最高优先级）
        SchemaPermissionHandle::class,
        
        // 1. 结构和安全校验
        ValidateMustHandle::class,
        ValidateRefuseHandle::class,
        // ... 其他Handle
    ]
];
```

#### 方法传递机制
- 需要将HTTP方法信息传递到Handle中
- 可以通过扩展 ConditionEntity 或 TableEntity 来传递方法信息
- 或者通过全局变量/上下文传递

#### 用户角色获取
```php
private function getUserRole(?array $currentUser): string
{
    if (!$currentUser) {
        return 'guest';
    }
    
    // 检查是否为超级管理员
    if (isset($currentUser['super']) && $currentUser['super'] == 1) {
        return 'admin';
    }
    
    // 复用现有的权限系统判断角色
    // 可以基于 CmsModuleUser::getUserAllAction() 判断
    return 'user';
}
```

---

## 📊 4. 配置管理方案

### 4.1 默认权限策略

```json
{
    "safe_tables": {
        "CmsUser": {
            "allow_get": true,
            "allow_post": true, 
            "allow_put": true,
            "allow_delete": false,
            "comment": "用户表：允许查询、创建、更新，禁止删除"
        },
        "CmsModules": {
            "allow_get": true,
            "allow_post": false,
            "allow_put": false,
            "allow_delete": false,
            "comment": "模块表：只允许查询，禁止修改"
        }
    },
    "dangerous_tables": {
        "cms_sensitive_data": {
            "allow_get": false,
            "allow_post": false,
            "allow_put": false, 
            "allow_delete": false,
            "comment": "敏感数据表：完全禁止APIJSON访问"
        }
    }
}
```

### 4.2 权限管理接口设计

```php
// API接口设计
class SchemaPermissionController extends BaseController
{
    // 获取表权限配置
    public function getConfigAction()
    // GET /api/schema-permission/config?table=CmsUser&system_id=4
    
    // 更新表权限配置  
    public function updateConfigAction()
    // POST /api/schema-permission/config
    
    // 批量检查权限
    public function batchCheckAction()
    // POST /api/schema-permission/batch-check
    
    // 重置默认权限
    public function resetConfigAction()
    // POST /api/schema-permission/reset
    
    // 获取权限配置列表
    public function listConfigsAction()
    // GET /api/schema-permission/list?system_id=4&page=1&page_size=20
}
```

### 4.3 配置示例

#### 基础权限配置
```json
{
    "table_name": "CmsUser",
    "allow_get": true,
    "allow_post": true,
    "allow_put": true,
    "allow_delete": false
}
```

#### 详细权限配置
```json
{
    "table_name": "CmsUser",
    "allow_get": true,
    "allow_post": true,
    "allow_put": true,
    "allow_delete": false,
    "permission_config": {
        "role_based": {
            "admin": ["GET", "POST", "PUT", "DELETE"],
            "hr": ["GET", "POST", "PUT"],
            "user": ["GET", "PUT"],
            "guest": []
        },
        "field_restrictions": {
            "GET": ["password", "salt"],
            "POST": ["super", "system_id"],
            "PUT": ["user_id", "create_time", "password", "salt"],
            "DELETE": []
        },
        "custom_rules": {
            "self_only_fields": ["password", "email", "phone"],
            "admin_only_fields": ["super", "system_id", "bigarea"],
            "readonly_fields": ["user_id", "create_time", "modify_time"]
        }
    }
}
```

---

## 🔒 5. 安全特性

### 5.1 默认安全策略
- **白名单模式**：未配置的表默认禁止访问
- **最小权限**：默认配置遵循最小权限原则
- **删除保护**：DELETE操作默认禁止，需要显式配置

### 5.2 防护机制
- **服务端强制**：权限检查在服务端执行，客户端无法绕过
- **多重验证**：表级→角色级→字段级→自定义规则多重检查
- **参数绑定**：所有SQL查询使用参数绑定，防止SQL注入
- **审计日志**：所有权限检查行为记录日志

### 5.3 角色隔离
```json
{
    "role_hierarchy": {
        "guest": {
            "description": "未登录用户",
            "permissions": ["GET(limited)"],
            "restrictions": ["只能访问公开信息"]
        },
        "user": {
            "description": "普通用户", 
            "permissions": ["GET", "POST", "PUT(self)"],
            "restrictions": ["只能修改自己的记录"]
        },
        "admin": {
            "description": "管理员",
            "permissions": ["GET", "POST", "PUT", "DELETE"],
            "restrictions": ["受表级权限限制"]
        },
        "super_admin": {
            "description": "超级管理员",
            "permissions": ["ALL"],
            "restrictions": ["无限制"]
        }
    }
}
```

### 5.4 敏感字段保护
```json
{
    "sensitive_fields": {
        "password": "永不返回",
        "salt": "永不返回", 
        "wechat_uid": "只有本人和管理员可见",
        "super": "只有管理员可见",
        "system_id": "只读，不允许修改"
    }
}
```

---

## 📈 6. 扩展性设计

### 6.1 动态权限
- 支持运行时修改权限配置，无需重启服务
- 配置变更实时生效，通过缓存失效机制
- 支持权限配置版本管理和回滚

### 6.2 条件权限
```json
{
    "conditional_rules": {
        "time_based": {
            "description": "基于时间的权限控制",
            "example": "工作时间(9:00-18:00)内允许修改用户信息"
        },
        "ip_based": {
            "description": "基于IP的权限控制", 
            "example": "只有内网IP(192.168.*)允许删除操作"
        },
        "data_based": {
            "description": "基于数据的权限控制",
            "example": "只能修改自己创建的记录或负责的部门数据"
        },
        "quota_based": {
            "description": "基于配额的权限控制",
            "example": "每天最多创建100条记录"
        }
    }
}
```

### 6.3 权限模板
```json
{
    "permission_templates": {
        "readonly_table": {
            "allow_get": true,
            "allow_post": false,
            "allow_put": false,
            "allow_delete": false,
            "description": "只读表模板"
        },
        "user_data_table": {
            "allow_get": true,
            "allow_post": true,
            "allow_put": true,
            "allow_delete": false,
            "permission_config": {
                "custom_rules": {
                    "self_only_fields": ["*"],
                    "admin_only_fields": ["system_id", "status"]
                }
            },
            "description": "用户数据表模板"
        },
        "config_table": {
            "allow_get": true,
            "allow_post": false,
            "allow_put": true,
            "allow_delete": false,
            "permission_config": {
                "role_based": {
                    "admin": ["GET", "PUT"],
                    "user": ["GET"],
                    "guest": []
                }
            },
            "description": "配置表模板"
        }
    }
}
```

---

## 🎯 7. 实施建议

### 7.1 分阶段实施

#### 阶段一：基础表级权限控制（Week 1-2）
- 扩展 `nocode_schema_config` 表结构
- 实现基础的表级权限检查
- 创建 `SchemaPermissionHandle` 类
- 集成到APIJSON Handle链中

#### 阶段二：角色权限和字段权限（Week 3-4）
- 实现角色权限检查
- 实现字段级权限控制
- 添加敏感字段过滤功能
- 完善权限配置模型

#### 阶段三：自定义规则和管理功能（Week 5-6）
- 实现自定义权限规则
- 开发权限管理API接口
- 添加权限配置管理界面
- 完善审计日志功能

#### 阶段四：高级功能和优化（Week 7-8）
- 实现权限模板功能
- 添加条件权限支持
- 性能优化和缓存机制
- 完善文档和测试

### 7.2 兼容性保证
- 现有APIJSON功能不受影响，向下兼容
- 权限系统可选启用，通过配置开关控制
- 平滑升级路径，可以逐步迁移现有表配置
- 提供权限配置迁移工具

### 7.3 性能考虑

#### 缓存策略
```php
// 权限配置缓存
class PermissionCache
{
    // 缓存权限配置，减少数据库查询
    public static function getTableConfig(string $tableName): array
    
    // 缓存用户角色，减少权限计算
    public static function getUserRole(int $userId): string
    
    // 批量预加载权限配置
    public static function preloadConfigs(array $tableNames): void
}
```

#### 数据库优化
```sql
-- 为权限查询添加索引
CREATE INDEX idx_schema_permission ON nocode_schema_config (system_id, name, allow_get, allow_post, allow_put, allow_delete);

-- 为JSON字段添加虚拟列索引（MySQL 5.7+）
ALTER TABLE nocode_schema_config 
ADD COLUMN role_config_admin JSON GENERATED ALWAYS AS (JSON_EXTRACT(permission_config, '$.role_based.admin')) VIRTUAL,
ADD INDEX idx_admin_permissions (role_config_admin);
```

#### 批量操作优化
- 支持批量权限检查，减少数据库查询次数
- 使用 IN 查询批量获取多表权限配置
- 权限检查结果缓存，避免重复计算

---

## ✅ 8. 方案优势

### 8.1 确切可靠
- ✅ 基于现有表结构扩展，风险可控，不影响现有功能
- ✅ 多层权限防护，安全可靠，从表级到字段级全覆盖
- ✅ 配置持久化，重启不丢失，数据库存储确保可靠性
- ✅ 服务端强制执行，客户端无法绕过

### 8.2 灵活易用
- ✅ JSON配置支持复杂权限规则，扩展性强
- ✅ 提供管理界面，操作简便，支持可视化配置
- ✅ 支持批量操作，效率高，可以快速配置多个表
- ✅ 权限模板复用，减少重复配置工作

### 8.3 扩展性强
- ✅ 权限配置可动态调整，支持热更新
- ✅ 支持自定义权限规则，满足复杂业务需求
- ✅ 预留扩展接口，支持条件权限等高级功能
- ✅ 角色体系可扩展，支持复杂的组织架构

### 8.4 维护友好
- ✅ 配置集中管理，便于维护和审计
- ✅ 权限变更有日志记录，可追溯
- ✅ 支持权限配置备份和恢复
- ✅ 提供权限检查工具，便于调试

---

## 🚀 9. 总结

这个方案充分利用了现有的 `nocode_schema_config` 表基础，通过扩展字段和JSON配置，实现了企业级的APIJSON权限控制系统。方案具有以下特点：

1. **渐进式实施**：可以分阶段实施，不影响现有功能
2. **多层防护**：从表级到字段级的全方位权限控制
3. **配置灵活**：JSON配置支持复杂的权限规则
4. **管理便捷**：提供完整的权限管理API和界面
5. **性能优化**：缓存机制和批量操作确保高性能
6. **安全可靠**：服务端强制执行，多重验证机制

通过实施这个方案，可以将APIJSON从一个开放的数据接口转变为一个安全可控的企业级数据服务，既保持了APIJSON的灵活性，又提供了必要的安全保障。