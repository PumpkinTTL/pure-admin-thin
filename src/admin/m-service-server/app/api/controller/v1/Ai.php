<?php

namespace app\api\controller\v1;

// 使用basecontroller
use app\BaseController;
use think\facade\Config;
use think\facade\Log;
use think\Response;
use app\api\services\LogService;
use think\response\Json;

class Ai extends BaseController
{
    // API配置
    protected $apiUrl = 'https://api.suanli.cn/v1/chat/completions';
    protected $apiKey;
    
    public function __construct()
    {
        // 从配置文件获取API密钥
        $this->apiKey = Config::get('keys.qwq_api', 'sk-W0rpStc95T7JVYVwDYc29IyirjtpPPby6SozFMQr17m8KWeo');
    }
    //api配置列表
    public function index()
    {
        $key = Config::get('keys.cerebras');
        return json(['code' => 200, 'msg' => 'AI接口测试', 'key' => $key]);
    }
    
    /**
     * 调用QWQ-32B大语言模型API
     * @return Json
     */
    public function qwqChat(): Json
    {
        // 获取请求参数
        $params = request()->param();
        
        // 检查是否提供了问题内容
        if (empty($params['content'])) {
            return json(['code' => 0, 'msg' => '请提供问题内容']);
        }
        
        $content = $params['content'];
        $systemPrompt = $params['system_prompt'] ?? ''; // 可选的系统提示词
        
        try {
            // 准备请求数据
            $requestData = [
                'model' => 'free:QwQ-32B',
                'messages' => []
            ];
            
            // 如果有系统提示词，添加到消息中
            if (!empty($systemPrompt)) {
                $requestData['messages'][] = [
                    'role' => 'system',
                    'content' => $systemPrompt
                ];
            }
            
            // 添加用户消息
            $requestData['messages'][] = [
                'role' => 'user',
                'content' => $content
            ];
            
            // 记录请求日志
            LogService::log("调用QWQ-32B API - 问题：{$content}", $requestData);
            
            // 发起API请求
            $response = $this->sendRequest($requestData);
            
            // 记录响应日志
            LogService::log("QWQ-32B API响应", $response);
            
            // 从响应中提取AI回复内容
            if (!empty($response['choices'][0]['message']['content'])) {
                $aiReply = $response['choices'][0]['message']['content'];
                return json([
                    'code' => 1,
                    'msg' => '调用成功',
                    'data' => [
                        'model' => 'free:QwQ-32B',
                        'content' => $aiReply,
                        'raw_response' => $response
                    ]
                ]);
            } else {
                return json(['code' => 0, 'msg' => 'API响应格式异常', 'data' => $response]);
            }
            
        } catch (\Exception $e) {
            // 记录错误日志
            LogService::error($e, ['msg' => '调用QWQ-32B API出错']);
            return json(['code' => 0, 'msg' => '调用失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 发送请求到QWQ-32B API
     * @param array $data 请求数据
     * @return array 响应数据
     * @throws \Exception
     */
    protected function sendRequest(array $data): array
    {
        // 初始化curl
        $ch = curl_init($this->apiUrl);
        
        // 设置请求头
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];
        
        // 设置请求选项
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30秒超时
        
        // 禁用SSL验证（注意：生产环境中这可能带来安全风险）
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        // 记录请求SQL日志
        LogService::sql("QWQ-32B API请求: " . json_encode($data));
        
        // 执行请求
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // 检查是否有错误
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('请求失败: ' . $error);
        }
        
        curl_close($ch);
        
        // 检查HTTP状态码
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \Exception('API返回错误状态码: ' . $httpCode . ', 响应: ' . $response);
        }
        
        // 解析JSON响应
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('API响应格式错误: ' . json_last_error_msg() . ', 原始响应: ' . $response);
        }
        
        return $result;
    }
    
