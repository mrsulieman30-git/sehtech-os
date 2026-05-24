<?php

namespace App\Observers;

use App\Models\KbArticle;
use App\Services\AgentRagService;

class KbArticleObserver
{
    public function saved(KbArticle $article, AgentRagService $rag): void
    {
        // Only index if published and meant for agents/internal
        if ($article->is_published) {
            $rag->indexDocument(
                $article->id,
                $article->content,
                'master-agent-id'
            );
        }
    }
}
