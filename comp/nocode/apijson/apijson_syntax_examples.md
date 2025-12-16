# APIJSON 语法示例大全

本文档展示了 APIJSON PHP SDK 支持的所有语法，包括查询语法和 CRUD 操作语法。

## 🎉 最新更新

### ✅ 嵌套 POST 插入功能完善 (2025-08-20)
- **新增功能**: 支持多层嵌套插入和智能外键检测
- **主要特性**:
  - 支持无限层级的嵌套插入（如：CmsUser → CmsModuleUser → CmsModules）
  - 智能外键检测，支持多种命名模式（`{父表名小写}_id`、`{父表主键}`、`parent_id` 等）
  - 手动指定外键值时，系统不会重复注入，避免冲突
  - 自动事务处理，确保数据一致性
- **使用场景**: 复杂的数据插入场景，如用户注册时同时创建权限、模块等关联数据
- **测试验证**: 已通过完整测试，包括基本嵌套、多层嵌套、手动外键等场景

### ✅ 关联查询 Limit 优化 (2025-08-14)
- **优化内容**: 当关联查询的字段是主键或唯一索引时，自动移除默认的 limit 10 限制
- **触发条件**: 
  - 查询中包含引用关系（`@` 语法）
  - 引用字段在目标表中是主键或唯一索引
  - 查询中没有明确设置 `@limit` 参数
- **优化效果**: 返回所有匹配的记录，而不是默认的 10 条
- **使用场景**: 多表关联查询中，当引用字段具有唯一性时，确保获取完整数据
- **测试验证**: 已通过完整测试，确保功能稳定可靠

**示例**:
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@limit": 5
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time",
      "@limit": 20
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
      // 注意：这里没有 @limit，但 module_id 是主键，会自动移除默认 limit
    }
  }
}
```

**返回结果**:
```json
{
  "[]": [
    {
      "BmsOperateLog": {
        "id": 3551,
        "uid": 100000001,
        "model": "openscreencard",
        "content": "发放",
        "operate_name": "admin",
        "XsUserProfile": {
          "uid": 100000001,
          "name": "符依依",
          "pay_room_money": 0,
          "XsUserMobile": {
            "uid": 100000001,
            "mobile": "886-18100000001"
          },
          "XsUserSettings": {
            "uid": 100000001,
            "language": "tr"
          },
          "XsUserMedal[]": [
            {
              "uid": 100000001,
              "medal_id": 44
            },
            {
              "uid": 100000001,
              "medal_id": 85
            }
          ]
        }
      }
    },
    {
      "BmsOperateLog": {
        "id": 3508,
        "uid": 100000027,
        "model": "activitytaskgameplaymultiwire",
        "content": "配置任务",
        "operate_name": "admin",
        "XsUserProfile": {
          "uid": 100000027,
          "name": "27",
          "pay_room_money": 0,
          "XsUserMobile": {
            "uid": 100000027,
            "mobile": "886-15500000027"
          },
          "XsUserSettings": {
            "uid": 100000027,
            "language": "zh_tw"
          },
          "XsUserMedal[]": [
            {
              "uid": 100000027,
              "medal_id": 18
            }
          ]
        }
      }
    },
    {
      "BmsOperateLog": {
        "id": 3514,
        "uid": 100000027,
        "model": "cms_modules",
        "content": "修改模块",
        "operate_name": "admin",
        "XsUserProfile": {
          "uid": 100000027,
          "name": "27",
          "pay_room_money": 0,
          "XsUserMobile": {
            "uid": 100000027,
            "mobile": "886-15500000027"
          },
          "XsUserSettings": {
            "uid": 100000027,
            "language": "zh_tw"
          },
          "XsUserMedal[]": [
            {
              "uid": 100000027,
              "medal_id": 18
            }
          ]
        }
      }
    },
    {
      "BmsOperateLog": {
        "id": 1,
        "uid": 100010255,
        "model": "CmsUser",
        "content": "修改用户",
        "operate_name": "符梓桐",
        "XsUserProfile": {
          "uid": 100010255,
          "name": "摘西瓜的猫～",
          "pay_room_money": 85800,
          "XsUserMobile": {
            "uid": 100010255,
            "mobile": "886-17320560001"
          },
          "XsUserSettings": {
            "uid": 100010255,
            "language": "zh_cn"
          },
          "XsUserMedal[]": [
            {
              "uid": 100010255,
              "medal_id": 16
            },
            {
              "uid": 100010255,
              "medal_id": 18
            }
          ]
        }
      }
    },
    {
      "BmsOperateLog": {
        "id": 2,
        "uid": 100010885,
        "model": "quickgiftconfig",
        "content": "修改",
        "operate_name": "符梓桐",
        "XsUserProfile": {
          "uid": 100010885,
          "name": "nickname",
          "pay_room_money": 0,
          "XsUserMobile": {
            "uid": 100010885,
            "mobile": "886-15926331263"
          },
          "XsUserSettings": {
            "uid": 100010885,
            "language": "ko"
          },
          "XsUserMedal[]": [
            {
              "uid": 100010885,
              "medal_id": 21
            }
          ]
        }
      }
    },
    {
      "BmsOperateLog": {
        "id": 3,
        "uid": 100010885,
        "model": "quickgiftconfig",
        "content": "修改",
        "operate_name": "符梓桐",
        "XsUserProfile": {
          "uid": 100010885,
          "name": "nickname",
          "pay_room_money": 0,
          "XsUserMobile": {
            "uid": 100010885,
            "mobile": "886-15926331263"
          },
          "XsUserSettings": {
            "uid": 100010885,
            "language": "ko"
          },
          "XsUserMedal[]": [
            {
              "uid": 100010885,
              "medal_id": 21
            }
          ]
        }
      }
    },
    {
      "BmsOperateLog": {
        "id": 3512,
        "uid": 100010888,
        "model": "multilang",
        "content": "批量修改",
        "operate_name": "admin",
        "XsUserProfile": {
          "uid": 100010888,
          "name": "嗯呢",
          "pay_room_money": 85800,
          "XsUserMobile": {
            "uid": 100010888,
            "mobile": "886-15926331260"
          },
          "XsUserSettings": {
            "uid": 100010888,
            "language": "zh_tw"
          },
          "XsUserMedal[]": [
            {
              "uid": 100010888,
              "medal_id": 28
            },
            {
              "uid": 100010888,
              "medal_id": 30
            },
            {
              "uid": 100010888,
              "medal_id": 33
            }
          ]
        }
      }
    },
    {
      "BmsOperateLog": {
        "id": 3562,
        "uid": 100010888,
        "model": "openscreencard",
        "content": "失效",
        "operate_name": "admin",
        "XsUserProfile": {
          "uid": 100010888,
          "name": "嗯呢",
          "pay_room_money": 85800,
          "XsUserMobile": {
            "uid": 100010888,
            "mobile": "886-15926331260"
          },
          "XsUserSettings": {
            "uid": 100010888,
            "language": "zh_tw"
          },
          "XsUserMedal[]": [
            {
              "uid": 100010888,
              "medal_id": 28
            },
            {
              "uid": 100010888,
              "medal_id": 30
            },
            {
              "uid": 100010888,
              "medal_id": 33
            }
          ]
        }
      }
    }
  ]
}
```

**优化前后对比**:
- **优化前**: `CmsModules[]` 只返回 10 条记录（受默认 limit 限制）
- **优化后**: `CmsModules[]` 返回所有匹配的记录（如 56 条）

### ✅ 聚合查询功能修复 (2025-08-13)
- **修复内容**: 数组查询 `[]` 中的聚合查询现在可以正常工作
- **支持功能**: `@group`、`COUNT(*)`、`SUM()`、`AVG()`、`MAX()`、`MIN()` 等聚合函数
- **使用场景**: 多表关联查询中的统计功能，如统计用户模块数量
- **测试验证**: 已通过完整测试，确保功能稳定可靠

**示例**:
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 5
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "user_id,COUNT(*) as module_count",
      "@group": "user_id"
    }
  }
}
```

## 📋 重要概念说明

### 单对象 vs 数组查询的区别

<table border="1" style="border-collapse: collapse; width: 100%;">
<tr>
<th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">语法</th>
<th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">含义</th>
<th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">返回结果</th>
<th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">适用场景</th>
</tr>
<tr>
<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">CmsUser</td>
<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">单对象查询</td>
<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">返回单个对象或null</td>
<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">根据主键查询、唯一条件查询</td>
</tr>
<tr>
<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">CmsUser[]</td>
<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">数组查询</td>
<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">返回对象数组</td>
<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">列表查询、条件查询、分页查询</td>
</tr>
</table>

