<template>
  <div class="type-manage-container">
    <!-- 操作栏 -->
    <el-row :gutter="8" align="middle" style="margin-bottom: 16px">
      <!-- 搜索 -->
      <el-col :xs="24" :sm="8" :md="6">
        <el-input
          v-model="searchForm.type_name"
          placeholder="搜索类型名称"
          clearable
          size="small"
          @keyup.enter="handleSearch"
        >
          <template #prefix>
            <IconifyIconOnline icon="ep:search" />
          </template>
        </el-input>
      </el-col>

      <!-- 状态筛选 -->
      <el-col :xs="12" :sm="6" :md="4">
        <el-select
          v-model="searchForm.status"
          placeholder="状态"
          clearable
          size="small"
          @change="handleSearch"
        >
          <el-option label="启用" :value="1" />
          <el-option label="停用" :value="0" />
        </el-select>
      </el-col>

      <!-- 操作按钮 -->
      <el-col :xs="12" :sm="10" :md="14" class="action-buttons">
        <el-button type="primary" size="small" @click="handleAdd">
          <IconifyIconOnline icon="ep:plus" />
          新增类型
        </el-button>
        <el-button
          type="danger"
          size="small"
          :disabled="selectedIds.length === 0"
          @click="handleBatchDelete"
        >
          <IconifyIconOnline icon="ep:delete" />
          删除
        </el-button>
      </el-col>
    </el-row>

    <!-- 数据表格 -->
    <el-table
      :data="tableData"
      v-loading="loading"
      style="width: 100%"
      @selection-change="handleSelectionChange"
      :header-cell-style="{ background: '#f5f7fa', color: '#606266' }"
      stripe
    >
      <el-table-column type="selection" width="45" align="center" />
      <el-table-column prop="id" label="ID" width="60" align="center" />

      <!-- 类型名称 -->
      <el-table-column prop="type_name" label="类型名称" min-width="120" align="center">
        <template #default="{ row }">
          <el-tag type="primary" effect="light">{{ row.type_name }}</el-tag>
        </template>
      </el-table-column>

      <!-- 类型编码 -->
      <el-table-column prop="type_code" label="类型编码" min-width="140" align="center" />

      <!-- 描述 -->
      <el-table-column
        prop="description"
        label="描述"
        min-width="150"
        align="center"
        show-overflow-tooltip
      >
        <template #default="{ row }">
          <span v-if="row.description">{{ row.description }}</span>
          <span v-else class="empty-text">-</span>
        </template>
      </el-table-column>

      <!-- 价格 -->
      <el-table-column prop="price" label="价格" width="90" align="center">
        <template #default="{ row }">
          <span v-if="row.price !== null" class="price-text">¥{{ row.price }}</span>
          <el-tag v-else type="info" size="small" effect="plain">不需要</el-tag>
        </template>
      </el-table-column>

      <!-- 会员时长 -->
      <el-table-column prop="membership_duration" label="会员时长" width="130" align="center">
        <template #default="{ row }">
          <template v-if="row.membership_duration === null">
            <el-tag type="info" size="small" effect="plain">不需要</el-tag>
          </template>
          <template v-else-if="row.membership_duration === 0">
            <el-tag type="success" size="small" effect="light">
              <IconifyIconOnline icon="ep:trophy" />永久
            </el-tag>
          </template>
          <template v-else>
            <span class="duration-text">{{ formatDuration(row.membership_duration) }}</span>
          </template>
        </template>
      </el-table-column>

      <!-- 可兑换天数 -->
      <el-table-column prop="available_days" label="可兑换天数" width="130" align="center">
        <template #default="{ row }">
          <template v-if="row.available_days === null">
            <el-tag type="success" size="small" effect="light">
              <IconifyIconOnline icon="ep:timer" />永久
            </el-tag>
          </template>
          <template v-else>
            <span class="duration-text">{{ row.available_days }}天</span>
          </template>
        </template>
      </el-table-column>

      <!-- 排序 -->
      <el-table-column prop="sort_order" label="排序" width="70" align="center" />

      <!-- 状态 -->
      <el-table-column prop="status" label="状态" width="80" align="center">
        <template #default="{ row }">
          <el-switch
            v-model="row.status"
            :active-value="1"
            :inactive-value="0"
            @change="handleStatusChange(row)"
          />
        </template>
      </el-table-column>

      <!-- 操作 -->
      <el-table-column label="操作" width="140" align="center" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" size="small" @click="handleEdit(row)">
            <IconifyIconOnline icon="ep:edit" />
            编辑
          </el-button>
          <el-button link type="danger" size="small" @click="handleDelete(row.id)">
            <IconifyIconOnline icon="ep:delete" />
            删除
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 分页 -->
    <el-pagination
      v-model:current-page="pagination.page"
      v-model:page-size="pagination.limit"
      :total="pagination.total"
      :page-sizes="[10, 20, 50, 100]"
      layout="total, sizes, prev, pager, next, jumper"
      @size-change="fetchList"
      @current-change="fetchList"
      style="margin-top: 16px; justify-content: center"
    />

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      destroy-on-close
      @close="handleDialogClose"
    >
      <el-form :model="formData" :rules="rules" ref="formRef" label-width="110px">
        <el-form-item label="类型名称" prop="type_name">
          <el-input v-model="formData.type_name" placeholder="请输入类型名称" />
        </el-form-item>

        <el-form-item label="类型编码" prop="type_code">
          <el-input
            v-model="formData.type_code"
            placeholder="请输入类型编码（英文）"
            :disabled="isEdit"
          />
        </el-form-item>

        <el-form-item label="描述">
          <el-input
            v-model="formData.description"
            type="textarea"
            :rows="2"
            placeholder="请输入类型描述"
          />
        </el-form-item>

        <el-form-item label="价格">
          <el-input-number
            v-model="formData.price"
            :min="0"
            :precision="2"
            placeholder="不填表示不需要价格"
            style="width: 100%"
            controls-position="right"
          />
          <div class="form-tip">留空表示该类型不需要价格字段</div>
        </el-form-item>

        <el-form-item label="会员时长">
          <el-input-number
            v-model="formData.membership_duration"
            :min="0"
            placeholder="单位：分钟，0=永久"
            style="width: 100%"
            controls-position="right"
          />
          <div class="form-tip">留空=不需要，0=永久会员，43200=30天</div>
        </el-form-item>

        <el-form-item label="可兑换天数">
          <el-input-number
            v-model="formData.available_days"
            :min="1"
            placeholder="生成后多少天内可兑换"
            style="width: 100%"
            controls-position="right"
          />
          <div class="form-tip">留空表示永久可兑换</div>
        </el-form-item>

        <el-form-item label="排序">
          <el-input-number
            v-model="formData.sort_order"
            :min="0"
            placeholder="数字越小越靠前"
            style="width: 100%"
            controls-position="right"
          />
        </el-form-item>

        <el-form-item label="状态">
          <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
          <span style="margin-left: 10px; color: #909399">{{
            formData.status === 1 ? "启用" : "停用"
          }}</span>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitLoading">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessageBox, type FormInstance, type FormRules } from "element-plus";
