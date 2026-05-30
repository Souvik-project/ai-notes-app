<?php

namespace Database\Factories;

use App\Services\LocalAiNotesService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);
        $content = fake()->paragraphs(3, true);

        return [
            'title' => $title,
            'content' => $content,
            'tags' => fake()->words(2),
            'summary' => null,
            'search_vector' => app(LocalAiNotesService::class)->vectorize($title.' '.$content),
        ];
    }
}