### 查询类型对比

#### 单对象查询 (CmsUser)
```json
{
  "CmsUser": {
    "user_id": 1,
    "@column": "user_id,user_name,user_email"
  }
}
```
**返回结果**:
```json
{
  "CmsUser": {
    "user_id": 1,
    "user_name": "admin",
    "user_email": "admin@ee.com"
  }
}
```

#### 数组查询 (CmsUser[])
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@column": "user_id,user_name,user_email",
    "@limit": 10
  }
}
```
**返回结果**:
```json
{
  "CmsUser[]": [
    {
      "user_id": 1,
      "user_name": "admin",
      "user_email": "admin@ee.com"
    },
    {
      "user_id": 2,
      "user_name": "一只喵",
      "user_email": "248600766@qq.com"
    }
  ]
}
```

## 1. 基础查询语法

### 1.1 单对象查询
```json
{
  "CmsUser": {
    "user_id": 1,
    "@column": "user_id,user_name,user_email"
  }
}
```

### 1.2 数组查询
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@column": "user_id,user_name,user_email"
  }
}
```

### 1.3 条件查询对比

#### 单对象条件查询
```json
{
  "CmsUser": {
    "user_email": "admin@ee.com",
    "@column": "user_id,user_name,user_email"
  }
}
```

#### 数组条件查询
```json
{
  "CmsUser[]": {
    "user_name$": "admin",
    "@column": "user_id,user_name,user_email"
  }
}
```

## 2. 🔍 比较操作符

### 2.1 🔍 单对象比较查询
```json
{
  "CmsUser": {
    "user_id>": 1,
    "user_id<": 100,
    "@column": "user_id,user_name,modify_time"
  }
}
```

### 2.2 📋 数组比较查询
```json
{
  "CmsUser[]": {
    "user_id>": 1,
    "modify_time<": "2025-01-01 00:00:00",
    "@column": "user_id,user_name,modify_time",
    "@limit": 20
  }
}
```

### 2.3 ❌ 不等于查询
```json
{
  "CmsUser[]": {
    "user_id!=": 1,
    "@column": "user_id,user_name"
  }
}
```

## 3. 📦 集合操作符

### 3.1 ✅ IN 查询 (仅适用于数组查询)
```json
{
  "CmsUser[]": {
    "user_id{}": [1, 2, 3, 4, 5],
    "@column": "user_id,user_name"
  }
}
```

### 3.2 ❌ NOT IN 查询 (仅适用于数组查询)
```json
{
  "CmsUser[]": {
    "user_id!{}": [1, 2, 3],
    "@column": "user_id,user_name"
  }
}
```

### 3.3 空数组自动 IN (仅适用于数组查询)
```json
{
  "CmsUser[]": {
    "user_id": [],
    "@column": "user_id,user_name"
  }
}
```

## 4. 字符串操作符

### 4.1 LIKE 包含查询
```json
{
  "CmsUser[]": {
    "user_name$": "admin",
    "@column": "user_id,user_name"
  }
}
```

### 4.2 LIKE 开头查询
```json
{
  "CmsUser[]": {
    "user_name^": "admin",
    "@column": "user_id,user_name"
  }
}
```

### 4.3 REGEXP 正则查询
```json
{
  "CmsUser[]": {
    "user_name%": "^admin.*",
    "@column": "user_id,user_name"
  }
}
```

## 5. 范围查询

### 5.1 BETWEEN 范围查询
```json
{
  "CmsUser[]": {
    "modify_time$": "2025-01-01 00:00:00,2025-01-02 00:00:00",
    "@column": "user_id,user_name,modify_time"
  }
}
```

**注意**: 对于 `timestamp` 类型的字段（如 `modify_time`、`last_login_time`），请使用标准的日期时间格式 `"YYYY-MM-DD HH:MM:SS"`，而不是时间戳数字。

## 6. 逻辑操作符

### 6.1 OR 查询
```json
{
  "CmsUser[]": {
    "user_id|user_name": "1",
    "@column": "user_id,user_name"
  }
}
```

### 6.2 复杂逻辑查询 (@ 语法)
```json
{
  "CmsUser[]": {
    "@": {
      "operator": "OR",
      "user_id": 1,
      "user_name$": "admin"
    },
    "@column": "user_id,user_name"
  }
}
```

### 6.3 嵌套逻辑查询
```json
{
  "CmsUser[]": {
    "@": {
      "operator": "OR",
      "user_id": 1,
      "AND": {
        "user_status": 1,
        "OR": {
          "user_name$": "admin",
          "user_email$": "admin"
        }
      }
    },
    "@column": "user_id,user_name,user_email"
  }
}
```

## 7. 引用查询

### 7.1 单对象引用查询
```json
{
  "CmsModuleUser": {
    "id": 1,
    "@column": "user_id"
  },
  "CmsUser": {
    "user_id@": "CmsModuleUser/user_id",
    "@column": "user_id:uid,user_name:name"
  }
}
```

### 7.2 数组引用查询
```json
{
  "CmsModuleUser[]": {
    "id{}": [1,2,3,4,5,6,7],
    "@column": "id,user_id"
  },
  "CmsUser[]": {
    "user_id@": "CmsModuleUser/user_id",
    "@column": "user_id:uid,user_name:name"
  }
}
```

### 7.3 多表引用查询
```json
{
  "CmsModuleUser[]": {
    "id{}": [1,2,3],
    "@column": "id,user_id,module_id"
  },
  "CmsUser[]": {
    "user_id@": "CmsModuleUser/user_id",
    "@column": "user_id,user_name"
  },
  "CmsModules[]": {
    "module_id@": "CmsModuleUser/module_id",
    "@column": "module_id,module_name"
  }
}
```

## 7.4 多表关联查询示例

### 7.4.0 🚀 关联查询 Limit 优化说明

#### 7.4.0.1 优化背景
在多表关联查询中，当子表没有明确设置 `@limit` 时，系统会应用默认的 limit 10 限制。这可能导致数据不完整，特别是当引用字段具有唯一性时。

#### 7.4.0.2 优化机制
- **触发条件**: 引用字段在目标表中是主键或唯一索引
- **优化行为**: 自动移除默认的 limit 10 限制
- **优化范围**: 仅影响没有明确设置 `@limit` 的查询
- **兼容性**: 不影响已设置 `@limit` 的查询

#### 7.4.0.3 优化示例对比

**场景**: 查询用户及其模块权限

**优化前** (受默认 limit 限制):

> 💡 **说明**: CmsModules[] 没有设置 @limit，会受默认 limit 10 限制

```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@limit": 5
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time",
      "@limit": 20
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

**返回结果** (优化前):

> ❌ **问题**: CmsModules[] 只返回 10 条记录，受默认 limit 限制

```json
[
  {
    "CmsUser": {"user_id": 554, "user_name": "符梓桐2", "user_email": "admin@ee2.com"},
    "CmsModuleUser[]": [{"module_id": 2365, "create_time": 1702281816}, {"module_id": 2369, "create_time": 1702281816}],
    "CmsModules[]": [{"module_id": 2365, "module_name": "列表", "controller": "operate/luckygiftdetailed", "action": "list"}, {"module_id": 2369, "module_name": "导出", "controller": "operate/luckygiftdetailed", "action": "export"}]
  },
  {
    "CmsUser": {"user_id": 555, "user_name": "十五", "user_email": "Iywoo@aopacloud.sg"},
    "CmsModuleUser[]": [],
    "CmsModules[]": []
  }
]
```

**优化后** (自动移除默认 limit):

> ✅ **说明**: CmsModules[] 没有 @limit，但 module_id 是主键，系统自动移除默认 limit

```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@limit": 5
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time",
      "@limit": 20
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

**返回结果** (优化后):

> ✅ **效果**: CmsModules[] 返回所有 16 条匹配记录，不再受默认 limit 限制

```json
[
  {
    "CmsUser": {"user_id": 554, "user_name": "符梓桐2", "user_email": "admin@ee2.com"},
    "CmsModuleUser[]": [{"module_id": 2365, "create_time": 1702281816}, {"module_id": 2369, "create_time": 1702281816}, {"module_id": 2568, "create_time": 1702281816}],
    "CmsModules[]": [{"module_id": 2365, "module_name": "列表", "controller": "operate/luckygiftdetailed", "action": "list"}, {"module_id": 2369, "module_name": "导出", "controller": "operate/luckygiftdetailed", "action": "export"}, {"module_id": 2568, "module_name": "列表", "controller": "operate/pushcontent", "action": "list"}]
  },
  {
    "CmsUser": {"user_id": 555, "user_name": "十五", "user_email": "Iywoo@aopacloud.sg"},
    "CmsModuleUser[]": [],
    "CmsModules[]": []
  }
]
```

