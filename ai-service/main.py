from fastapi import FastAPI, HTTPException, Header
from pydantic import BaseModel
import os
import json
import chromadb
from chromadb.utils import embedding_functions
from typing import List, Optional
from openai import AsyncOpenAI

app = FastAPI()

# Initialize ChromaDB for RAG
chroma_client = chromadb.PersistentClient(path="./chroma_db")
embedding_func = embedding_functions.DefaultEmbeddingFunction()
collection = chroma_client.get_or_create_collection(name="sehtech_kb", embedding_function=embedding_func)

SECRET_TOKEN = os.getenv("PYTHON_SERVICE_SECRET", "secret_token_123")

class ChatRequest(BaseModel):
    agent_id: str
    message: str
    context_chunks: List[str]
    conversation_history: List[dict]
    system_prompt: str
    sub_agents: Optional[List[dict]] = []
    ai_config: Optional[dict] = {}

class RagSearchRequest(BaseModel):
    query: str
    agent_id: str
    top_k: int = 5

def get_ai_client(config: dict):
    provider = config.get('provider', 'deepseek')
    
    if provider == 'openai':
        return AsyncOpenAI(api_key=config.get('openai', {}).get('api_key', '')), config.get('openai', {}).get('model', 'gpt-4o')
    elif provider == 'gemini':
        # Placeholder for Gemini (requires google-generativeai or OpenAI wrapper)
        return AsyncOpenAI(api_key=config.get('gemini', {}).get('api_key', ''), base_url="https://generativelanguage.googleapis.com/v1beta/openai/"), config.get('gemini', {}).get('model', 'gemini-1.5-pro')
    elif provider == 'claude':
        # Placeholder for Claude (requires anthropic SDK, but we use OpenAI wrapper if available or fallback)
        return AsyncOpenAI(api_key=config.get('claude', {}).get('api_key', ''), base_url="https://api.anthropic.com/v1/messages"), config.get('claude', {}).get('model', 'claude-3-opus-20240229')
    else:
        # Default DeepSeek
        ds_key = config.get('deepseek', {}).get('api_key', '') or os.getenv("DEEPSEEK_API_KEY", "")
        ds_model = config.get('deepseek', {}).get('model', 'deepseek-chat')
        return AsyncOpenAI(api_key=ds_key, base_url="https://api.deepseek.com"), ds_model

@app.post("/api/agent/chat")
async def agent_chat(request: ChatRequest, authorization: str = Header(None)):
    if authorization != f"Bearer {SECRET_TOKEN}":
        raise HTTPException(status_code=403, detail="Unauthorized")
    
    client, model_name = get_ai_client(request.ai_config)
    
    messages = [{"role": "system", "content": request.system_prompt}]
    
    if request.context_chunks:
        context_str = "\n\n".join(request.context_chunks)
        messages.append({
            "role": "system",
            "content": f"Relevant Knowledge Base Context:\n{context_str}"
        })
        
    for msg in request.conversation_history:
        messages.append(msg)
        
    messages.append({"role": "user", "content": request.message})

    # Prepare tools if this is the master agent
    tools = None
    if request.agent_id == 'master' and request.sub_agents:
        agent_descriptions = "\n".join([f"- {a['slug']}: {a['description']}" for a in request.sub_agents])
        tools = [
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
        ]

    try:
        # Initial call
        response = await client.chat.completions.create(
            model=model_name,
            messages=messages,
            tools=tools,
            max_tokens=2048,
            temperature=0.7
        )
        
        response_msg = response.choices[0].message
        
        # Tool execution loop
        while response_msg.tool_calls:
            messages.append(response_msg) # Append the assistant's tool call message
            
            for tool_call in response_msg.tool_calls:
                if tool_call.function.name == "delegate_task_to_agent":
                    args = json.loads(tool_call.function.arguments)
                    target_slug = args.get("target_agent_slug")
                    task = args.get("task_description")
                    
                    # Find the sub-agent system prompt
                    sub_agent = next((a for a in request.sub_agents if a['slug'] == target_slug), None)
                    
                    if sub_agent:
                        # Execute sub-agent internally
                        sub_messages = [
                            {"role": "system", "content": sub_agent['system_prompt']},
                            {"role": "user", "content": task}
                        ]
                        sub_resp = await client.chat.completions.create(
                            model=model_name,
                            messages=sub_messages,
                            max_tokens=1024,
                            temperature=0.7
                        )
                        result_content = sub_resp.choices[0].message.content
                    else:
                        result_content = f"Error: Agent '{target_slug}' not found."
                        
                    # Append tool response
                    messages.append({
                        "role": "tool",
                        "tool_call_id": tool_call.id,
                        "name": tool_call.function.name,
                        "content": result_content
                    })
                    
            # Call again with tool responses
            response = await client.chat.completions.create(
                model=model_name,
                messages=messages,
                tools=tools,
                max_tokens=2048,
                temperature=0.7
            )
            response_msg = response.choices[0].message
        
        return {
            "response_text": response_msg.content,
            "tokens_used": response.usage.total_tokens if response.usage else 0,
            "model_used": model_name
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/rag/search")
async def rag_search(request: RagSearchRequest, authorization: str = Header(None)):
    if authorization != f"Bearer {SECRET_TOKEN}":
        raise HTTPException(status_code=403, detail="Unauthorized")

    results = collection.query(
        query_texts=[request.query],
        n_results=request.top_k,
        where={"agent_id": request.agent_id} # Scope search to agent's department
    )
    
    # Format chunks for Laravel
    chunks = []
    if results and results.get('documents') and len(results['documents']) > 0:
        for i in range(len(results['documents'][0])):
            chunks.append({
                "content": results['documents'][0][i],
                "metadata": results['metadatas'][0][i] if results.get('metadatas') and len(results['metadatas']) > 0 else {}
            })
        
    return {"chunks": chunks}

@app.post("/api/embed/document")
async def embed_document(data: dict, authorization: str = Header(None)):
    if authorization != f"Bearer {SECRET_TOKEN}":
        raise HTTPException(status_code=403, detail="Unauthorized")

    # data: { doc_id, content, agent_id, metadata }
    collection.add(
        documents=[data['content']],
        metadatas=[{"agent_id": data['agent_id'], "doc_id": data['doc_id']}],
        ids=[data['doc_id']]
    )
    return {"status": "indexed"}
