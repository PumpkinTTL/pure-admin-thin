# 🔄 卡密系统类型表重构更新说明

## 📅 更新日期
**2025-10-05**

## 📋 更新概述
对卡密系统进行架构升级，引入 `bl_card_types` 类型表，实现配置与数据分离，支持灵活的字段组合和动态UI展示。

---

## 🎯 **重构目标**

### **核心改进**
1. ✅ **配置集中化** - 所有卡密类型配置集中在类型表管理
2. ✅ **字段可选化** - 支持字段级别的NULL（不需要该字段）
3. ✅ **动态UI** - 根据类型自动显示/隐藏相关字段
4. ✅ **数据一致性** - 外键约束保证数据完整性
5. ✅ **易于扩展** - 新增类型只需在类型表配置，无需修改代码

### **解决的痛点**
- ❌ **旧问题1**: 每次生成卡密都要手动填写价格、时长等配置
- ❌ **旧问题2**: 相同类型的卡密配置不统一，容易出错
- ❌ **旧问题3**: 新增卡密类型需要修改代码
- ❌ **旧问题4**: 无法灵活组合字段（如注册码不需要价格）

---

## 🗄️ **数据库变更**

### **新增表: bl_card_types (卡密类型表)**

```sql
CREATE TABLE `bl_card_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `type_name` varchar(50) NOT NULL COMMENT '类型名称',
  `type_code` varchar(50) NOT NULL COMMENT '类型编码',
  `description` varchar(255) DEFAULT NULL COMMENT '类型描述',
  `icon` varchar(100) DEFAULT NULL COMMENT '图标',
  
  -- NULL表示不需要该字段
  `membership_duration` int(11) DEFAULT NULL COMMENT '会员时长(分钟)，NULL=不需要会员时长，0=永久',
  `price` decimal(10,2) DEFAULT NULL COMMENT '价格，NULL=不需要价格',
  `available_days` int(11) DEFAULT NULL COMMENT '可兑换天数，NULL=永久可用',
  
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态: 0=停用, 1=启用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_name` (`type_name`),
  UNIQUE KEY `uk_type_code` (`type_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡密类型表';
```

### **修改表: bl_card_keys (卡密主表)**

```sql
-- 1. 删除旧表（数据迁移请提前备份）
DROP TABLE IF EXISTS `bl_card_keys`;

