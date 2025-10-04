# 📘 卡密系统字段说明文档

## 📋 核心字段解释

卡密系统中有**两个关键的时间概念**,经常容易混淆,本文档详细说明它们的区别和使用场景。

---

## 🔑 **字段1: membership_duration (会员时长)**

### 📌 **字段信息**
- **字段名**: `membership_duration`
- **类型**: `INT`
- **单位**: 分钟
- **作用时机**: **兑换后生效**

### 💡 **含义**
用户使用(兑换)这个卡密后,**获得的会员权益时长**。

### 📊 **取值说明**
| 值 | 含义 | 说明 |
|---|------|------|
| `0` | 永久会员 | 兑换后获得永久会员资格 |
| `60` | 1小时会员 | 兑换后获得1小时会员 |
| `1440` | 1天会员 | 兑换后获得24小时会员 |
| `10080` | 7天会员 | 兑换后获得7天会员 |
| `43200` | 30天会员 | 兑换后获得30天会员 |

### 🎯 **实际场景**
```
用户场景:
1. 用户输入卡密: ABCD-1234-EFGH-5678
2. 系统验证卡密有效
3. 用户点击"兑换"
4. ✅ 系统读取 membership_duration = 43200 (30天)
5. ✅ 用户获得30天会员权益
6. ✅ 会员到期时间 = 当前时间 + 30天
```

### 💻 **计算逻辑**
```javascript
// 会员到期时间计算
if (cardKey.membership_duration === 0) {
  // 永久会员
  memberExpireTime = null; // 或 "9999-12-31"
} else {
  // 有期限会员
  const useTime = new Date(); // 兑换时间
  const minutes = cardKey.membership_duration;
  memberExpireTime = new Date(useTime.getTime() + minutes * 60 * 1000);
}
```

---

## ⏰ **字段2: available_time (兑换期限)**

### 📌 **字段信息**
- **字段名**: `available_time`
- **类型**: `DATETIME`
- **格式**: `YYYY-MM-DD HH:mm:ss`
- **作用时机**: **兑换前检查**

### 💡 **含义**
卡密本身的**可兑换截止时间**,超过这个时间后,卡密作废无法使用。

### 📊 **取值说明**
| 值 | 含义 | 说明 |
|---|------|------|
| `NULL` | 永久可用 | 卡密永远可以兑换,没有时间限制 |
| `2025-12-31 23:59:59` | 年底前可用 | 必须在2025年底前兑换 |
| `2025-10-11 00:00:00` | 7天内可用 | 假设今天是10月4日,则7天内必须兑换 |

### 🎯 **实际场景**
```
用户场景:
1. 用户在 2025-10-15 输入卡密
2. ⚠️ 系统检查 available_time = "2025-10-11 23:59:59"
3. ❌ 当前时间已超过可用期限
4. ❌ 提示: "该卡密已过期,无法兑换"
```

### 💻 **验证逻辑**
```javascript
// 卡密可用性检查
function checkCardKeyAvailability(cardKey) {
  // 第一步: 检查状态
  if (cardKey.status !== 0) {
    return { valid: false, reason: "卡密已使用或禁用" };
  }
  
  // 第二步: 检查兑换期限
  if (cardKey.available_time) {
    const deadline = new Date(cardKey.available_time);
    const now = new Date();
    
    if (deadline < now) {
      return { valid: false, reason: "卡密已过期,超出兑换期限" };
    }
  }
  
  // 通过检查
  return { valid: true };
}
```

---

## 🔄 **两个字段的对比**

| 维度 | membership_duration | available_time |
|-----|---------------------|----------------|
| **中文名** | 会员时长 | 兑换期限 |
| **作用对象** | 用户会员资格 | 卡密本身 |
| **作用时机** | 兑换后生效 | 兑换前检查 |
| **NULL/0含义** | 0=永久会员 | NULL=永久可用 |
| **影响范围** | 用户会员到期时间 | 卡密是否可用 |
| **UI展示** | "30天会员" | "2025-12-31前可用" |
| **数据类型** | INT (分钟数) | DATETIME |

---

## 📚 **完整使用示例**

### **示例1: 标准30天会员卡,必须7天内兑换**
```json
{
  "code": "ABCD-1234-EFGH-5678",
  "type": "30天VIP会员卡",
  "membership_duration": 43200,         // 兑换后获得30天会员
  "available_time": "2025-10-11 23:59:59", // 必须在7天内兑换
  "status": 0
}
```

**业务逻辑:**
1. ✅ 用户必须在 `2025-10-11 23:59:59` 前使用这个卡密
2. ✅ 一旦兑换成功,用户获得30天会员资格
3. ✅ 会员到期时间 = 兑换时间 + 30天
4. ❌ 如果超过 `2025-10-11 23:59:59` 还没兑换,卡密作废