#### 7.4.0.4 优化条件详解

**✅ 会触发优化的场景**:

> 💡 **说明**: 引用主键字段，没有设置 @limit，会触发自动优化

```json
{
  "CmsModules[]": {
    "module_id@": "CmsModuleUser/module_id",
    "@column": "module_id,module_name"
  }
}
```

**❌ 不会触发优化的场景**:

1. **明确设置了 @limit**:

> ❌ **说明**: 明确设置了 @limit，不触发自动优化

```json
{
  "CmsModules[]": {
    "module_id@": "CmsModuleUser/module_id",
    "@column": "module_id,module_name",
    "@limit": 5
  }
}
```

2. **没有引用关系**:

> ❌ **说明**: 没有引用关系，不触发自动优化

```json
{
  "CmsModules[]": {
    "module_id>": 1000,
    "@column": "module_id,module_name"
  }
}
```

3. **引用字段不是主键或唯一索引**:

> ❌ **说明**: 引用字段不是主键或唯一索引，不触发自动优化

```json
{
  "CmsModules[]": {
    "module_name@": "CmsModuleUser/module_name",
    "@column": "module_id,module_name"
  }
}
```

#### 7.4.0.5 性能影响

**优化优势**:
- **数据完整性**: 确保获取所有匹配的记录
- **业务准确性**: 避免因 limit 限制导致的数据缺失
- **用户体验**: 提供完整的数据视图

**注意事项**:
- **数据量控制**: 当引用字段匹配大量记录时，可能影响性能
- **内存使用**: 大量数据可能增加内存消耗
- **网络传输**: 更多数据可能增加网络传输时间

**最佳实践**:

> 💡 **说明**: 控制主表记录数，让系统自动优化子表查询

```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 10
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

### 7.4.1 基础多表关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@limit": 10
    }
  }
}
```

### 7.4.2 用户-模块关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@limit": 5
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time"
    },
    "CmsModules": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

### 7.4.3 用户权限关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@limit": 3
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time,system_id"
    },
    "CmsModules": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action,deleted"
    }
  }
}
```

### 7.4.4 复杂业务关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "user_id>": 1,
      "@column": "user_id,user_name,user_email,modify_time",
      "@limit": 5,
      "@order": "modify_time-"
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time,system_id",
      "@limit": 20
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "deleted": 0,
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

### 7.4.5 条件关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "user_name$": "admin",
      "@column": "user_id,user_name,user_email",
      "@limit": 10
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "module_id>": 5,
      "@column": "module_id,create_time",
      "@limit": 15
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "deleted": 0,
      "@column": "module_id,module_name"
    }
  }
}
```

### 7.4.6 聚合关联查询（统计用户模块数量）
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "user_id>": 563,
      "@column": "user_id,user_name",
      "@limit": 4
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "user_id,COUNT(*) as module_count",
      "@group": "user_id"
    }
  }
}
```

**返回结果**:
```json
{
  "[]": [
    {
      "CmsUser": {
        "user_id": 564,
        "user_name": "Alvin@olaparty.sg"
      },
      "CmsModuleUser": null
    },
    {
      "CmsUser": {
        "user_id": 566,
        "user_name": "翔哥"
      },
      "CmsModuleUser": {
        "user_id": 566,
        "module_count": 13
      }
    },
    {
      "CmsUser": {
        "user_id": 567,
        "user_name": "ShawnLim@olaparty.sg"
      },
      "CmsModuleUser": null
    },
    {
      "CmsUser": {
        "user_id": 568,
        "user_name": "admin@ee.com"
      },
      "CmsModuleUser": {
        "user_id": 568,
        "module_count": 15
      }
    }
  ]
}
```

**注意**: 
1. 在多表关联查询中使用聚合时，聚合后的表无法被其他表引用，因为聚合会改变数据结构
2. 聚合查询在多表关联中返回单个汇总对象，而不是数组
3. 如果某个用户没有模块权限，聚合查询返回 `null`
4. **✅ 修复说明**: 现在聚合查询已支持在数组查询 `[]` 中正确工作，能够返回每个用户的模块统计信息

### 7.4.7 嵌套关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@limit": 3
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time",
      "CmsModules": {
        "module_id@": "/module_id",
        "@column": "module_name,controller,action"
      }
    }
  }
}
```

### 7.4.8 多层级关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 2
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,system_id",
      "CmsModules": {
        "module_id@": "/module_id",
        "@column": "module_name,parent_module_id",
        "CmsModules": {
          "module_id@": "/parent_module_id",
          "@column": "module_name,controller"
        }
      }
    }
  }
}
```

### 7.4.9 分页关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@limit": 10,
      "@offset": 0,
      "@order": "modify_time-"
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time",
      "@limit": 13
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name"
    }
  }
}
```

### 7.4.10 统计关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 5
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "user_id,COUNT(*) as module_count",
      "@group": "user_id"
    }
  }
}
```

**返回结果**:
```json
{
  "[]": [
    {
      "CmsUser": {
        "user_id": 1,
        "user_name": "admin"
      },
      "CmsModuleUser": {
        "user_id": 1,
        "module_count": 21
      }
    },
    {
      "CmsUser": {
        "user_id": 2,
        "user_name": "一只喵"
      },
      "CmsModuleUser": null
    }
  ]
}
```

**说明**: 聚合查询在多表关联中返回单个汇总对象，如果某个用户没有模块权限则返回空数组 `[]`。

### 7.4.11 用户完整信息查询
```json
{
  "[]": {
    "CmsUser": {
      "user_id": 1,
      "@column": "user_id,user_name,user_email,user_status,modify_time"
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time,system_id"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action,deleted"
    }
  }
}
```

### 7.4.12 用户权限系统关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email,system_id",
      "@limit": 5
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time,system_id"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "deleted": 0,
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

### 7.4.13 用户模块权限统计查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@limit": 10,
      "@order": "modify_time-"
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time,system_id"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,parent_module_id"
    }
  }
}
```

### 7.4.14 用户语言区域关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email,language,bigarea",
      "@limit": 10,
      "@order": "modify_time-"
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time,system_id"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

