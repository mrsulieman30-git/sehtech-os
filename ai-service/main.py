from http.server import HTTPServer, BaseHTTPRequestHandler
import json
import sqlite3
import os
import urllib.request
import sys

def load_env(env_path):
    if os.path.exists(env_path):
        try:
            with open(env_path, 'r', encoding='utf-8') as f:
                for line in f:
                    line = line.strip()
                    if not line or line.startswith('#'):
                        continue
                    if '=' in line:
                        key, val = line.split('=', 1)
                        key = key.strip()
                        val = val.strip().strip('"').strip("'")
                        os.environ[key] = val
        except Exception as e:
            print(f"Warning: Failed to load {env_path}: {e}")

# Load .env configurations
base_dir = os.path.dirname(os.path.abspath(__file__))
load_env(os.path.join(base_dir, ".env"))
load_env(os.path.join(base_dir, "..", ".env"))

SECRET_TOKEN = os.environ.get("PYTHON_SERVICE_SECRET", "secret_token_123")

# Initialize SQLite database for lightweight local RAG
db_path = os.path.join(base_dir, "local_rag.db")
conn = sqlite3.connect(db_path, check_same_thread=False)
cursor = conn.cursor()
cursor.execute("""
    CREATE TABLE IF NOT EXISTS sehtech_kb (
        doc_id TEXT PRIMARY KEY,
        content TEXT,
        agent_id TEXT,
        metadata TEXT
    )
""")
conn.commit()

def extract_ai_config(ai_config):
    provider = ai_config.get('provider', 'deepseek')
    
    if provider == 'openai':
        api_key = ai_config.get('openai', {}).get('api_key', '')
        model_name = ai_config.get('openai', {}).get('model', 'gpt-4o')
        base_url = "https://api.openai.com/v1"
    elif provider == 'gemini':
        api_key = ai_config.get('gemini', {}).get('api_key', '')
        model_name = ai_config.get('gemini', {}).get('model', 'gemini-1.5-pro')
        base_url = "https://generativelanguage.googleapis.com/v1beta/openai"
    elif provider == 'claude':
        api_key = ai_config.get('claude', {}).get('api_key', '')
        model_name = ai_config.get('claude', {}).get('model', 'claude-3-opus-20240229')
        base_url = "https://api.anthropic.com/v1"
    else:
        # Default DeepSeek
        api_key = ai_config.get('deepseek', {}).get('api_key', '') or os.environ.get("DEEPSEEK_API_KEY", "")
        model_name = ai_config.get('deepseek', {}).get('model', 'deepseek-chat')
        base_url = os.environ.get("DEEPSEEK_BASE_URL", "https://api.deepseek.com/v1")
        
    return provider, api_key, model_name, base_url

def call_openai_compatible(api_key, model_name, base_url, messages, tools=None):
    url = f"{base_url.rstrip('/')}/chat/completions"
    headers = {
        "Content-Type": "application/json",
        "Authorization": f"Bearer {api_key}"
    }
    
    payload = {
        "model": model_name,
        "messages": messages,
        "temperature": 0.7,
        "max_tokens": 2048
    }
    if tools:
        payload["tools"] = tools
        
    req = urllib.request.Request(
        url,
        data=json.dumps(payload).encode('utf-8'),
        headers=headers,
        method='POST'
    )
    
    with urllib.request.urlopen(req, timeout=120) as response:
        res_body = response.read().decode('utf-8')
        return json.loads(res_body)

