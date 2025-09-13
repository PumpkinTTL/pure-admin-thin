<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/**
 * 更新权限表结构，初始化parent_id字段
 */
class UpdatePermissionsStructure extends Command
{
    protected function configure()
    {
        $this->setName('update:permissions')
            ->setDescription('更新权限表结构，初始化parent_id字段');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始更新权限表结构...');
        
        try {
            // 1. 检查permissions表是否存在parent_id字段
            $tablePrefix = config('database.connections.mysql.prefix');
            $hasParentId = false;
            
            $columns = Db::query("SHOW COLUMNS FROM {$tablePrefix}permissions");
            foreach ($columns as $column) {
                if ($column['Field'] == 'parent_id') {
                    $hasParentId = true;
                    break;
                }
            }
            
            // 2. 如果不存在parent_id字段，则添加
            if (!$hasParentId) {
                $output->writeln('添加parent_id字段...');
                Db::execute("ALTER TABLE {$tablePrefix}permissions ADD COLUMN `parent_id` INT DEFAULT 0 COMMENT '父级权限ID, 0表示顶级权限'");
                $output->writeln('parent_id字段添加成功');
            } else {
                $output->writeln('parent_id字段已存在，跳过添加步骤');
            }
            
            // 3. 初始化所有现有权限的parent_id为0（顶级权限）
            $output->writeln('初始化所有权限的parent_id值...');
            Db::name('permissions')->where('parent_id IS NULL')->update(['parent_id' => 0]);
            
            // 4. 根据已有的权限iden进行分组归类（可选，根据实际业务规则调整）
            $output->writeln('根据权限标识进行归类...');
            
            // 示例：创建基础权限分类
            $this->createPermissionCategories($output);
            
            $output->writeln('权限表结构更新完成！');
            return 0;
        } catch (\Exception $e) {
            $output->error('更新权限表结构失败: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * 创建权限分类
     * @param Output $output
     */
    protected function createPermissionCategories(Output $output)
    {
        // 定义基础权限分类
        $categories = [
            ['name' => '系统管理', 'iden' => 'system', 'description' => '系统管理相关权限'],
            ['name' => '用户管理', 'iden' => 'user', 'description' => '用户管理相关权限'],
            ['name' => '角色管理', 'iden' => 'role', 'description' => '角色管理相关权限'],
            ['name' => '权限管理', 'iden' => 'permission', 'description' => '权限管理相关权限'],
            ['name' => '菜单管理', 'iden' => 'menu', 'description' => '菜单管理相关权限'],
            ['name' => '内容管理', 'iden' => 'content', 'description' => '内容管理相关权限'],
        ];
        
        // 创建分类
        foreach ($categories as $category) {
            // 检查分类是否已存在
            $existingCategory = Db::name('permissions')
                ->where('iden', $category['iden'])
                ->where('parent_id', 0)
                ->find();
            
            if (!$existingCategory) {
                // 创建分类
                $category['parent_id'] = 0; // 顶级分类
                $categoryId = Db::name('permissions')->insertGetId($category);
                $output->writeln("创建权限分类: {$category['name']} (ID: {$categoryId})");
                
                // 将相关权限归入该分类
                $pattern = $category['iden'] . ':%';
                $count = Db::name('permissions')
                    ->where('iden', 'like', $pattern)
                    ->where('id', '<>', $categoryId)
                    ->update(['parent_id' => $categoryId]);
                
                $output->writeln("  归类 {$count} 个权限到 {$category['name']} 分类");
            } else {
                $output->writeln("权限分类已存在: {$category['name']} (ID: {$existingCategory['id']})");
                
                // 将相关权限归入该分类
                $pattern = $category['iden'] . ':%';
                $count = Db::name('permissions')
                    ->where('iden', 'like', $pattern)
                    ->where('id', '<>', $existingCategory['id'])
                    ->update(['parent_id' => $existingCategory['id']]);
                
                $output->writeln("  归类 {$count} 个权限到 {$category['name']} 分类");
            }
        }
    }
} 