import { message } from "@/utils/message";
import {
  getCardTypeList,
  createCardType,
  updateCardType,
  deleteCardType,
  batchDeleteCardType,
  type CardType,
  type CardTypeFormData
} from "@/api/cardType";

// 搜索表单
const searchForm = reactive({
  type_name: "",
  status: undefined
});

// 表格数据
const tableData = ref<CardType[]>([]);
const loading = ref(false);
const selectedIds = ref<number[]>([]);

// 分页
const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0
});

// 对话框
const dialogVisible = ref(false);
const dialogTitle = ref("新增类型");
const isEdit = ref(false);
const submitLoading = ref(false);
const formRef = ref<FormInstance>();

// 表单数据
const formData = reactive<CardTypeFormData>({
  type_name: "",
  type_code: "",
  description: "",
  membership_duration: null,
  price: null,
  available_days: null,
  sort_order: 0,
  status: 1
});

const currentEditId = ref<number>();

// 表单验证规则
const rules: FormRules = {
  type_name: [{ required: true, message: "请输入类型名称", trigger: "blur" }],
  type_code: [{ required: true, message: "请输入类型编码", trigger: "blur" }]
};

/**
 * 获取列表
 */
const fetchList = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.page,
      limit: pagination.limit,
      ...searchForm
    };
    const response = await getCardTypeList(params);
    if (response.code === 200) {
      tableData.value = response.data.list || [];
      pagination.total = response.data.total || 0;
    }
  } catch (error) {
    message("获取列表失败", { type: "error" });
  } finally {
    loading.value = false;
  }
};

/**
 * 搜索
 */
const handleSearch = () => {
  pagination.page = 1;
  fetchList();
};

/**
 * 选择变化
 */
const handleSelectionChange = (selection: CardType[]) => {
  selectedIds.value = selection.map(item => item.id);
};

/**
 * 新增
 */
const handleAdd = () => {
  isEdit.value = false;
  dialogTitle.value = "新增类型";
  resetForm();
  dialogVisible.value = true;
};

/**
 * 编辑
 */