class AgentServiceHandler(BaseHTTPRequestHandler):
    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        self.end_headers()

    def send_cors_headers(self, status):
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')

    def do_POST(self):
        auth_header = self.headers.get('Authorization')
        if not auth_header or auth_header != f"Bearer {SECRET_TOKEN}":
            self.send_cors_headers(403)
            self.end_headers()
            self.wfile.write(json.dumps({"detail": "Unauthorized"}).encode('utf-8'))
            return
            
        content_length = int(self.headers.get('Content-Length', 0))
        post_data = self.rfile.read(content_length).decode('utf-8')
        
        try:
            req_data = json.loads(post_data) if post_data else {}
        except Exception as e:
            self.send_cors_headers(400)
            self.end_headers()
            self.wfile.write(json.dumps({"detail": f"Invalid JSON: {str(e)}"}).encode('utf-8'))
            return

        try:
            if self.path == "/api/embed/document":
                self.handle_embed(req_data)
            elif self.path == "/api/rag/search":
                self.handle_rag(req_data)
            elif self.path == "/api/agent/chat":
                self.handle_chat(req_data)
            else:
                self.send_cors_headers(404)
                self.end_headers()
                self.wfile.write(json.dumps({"detail": "Not Found"}).encode('utf-8'))
        except Exception as e:
            self.send_cors_headers(500)
            self.end_headers()
            err_msg = str(e)
            if hasattr(e, 'read'):
                try:
                    err_msg += f" - Response: {e.read().decode('utf-8')}"
                except:
                    pass
            self.wfile.write(json.dumps({"detail": err_msg}).encode('utf-8'))

    def handle_embed(self, req_data):
        doc_id = req_data.get('doc_id')
        content = req_data.get('content')
        agent_id = req_data.get('agent_id')
        metadata = json.dumps(req_data.get('metadata', {}))
        
        cursor.execute(
            "INSERT OR REPLACE INTO sehtech_kb (doc_id, content, agent_id, metadata) VALUES (?, ?, ?, ?)",
            (doc_id, content, agent_id, metadata)
        )
        conn.commit()
        
        self.send_cors_headers(200)
        self.end_headers()
        self.wfile.write(json.dumps({"status": "indexed"}).encode('utf-8'))

    def handle_rag(self, req_data):
        query = req_data.get('query', '')
        agent_id = req_data.get('agent_id', '')
        top_k = req_data.get('top_k', 5)
        
        cursor.execute("SELECT doc_id, content, agent_id, metadata FROM sehtech_kb WHERE agent_id = ?", (agent_id,))
        rows = cursor.fetchall()
        
        documents = []
        for r in rows:
            documents.append({
                "doc_id": r[0],
                "content": r[1],
                "agent_id": r[2],
                "metadata": json.loads(r[3]) if r[3] else {}
            })
            
        # Scoring logic using case-insensitive keyword and exact phrase matching
        query_words = [w.lower() for w in query.split() if len(w) > 2]
        scored_docs = []
        for doc in documents:
            doc_content = doc['content'].lower()
            score = 0
            for word in query_words:
                score += doc_content.count(word)
            if query.lower() in doc_content:
                score += len(query_words) * 5 + 5
            scored_docs.append((score, doc))
            
        scored_docs.sort(key=lambda x: x[0], reverse=True)
        top_docs = [item[1] for item in scored_docs[:top_k]]
        
        chunks = []
        for doc in top_docs:
            chunks.append({
                "content": doc['content'],
                "metadata": doc['metadata']
            })
            
        self.send_cors_headers(200)
        self.end_headers()
        self.wfile.write(json.dumps({"chunks": chunks}).encode('utf-8'))

    def handle_chat(self, req_data):
        agent_id = req_data.get('agent_id', '')
        message = req_data.get('message', '')
        context_chunks = req_data.get('context_chunks', [])
        conversation_history = req_data.get('conversation_history', [])
        system_prompt = req_data.get('system_prompt', '')
        sub_agents = req_data.get('sub_agents', [])
        ai_config = req_data.get('ai_config', {})
        
        provider, api_key, model_name, base_url = extract_ai_config(ai_config)
        
        messages = [{"role": "system", "content": system_prompt}]
        
        if context_chunks:
            context_str = "\n\n".join(context_chunks)
            messages.append({
                "role": "system",
                "content": f"Relevant Knowledge Base Context:\n{context_str}"
            })
            
        for msg in conversation_history:
            messages.append(msg)
            
        messages.append({"role": "user", "content": message})
        
        tools = [
            {
                "type": "function",
                "function": {
                    "name": "execute_system_action",
                    "description": "Execute a specific system action to modify or delete data. Use this ONLY if explicitly commanded by the user.",
                    "parameters": {
                        "type": "object",
                        "properties": {
                            "action": {
                                "type": "string",
                                "description": "The action to execute (e.g., 'create_employee', 'terminate_employee', 'create_dev_task')",
                                "enum": ["create_employee", "terminate_employee", "create_dev_task"]
                            },
                            "payload": {
                                "type": "string",
                                "description": "JSON string containing the payload for the action. For create_employee: {name, email, department, job_title, salary}. For terminate_employee: {email}. For create_dev_task: {target_project (ID or name), target_folder (ID or name, optional, auto-created if missing), title, description, priority, status}."
                            }
                        },
                        "required": ["action", "payload"]
                    }
                }
            }
        ]
        
        if agent_id == 'master' and sub_agents:
            agent_descriptions = "\n".join([f"- {a['slug']}: {a['description']}" for a in sub_agents])
            tools.append(
                {
                    "type": "function",
                    "function": {
                        "name": "delegate_task_to_agent",
                        "description": f"Delegate a specialized task to a departmental sub-agent. Available agents:\n{agent_descriptions}",
                        "parameters": {
                            "type": "object",
                            "properties": {
                                "target_agent_slug": {
                                    "type": "string",
                                    "description": "The slug of the agent to delegate to (e.g., hr-agent, finance-agent)"
                                },
                                "task_description": {
                                    "type": "string",
                                    "description": "The specific task or question to ask the sub-agent"
                                }
                            },
                            "required": ["target_agent_slug", "task_description"]
                        }
                    }
                }
            )
            
        # Call LLM & process tool loop
        resp = call_openai_compatible(api_key, model_name, base_url, messages, tools)
        choice = resp['choices'][0]
        resp_msg = choice['message']
        
        while resp_msg.get('tool_calls'):
            messages.append(resp_msg)
            
            for tc in resp_msg['tool_calls']:
                if tc['function']['name'] == 'delegate_task_to_agent':
                    args = json.loads(tc['function']['arguments'])
                    target_slug = args.get('target_agent_slug')
                    task = args.get('task_description')
                    
                    sub_agent = next((a for a in sub_agents if a['slug'] == target_slug), None)
                    
                    if sub_agent:
                        sub_messages = [
                            {"role": "system", "content": sub_agent['system_prompt']},
                            {"role": "user", "content": task}
                        ]
                        sub_resp = call_openai_compatible(api_key, model_name, base_url, sub_messages)
                        result_content = sub_resp['choices'][0]['message']['content']
                    else:
                        result_content = f"Error: Agent '{target_slug}' not found."
                        
                    messages.append({
                        "role": "tool",
                        "tool_call_id": tc['id'],
                        "name": tc['function']['name'],
                        "content": result_content
                    })
                elif tc['function']['name'] == 'execute_system_action':
                    args = json.loads(tc['function']['arguments'])
                    action = args.get('action')
                    payload = args.get('payload')
                    laravel_url = req_data.get('laravel_url', 'http://127.0.0.1:8000')
                    
                    try:
                        req_url = f"{laravel_url.rstrip('/')}/api/internal/agent-action"
                        req_headers = {
                            "Content-Type": "application/json",
                            "Authorization": f"Bearer {SECRET_TOKEN}"
                        }
                        req_payload = {
                            "action": action,
                            "payload": payload,
                            "user_id": "ai_agent"
                        }
                        
                        agent_req = urllib.request.Request(
                            req_url,
                            data=json.dumps(req_payload).encode('utf-8'),
                            headers=req_headers,
                            method='POST'
                        )
                        
                        with urllib.request.urlopen(agent_req, timeout=30) as agent_resp:
                            result_content = agent_resp.read().decode('utf-8')
                    except Exception as e:
                        err_msg = str(e)
                        if hasattr(e, 'read'):
                            try:
                                err_msg += f" - Response: {e.read().decode('utf-8')}"
                            except:
                                pass
                        result_content = f"Error executing action: {err_msg}"
                        
                    messages.append({
                        "role": "tool",
                        "tool_call_id": tc['id'],
                        "name": tc['function']['name'],
                        "content": result_content
                    })
            
            resp = call_openai_compatible(api_key, model_name, base_url, messages, tools)
            choice = resp['choices'][0]
            resp_msg = choice['message']
            
        self.send_cors_headers(200)
        self.end_headers()
        self.wfile.write(json.dumps({
            "response_text": resp_msg.get('content'),
            "tokens_used": resp.get('usage', {}).get('total_tokens', 0),
            "model_used": model_name
        }).encode('utf-8'))

def run(port=8001):
    server_address = ('127.0.0.1', port)
    httpd = HTTPServer(server_address, AgentServiceHandler)
    print(f"Starting zero-dependency Universal AI backend on http://127.0.0.1:{port}...")
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nStopping AI backend...")
        httpd.server_close()

if __name__ == '__main__':
    port_arg = 8001
    if len(sys.argv) > 1:
        try:
            port_arg = int(sys.argv[1])
        except ValueError:
            pass
    run(port_arg)
