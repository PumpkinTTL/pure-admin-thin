from flask import Flask, jsonify, request
from flask_cors import CORS
import sys
import os

# 获取正确的路径
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.append(os.path.join(BASE_DIR, "src", "utils", "python"))

# 导入Python脚本
import openai

app = Flask(__name__)
CORS(app)  # 允许跨域请求，开发环境需要

@app.route('/api/openai', methods=['POST'])
def call_openai():
    # 从请求中获取参数（可选）
    data = request.get_json(silent=True) or {}
    
    # 调用Python脚本中的函数
    result = openai.result()
    
    # 返回结果
    return jsonify({"success": True, "result": result})

if __name__ == '__main__':
    app.run(debug=True, port=5000)
