# 🔄 卡密字段重命名更新说明

## 📅 更新日期
**2025-10-04**

## 📋 更新概述
为了消除字段语义混淆,将卡密表的 `valid_minutes` 字段重命名为 `membership_duration`,同时优化了相关UI文案。

---

## 🔄 **字段变更**

### **数据库变更**
```sql
-- 主要字段重命名
ALTER TABLE `bl_card_keys` 
CHANGE COLUMN `valid_minutes` `membership_duration` int(11) NOT NULL DEFAULT 0 
COMMENT '兑换后获得的会员时长(分钟), 0表示永久会员';
```

### **变更原因**
旧字段名 `valid_minutes` 容易造成以下混淆:
- ❌ 容易理解为"卡密本身的有效分钟数"
- ❌ 与 `available_time` (卡密可用期限)概念混淆
- ❌ 开发时需要额外注释才能理解准确含义

新字段名 `membership_duration` 语义明确:
- ✅ 一看就知道是"会员持续时间"
- ✅ 与"兑换后获得"的概念直接关联
- ✅ 与 `available_time` 区分清晰

---

## 🎯 **两个关键概念**

### **1. membership_duration (会员时长)**
- **含义**: 用户兑换卡密后,获得的会员权益时长
- **作用时机**: 兑换后生效
- **示例**: `43200` = 兑换后获得30天会员

### **2. available_time (兑换期限)**
- **含义**: 卡密本身的可兑换截止时间
- **作用时机**: 兑换前检查
- **示例**: `"2025-12-31 23:59:59"` = 必须在此前兑换

---

## 📝 **代码变更清单**

### ✅ **前端代码修改**

#### **1. API接口文件 (`src/api/cardKey.ts`)**
```typescript
// 旧代码
export interface CardKey {
  valid_minutes: number; // 兑换物品的时长（分钟）
}

// 新代码
export interface CardKey {
  /** 
   * 兑换后获得的会员时长(分钟)
   * @example 0 - 永久会员
   * @example 43200 - 30天会员
   */
  membership_duration: number;
}
```

#### **2. 工具函数重命名**
```typescript
// 新增主函数
export const formatMembershipDuration = (minutes: number): string => {
  // ...
};

// 保留向后兼容别名
export const formatValidMinutes = formatMembershipDuration;
```

#### **3. UI组件更新**

**cardKey.vue (列表页)**
```vue
<!-- 旧代码 -->
<el-table-column prop="valid_minutes" label="会员时长" />

<!-- 新代码 -->
<el-table-column prop="membership_duration" label="赠送时长" />
```

**GenerateDialog.vue (生成对话框)**
```vue
<!-- 旧代码 -->
<el-form-item label="有效时长" prop="valid_minutes">
  <el-input-number v-model="form.valid_minutes" />
</el-form-item>

<!-- 新代码 -->
<el-form-item label="会员时长" prop="membership_duration">
  <el-input-number v-model="form.membership_duration" />
  <div class="form-tip">用户兑换后获得的会员时长</div>
</el-form-item>
```

---

## 🎨 **UI文案优化**

### **表格列名变更**
| 位置 | 旧文案 | 新文案 | 说明 |
|------|--------|--------|------|
| 列表页 | "会员时长" | "赠送时长" | 更明确表达是兑换后赠送的时长 |
| 列表页 | "可用期限" | "兑换期限" | 强调是卡密本身的兑换期限 |
| 生成对话框 | "有效时长" | "会员时长" | 与字段名一致 |
| 生成对话框 | "可用期限" | "兑换期限" | 与列表页统一 |

### **提示文案新增**
```vue
<!-- 会员时长提示 -->
<div class="form-tip">用户兑换后获得的会员时长</div>

<!-- 兑换期限提示 -->
<div class="form-tip">卡密本身的可兑换截止时间，超过此时间卡密作废</div>
```

---

## 🔄 **向后兼容处理**

为了保证代码平滑过渡,我们保留了旧函数名作为别名:

```typescript
// 旧函数名仍然可用
export const formatValidMinutes = formatMembershipDuration;
export const ValidMinutesOptions = MembershipDurationOptions;
```

**建议**: 新代码使用新的函数名,旧代码可以暂时继续使用,但应逐步迁移。

---

## ✅ **验证清单**

### **前端验证**
- [x] TypeScript接口更新完成
- [x] 所有组件引用更新完成
- [x] UI文案优化完成
- [x] 函数命名优化完成
- [x] 向后兼容别名添加

### **后端验证**
- [x] 数据库字段重命名 (由开发者完成)
- [ ] API返回字段更新 (需要后端开发者确认)
- [ ] Model层字段映射更新 (需要后端开发者确认)
- [ ] Service层业务逻辑更新 (需要后端开发者确认)

---

## 📚 **相关文档**

详细的字段说明文档,请查看:
- 📘 [卡密系统字段说明文档](./cardkey-fields-guide.md)

该文档包含:
- 两个字段的详细解释
- 完整的使用示例
- UI展示规范
- 常见错误避免方法
- 开发清单

---

## 🚨 **注意事项**

### **开发者需要注意**
1. ⚠️ 数据库字段已改名,所有SQL查询需要使用新字段名
2. ⚠️ API接口返回需要使用新字段名
3. ⚠️ 前端接口类型已更新,需要同步后端返回
4. ⚠️ 旧的 `valid_minutes` 字段名已废弃

### **联调检查点**
- [ ] 前端能正常获取 `membership_duration` 字段
- [ ] 生成卡密时 `membership_duration` 正确保存
- [ ] 列表页能正确显示会员时长
- [ ] 兑换时能正确计算会员到期时间

---

## 🔗 **相关提交**
- 前端代码更新: `feat: 重命名 valid_minutes 为 membership_duration`
- 文档更新: `docs: 添加卡密字段说明文档`

---

**如有疑问,请查看详细文档或联系开发团队。**

