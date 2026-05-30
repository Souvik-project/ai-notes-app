<?php

namespace App\Services;

use App\Models\Note;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LocalAiNotesService
{
    /** @var array<int, string> */
    private array $stopWords = [
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 'has', 'have', 'in', 'is', 'it',
        'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'were', 'with', 'you', 'your',
    ];

    /**
     * @return array<string, float>
     */
    public function vectorize(string $text): array
    {
        $tokens = $this->tokens($text);
        $counts = array_count_values($tokens);
        $total = max(count($tokens), 1);

        $vector = [];
        foreach ($counts as $token => $count) {
            $vector[$token] = round($count / $total, 6);
        }

        arsort($vector);

        return array_slice($vector, 0, 80, true);
    }

    /**
     * @param  Collection<int, Note>  $notes
     * @return Collection<int, array{note: Note, score: float}>
     */
    public function semanticSearch(Collection $notes, string $query): Collection
    {
        $queryVector = $this->vectorize($query);

        return $notes
            ->map(fn (Note $note): array => [
                'note' => $note,
                'score' => $this->cosineSimilarity(
                    $queryVector,
                    $note->search_vector ?: $this->vectorize($note->title.' '.$note->content)
                ),
            ])
            ->filter(fn (array $result): bool => $result['score'] > 0)
            ->sortByDesc('score')
            ->values();
    }

    public function summarize(Note $note): string
    {
        $sentences = $this->sentences($note->content);

        if (count($sentences) <= 2) {
            return Str::limit(trim($note->content), 420);
        }

        $documentVector = $this->vectorize($note->title.' '.$note->content);
        $ranked = collect($sentences)
            ->map(fn (string $sentence, int $index): array => [
                'index' => $index,
                'sentence' => trim($sentence),
                'score' => $this->cosineSimilarity($documentVector, $this->vectorize($sentence)),
            ])
            ->sortByDesc('score')
            ->take(3)
            ->sortBy('index')
            ->pluck('sentence')
            ->implode(' ');

        return Str::limit($ranked, 650);
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $text): array
    {
        $words = preg_split('/[^a-z0-9]+/i', Str::lower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            $words,
            fn (string $word): bool => strlen($word) > 2 && ! in_array($word, $this->stopWords, true)
        ));
    }

    /**
     * @return array<int, string>
     */
    private function sentences(string $text): array
    {
        return preg_split('/(?<=[.!?])\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @param  array<string, float>  $left
     * @param  array<string, float>  $right
     */
    private function cosineSimilarity(array $left, array $right): float
    {
        $dot = 0.0;
        $leftMagnitude = 0.0;
        $rightMagnitude = 0.0;

        foreach ($left as $token => $weight) {
            $leftMagnitude += $weight ** 2;
            $dot += $weight * ($right[$token] ?? 0);
        }

        foreach ($right as $weight) {
            $rightMagnitude += $weight ** 2;
        }

        if ($leftMagnitude === 0.0 || $rightMagnitude === 0.0) {
            return 0.0;
        }

        return round($dot / (sqrt($leftMagnitude) * sqrt($rightMagnitude)), 4);
    }
}
