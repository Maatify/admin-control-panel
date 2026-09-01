<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Services;

use Maatify\Storage\DTO\StoredFile;
use Maatify\Storage\Services\FileUploadService;
use Maatify\Storage\Services\AudioUploadService;
use Maatify\Storage\Tests\Unit\StorageModuleTestCase;

/**
 * Tests for AudioUploadService
 *
 * Tests include validation of:
 * - Default allowed extensions (mp3, wav, ogg)
 * - Custom size limits
 * - Semantic basename generation (business context in filenames)
 */
final class AudioUploadServiceTest extends StorageModuleTestCase
{
    private AudioUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $storageAdapter = $this->createMock(\Maatify\Storage\Contracts\StorageAdapterInterface::class);
        $storageAdapter
            ->method('store')
            ->willReturnCallback(function ($file, $path) {
                return new StoredFile(
                    path: $path,
                    url: $path,  // For testing, path and url are the same
                );
            });

        $fileUploadService = new FileUploadService($storageAdapter);
        $this->service = new AudioUploadService($fileUploadService);
    }

    public function testUploadsAudioWithDefaults(): void
    {
        $file = $this->createMockMp3File('intro.mp3');

        $path = $this->service->upload($file, 'podcasts');

        $this->assertStringStartsWith('podcasts/', $path->path);
        $this->assertStringEndsWith('.mp3', $path->path);
    }

    public function testUploadsAudioWithCustomBaseName(): void
    {
        $file = $this->createMockUploadedFile('audio content', 'audio.mp3');

        $path = $this->service->upload(
            $file,
            'music',
            customBaseName: 'dua-001'
        );

        $this->assertStringStartsWith('music/dua-001-', $path->path);
        $this->assertStringEndsWith('.mp3', $path->path);
    }

    public function testSemanticNamingForPodcasts(): void
    {
        $file = $this->createMockMp3File('episode.mp3');

        $path = $this->service->upload(
            $file,
            'podcasts',
            customBaseName: 'episode-15-understanding-quran'
        );

        $this->assertStringContainsString('episode-15-understanding-quran-', $path->path);
    }

    public function testSemanticNamingForMusic(): void
    {
        $file = $this->createMockMp3File('song.mp3');

        $path = $this->service->upload(
            $file,
            'music',
            customBaseName: 'nasheed-beautiful-morning'
        );

        $this->assertStringContainsString('nasheed-beautiful-morning-', $path->path);
    }

    public function testUploadsMp3Audio(): void
    {
        $file = $this->createMockMp3File('audio.mp3');

        $path = $this->service->upload(
            $file,
            'audio',
            allowedExtensions: ['mp3']
        );

        $this->assertStringEndsWith('.mp3', $path->path);
    }

    public function testUploadsMultipleFormats(): void
    {
        $fileCreators = [
            'mp3' => fn() => $this->createMockMp3File('audio.mp3'),
            'wav' => fn() => $this->createMockWavFile('audio.wav'),
            'ogg' => fn() => $this->createMockOggFile('audio.ogg'),
        ];

        foreach ($fileCreators as $format => $creator) {
            $file = $creator();

            $path = $this->service->upload(
                $file,
                'audio'
            );

            $this->assertStringEndsWith(".{$format}", $path->path);
        }
    }

    public function testUploadsAudioWithCustomSizeLimit(): void
    {
        // Create audio file with MP3 magic bytes
        $mp3Magic = "\xFF\xFB";
        $content = str_repeat('x', 50 * 1024); // 50KB (simulating audio file)
        $largeAudio = $mp3Magic . $content;
        $file = $this->createMockUploadedFile($largeAudio, 'audio.mp3');

        $path = $this->service->upload(
            $file,
            'podcasts',
            maxSizeBytes: 100 * 1024 * 1024 // 100MB
        );

        $this->assertNotEmpty($path->path);
    }

    public function testRejectsAudioExceedingSizeLimit(): void
    {
        $this->expectException(\Maatify\Storage\Exception\InvalidFileException::class);

        // Create MP3 content with valid magic bytes so MIME validator passes
        // Then test that size validator rejects it for exceeding limit
        $mp3Content = "\xFF\xFB" . str_repeat('x', 2 * 1024 * 1024); // 2MB with MP3 header
        $file = $this->createMockUploadedFile($mp3Content, 'audio.mp3');

        $this->service->upload(
            $file,
            'audio',
            maxSizeBytes: 1024 * 1024 // 1MB limit
        );
    }

    public function testRejectsDisallowedFormat(): void
    {
        $this->expectException(\Maatify\Storage\Exception\InvalidFileException::class);

        $file = $this->createMockUploadedFile('content', 'script.exe');

        $this->service->upload(
            $file,
            'audio',
            allowedExtensions: ['mp3', 'wav']
        );
    }

    public function testSanitizesBasenameCaseSensitivity(): void
    {
        $file = $this->createMockMp3File('MyAudio.MP3');

        $path = $this->service->upload(
            $file,
            'audio',
            customBaseName: 'PodcastEpisode'
        );

        $this->assertStringContainsString('podcastepisode-', $path->path);
        $this->assertStringEndsWith('.mp3', $path->path);
    }

    public function testGeneratesUniqueFilenamesEvenWithSameBaseName(): void
    {
        $file = $this->createMockMp3File('audio.mp3');

        $path1 = $this->service->upload(
            $file,
            'audio',
            customBaseName: 'episode'
        );

        $path2 = $this->service->upload(
            $file,
            'audio',
            customBaseName: 'episode'
        );

        // Different paths due to random suffix
        $this->assertNotEquals($path1->path, $path2->path);

        // But same prefix
        $this->assertTrue(
            strpos($path1->path, 'episode-') === 6 &&
            strpos($path2->path, 'episode-') === 6
        );
    }

    public function testHandlesSpecialCharactersInBaseName(): void
    {
        $file = $this->createMockMp3File('audio.mp3');

        $path = $this->service->upload(
            $file,
            'audio',
            customBaseName: 'Podcast#2024@Advanced!Episode&Pro'
        );

        // Should be sanitized
        $this->assertStringContainsString('podcast2024advancedepisodepro-', $path->path);
    }

    public function testNullCustomBaseNameAutoGenerates(): void
    {
        $file = $this->createMockMp3File('my-favorite-podcast.mp3');

        $path = $this->service->upload(
            $file,
            'audio',
            customBaseName: null
        );

        // Should auto-generate from filename
        $this->assertStringContainsString('my-favorite-podcast-', $path->path);
    }

    public function testLargeAudioFilesWithSemanticNaming(): void
    {
        // Create large MP3 for testing (75MB in test is just 75KB for speed)
        $mp3Magic = "\xFF\xFB";
        $content = str_repeat('x', 75 * 1024);
        $largeAudio = $mp3Magic . $content;
        $file = $this->createMockUploadedFile($largeAudio, 'large.mp3');

        $path = $this->service->upload(
            $file,
            'podcasts',
            maxSizeBytes: 100 * 1024 * 1024, // 100MB
            customBaseName: 'quran-recitation-juz-1'
        );

        $this->assertStringContainsString('quran-recitation-juz-1-', $path->path);
        $this->assertStringEndsWith('.mp3', $path->path);
    }

    public function testUploadsAllAudioFormatsWithSemanticNaming(): void
    {
        $fileCreators = [
            'mp3' => fn() => $this->createMockMp3File('audio.mp3'),
            'wav' => fn() => $this->createMockWavFile('audio.wav'),
            'ogg' => fn() => $this->createMockOggFile('audio.ogg'),
        ];

        foreach ($fileCreators as $format => $creator) {
            $file = $creator();

            $path = $this->service->upload(
                $file,
                'audio',
                customBaseName: "dua-{$format}"
            );

            $this->assertStringContainsString("dua-{$format}-", $path->path);
            $this->assertStringEndsWith(".{$format}", $path->path);
        }
    }

    public function testAllowedExtensionsRestrictsMimeTypes(): void
    {
        // This test ensures semantic correctness: allowedExtensions: ['mp3'] should
        // ONLY accept audio/mpeg, not audio/wav or audio/ogg with mp3 extension
        $this->expectException(\Maatify\Storage\Exception\InvalidFileException::class);

        // WAV content with MP3 extension - content > 32 bytes so injectMagicBytesIfNeeded won't touch it
        $wavContent = "RIFF\x00\x00\x00\x00WAVE" . str_repeat("\x00", 100);
        $file = $this->createMockUploadedFile($wavContent, 'audio.mp3');

        // This should reject because content is WAV (audio/wav) but only mp3 (audio/mpeg) allowed
        $this->service->upload(
            $file,
            'audio',
            allowedExtensions: ['mp3']  // Only MP3, not WAV
        );
    }

    public function testRejectsUnsupportedCustomExtensionCleanly(): void
    {
        // Test that unsupported extensions throw proper InvalidFileException
        // not PHP warnings/errors
        $this->expectException(\Maatify\Storage\Exception\InvalidFileException::class);

        $file = $this->createMockMp3File('audio.mp3');

        $this->service->upload(
            $file,
            'audio',
            allowedExtensions: ['aac']  // AAC not supported
        );
    }

    public function testNormalizesCustomAllowedExtensions(): void
    {
        // Test that extensions are normalized: uppercase → lowercase, remove leading dot
        $file = $this->createMockMp3File('audio.MP3');

        $path = $this->service->upload(
            $file,
            'audio',
            allowedExtensions: ['.MP3']  // Uppercase with dot
        );

        // Should successfully upload (normalized to 'mp3') and preserve original extension
        $this->assertStringEndsWith('.mp3', $path->path);
        $this->assertStringContainsString('audio/', $path->path);
    }

    public function testAcceptsNormalizedVariantsOfExtensions(): void
    {
        // All these should be equivalent after normalization
        $variants = [
            'mp3',      // lowercase
            'MP3',      // uppercase
            '.mp3',     // with dot
            '.MP3',     // uppercase with dot
        ];

        foreach ($variants as $variant) {
            $file = $this->createMockMp3File('test.mp3');

            $path = $this->service->upload(
                $file,
                'audio',
                allowedExtensions: [$variant]
            );

            $this->assertStringEndsWith('.mp3', $path->path);
        }
    }
}