**时间线:**
```
2025-10-04            2025-10-11          2025-11-10
    |                     |                    |
    现在                兑换期限             会员到期
    ↓                     ↓                    ↓
  [可以兑换]  →  [必须兑换]  →  [卡密作废]
                     ↑
                  假设这天兑换
                     ↓
              获得30天会员 (membership_duration)
                     ↓
              会员到期时间: 2025-11-10
```

---

### **示例2: 永久会员卡,永久可用**
```json
{
  "code": "WXYZ-9876-IJKL-5432",
  "type": "永久VIP卡",
  "membership_duration": 0,      // 兑换后获得永久会员
  "available_time": null,        // 永久可用,任何时候都可以兑换
  "status": 0
}
```

**业务逻辑:**
1. ✅ 卡密永远不会过期 (`available_time = null`)
2. ✅ 用户任何时候都可以兑换
3. ✅ 兑换后获得永久会员 (`membership_duration = 0`)

---

### **示例3: 1小时体验卡,90天内兑换**
```json
{
  "code": "TEST-1111-XXXX-2222",
  "type": "1小时体验卡",
  "membership_duration": 60,             // 兑换后获得1小时会员
  "available_time": "2026-01-03 23:59:59", // 90天内兑换
  "status": 0
}
```

**业务逻辑:**
1. ✅ 用户有90天时间来兑换这个卡密
2. ✅ 兑换后只能体验1小时会员
3. ✅ 适合用于新用户体验活动

---

## 🎨 **UI展示规范**

### **列表页展示**
```vue
<!-- 赠送时长列 -->
<el-table-column prop="membership_duration" label="赠送时长">
  <template #default="{ row }">
    <el-tag v-if="row.membership_duration === 0" type="success">
      永久
    </el-tag>
    <span v-else>{{ formatMembershipDuration(row.membership_duration) }}</span>
  </template>
</el-table-column>

<!-- 兑换期限列 -->
<el-table-column prop="available_time" label="兑换期限">
  <template #default="{ row }">
    <el-tag v-if="!row.available_time" type="success">
      永久可用
    </el-tag>
    <span v-else :style="{ color: isExpired(row.available_time) ? 'red' : 'black' }">
      {{ row.available_time }}
    </span>
  </template>
</el-table-column>
```

### **文案规范**
| 场景 | 推荐文案 | 不推荐文案 |
|------|---------|-----------|
| 会员时长 | "赠送时长"、"会员时长" | ~~"有效期"~~、~~"有效时长"~~ |
| 兑换期限 | "兑换期限"、"可用期限" | ~~"有效期"~~、~~"截止时间"~~ |
| 永久会员 | "永久会员"、"永久" | ~~"永久有效"~~ |
| 永久可用 | "永久可用"、"不限时" | ~~"永久有效"~~ |

---

## ⚠️ **常见错误和避免方法**

### ❌ **错误1: 混淆两个字段**
```javascript
// 错误: 用 membership_duration 判断卡密是否过期
if (cardKey.membership_duration < Date.now()) {
  return "卡密已过期"; // ❌ 错误!
}
```

✅ **正确做法:**
```javascript
// 正确: 用 available_time 判断卡密是否过期
if (cardKey.available_time && new Date(cardKey.available_time) < new Date()) {
  return "卡密已过期"; // ✅ 正确!
}
```

---

### ❌ **错误2: UI文案不清晰**
```vue
<!-- 错误: 两列都叫"有效期" -->
<el-table-column label="有效期">{{ row.membership_duration }}</el-table-column>
<el-table-column label="有效期">{{ row.available_time }}</el-table-column>
```

✅ **正确做法:**
```vue
<!-- 正确: 明确区分两个概念 -->
<el-table-column label="赠送时长">{{ row.membership_duration }}</el-table-column>
<el-table-column label="兑换期限">{{ row.available_time }}</el-table-column>
```

---

## 📝 **开发清单**

### **前端开发者需要注意:**
- [ ] 接口字段使用 `membership_duration` 而不是 `valid_minutes`
- [ ] UI文案明确区分 "赠送时长" 和 "兑换期限"
- [ ] 使用 `formatMembershipDuration()` 格式化会员时长
- [ ] 兑换期限过期时,用红色标注提示
- [ ] 表单验证两个字段都正确填写

### **后端开发者需要注意:**
- [ ] 数据库字段使用 `membership_duration` 而不是 `valid_minutes`
- [ ] API返回时包含两个字段
- [ ] 兑换时先检查 `available_time` 是否过期
- [ ] 计算会员到期时间使用 `membership_duration`
- [ ] SQL查询时注意字段名称

---

## 🔗 **相关文档**
- [卡密API接口文档](./cardkey-api.md)
- [数据库表结构](./database-schema.md)
- [前端组件开发指南](./frontend-guide.md)

---

## 📞 **问题反馈**
如有疑问,请联系开发团队或查看代码注释。

**最后更新时间**: 2025-10-04
**维护者**: AI Assistant