const handleEdit = (row: CardType) => {
  isEdit.value = true;
  dialogTitle.value = "编辑类型";
  currentEditId.value = row.id;

  // 填充表单
  Object.assign(formData, {
    type_name: row.type_name,
    type_code: row.type_code,
    description: row.description || "",
    membership_duration: row.membership_duration,
    price: row.price,
    available_days: row.available_days,
    sort_order: row.sort_order,
    status: row.status
  });

  dialogVisible.value = true;
};

/**
 * 提交表单
 */
const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async valid => {
    if (!valid) return;

    submitLoading.value = true;
    try {
      const submitData = { ...formData };

      // 处理空值
      if (submitData.price === null || submitData.price === undefined || submitData.price === "") {
        submitData.price = null;
      }
      if (
        submitData.membership_duration === null ||
        submitData.membership_duration === undefined ||
        submitData.membership_duration === ""
      ) {
        submitData.membership_duration = null;
      }
      if (
        submitData.available_days === null ||
        submitData.available_days === undefined ||
        submitData.available_days === ""
      ) {
        submitData.available_days = null;
      }

      let response;
      if (isEdit.value) {
        response = await updateCardType(currentEditId.value!, submitData);
      } else {
        response = await createCardType(submitData);
      }

      if (response.code === 200) {
        message(isEdit.value ? "更新成功" : "创建成功", { type: "success" });
        dialogVisible.value = false;
        fetchList();
      } else {
        message(response.message || "操作失败", { type: "error" });
      }
    } catch (error) {
      message("操作失败", { type: "error" });
    } finally {
      submitLoading.value = false;
    }
  });
};

/**
 * 状态切换
 */
const handleStatusChange = async (row: CardType) => {
  try {
    const response = await updateCardType(row.id, { status: row.status } as CardTypeFormData);
    if (response.code === 200) {
      message("状态更新成功", { type: "success" });
      fetchList();
    } else {
      message(response.message || "状态更新失败", { type: "error" });
      // 恢复状态
      row.status = row.status === 1 ? 0 : 1;
    }
  } catch (error) {
    message("状态更新失败", { type: "error" });
    // 恢复状态
    row.status = row.status === 1 ? 0 : 1;
  }
};

/**
 * 删除
 */
const handleDelete = async (id: number) => {
  try {
    await ElMessageBox.confirm("确定要删除该类型吗？", "删除确认", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning"
    });

    const response = await deleteCardType(id);
    if (response.code === 200) {
      message("删除成功", { type: "success" });
      fetchList();
    } else {
      message(response.message || "删除失败", { type: "error" });
    }
  } catch (error) {
    if (error !== "cancel") {
      message("删除失败", { type: "error" });
    }
  }
};

/**
 * 批量删除
 */
const handleBatchDelete = async () => {
  if (selectedIds.value.length === 0) {
    message("请选择要删除的类型", { type: "warning" });
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${selectedIds.value.length} 个类型吗？`,
      "批量删除确认",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    const response = await batchDeleteCardType(selectedIds.value);
    if (response.code === 200) {
      message("批量删除成功", { type: "success" });
      selectedIds.value = [];
      fetchList();
    } else {
      message(response.message || "批量删除失败", { type: "error" });
    }
  } catch (error) {
    if (error !== "cancel") {
      message("批量删除失败", { type: "error" });
    }
  }
};

/**
 * 对话框关闭
 */
const handleDialogClose = () => {
  resetForm();
};

/**
 * 重置表单
 */
const resetForm = () => {
  if (formRef.value) {
    formRef.value.resetFields();
  }
  Object.assign(formData, {
    type_name: "",
    type_code: "",
    description: "",
    membership_duration: null,
    price: null,
    available_days: null,
    sort_order: 0,
    status: 1
  });
  currentEditId.value = undefined;
};

/**
 * 格式化时长
 */
const formatDuration = (minutes: number): string => {
  if (minutes < 60) {
    return `${minutes}分钟`;
  } else if (minutes < 1440) {
    return `${Math.floor(minutes / 60)}小时`;
  } else {
    return `${Math.floor(minutes / 1440)}天`;
  }
};

// 组件挂载时获取数据
onMounted(() => {
  fetchList();
});
</script>

<style scoped lang="scss">
.type-manage-container {
  .action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
  }

  .empty-text {
    color: #c0c4cc;
  }

  .price-text {
    color: #f56c6c;
    font-weight: 500;
  }

  .duration-text {
    color: #409eff;
    font-weight: 500;
  }

  .form-tip {
    font-size: 12px;
    color: #909399;
    margin-top: 4px;
  }

}
</style>