### 7.4.15 超级管理员权限查询
```json
{
  "[]": {
    "CmsUser": {
      "super": 1,
      "user_status": 1,
      "@column": "user_id,user_name,user_email,super,bigarea",
      "@limit": 20,
      "@order": "modify_time-"
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time,system_id"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

### 7.4.16 员工号关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "job_num!=": "",
      "@column": "user_id,user_name,user_email,job_num",
      "@limit": 50,
      "@order": "modify_time-"
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time,system_id"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

### 7.4.17 企业微信用户关联查询
```json
{
  "[]": {
    "CmsUser": {
      "from_wechat": 1,
      "user_status": 1,
      "@column": "user_id,user_name,user_email,wechat_uid",
      "@limit": 20,
      "@order": "modify_time-"
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time,system_id"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

### 7.4.18 系统用户关联查询
```json
{
  "[]": {
    "CmsUser": {
      "system_id": 1,
      "user_status": 1,
      "@column": "user_id,user_name,user_email,system_id",
      "@limit": 15,
      "@order": "modify_time-"
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time,system_id"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

### 7.4.19 应用用户关联查询
```json
{
  "[]": {
    "CmsUser": {
      "app": "5",
      "user_status": 1,
      "@column": "user_id,user_name,user_email,app",
      "@limit": 10
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time,system_id"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

### 7.4.20 用户登录时间关联查询
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "last_login_time>": "2025-01-01 00:00:00",
      "@column": "user_id,user_name,user_email,last_login_time",
      "@limit": 20,
      "@order": "last_login_time-"
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time,system_id"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

### 7.4.21 操作日志-用户多层关联查询
```json
{
  "[]": {
    "BmsOperateLog": {
      "uid>": 1,
      "@column": "id,uid,model,content,operate_name",
      "@limit": 10,
      "XsUserProfile": {
        "uid@": "/uid",
        "@column": "uid,name,pay_room_money",
        "XsUserMobile": {
          "uid@": "/uid",
          "@column": "uid,mobile"
        },
        "XsUserSettings": {
          "uid@": "/uid",
          "@column": "uid,language"
        },
        "XsUserMedal[]": {
          "uid@": "/uid",
          "@column": "uid,medal_id"
        }
      }
    }
  }
}
```

> 写法差异说明（数组根 vs 对象根数组表）

```json
{
  "BmsOperateLog[]": {
    "uid>": 1,
    "@column": "id,uid,model,content,operate_name",
    "@limit": 2,
    "XsUserProfile": {
      "uid@": "/uid",
      "@column": "uid,name,pay_room_money",
      "XsUserMobile": {"uid@": "/uid", "@column": "uid,mobile"},
      "XsUserSettings": {"uid@": "/uid", "@column": "uid,language"},
      "XsUserMedal[]": {"uid@": "/uid", "@column": "uid,medal_id"}
    }
  }
}
```

- 差异对比：
  - 对象根 + `BmsOperateLog[]`（上例）与 `"[]"` 包裹对象（本节原例）语义等价，均为“列表 + 多层嵌套”。
  - 列表查询`@limit`：
    - 放在 `BmsOperateLog[]` 上（对象根数组表）直接限制主表返回行数。
    - 放在 `"[]"` → `BmsOperateLog` 上（数组根）同样生效，两者结果结构一致：每条日志记录挂载 `XsUserProfile` 及其子表。
  - 解析与性能：两种写法均支持相对引用 `"/uid"`，嵌套解析一致；对象根数组表更直观，数组根便于同时并列多个主表。
  - 注意：不要把子表键（如 `XsUserProfile`）写进主表条件区，否则会被当成字段（已在实现层屏蔽）。

## 7.5 聚合查询与多表关联的限制和最佳实践

### 7.5.1 聚合查询的限制

#### ❌ 错误的聚合查询示例
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 5
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id",
      "@group": "user_id",
      "@sum": "module_id"  // ❌ 对主键求和无意义
    },
    "CmsModules": {
      "module_id@": "CmsModuleUser/module_id",  // ❌ 聚合后无法引用
      "@column": "module_id,module_name"
    }
  }
}
```

**问题分析**:
1. **对主键求和无意义**: `module_id` 是主键，求和没有业务含义
2. **聚合后无法引用**: 聚合查询会改变数据结构，其他表无法引用聚合后的字段
3. **引用关系断裂**: `CmsModules` 无法找到 `CmsModuleUser` 的 `module_id` 字段
4. **返回格式问题**: 聚合查询在多表关联中应该返回单个对象，而不是数组
5. **✅ 已修复**: 现在聚合查询在数组查询 `[]` 中可以正确工作，支持 `@group` 和聚合函数

#### ✅ 正确的聚合查询示例

**方案1: 只聚合最后一个表**
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 5
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "user_id,COUNT(*) as access_count",
      "@group": "user_id"
    }
  }
}
```

### 7.5.2 聚合查询最佳实践

#### 1. 选择合适的聚合字段
```json
// ✅ 有意义的聚合
"@sum": "create_time"      // 时间戳求和
"@count": "*"              // 记录数量统计
"@avg": "system_id"        // 平均值计算

// ❌ 无意义的聚合
"@sum": "module_id"        // 主键求和无意义
"@sum": "user_id"          // 主键求和无意义
```

#### 2. 聚合查询的适用场景
- **统计报表**: 用户数量、模块数量、访问次数等
- **数据分析**: 平均值、总和、最大值、最小值等
- **业务指标**: 活跃用户数、模块使用率等

#### 3. 多表关联中的聚合策略
- **只聚合最后一个表**: 避免引用关系断裂
- **分别查询**: 聚合查询和详情查询分开执行
- **使用子查询**: 在单个表中进行复杂聚合

## 8. 字段映射

### 8.1 单对象字段映射
```json
{
  "CmsUser": {
    "user_id": 1,
    "@column": "user_id:uid,user_name:name,user_email:email"
  }
}
```

### 8.2 数组字段映射
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@column": "user_id:uid,user_name:name,user_email:email,modify_time"
  }
}
```

## 9. 分页和排序

### 9.1 分页查询 (仅适用于数组查询)
```json
{
  "CmsUser[]": {
    "@limit": 10,
    "@offset": 0,
    "@column": "user_id,user_name"
  }
}
```

### 9.2 排序查询
```json
{
  "CmsUser[]": {
    "@order": "modify_time-",
    "@limit": 10,
    "@column": "user_id,user_name,modify_time"
  }
}
```

### 9.3 多字段排序
```json
{
  "CmsUser[]": {
    "@order": "user_status+,modify_time-",
    "@limit": 10,
    "@column": "user_id,user_name,user_status,modify_time"
  }
}
```

## 10. 分组和聚合

### 10.1 分组查询
```json
{
  "CmsUser[]": {
    "@group": "user_status",
    "@column": "user_status,COUNT(*) as count"
  }
}
```

### 10.2 HAVING 条件
```json
{
  "CmsUser[]": {
    "@group": "user_status",
    "@having": "COUNT(*) > 5",
    "@column": "user_status,COUNT(*) as count"
  }
}
```

## 11. 函数查询

### 11.1 聚合函数
```json
{
  "CmsUser": {
    "@column": "COUNT(*) as total,AVG(user_id) as avg_user_id,MAX(modify_time) as latest"
  }
}
```

### 11.2 有意义的聚合查询示例

#### 用户统计查询
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@column": "user_status,COUNT(*) as user_count",
    "@group": "user_status"
  }
}
```

#### 模块使用统计
```json
{
  "CmsModuleUser[]": {
    "@column": "module_id,COUNT(*) as user_count",
    "@group": "module_id",
    "@order": "user_count-",
    "@having": "user_count >= 2",
    "@limit": 20
  }
}
```

#### 系统活跃度统计
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "last_login_time>": "2025-01-01 00:00:00",
    "@column": "system_id,COUNT(*) as active_users,AVG(last_login_time) as avg_login_time",
    "@group": "system_id"
  }
}
```

### 11.3 字符串函数
```json
{
  "CmsUser[]": {
    "@column": "CONCAT(user_name, ' - ', user_email) as full_info"
  }
}
```

## 12. 复杂嵌套查询

### 12.1 单对象嵌套查询
```json
{
  "CmsUser": {
    "user_id": 1,
    "@column": "user_id,user_name",
    "CmsModuleUser[]": {
      "user_id@": "/user_id",
      "@column": "module_id,create_time"
    }
  }
}
```

### 12.2 数组嵌套查询
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@column": "user_id,user_name",
    "CmsModuleUser[]": {
      "user_id@": "/user_id",
      "@column": "module_id"
    }
  }
}
```

### 12.3 多层级嵌套
```json
{
  "CmsUser[]": {
    "user_id": 1,
    "@column": "user_id,user_name",
    "CmsModuleUser[]": {
      "user_id@": "/user_id",
      "@column": "module_id",
      "CmsModules": {
        "module_id@": "/module_id",
        "@column": "module_name"
      }
    }
  }
}
```

### 12.4 多层级嵌套（链式 vs 兄弟聚合）

```json
{
  "CmsUser": {
    "user_id": 1,
    "@column": "user_id,user_name",
    "CmsModuleUser[]": {
      "user_id@": "/user_id",
      "@limit": 15,
      "@column": "module_id,create_time",
      "CmsModules": {
        "module_id@": "/module_id",
        "@column": "module_id,module_name,parent_module_id",
        "CmsModules": {
          "module_id@": "/parent_module_id",
          "@column": "module_id,module_name,parent_module_id"
        }
      }
    }
  }
}
```

```json
{
  "CmsUser": {
    "user_id": 1,
    "@column": "user_id,user_name",
    "CmsModuleUser[]": {
      "user_id@": "/user_id",
      "@limit": 15,
      "@column": "module_id,create_time"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,parent_module_id",
      "CmsModules": {
        "module_id@": "/parent_module_id",
        "@column": "module_id,module_name,parent_module_id"
      }
    }
  }
}
```

> 精简对比：
> - 语义结构：链式写法更贴近“用户→关联→模块”的自然层级；兄弟聚合将所有模块集中到 `CmsModules[]`，便于统一筛选/排序。
> - 查询次数：链式可能产生 N+N 次子查询；兄弟聚合通过 `IN` 一次取全，通常更优（1+N）。
> - 去重：链式天然一一对应；兄弟聚合对模块天然去重。
> - 自动优化：当 `module_id@` 引用目标表主键/唯一索引时，系统会移除默认 10 条限制；兄弟聚合场景同样生效。

