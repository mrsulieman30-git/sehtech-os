<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AgentRagService
{
    public function search(string $query, string $agentId, int $topK = 5): array
    {
        $response = Http::withToken(config('services.python.secret'))
            ->post(config('services.python.url') . '/api/rag/search', [
                'query' => $query,
                'agent_id' => $agentId,
                'top_k' => $topK
            ]);

        return $response->successful() ? $response->json()['chunks'] : [];
    }

    public function indexDocument(string $docId, string $content, string $agentId)
    {
        Http::withToken(config('services.python.secret'))
            ->post(config('services.python.url') . '/api/embed/document', [
                'doc_id' => $docId,
                'content' => $content,
                'agent_id' => $agentId
            ]);
    }
}
