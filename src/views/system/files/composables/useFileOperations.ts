import { ref } from "vue";
import { message } from "@/utils/message";
import {
  deleteFile,
  restoreFile,
  forceDeleteFile,
  batchDeleteFiles,
  type FileInfo
} from "@/api/fileManage";

/**
 * 文件操作相关的composable
 */
export function useFileOperations() {
  const deleteLoading = ref(false);

  /**
   * 软删除文件
   */
  const handleSoftDelete = async (fileId: number) => {
    try {
      const res: any = await deleteFile(fileId);
      if (res?.code === 200) {
        message("文件删除成功", { type: "success" });
        return true;
      } else {
        message(res?.message || "删除失败", { type: "error" });
        return false;
      }
    } catch (error) {
      console.error("删除文件失败:", error);
      message("操作失败，请稍后重试", { type: "error" });
      return false;
    }
  };

  /**
   * 恢复文件
   */
  const handleRestore = async (fileId: number) => {
    try {
      const res: any = await restoreFile(fileId);
      if (res?.code === 200) {
        message("文件恢复成功", { type: "success" });
        return true;
      } else {
        message(res?.message || "文件恢复失败", { type: "error" });
        return false;
      }
    } catch (error) {
      console.error("恢复文件失败:", error);
      message("文件恢复失败，请稍后重试", { type: "error" });
      return false;
    }
  };

  /**
   * 永久删除文件
   */
  const handleForceDelete = async (fileId: number) => {
    try {
      const res: any = await forceDeleteFile(fileId);
      if (res?.code === 200) {
        message("文件永久删除成功", { type: "success" });
        return true;
      } else {
        message(res?.message || "文件永久删除失败", { type: "error" });
        return false;
      }
    } catch (error) {
      console.error("永久删除文件失败:", error);
      message("文件永久删除失败，请稍后重试", { type: "error" });
      return false;
    }
  };

  /**
   * 批量删除文件
   */
  const handleBatchDelete = async (
    fileIds: number[],
    isForce: boolean = false
  ) => {
    try {
      const res: any = await batchDeleteFiles(fileIds, isForce);
      if (res?.code === 200) {
        const action = isForce ? "永久删除" : "删除";
        message(`批量${action}成功`, { type: "success" });
        return true;
      } else {
        message(res?.message || "批量删除失败", { type: "error" });
        return false;
      }
    } catch (error) {
      console.error("批量删除文件失败:", error);
      message("批量删除失败，请稍后重试", { type: "error" });
      return false;
    }
  };

  return {
    deleteLoading,
    handleSoftDelete,
    handleRestore,
    handleForceDelete,
    handleBatchDelete
  };
}