## 13. 组合查询示例

### 13.1 复杂条件组合 (数组查询)
```json
{
  "CmsUser[]": {
    "user_id>": 1,
    "user_status": 1,
    "user_name$": "admin",
    "modify_time$": "2025-01-01 00:00:00,2025-01-02 00:00:00",
    "@order": "modify_time-",
    "@limit": 20,
    "@offset": 0,
    "@column": "user_id:uid,user_name:name,user_email:email,modify_time:mtime"
  }
}
```

### 13.2 复杂条件组合 (单对象查询)
```json
{
  "CmsUser": {
    "user_id>": 1,
    "user_status": 1,
    "user_name$": "admin",
    "modify_time$": "2025-01-01 00:00:00,2025-01-02 00:00:00",
    "@column": "user_id:uid,user_name:name,user_email:email,modify_time:mtime"
  }
}
```

## 14. CRUD 操作语法

### 14.1 POST - 创建操作

#### 14.1.1 单对象创建
```json
{
  "CmsUser": {
    "user_name": "新用户",
    "user_email": "newuser@example.com",
    "user_status": 1
  }
}
```

#### 14.1.2 批量创建
```json
{
  "CmsUser": [
    {
      "user_name": "新用户1",
      "user_email": "newuser1@example.com",
      "user_status": 1
    },
    {
      "user_name": "新用户2",
      "user_email": "newuser2@example.com",
      "user_status": 1
    }
  ]
}
```

#### 14.1.3 嵌套插入（父子表关联）

支持在创建主表记录的同时，自动创建关联的子表记录。系统会自动处理外键关联。

**🎯 语法特点**：
- 子表以大写字母开头的键名表示
- 使用 `@foreign_key` 指令指定外键字段名
- 自动注入父表的主键ID到子表的外键字段
- 支持多层嵌套插入

**📝 示例1：基本嵌套插入**

```json
{
  "CmsUser": {
    "user_name": "new_user002",
    "user_email": "newuser002@ee.com",
    "user_status": 1,
    "system_id": 1,
    "CmsModuleUser": {
      "@foreign_key": "user_id",
      "module_id": 123,
      "system_id": 4
    }
  }
}
```

**✅ 返回结果**：
```json
{
  "user_id": 586,
  "count": 1,
  "CmsModuleUser": {
    "id": 1234,
    "count": 1
  }
}
```

**📝 示例2：多层嵌套插入**
```json
{
  "CmsUser": {
    "user_name": "admin_user",
    "user_email": "admin@example.com",
    "user_status": 1,
    "system_id": 1,
    "CmsModuleUser": {
      "@foreign_key": "user_id",
      "module_id": 2466,
      "system_id": 1,
      "CmsModules": {
        "@foreign_key": "module_id",
        "module_name": "新模块",
        "parent_module_id": 0
      }
    }
  }
}
```

**📝 示例3：手动指定外键值**

```json
{
  "CmsUser": {
    "user_name": "manual_fk_user",
    "user_email": "manual@example.com",
    "user_status": 1,
    "system_id": 1,
    "CmsModuleUser": {
      "user_id": 999,  // 手动指定外键值
      "module_id": 2471,
      "system_id": 1
    }
  }
}
```

**✅ 返回结果**：
```json
{
  "user_id": 596,
  "count": 1,
  "CmsModuleUser": {
    "id": 684,
    "count": 1
  }
}
```

**🔗 外键关联规则**：
1. **显式指定**：使用 `@foreign_key` 指令指定外键字段名
2. **约定规则**：默认使用 `{父表名小写}_id` 作为外键字段名
3. **手动指定**：可以在子表数据中直接提供外键值，此时不会自动注入
4. **智能检测**：系统会自动检测多种外键命名模式，包括 `{父表名小写}_id`、`{父表主键}`、`parent_id` 等

**🌳 多层嵌套支持**：
- 支持无限层级的嵌套插入
- 每一层都会自动处理外键关联
- 子表可以包含自己的子表，形成树形结构

**⚠️ 注意事项**：
- 嵌套插入会自动开启数据库事务，任何一步失败都会回滚
- 支持唯一索引预检查，避免重复数据插入
- 子表数据中的 `@foreign_key` 指令会被自动移除，不会作为字段插入
- 支持批量嵌套插入，每个主表记录可以有独立的子表数据
- 手动指定外键值时，系统不会重复注入外键，避免冲突

### 14.2 PUT - 更新操作

#### 14.2.1 单对象更新
```json
{
  "CmsUser": {
    "user_id": 1,
    "user_name": "新名字",
    "user_email": "new@example.com"
  }
}
```

#### 14.2.2 条件批量更新
```json
{
  "CmsUser": {
    "user_id>": 100,
    "user_status": 1,
    "user_name": "批量更新用户"
  }
}
```

#### 14.2.3 批量更新（数组语法）
```json
{
  "CmsUser": [
    {
      "user_id": 1,
      "user_name": "更新用户1",
      "user_status": 2
    },
    {
      "user_id": 2,
      "user_name": "更新用户2",
      "user_status": 2
    }
  ]
}
```

#### 14.2.4 嵌套更新（父子表关联）
支持在更新主表记录的同时，更新或新增关联的子表记录。

**语法特点**：
- 子表以大写字母开头的键名表示
- 自动处理外键关联
- 支持子表的更新和新增操作

**示例1：基本嵌套更新**
```json
{
  "CmsUser": {
    "user_id": 1,
    "user_name": "更新用户",
    "CmsModuleUser": {
      "module_id": 123,
      "system_id": 4
    }
  }
}
```

**示例2：条件嵌套更新**
```json
{
  "CmsUser": {
    "user_id>": 100,
    "user_status": 1,
    "CmsModuleUser": {
      "module_id": 2466,
      "system_id": 1
    }
  }
}
```

**返回结果**：
```json
{
  "ok": true,
  "count": 10
}
```

**⚠️ 安全机制**：
- **必须包含 WHERE 条件**：为防止全表更新，请求中必须至少包含一个 WHERE 条件
- **字段智能分离**：SDK 会自动识别用作 WHERE 的字段，并将它们从 SET 数据中剔除
- **事务保护**：所有更新操作都在事务中执行，确保数据一致性

### 14.3 DELETE - 删除操作

#### 单对象删除
```json
{
  "CmsUser": {
    "user_id": 1
  }
}
```

#### 条件删除 (数组语法)
```json
{
  "CmsUser[]": {
    "user_status": 0
  }
}
```

### 14.4 REPLACE - 替换操作

#### 单对象替换
```json
{
  "CmsUser": {
    "user_id": 1,
    "user_name": "替换名字",
    "user_email": "replace@example.com"
  }
}
```

#### 批量替换
```json
{
  "CmsUser": [
    {
      "user_id": 1,
      "user_name": "替换用户1",
      "user_email": "replace1@example.com",
      "user_status": 3
    },
    {
      "user_id": 2,
      "user_name": "替换用户2",
      "user_email": "replace2@example.com",
      "user_status": 3
    }
  ]
}
```

## 15. @insert 语法

### 15.1 基础 @insert 语法
```json
{
  "CmsUser": {
    "user_name": "新用户",
    "user_email": "newuser@example.com",
    "@insert": {
      "CmsModuleUser": {
        "module_id": 1
      }
    }
  }
}
```

### 15.2 多表 @insert 语法
```json
{
  "CmsUser": {
    "user_name": "新用户",
    "user_email": "newuser@example.com",
    "@insert": {
      "CmsModuleUser": {
        "module_id": 1
      },
      "CmsUserRole": {
        "role_id": 2
      }
    }
  }
}
```

### 15.3 嵌套 @insert 语法
```json
{
  "CmsUser": {
    "user_name": "新用户",
    "user_email": "newuser@example.com",
    "@insert": {
      "CmsModuleUser": {
        "module_id": 1,
        "@insert": {
          "CmsModulePermission": {
            "permission_id": 3
          }
        }
      }
    }
  }
}
```

## 16. @update 语法

### 16.1 基础 @update 语法
```json
{
  "CmsUser": {
    "user_id": 1,
    "user_name": "新名字",
    "@update": {
      "CmsModuleUser": {
        "module_id": 2
      }
    }
  }
}
```

### 16.2 多表 @update 语法
```json
{
  "CmsUser": {
    "user_id": 1,
    "user_name": "新名字",
    "@update": {
      "CmsModuleUser": {
        "module_id": 2
      },
      "CmsUserRole": {
        "role_id": 3
      }
    }
  }
}
```

