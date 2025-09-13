import OpenAI from "openai";
// 导入环境变量的key
const deepseekApiKey = import.meta.env.VITE_DEEPSEEK_API_KEY;
// DeepSeek API客户端
const deepseek = new OpenAI({
  baseURL: 'https://api.deepseek.com',
  apiKey: deepseekApiKey,
  dangerouslyAllowBrowser: true,
});

// DeepSeek API请求方法
export async function request(prompt: string = "你好！"): Promise<string> {
  console.log(deepseekApiKey);

  const completion = await deepseek.chat.completions.create({
    messages: [{ role: "system", content: prompt }],
    model: "deepseek-chat",
  });
  return completion.choices[0].message.content;
}


// 文心 API客户端
const wenxin = new OpenAI({
  baseURL: 'https://qianfan.baidubce.com/v2',
  apiKey: 'bce-v3/ALTAK-ODB0RI4JJynUCxCkLv8W6/f6f8c17fad492933da12a475d53d652da047bfb0',
  defaultHeaders: { "appid": "undefined" },
  dangerouslyAllowBrowser: true,
});

export async function wenxinRequest(prompt: string = "你好！") {
  const response = await wenxin.chat.completions.create({
    "model": "ERNIE-4.0-8K",
    "messages": [{ role: "system", content: prompt }],
    "temperature": 0.8,
    "top_p": 0.8,
    "penalty_score": 1,
    "web_search": {
      "enable": true,
      "enable_trace": false
    }
  });

  console.log(response);
}
