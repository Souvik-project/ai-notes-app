<?php

namespace Tests\Feature;

use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_note_can_be_created_and_fetched(): void
    {
        $response = $this->postJson('/api/notes', [
            'title' => 'Release plan',
            'content' => 'Ship the notes API after validation and search tests pass.',
            'tags' => ['work', 'release'],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Release plan');

        $this->getJson('/api/notes/'.$response->json('data.id'))
            ->assertOk()
            ->assertJsonPath('data.tags.0', 'work');
    }

    public function test_validation_errors_return_422(): void
    {
        $this->postJson('/api/notes', [
            'title' => '',
            'content' => 'bad',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'errors', 'data', 'meta']);
    }

    public function test_note_can_be_updated(): void
    {
        $note = Note::factory()->create([
            'title' => 'Old title',
            'content' => 'Original content for the note.',
        ]);

        $this->putJson('/api/notes/'.$note->id, [
            'title' => 'Updated title',
            'content' => 'Updated content with enough detail.',
            'tags' => ['updated'],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Note updated.')
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.tags.0', 'updated');

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'title' => 'Updated title',
        ]);
    }

    public function test_note_can_be_deleted(): void
    {
        $note = Note::factory()->create();

        $this->deleteJson('/api/notes/'.$note->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Note deleted.')
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('notes', [
            'id' => $note->id,
        ]);
    }

    public function test_missing_note_returns_clean_404_json(): void
    {
        $this->getJson('/api/notes/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Resource not found.')
            ->assertJsonStructure(['message', 'errors', 'data', 'meta']);
    }

    public function test_notes_are_paginated(): void
    {
        Note::factory()->count(12)->create();

        $this->getJson('/api/notes?page=2&limit=5')
            ->assertOk()
            ->assertJsonPath('meta.pagination.current_page', 2)
            ->assertJsonPath('meta.pagination.per_page', 5)
            ->assertJsonCount(5, 'data');
    }

    public function test_semantic_search_returns_relevant_notes(): void
    {
        $this->postJson('/api/notes', [
            'title' => 'Travel checklist',
            'content' => 'Passport tickets hotel booking and airport transfer details.',
        ]);

        $this->postJson('/api/notes', [
            'title' => 'Database indexes',
            'content' => 'Optimize query performance with indexes and explain plans.',
        ]);

        $this->getJson('/api/notes/search?q=airport hotel')
            ->assertOk()
            ->assertJsonPath('data.0.note.title', 'Travel checklist');
    }

    public function test_summary_endpoint_generates_summary(): void
    {
        $note = Note::create([
            'title' => 'Architecture',
            'content' => 'The API validates all input. The service builds semantic vectors. The frontend consumes JSON endpoints. Tests confirm the most important workflows.',
            'search_vector' => [],
        ]);

        $this->postJson("/api/notes/{$note->id}/summary")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'summary']]);

        $this->assertNotEmpty($note->fresh()->summary);
    }
}