### 16.3 条件 @update 语法
```json
{
  "CmsUser": {
    "user_id": 1,
    "user_name": "新名字",
    "@update": {
      "CmsModuleUser": {
        "module_id": 2,
        "user_id": 1
      }
    }
  }
}
```

## 17. @replace 语法

### 17.1 基础 @replace 语法
```json
{
  "CmsUser": {
    "user_id": 1,
    "user_name": "替换名字",
    "user_email": "replace@example.com",
    "@replace": {
      "CmsModuleUser": {
        "module_id": 3
      }
    }
  }
}
```

### 17.2 多表 @replace 语法
```json
{
  "CmsUser": {
    "user_id": 1,
    "user_name": "替换名字",
    "@replace": {
      "CmsModuleUser": {
        "module_id": 3
      },
      "CmsUserRole": {
        "role_id": 4
      }
    }
  }
}
```

## 18. 混合语法

### 18.1 POST + @insert 混合语法
```json
{
  "CmsUser": {
    "user_name": "新用户",
    "user_email": "newuser@example.com",
    "@insert": {
      "CmsModuleUser": {
        "module_id": 1
      }
    }
  }
}
```

### 18.2 PUT + @update 混合语法
```json
{
  "CmsUser": {
    "user_id": 1,
    "user_name": "新名字",
    "user_status": 1,
    "@update": {
      "CmsModuleUser": {
        "module_id": 2
      }
    }
  }
}
```

### 18.3 REPLACE + @replace 混合语法
```json
{
  "CmsUser": {
    "user_id": 1,
    "user_name": "替换名字",
    "user_email": "replace@example.com",
    "user_status": 1,
    "@replace": {
      "CmsModuleUser": {
        "module_id": 3
      }
    }
  }
}
```

## 19. 批量操作

### 19.1 批量插入
```json
{
  "CmsUser": [
    {
      "user_name": "批量用户1",
      "user_email": "batch1@example.com",
      "user_status": 1
    },
    {
      "user_name": "批量用户2",
      "user_email": "batch2@example.com",
      "user_status": 1
    },
    {
      "user_name": "批量用户3",
      "user_email": "batch3@example.com",
      "user_status": 1
    }
  ]
}
```

### 19.2 批量更新
```json
{
  "CmsUser": [
    {
      "user_id": 1,
      "user_name": "新用户1",
      "user_status": 2
    },
    {
      "user_id": 2,
      "user_name": "新用户2",
      "user_status": 2
    },
    {
      "user_id": 3,
      "user_name": "新用户3",
      "user_status": 2
    }
  ]
}
```

### 19.3 批量替换
```json
{
  "CmsUser": [
    {
      "user_id": 1,
      "user_name": "替换用户1",
      "user_email": "replace1@example.com",
      "user_status": 3
    },
    {
      "user_id": 2,
      "user_name": "替换用户2",
      "user_email": "replace2@example.com",
      "user_status": 3
    },
    {
      "user_id": 3,
      "user_name": "替换用户3",
      "user_email": "replace3@example.com",
      "user_status": 3
    }
  ]
}
```

### 19.4 批量操作特性

#### 19.4.1 自动分批处理
- 系统自动将大批量数据分成每批100条进行处理
- 避免单次操作数据量过大导致性能问题
- 支持事务回滚，确保数据一致性

#### 19.4.2 批量插入结果
```json
{
  "count": 3,
  "batches": 1,
  "results": {
    "batch_0": [
      {
        "user_id": 1,
        "count": 1
      },
      {
        "user_id": 2,
        "count": 1
      },
      {
        "user_id": 3,
        "count": 1
      }
    ]
  }
}
```

#### 19.4.3 批量更新结果
```json
{
  "count": 3,
  "batches": 1,
  "results": {
    "batch_0": {
      "results": [
        {
          "ok": true,
          "count": 1
        },
        {
          "ok": true,
          "count": 1
        },
        {
          "ok": true,
          "count": 1
        }
      ],
      "count": 3
    }
  }
}
```

#### 19.4.4 批量替换结果
```json
{
  "count": 3,
  "batches": 1,
  "results": {
    "batch_0": [
      {
        "user_id": 1,
        "count": 1
      },
      {
        "user_id": 2,
        "count": 1
      },
      {
        "user_id": 3,
        "count": 1
      }
    ]
  }
}
```

## 20. 复杂嵌套操作

### 20.1 多层级嵌套
```json
{
  "CmsUser": {
    "user_name": "复杂用户",
    "user_email": "complex@example.com",
    "@insert": {
      "CmsModuleUser": {
        "module_id": 1,
        "@insert": {
          "CmsModulePermission": {
            "permission_id": 3
          }
        }
      },
      "CmsUserRole": {
        "role_id": 2,
        "@insert": {
          "CmsRolePermission": {
            "permission_id": 4
          }
        }
      }
    }
  }
}
```

### 20.2 混合操作
```json
{
  "CmsUser": {
    "user_id": 572,
    "user_name": "混合操作",
    "@insert": {
      "CmsModuleUser": {
        "module_id": 1
      }
    },
    "@update": {
      "CmsUserRole": {
        "role_id": 3
      }
    },
    "@replace": {
      "CmsUserProfile": {
        "profile_data": "新数据"
      }
    }
  }
}
```

## 21. 聚合和高级操作符

### 21.1 @sum - 聚合求和

#### 单字段求和
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@sum": "user_id"
  }
}
```
生成 SQL: `SELECT SUM(user_id) AS sum_user_id FROM cms_user WHERE user_status = 1`

#### 多字段求和
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@sum": ["user_id", "system_id"]
  }
}
```
生成 SQL: `SELECT SUM(user_id) AS sum_user_id, SUM(system_id) AS sum_system_id FROM cms_user WHERE user_status = 1`

### 21.2 @distinct - 去重查询

#### 单字段去重
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@distinct": "user_email"
  }
}
```
生成 SQL: `SELECT DISTINCT user_email FROM cms_user WHERE user_status = 1`

#### 多字段去重
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@distinct": ["user_email", "user_name"]
  }
}
```
生成 SQL: `SELECT DISTINCT user_email, user_name FROM cms_user WHERE user_status = 1`

### 21.3 @alias - 字段别名

```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@column": "user_id,user_name,user_email",
    "@alias": {
      "user_id": "uid",
      "user_name": "name",
      "user_email": "email"
    }
  }
}
```
生成 SQL: `SELECT user_id AS uid, user_name AS name, user_email AS email FROM cms_user WHERE user_status = 1`

### 21.4 @explain - SQL执行计划

```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@explain": true
  }
}
```
生成 SQL: `EXPLAIN SELECT * FROM cms_user WHERE user_status = 1`

### 21.5 组合使用示例

#### 聚合 + 分组 + 别名
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@group": "system_id",
    "@sum": "user_id",
    "@alias": {
      "system_id": "sid",
      "sum_user_id": "total_uid"
    }
  }
}
```
生成 SQL: `SELECT system_id AS sid, SUM(user_id) AS total_uid FROM cms_user WHERE user_status = 1 GROUP BY system_id`

#### 去重 + 别名
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "@distinct": ["user_email", "user_name"],
    "@alias": {
      "user_email": "email",
      "user_name": "name"
    }
  }
}
```
生成 SQL: `SELECT DISTINCT user_email AS email, user_name AS name FROM cms_user WHERE user_status = 1`

#### 复杂聚合查询
```json
{
  "CmsUser[]": {
    "user_status": 1,
    "modify_time>": "2024-01-01 00:00:00",
    "@group": "system_id,user_status",
    "@sum": ["user_id", "system_id"],
    "@having": "sum_user_id > 1000",
    "@alias": {
      "system_id": "sid",
      "sum_user_id": "total_uid",
      "sum_system_id": "total_sid"
    },
    "@order": "total_uid-"
  }
}
```
生成 SQL: `SELECT system_id AS sid, SUM(user_id) AS total_uid, SUM(system_id) AS total_sid FROM cms_user WHERE user_status = 1 AND modify_time > '2024-01-01 00:00:00' GROUP BY system_id, user_status HAVING sum_user_id > 1000 ORDER BY total_uid DESC`

## 22. 操作符对照表