-- 2. 创建新结构的卡密表
CREATE TABLE `bl_card_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `card_key` varchar(64) NOT NULL COMMENT '卡密码',
  `type_id` int(11) NOT NULL COMMENT '卡密类型ID，关联bl_card_types.id',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0-未使用，1-已使用，2-已禁用',
  `user_id` int(11) DEFAULT NULL COMMENT '使用者ID',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `use_time` datetime DEFAULT NULL COMMENT '使用时间',
  `expire_time` datetime DEFAULT NULL COMMENT '卡密本身的过期时间（优先级高于类型表的available_days）',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_card_key` (`card_key`),
  KEY `idx_type_id` (`type_id`),
  KEY `idx_status` (`status`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_expire_time` (`expire_time`),
  CONSTRAINT `fk_card_keys_type` FOREIGN KEY (`type_id`) REFERENCES `bl_card_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡密表';
```

### **关键变更说明**

| 变更项 | 旧设计 | 新设计 | 原因 |
|--------|--------|--------|------|
| **类型字段** | `type` varchar | `type_id` int | 关联类型表，统一管理 |
| **价格字段** | `price` 存在主表 | ❌ 移除 | 从类型表读取 |
| **会员时长** | `membership_duration` 存在主表 | ❌ 移除 | 从类型表读取 |
| **可用期限** | `available_time` datetime | `expire_time` datetime | 重命名更语义化，可覆盖类型默认值 |

---

## 🏗️ **数据模型设计**

### **字段可选性设计（核心特性）**

类型表中的配置字段支持三种状态：

| 字段值 | 含义 | 使用场景 |
|--------|------|----------|
| **NULL** | 不需要该字段 | 如：注册邀请码不需要价格 |
| **0** | 特殊值（如永久会员） | 如：永久会员卡 membership_duration=0 |
| **具体值** | 需要该字段且有默认值 | 如：30天会员卡 membership_duration=43200 |

### **示例类型配置**

```sql
-- 示例1: VIP会员码 - 需要价格、会员时长、兑换期限
INSERT INTO `bl_card_types` VALUES (
  1, 'VIP会员码', 'vip_membership', 
  'VIP会员兑换码，赠送30天会员', NULL,
  43200,  -- 30天会员时长
  199.00, -- 价格199元
  90,     -- 90天内可兑换
  1, 1, NOW(), NULL
);

-- 示例2: 永久会员码 - 需要价格，永久会员，永久可兑换
INSERT INTO `bl_card_types` VALUES (
  3, '永久会员码', 'permanent_membership', 
  '永久会员兑换码', NULL,
  0,      -- 0=永久会员
  999.00, -- 价格999元
  NULL,   -- NULL=永久可兑换
  3, 1, NOW(), NULL
);

-- 示例3: 注册邀请码 - 不需要价格，不需要会员时长
INSERT INTO `bl_card_types` VALUES (
  4, '注册邀请码', 'invitation_code', 
  '用户注册时使用的邀请码', NULL,
  NULL,   -- NULL=不需要会员时长
  NULL,   -- NULL=不需要价格
  NULL,   -- NULL=永久可兑换
  4, 1, NOW(), NULL
);

-- 示例4: 商品兑换码 - 不需要价格、不需要会员时长，但30天内可兑换
INSERT INTO `bl_card_types` VALUES (
  5, '商品兑换码', 'goods_exchange', 
  '兑换实物商品或虚拟物品', NULL,
  NULL,   -- NULL=不需要会员时长
  NULL,   -- NULL=不需要价格
  30,     -- 30天内可兑换
  5, 1, NOW(), NULL
);
```

---

## 💻 **后端代码变更**

### ✅ **新增文件**

#### **1. Model: CardType.php**
```php
namespace app\api\model;

class CardType extends Model
{
    protected $name = 'card_types';
    
    // 判断是否需要会员时长字段
    public function needsMembershipDuration(): bool
    {
        return $this->membership_duration !== null;
    }
    
    // 判断是否需要价格字段
    public function needsPrice(): bool
    {
        return $this->price !== null;
    }
    
    // 获取所有启用的类型
    public static function getEnabledTypes(): array
    {
        return self::where('status', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
    }
}
```

#### **2. Service: CardTypeService.php**
```php
namespace app\api\services;

class CardTypeService
{
    // 获取类型列表
    public function getList(array $params): array;
    
    // 获取启用的类型
    public function getEnabledTypes(): array;
    
    // 创建类型
    public function create(array $data): array;
    
    // 更新类型
    public function update(int $id, array $data): array;
    
    // 删除类型
    public function delete(int $id): array;
    
    // 批量删除类型
    public function batchDelete(array $ids): array;
}
```

#### **3. Controller: CardType.php**
```php
namespace app\api\controller\v1;

class CardType extends BaseController
{
    // GET /api/v1/cardtype/list - 获取类型列表
    public function index(Request $request): Json;
    
    // GET /api/v1/cardtype/enabled - 获取启用的类型
    public function enabled(Request $request): Json;
    
    // POST /api/v1/cardtype/create - 创建类型
    public function create(Request $request): Json;
    
    // PUT /api/v1/cardtype/update/:id - 更新类型
    public function update(Request $request, int $id): Json;
    
    // DELETE /api/v1/cardtype/delete/:id - 删除类型
    public function delete(Request $request, int $id): Json;
}
```

### ✅ **修改文件**

#### **CardKey Model 变更**
```php
// 旧代码 - 字段包含在主表
protected $type = [
    'id' => 'integer',
    'status' => 'integer',
    'price' => 'float',  // ❌ 移除
    'membership_duration' => 'integer',  // ❌ 移除
    'available_time' => 'datetime',  // 改为 expire_time
];

// 新代码 - 通过关联读取
protected $type = [
    'id' => 'integer',
    'type_id' => 'integer',
    'status' => 'integer',
    'expire_time' => 'datetime',
];

// 新增关联
public function cardType()
{
    return $this->belongsTo(CardType::class, 'type_id', 'id');
}

// 动态获取价格
public function getPrice(): ?float
{
    return $this->cardType ? $this->cardType->price : null;
}

// 动态获取会员时长
public function getMembershipDuration(): ?int
{
    return $this->cardType ? $this->cardType->membership_duration : null;
}
```

#### **CardKeyService 变更**
```php
// 旧代码 - 手动输入配置
public function generate(array $data): array
{
    return CardKeyUtil::batchGenerate(1, $data['type'], [
        'price' => $data['price'] ?? null,
        'membership_duration' => $data['membership_duration'] ?? 0,
        'remark' => $data['remark'] ?? ''
    ]);
}

// 新代码 - 从类型表读取配置
public function generate(array $data): array
{
    $cardType = CardType::find($data['type_id']);
    if (!$cardType) {
        return ['success' => false, 'message' => '卡密类型不存在'];
    }
    
    return CardKeyUtil::batchGenerate(1, $data['type_id'], $cardType, [
        'expire_time' => $data['expire_time'] ?? null,
        'remark' => $data['remark'] ?? ''
    ]);
}
```

---

## 🎨 **前端代码变更**

### ✅ **新增文件**

#### **1. API: cardType.ts**
```typescript
import { http } from "@/utils/http";

export interface CardType {
  id: number;
  type_name: string;
  type_code: string;
  description?: string;
  membership_duration?: number | null;  // NULL = 不需要
  price?: number | null;  // NULL = 不需要
  available_days?: number | null;  // NULL = 永久可用
  sort_order: number;
  status: number;
}

// 获取启用的类型列表
export const getEnabledCardTypes = () => {
  return http.request<any>("get", "/api/v1/cardtype/enabled");
};

// 获取类型列表
export const getCardTypeList = (params: CardTypeListParams) => {
  return http.request<any>("get", "/api/v1/cardtype/list", { params });
};

// 创建类型
export const createCardType = (data: CardTypeFormData) => {
  return http.request<any>("post", "/api/v1/cardtype/create", { data });
};

// 更新类型
export const updateCardType = (id: number, data: CardTypeFormData) => {
  return http.request<any>("put", `/api/v1/cardtype/update/${id}`, { data });
};

// 删除类型
export const deleteCardType = (id: number) => {
  return http.request<any>("delete", `/api/v1/cardtype/delete/${id}`);
};
```

#### **2. Component: TypeManage.vue**
- 完整的类型管理CRUD界面
- 支持搜索、筛选、排序
- 动态表单支持NULL字段
- 实时状态切换

### ✅ **修改文件**

#### **cardKey.vue - Tab集成**
```vue
<template>
  <div class="card-key-container">
    <!-- 统计卡片 -->
    <div class="stats-container">...</div>

    <!-- 主内容卡片 -->
    <el-card class="main-card">
      <!-- ✨ 新增：Tab切换 -->
      <el-tabs v-model="activeTab">
        <!-- 卡密列表Tab -->
        <el-tab-pane label="卡密列表" name="cardKeys">
          <!-- 原有的卡密管理界面 -->
        </el-tab-pane>

        <!-- ✨ 新增：类型管理Tab -->
        <el-tab-pane label="类型管理" name="cardTypes">
          <TypeManage />
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>
```

#### **GenerateDialog.vue - 动态字段**
```vue
<template>
  <el-dialog title="生成卡密">
    <el-form>
      <!-- ✨ 改造：选择类型 -->
      <el-form-item label="卡密类型" prop="type_id">
        <el-select v-model="form.type_id" @change="handleTypeChange">
          <el-option
            v-for="type in cardTypes"
            :key="type.id"
            :label="type.type_name"
            :value="type.id"
          />
        </el-select>
        <!-- 显示类型配置摘要 -->
        <div class="form-tip" v-if="selectedType">
          <span v-if="selectedType.price !== null">价格: ￥{{ selectedType.price }}</span>
          <span v-if="selectedType.membership_duration !== null">
            | 会员时长: {{ formatDuration(selectedType.membership_duration) }}
          </span>
        </div>
      </el-form-item>

      <!-- ✨ 条件显示：仅当类型需要时显示会员时长 -->
      <el-form-item label="会员时长" v-if="showMembershipDuration">
        <el-alert type="info" :closable="false">
          <span v-if="selectedType?.membership_duration === 0">
            该类型为<strong>永久会员</strong>
          </span>
          <span v-else>
            该类型赠送<strong>{{ formatDuration(selectedType.membership_duration) }}</strong>会员
          </span>
        </el-alert>
      </el-form-item>

      <!-- ✨ 条件显示：仅当类型需要时显示价格 -->
      <el-form-item label="价格" v-if="showPrice">
        <el-alert type="info" :closable="false">
          该类型价格为<strong>￥{{ selectedType.price }}</strong>
        </el-alert>
      </el-form-item>

      <!-- ✨ 保留：可覆盖类型默认值 -->
      <el-form-item label="兑换期限" prop="expire_time">
        <div class="form-tip">
          <span v-if="selectedType?.available_days">
            该类型默认在<strong>{{ selectedType.available_days }}天</strong>内可兑换
          </span>
          <span v-else>该类型默认<strong>永久可兑换</strong></span>
        </div>
        <!-- 选项按钮：使用默认 / 7天 / 30天 / 90天 / 永久 / 自定义 -->
      </el-form-item>
    </el-form>
  </el-dialog>
</template>

<script setup lang="ts">
import { getEnabledCardTypes, type CardType } from "@/api/cardType";

const cardTypes = ref<CardType[]>([]);
const selectedType = ref<CardType | null>(null);

// 计算属性：是否显示会员时长
const showMembershipDuration = computed(() => {
  return selectedType.value && selectedType.value.membership_duration !== null;
});

// 计算属性：是否显示价格
const showPrice = computed(() => {
  return selectedType.value && selectedType.value.price !== null;
});

// 类型变化处理
const handleTypeChange = (typeId: number) => {
  selectedType.value = cardTypes.value.find(t => t.id === typeId) || null;
};
</script>
```

---

## 📊 **业务逻辑变化**

### **生成卡密流程**

#### **旧流程**
```
1. 用户输入类型（字符串）
2. 用户输入价格
3. 用户输入会员时长
4. 用户输入兑换期限
5. 提交生成
```

#### **新流程**
```
1. 用户选择类型（下拉选择）
2. ✨ 系统自动填充价格、会员时长（从类型表读取）
3. ✨ 系统显示默认兑换期限（可选择使用默认或覆盖）
4. ✨ 只有类型需要的字段才显示
5. 提交生成
```

### **兑换卡密流程**

#### **旧流程**
```
1. 验证卡密状态
2. 验证 available_time 是否过期
3. 从 CardKey.membership_duration 读取会员时长
4. 计算会员到期时间
5. 更新用户会员状态
```

#### **新流程**
```
1. 验证卡密状态
2. ✨ 验证 expire_time（优先）或 CardType.available_days
3. ✨ 从 CardType.membership_duration 读取会员时长
4. 计算会员到期时间
5. 更新用户会员状态
```

---

## ✅ **升级步骤**

### **1. 数据库迁移**
```sql
-- 步骤1: 备份数据
CREATE TABLE bl_card_keys_backup AS SELECT * FROM bl_card_keys;

-- 步骤2: 创建类型表
-- (执行上面的 CREATE TABLE bl_card_types)

-- 步骤3: 插入类型数据
-- (执行示例类型配置SQL)

-- 步骤4: 重建卡密表
DROP TABLE bl_card_keys;
-- (执行新的 CREATE TABLE bl_card_keys)

-- 步骤5: 数据迁移（如需要）
-- 根据实际情况编写数据迁移脚本
```

### **2. 后端部署**
```bash
# 1. 更新代码
git pull

# 2. 清除缓存
php think clear

# 3. 重启服务
# systemctl restart php-fpm (根据实际环境)
```

### **3. 前端部署**
```bash
# 1. 安装依赖（如有新增）
pnpm install

# 2. 构建生产版本
pnpm build

# 3. 部署到服务器
# (根据实际部署流程)
```

---

## 🧪 **测试清单**

### **功能测试**
- [ ] 类型管理：增删改查
- [ ] 类型状态切换
- [ ] 生成卡密：选择类型后正确显示字段
- [ ] 生成卡密：使用类型默认配置
- [ ] 生成卡密：覆盖类型默认配置
- [ ] 兑换卡密：正确读取类型配置
- [ ] 兑换期限：优先级判断正确

### **边界测试**
- [ ] 类型字段为NULL时的处理
- [ ] 删除正在使用的类型（应该失败）
- [ ] 停用的类型不出现在生成列表
- [ ] 单个卡密覆盖类型默认值

### **UI测试**
- [ ] Tab切换流畅
- [ ] 动态字段显示/隐藏正确
- [ ] 类型配置摘要显示正确
- [ ] 提示文案清晰易懂

---

## 📚 **相关文档**

- 📘 [卡密系统字段说明文档](./cardkey-fields-guide.md) - 字段详细说明
- 📘 [卡密字段重命名更新说明](./CHANGELOG-cardkey-field-rename.md) - 上一次字段重构
- 🔗 [API接口文档](#) - 接口详细说明（待补充）
- 🔗 [类型管理使用手册](#) - 用户操作指南（待补充）

---

## ⚠️ **注意事项**

### **开发者须知**
1. ⚠️ **数据迁移**：旧数据需要迁移到新结构，请提前备份
2. ⚠️ **外键约束**：删除类型前需要先删除关联的卡密
3. ⚠️ **NULL语义**：字段为NULL表示"不需要该字段"，不是"值为空"
4. ⚠️ **优先级**：单个卡密的 expire_time 优先于类型的 available_days

### **运维须知**
1. 📊 **监控指标**：关注类型表的修改频率和关联查询性能
2. 🔒 **权限管理**：类型管理应限制为管理员权限
3. 💾 **备份策略**：类型表配置变更应记录审计日志

---

## 🔗 **相关提交**

- feat(backend): 新增 CardType Model/Service/Controller
- feat(backend): 修改 CardKey Model 支持 type_id 关联
- feat(frontend): 新增 cardType API 和 TypeManage 组件
- feat(frontend): 重构 GenerateDialog 支持动态字段
- feat(frontend): Tab集成类型管理到卡密页面
- docs: 添加类型表重构文档

---

## 🎉 **升级收益**

### **用户体验**
- ⚡ 生成卡密更快速（无需重复填写配置）
- 🎯 操作更简单（选择类型即可）
- 📱 界面更清爽（只显示需要的字段）

### **开发效率**
- 🔧 新增类型无需改代码（配置即可）
- 🐛 减少配置错误（统一管理）
- 📦 代码更简洁（逻辑分离）

### **系统维护**
- 🔍 配置集中易于审计
- 🔒 数据完整性有保障
- 📈 易于扩展新功能

---

**文档维护者**: AI Assistant  
**最后更新**: 2025-10-05  
**版本**: v2.0.0