    /**
     * 历史聊天记录方法（支持多轮对话）
     * @return Json
     */
    public function chatWithHistory(): Json
    {
        // 获取请求参数
        $params = request()->param();
        
        // 检查是否提供了消息历史
        if (empty($params['messages']) || !is_array($params['messages'])) {
            return json(['code' => 0, 'msg' => '请提供有效的消息历史']);
        }
        
        try {
            // 准备请求数据
            $requestData = [
                'model' => 'free:QwQ-32B',
                'messages' => $params['messages']
            ];
            
            // 记录请求日志
            LogService::log("调用QWQ-32B API - 多轮对话", $requestData);
            
            // 发起API请求
            $response = $this->sendRequest($requestData);
            
            // 记录响应日志
            LogService::log("QWQ-32B API多轮对话响应", $response);
            
            // 从响应中提取AI回复内容
            if (!empty($response['choices'][0]['message']['content'])) {
                $aiReply = $response['choices'][0]['message']['content'];
                return json([
                    'code' => 1,
                    'msg' => '调用成功',
                    'data' => [
                        'model' => 'free:QwQ-32B',
                        'reply' => $aiReply,
                        'raw_response' => $response
                    ]
                ]);
            } else {
                return json(['code' => 0, 'msg' => 'API响应格式异常', 'data' => $response]);
            }
            
        } catch (\Exception $e) {
            // 记录错误日志
            LogService::error($e, ['msg' => '调用QWQ-32B API多轮对话出错']);
            return json(['code' => 0, 'msg' => '调用失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 流式输出聊天响应
     * @return \think\Response
     */
    public function streamChat()
    {
        // 获取请求参数
        $params = request()->param();
        
        // 检查是否提供了问题内容
        if (empty($params['content'])) {
            return json(['code' => 0, 'msg' => '请提供问题内容']);
        }
        
        $content = $params['content'];
        $systemPrompt = $params['system_prompt'] ?? '';
        
        // 准备消息数据
        $messages = [];
        
        // 处理历史消息
        if (!empty($params['history'])) {
            // 尝试解析JSON历史记录
            try {
                $history = json_decode($params['history'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($history)) {
                    $messages = $history;
                    
                    // 记录日志
                    LogService::log("使用历史消息 - 数量: " . count($history));
                }
            } catch (\Exception $e) {
                LogService::error($e, ['msg' => '历史消息解析失败']);
            }
        } else {
            // 如果没有历史记录，创建新的消息数组
            
            // 如果有系统提示词，添加到消息中
            if (!empty($systemPrompt)) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $systemPrompt
                ];
            }
            
            // 添加用户消息
            $messages[] = [
                'role' => 'user',
                'content' => $content
            ];
        }
        
        // 如果消息数组为空或最后一条消息不是用户消息，添加当前用户问题
        if (empty($messages) || 
            (end($messages)['role'] !== 'user' || end($messages)['content'] !== $content)) {
            $messages[] = [
                'role' => 'user',
                'content' => $content
            ];
        }
        
        // 准备请求数据
        $requestData = [
            'model' => 'free:QwQ-32B',
            'messages' => $messages,
            'stream' => true  // 启用流式输出
        ];
        
        // 记录请求日志
        LogService::log("调用QWQ-32B API流式输出 - 问题：{$content}");
        
        // 设置响应头，使用SSE格式
        ob_clean(); // 清除之前的输出缓冲
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // 禁用Nginx缓冲
        
        // 发送初始数据
        echo "event: start\n";
        echo "data: " . json_encode(['status' => 'started']) . "\n\n";
        flush();
        
        try {
            // 执行流式请求
            $this->sendStreamRequest($requestData, function($chunk) {
                // 检查数据块是否有效
                if (!empty($chunk)) {
                    // 发送数据块
                    echo "event: chunk\n";
                    echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                    flush();
                }
            });
            
            // 发送结束信号
            echo "event: end\n";
            echo "data: " . json_encode(['status' => 'completed']) . "\n\n";
            flush();
            
            return response('', 200); // 不需要额外的响应内容
            
        } catch (\Exception $e) {
            // 发送错误信息
            echo "event: error\n";
            echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
            flush();
            
            // 记录错误日志
            LogService::error($e, ['msg' => 'QWQ-32B流式输出错误']);
            
            return response('', 500);
        }
    }
    
    /**
     * 发送流式请求并逐块处理响应
     * @param array $data 请求数据
     * @param callable $callback 处理数据块的回调函数
     * @throws \Exception
     */
    protected function sendStreamRequest(array $data, callable $callback)
    {
        // 初始化curl
        $ch = curl_init($this->apiUrl);
        
        // 设置请求头
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: text/event-stream' // 接受SSE格式的响应
        ];
        
        // 设置请求选项
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 增加超时时间
        
        // 禁用SSL验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        // 设置写入回调函数
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use ($callback) {
            // 处理返回的数据块
            $lines = explode("\n", $data);
            $buffer = '';
            
            foreach ($lines as $line) {
                // 跳过空行和注释
                if (empty(trim($line)) || strpos($line, ':') === 0) {
                    continue;
                }
                
                // 处理data:行
                if (strpos($line, 'data:') === 0) {
                    $jsonData = trim(substr($line, 5));
                    
                    // 检查是否是[DONE]标记
                    if ($jsonData === '[DONE]') {
                        continue;
                    }
                    
                    try {
                        $parsed = json_decode($jsonData, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            // 提取内容
                            if (isset($parsed['choices'][0]['delta']['content'])) {
                                $textChunk = $parsed['choices'][0]['delta']['content'];
                                $buffer .= $textChunk;
                                
                                // 调用回调函数处理文本块
                                $callback($textChunk);
                            }
                        }
                    } catch (\Exception $e) {
                        // 忽略解析错误，继续处理
                        LogService::log("流式响应解析错误: " . $e->getMessage(), [], 'warning');
                    }
                }
            }
            
            // 继续接收数据
            return strlen($data);
        });
        
        // 执行请求
        $success = curl_exec($ch);
        
        // 检查是否有错误
        if ($success === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('流式请求失败: ' . $error);
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 检查HTTP状态码
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \Exception('API返回错误状态码: ' . $httpCode);
        }
        
        return true;
    }
}
