<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use think\facade\Route as ThinkRoute;

class ImportRoutes extends Command
{
    protected function configure()
    {
        $this->setName('import:routes')
            ->setDescription('Import all routes from route.php into database')
            ->addOption('clear', 'c', Option::VALUE_NONE, 'Clear existing routes before import');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('Starting routes import...');
        
        // 是否先清空表
        if ($input->hasOption('clear')) {
            Db::name('route')->delete(true);
            $output->writeln('Cleared existing routes');
        }
        
        // 获取所有路由
        $routes = $this->parseRouteFile();
        
        // 导入到数据库
        $count = $this->importRoutes($routes, $output);
        
        $output->writeln("Import completed. Imported $count routes.");
        return 0;
    }
    
    /**
     * 解析route.php文件获取所有路由
     * @return array
     */
    protected function parseRouteFile(): array
    {
        $routeFile = file_get_contents(app_path() . '/api/route/route.php');
        $routes = [];
        
        // 正则匹配所有Route::rule调用
        preg_match_all('/Route::rule\([\'"]([^\'"]+)[\'"],\s*[\'"]([^\'"]+)[\'"]\)/', $routeFile, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $path = $match[1];
            $action = $match[2];
            
            // 解析版本和控制器
            preg_match('/^:version\.([^\/]+)\/(.+)$/', $action, $actionMatches);
            
            if (isset($actionMatches[1]) && isset($actionMatches[2])) {
                $model = $actionMatches[1];
                $method = $actionMatches[2];
                
                // 提取路由组信息
                preg_match('/Route::group\([\'"]([^\'"]+)[\'"],\s*function\s*\(\)\s*\{(?:[^{}]|(?R))*' . preg_quote($match[0], '/') . '/s', $routeFile, $groupMatches);
                
                $groupPath = '';
                if (isset($groupMatches[1])) {
                    $groupPath = str_replace('/:version/', '', $groupMatches[1]);
                }
                
                // 构建完整路径
                $fullPath = '/api/v1' . $groupPath . $path;
                
                // 添加到路由列表
                $routes[] = [
                    'version' => 'v1',
                    'method' => $method,
                    'model' => $model,
                    'path' => $path,
                    'full_path' => $fullPath,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                    'description' => '',
                    'status' => 1
                ];
            }
        }
        
        return $routes;
    }
    
    /**
     * 将路由导入到数据库
     * @param array $routes
     * @param Output $output
     * @return int
     */
    protected function importRoutes(array $routes, Output $output): int
    {
        $count = 0;
        
        foreach ($routes as $route) {
            try {
                // 检查是否已存在
                $exists = Db::name('route')
                    ->where('full_path', $route['full_path'])
                    ->where('method', $route['method'])
                    ->find();
                
                if (!$exists) {
                    // 插入新路由
                    Db::name('route')->insert($route);
                    $count++;
                    $output->writeln("Imported: {$route['full_path']} -> {$route['model']}/{$route['method']}");
                } else {
                    $output->writeln("Skipped (exists): {$route['full_path']}");
                }
            } catch (\Exception $e) {
                $output->error("Error importing route {$route['full_path']}: " . $e->getMessage());
            }
        }
        
        return $count;
    }
} 