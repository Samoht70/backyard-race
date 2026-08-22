<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Event;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * @var array<int, array{title: string, description: string, file: string}>
     */
    private const DOCUMENTS = [
        [
            'title' => 'Règlement de la course',
            'description' => 'Les règles de la nuit, de la première boucle au dernier coureur debout.',
            'file' => 'reglement.pdf',
        ],
        [
            'title' => 'Trace GPX de la boucle',
            'description' => 'La boucle à charger dans ta montre avant le départ.',
            'file' => 'boucle.gpx',
        ],
    ];

    public function run(): void
    {
        $event = Event::currentOrNull();

        if ($event === null || $event->documents()->exists()) {
            return;
        }

        foreach (self::DOCUMENTS as $entry) {
            $document = $event->documents()->create([
                'title' => $entry['title'],
                'description' => $entry['description'],
            ]);

            $document->addMedia(database_path("seeders/files/{$entry['file']}"))
                ->preservingOriginal()
                ->toMediaCollection(Document::FILE_COLLECTION);
        }
    }
}
