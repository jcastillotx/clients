<?php

namespace Tests\Feature;

use App\Http\Livewire\Client\KnowledgeBase as KnowledgeBaseComponent;
use App\Http\Livewire\Client\Messaging as MessagingComponent;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientPortalExtrasTest extends TestCase
{
    use RefreshDatabase;

    public function test_knowledge_base_feedback_creates_row(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->create(['client_id' => $client->id]);

        $cat = KnowledgeBaseCategory::create(['name' => 'FAQ', 'slug' => 'faq']);
        $article = KnowledgeBaseArticle::create([
            'category_id' => $cat->id,
            'title' => 'Test',
            'slug' => 'test',
            'body' => 'Body',
            'is_published' => true,
        ]);

        Livewire::actingAs($user)
            ->test(KnowledgeBaseComponent::class, ['articleId' => $article->id])
            ->set('feedbackComment', 'helped')
            ->call('submitFeedback', true);

        $this->assertDatabaseHas('knowledge_base_feedback', [
            'article_id' => $article->id,
            'user_id' => $user->id,
            'was_helpful' => 1,
        ]);
    }

    public function test_messaging_marks_messages_as_read(): void
    {
        $client = Client::factory()->create();
        $clientUser = User::factory()->create(['client_id' => $client->id]);
        $staff = User::factory()->create(['client_id' => null]);

        $conv = Conversation::create([
            'client_id' => $client->id,
            'title' => 'Support Chat',
            'is_closed' => false,
        ]);
        $conv->participants()->syncWithoutDetaching([
            $clientUser->id => ['role' => 'client'],
            $staff->id => ['role' => 'staff'],
        ]);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $staff->id,
            'body' => 'Hello',
            'type' => 'text',
        ]);

        Livewire::actingAs($clientUser)
            ->test(MessagingComponent::class)
            ->set('conversationId', $conv->id)
            ->call('markVisibleAsRead');

        $this->assertTrue(
            MessageRead::query()
                ->where('message_id', $msg->id)
                ->where('user_id', $clientUser->id)
                ->whereNotNull('read_at')
                ->exists()
        );
    }
}