| 操作符 | 含义 | 示例 | SQL 等价 | 适用类型 |
|--------|------|------|----------|----------|
| `=` | 等于 | `"user_id": 572` | `user_id = 572` | 单对象/数组 |
| `>` | 大于 | `"user_id>": 100` | `user_id > 100` | 单对象/数组 |
| `<` | 小于 | `"user_id<": 1000` | `user_id < 1000` | 单对象/数组 |
| `>=` | 大于等于 | `"user_id>=": 100` | `user_id >= 100` | 单对象/数组 |
| `<=` | 小于等于 | `"user_id<=": 1000` | `user_id <= 1000` | 单对象/数组 |
| `!=` | 不等于 | `"user_id!=": 572` | `user_id != 572` | 单对象/数组 |
| `{}` | IN | `"user_id{}": [1,2,3]` | `user_id IN (1,2,3)` | 仅数组 |
| `!{}` | NOT IN | `"user_id!{}": [1,2,3]` | `user_id NOT IN (1,2,3)` | 仅数组 |
| `$` | LIKE 包含 | `"user_name$": "admin"` | `user_name LIKE '%admin%'` | 单对象/数组 |
| `^` | LIKE 开头 | `"user_name^": "admin"` | `user_name LIKE 'admin%'` | 单对象/数组 |
| `%` | REGEXP | `"user_name%": "^admin.*"` | `user_name REGEXP '^admin.*'` | 单对象/数组 |
| `$` | BETWEEN | `"modify_time$": "2025-01-01 00:00:00,2025-01-02 00:00:00"` | `modify_time BETWEEN '2025-01-01 00:00:00' AND '2025-01-02 00:00:00'` | 单对象/数组 |
| `\|` | OR | `"user_id\|user_name": "1"` | `user_id = '1' OR user_name = '1'` | 单对象/数组 |
| `@` | 引用 | `"user_id@": "CmsModuleUser/user_id"` | 关联查询 | 单对象/数组 |
| `@` | 复杂逻辑 | `"@": {"operator": "OR", ...}` | 复杂WHERE条件 | 单对象/数组 |
| `@insert` | 嵌套插入 | `"@insert": {"CmsModuleUser": {...}}` | 嵌套 INSERT | 仅单对象 |
| `@update` | 嵌套更新 | `"@update": {"CmsModuleUser": {...}}` | 嵌套 UPDATE | 仅单对象 |
| `@replace` | 嵌套替换 | `"@replace": {"CmsModuleUser": {...}}` | 嵌套 REPLACE | 仅单对象 |
| `@sum` | 聚合求和 | `"@sum": "create_time"` | `SUM(create_time)` | 仅数组 |
| `@distinct` | 去重查询 | `"@distinct": "user_email"` | `DISTINCT user_email` | 仅数组 |
| `@alias` | 字段别名 | `"@alias": {"user_id": "uid"}` | `user_id AS uid` | 单对象/数组 |
| `@explain` | SQL执行计划 | `"@explain": true` | `EXPLAIN SELECT ...` | 单对象/数组 |
| `[]` | 批量操作 | `"CmsUser": [{...}, {...}]` | 批量 CRUD | 仅CRUD操作 |

### 22.1 🚀 Limit 优化说明

#### 22.1.1 自动优化机制
当满足以下条件时，系统会自动移除默认的 `LIMIT 10` 限制：

1. **引用关系**: 查询中包含 `@` 引用语法
2. **唯一性字段**: 引用字段在目标表中是主键或唯一索引
3. **无明确限制**: 查询中没有明确设置 `@limit` 参数

#### 22.1.2 优化示例

**✅ 会触发自动优化**:

> 💡 **说明**: 引用主键字段，没有 @limit，自动移除默认 LIMIT 10

```json
{
  "CmsModules[]": {
    "module_id@": "CmsModuleUser/module_id",
    "@column": "module_id,module_name"
  }
}
```

**❌ 不会触发自动优化**:

> ❌ **说明**: 明确设置了 @limit，不触发自动优化

```json
{
  "CmsModules[]": {
    "module_id@": "CmsModuleUser/module_id",
    "@column": "module_id,module_name",
    "@limit": 5
  }
}
```

#### 22.1.3 优化效果对比

| 场景 | 优化前 | 优化后 | 说明 |
|------|--------|--------|------|
| 引用主键字段，无 @limit | 返回 10 条 | 返回所有匹配记录 | ✅ 自动优化生效 |
| 引用主键字段，有 @limit | 返回指定条数 | 返回指定条数 | ❌ 不触发优化 |
| 引用非主键字段 | 返回 10 条 | 返回 10 条 | ❌ 不触发优化 |
| 无引用关系 | 返回 10 条 | 返回 10 条 | ❌ 不触发优化 |

## 23. 事务支持

所有 CRUD 操作都支持事务，确保数据一致性：

- **POST**: 自动开启事务，插入失败时回滚
- **PUT**: 自动开启事务，更新失败时回滚  
- **DELETE**: 自动开启事务，删除失败时回滚
- **REPLACE**: 自动开启事务，替换失败时回滚

## 24. 错误处理

### 24.1 唯一索引冲突
```json
{
  "CmsUser": {
    "user_email": "admin@ee.com",
    "user_name": "重复用户"
  }
}
```
**错误**: `CmsUser 唯一索引冲突: {"user_email":"admin@ee.com"}`

### 24.2 外键约束
```json
{
  "CmsModuleUser": {
    "user_id": 99999,
    "module_id": 1
  }
}
```
**错误**: `外键约束失败: user_id 99999 不存在`

### 24.3 必填字段验证
```json
{
  "CmsUser": {
    "user_name": "测试用户"
  }
}
```
**错误**: `必填字段缺失: user_email`

## 25. 性能优化建议

### 25.1 批量操作
- 使用 `[]` 语法进行批量插入
- 使用条件更新进行批量更新
- 避免逐条操作

### 25.2 索引优化
- 为常用查询字段创建索引
- 为外键字段创建索引
- 为唯一字段创建唯一索引

### 25.3 查询优化
- 单对象查询使用 `CmsUser`，数组查询使用 `CmsUser[]`
- 合理使用 `@column` 指定需要的字段
- 避免使用 `@explain` 在生产环境

## 26. 最佳实践

### 26.1 数据验证
- 在应用层进行数据验证
- 使用数据库约束确保数据完整性
- 处理唯一索引冲突

### 26.2 错误处理
- 捕获并处理异常
- 提供有意义的错误信息
- 记录操作日志

### 26.3 性能考虑
- 使用批量操作减少数据库交互
- 合理使用索引
- 监控查询性能

### 26.4 安全性
- 验证用户权限
- 防止 SQL 注入
- 保护敏感数据

### 26.5 聚合查询注意事项
- **避免对主键聚合**: 不要对 `user_id`、`module_id` 等主键进行求和
- **聚合字段选择**: 选择有业务意义的数值字段进行聚合
- **多表关联限制**: 聚合后的表无法被其他表引用
- **性能考虑**: 聚合查询可能影响性能，合理使用索引
- **✅ 修复说明**: 现在聚合查询在数组查询 `[]` 中已完全支持，包括 `@group` 和聚合函数

### 26.6 聚合查询修复和改进

#### 26.6.1 修复内容
1. **数组查询中的聚合支持**: 现在可以在 `[]` 数组查询中正确使用聚合函数
2. **GROUP BY 支持**: 完全支持 `@group` 关键字进行分组聚合
3. **聚合函数支持**: 支持 `COUNT(*)`、`SUM()`、`AVG()`、`MAX()`、`MIN()` 等聚合函数
4. **引用关系处理**: 正确处理有引用关系的聚合查询

#### 26.6.2 使用示例
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 5
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "user_id,COUNT(*) as module_count",
      "@group": "user_id"
    }
  }
}
```

#### 26.6.3 返回格式
- **有数据**: 返回聚合结果对象，如 `{"user_id": 1, "module_count": 21}`
- **无数据**: 返回空数组 `[]`，而不是 `null`
- **数组结构**: 每个主表记录对应一个聚合结果

#### 26.6.4 支持的聚合关键字
- `@group`: 分组字段
- `@sum`: 求和字段
- `@count`: 计数字段
- `@avg`: 平均值字段
- `@max`: 最大值字段
- `@min`: 最小值字段

### 26.7 查询类型选择
- **单对象查询**: 用于根据主键或唯一条件查询单个记录
- **数组查询**: 用于列表查询、条件查询、分页查询
- **聚合查询**: 用于统计报表、数据分析（✅ 现在完全支持）
- **批量操作**: 用于大量数据的增删改操作

### 26.8 关联查询 Limit 优化最佳实践

#### 26.8.1 优化使用场景

**✅ 推荐使用优化的场景**:
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 10
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",  // ✅ 引用主键字段
      "@column": "module_id,module_name,controller,action"
      // ✅ 让系统自动优化，确保获取所有模块信息
    }
  }
}
```

