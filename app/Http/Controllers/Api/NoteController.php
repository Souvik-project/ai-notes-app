<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Services\LocalAiNotesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(private readonly LocalAiNotesService $ai)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:255'],
        ]);

        $limit = (int) ($validated['limit'] ?? 10);
        $query = Note::query()->latest();

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $notes = $query->paginate($limit);

        return $this->success($notes->items(), [
            'pagination' => [
                'current_page' => $notes->currentPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
                'last_page' => $notes->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedNote($request);
        $validated['search_vector'] = $this->ai->vectorize($validated['title'].' '.$validated['content']);

        $note = Note::create($validated);

        return $this->success($note, status: 201, message: 'Note created.');
    }

    public function show(Note $note): JsonResponse
    {
        return $this->success($note);
    }

    public function update(Request $request, Note $note): JsonResponse
    {
        $validated = $this->validatedNote($request, updating: true);
        $nextTitle = $validated['title'] ?? $note->title;
        $nextContent = $validated['content'] ?? $note->content;

        $validated['search_vector'] = $this->ai->vectorize($nextTitle.' '.$nextContent);
        $note->update($validated);

        return $this->success($note->fresh(), message: 'Note updated.');
    }

    public function destroy(Note $note): JsonResponse
    {
        $note->delete();

        return $this->success(null, message: 'Note deleted.');
    }

    public function semanticSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $results = $this->ai
            ->semanticSearch(Note::query()->latest()->get(), $validated['q'])
            ->take((int) ($validated['limit'] ?? 10))
            ->map(fn (array $result): array => [
                'score' => $result['score'],
                'note' => $result['note'],
            ])
            ->values();

        return $this->success($results);
    }

    public function summary(Note $note): JsonResponse
    {
        $summary = $this->ai->summarize($note);
        $note->update(['summary' => $summary]);

        return $this->success([
            'id' => $note->id,
            'summary' => $summary,
        ], message: 'Summary generated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedNote(Request $request, bool $updating = false): array
    {
        $presence = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$presence, 'string', 'max:160'],
            'content' => [$presence, 'string', 'min:5'],
            'tags' => ['sometimes', 'array', 'max:12'],
            'tags.*' => ['string', 'max:32'],
        ]);
    }

    private function success(mixed $data, array $meta = [], int $status = 200, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => (object) $meta,
        ], $status);
    }
}