**❌ 不推荐使用优化的场景**:
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 10
    },
    "CmsLog[]": {
      "user_id@": "CmsUser/user_id",  // ❌ 日志表可能数据量很大
      "@column": "log_id,action,create_time"
      // ❌ 建议设置合理的 @limit 避免性能问题
    }
  }
}
```

#### 26.8.2 性能优化策略

**策略1: 分层控制**
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 5  // ✅ 控制主表记录数
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time",
      "@limit": 50  // ✅ 控制中间表记录数
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
      // ✅ 自动优化，但数据量受中间表限制
    }
  }
}
```

**策略2: 条件过滤**
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 10
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "create_time>": "2024-01-01 00:00:00",  // ✅ 添加时间过滤
      "@column": "module_id,create_time"
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "deleted": 0,  // ✅ 添加状态过滤
      "@column": "module_id,module_name,controller,action"
    }
  }
}
```

**策略3: 分页查询**
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 10,
      "@offset": 0
    },
    "CmsModuleUser[]": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time",
      "@limit": 20,
      "@offset": 0
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,controller,action"
      // ✅ 自动优化，但数据量受中间表分页限制
    }
  }
}
```

#### 26.8.3 监控和调试

**启用调试日志**:
```php
// 在日志中查看优化是否生效
// 查找关键字: "applyLimitOptimization" 和 "优化生效"
```

**性能监控**:
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 1  // ✅ 测试时使用小数据量
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name",
      "@explain": true  // ✅ 查看执行计划
    }
  }
}
```

#### 26.8.4 常见问题和解决方案

**问题1: 数据量过大导致性能问题**
```json
// ❌ 可能导致性能问题
{
  "CmsModules[]": {
    "module_id@": "CmsModuleUser/module_id",
    "@column": "module_id,module_name"
  }
}

// ✅ 解决方案：添加条件过滤
{
  "CmsModules[]": {
    "module_id@": "CmsModuleUser/module_id",
    "deleted": 0,  // 添加过滤条件
    "@column": "module_id,module_name"
  }
}
```

**问题2: 需要限制返回记录数**
```json
// ❌ 优化会移除默认 limit
{
  "CmsModules[]": {
    "module_id@": "CmsModuleUser/module_id",
    "@column": "module_id,module_name"
  }
}

// ✅ 解决方案：明确设置 @limit
{
  "CmsModules[]": {
    "module_id@": "CmsModuleUser/module_id",
    "@column": "module_id,module_name",
    "@limit": 100  // 明确设置限制
  }
}
```

**问题3: 引用字段不是主键或唯一索引**
```json
// ❌ 不会触发优化
{
  "CmsModules[]": {
    "module_name@": "CmsModuleUser/module_name",
    "@column": "module_id,module_name"
  }
}

// ✅ 解决方案：使用主键引用或设置 @limit
{
  "CmsModules[]": {
    "module_id@": "CmsModuleUser/module_id",  // 使用主键引用
    "@column": "module_id,module_name"
  }
}
```

#### 26.8.5 最佳实践总结

1. **合理使用优化**: 只在引用主键或唯一索引时依赖自动优化
2. **控制数据量**: 通过主表和中间表的 limit 控制最终数据量
3. **添加过滤条件**: 使用业务条件减少不必要的数据查询
4. **监控性能**: 使用 `@explain` 和日志监控查询性能
5. **明确限制**: 当需要限制记录数时，明确设置 `@limit`
6. **测试验证**: 在生产环境使用前，充分测试优化效果

## 27. 🎯 功能总结

### 27.1 核心功能特性

#### 27.1.1 查询功能
- ✅ **单对象查询**: 根据主键或唯一条件查询单个记录
- ✅ **数组查询**: 支持条件查询、分页查询、排序查询
- ✅ **关联查询**: 支持多表关联、嵌套查询、引用查询
- ✅ **聚合查询**: 支持分组、统计、聚合函数（✅ 已修复）
- ✅ **复杂查询**: 支持逻辑操作符、字符串操作符、范围查询

#### 27.1.2 CRUD 操作
- ✅ **创建操作**: 支持单条和批量插入
- ✅ **更新操作**: 支持单条和批量更新
- ✅ **删除操作**: 支持单条和条件删除
- ✅ **替换操作**: 支持单条和批量替换
- ✅ **嵌套操作**: 支持 `@insert`、`@update`、`@replace`

#### 27.1.3 高级功能
- ✅ **事务支持**: 所有操作都支持事务，确保数据一致性
- ✅ **批量处理**: 自动分批处理大量数据，避免性能问题
- ✅ **错误处理**: 完善的错误处理和异常捕获机制
- ✅ **性能优化**: 支持索引优化、查询优化、执行计划分析

### 27.2 🚀 最新优化功能

#### 27.2.1 关联查询 Limit 优化 (2025-08-14)
**优化内容**: 当关联查询的字段是主键或唯一索引时，自动移除默认的 limit 10 限制

**优化效果**:
- **数据完整性**: 确保获取所有匹配的记录
- **业务准确性**: 避免因 limit 限制导致的数据缺失
- **用户体验**: 提供完整的数据视图

**使用场景**:
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 5
    },
    "CmsModules[]": {
      "module_id@": "CmsModuleUser/module_id",  // 引用主键字段
      "@column": "module_id,module_name,controller,action"
      // ✅ 自动优化：返回所有匹配记录，不受默认 limit 10 限制
    }
  }
}
```

**优化前后对比**:
- **优化前**: 返回 10 条记录（受默认 limit 限制）
- **优化后**: 返回所有匹配记录（如 56 条）

#### 27.2.2 聚合查询功能修复 (2025-08-13)
**修复内容**: 数组查询 `[]` 中的聚合查询现在可以正常工作

**支持功能**:
- `@group`: 分组聚合
- `COUNT(*)`: 记录数量统计
- `SUM()`: 数值求和
- `AVG()`: 平均值计算
- `MAX()`: 最大值
- `MIN()`: 最小值

**使用示例**:
```json
{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 5
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "user_id,COUNT(*) as module_count",
      "@group": "user_id"
    }
  }
}
```

### 27.3 技术架构

#### 27.3.1 核心组件
- **Parse**: 查询解析和语法处理
- **Handle**: 各种操作符和功能的处理器
- **Entity**: 数据实体和条件管理
- **Method**: 具体的 CRUD 操作实现

#### 27.3.2 优化机制
- **引用解析**: 自动解析和转换引用关系
- **条件优化**: 智能优化查询条件
- **Limit 优化**: 基于索引的自动 limit 优化
- **性能监控**: 完整的日志和调试支持

### 27.4 最佳实践指南

#### 27.4.1 查询设计
1. **合理选择查询类型**: 单对象 vs 数组查询
2. **优化关联查询**: 利用 Limit 优化功能
3. **控制数据量**: 使用分页和条件过滤
4. **监控性能**: 使用 `@explain` 分析执行计划

#### 27.4.2 性能优化
1. **索引设计**: 为常用查询字段创建索引
2. **批量操作**: 使用批量操作减少数据库交互
3. **条件过滤**: 添加业务条件减少数据量
4. **分页查询**: 使用分页避免大量数据传输

#### 27.4.3 错误处理
1. **异常捕获**: 完善的异常处理机制
2. **数据验证**: 在应用层进行数据验证
3. **约束检查**: 利用数据库约束确保数据完整性
4. **日志记录**: 详细的操作日志和错误日志

### 27.5 版本更新历史

#### v1.0.0 (2025-08-14)
- ✅ 新增关联查询 Limit 优化功能
- ✅ 修复聚合查询在数组查询中的问题
- ✅ 完善错误处理和日志记录
- ✅ 优化性能和稳定性

#### v0.9.0 (2025-08-13)
- ✅ 基础 CRUD 操作支持
- ✅ 多表关联查询支持
- ✅ 复杂查询语法支持
- ✅ 事务和批量操作支持

---

**📝 文档维护**: 本文档会持续更新，反映最新的功能特性和最佳实践。
**🐛 问题反馈**: 如发现文档错误或功能问题，请及时反馈。
**💡 功能建议**: 欢迎提出功能改进和优化建议。