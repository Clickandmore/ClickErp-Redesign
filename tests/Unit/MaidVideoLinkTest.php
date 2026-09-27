<?php

namespace Tests\Unit;

use App\Support\MaidVideoLink;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MaidVideoLinkTest extends TestCase
{
    public function test_links_and_embeds_are_normalized_for_both_forms(): void
    {
        foreach ([
            [' https://example.com/video.mp4 ', 'https://example.com/video.mp4'],
            ['<iframe src="https://example.com/embed?a=1&amp;b=2"></iframe>', 'https://example.com/embed?a=1&b=2'],
            ['https://drive.google.com/file/d/abc/view?usp=sharing', 'https://drive.google.com/file/d/abc/preview'],
            ['https://drive.google.com/open?id=abc', 'https://drive.google.com/file/d/abc/preview'],
            ['', null],
        ] as [$input, $expected]) {
            $actual = MaidVideoLink::normalize($input);
            $this->assertSame($expected, $actual);
            $this->assertFalse(validator(['video_link' => $actual], ['video_link' => 'nullable|url|max:2000'])->fails());
        }
        $this->assertTrue(validator(['video_link' => MaidVideoLink::normalize('not a link')], ['video_link' => 'nullable|url|max:2000'])->fails());
    }

    public function test_video_validation_accepts_supported_files_and_rejects_oversized_or_invalid_files(): void
    {
        $rules = ['video' => 'nullable|file|mimes:mp4,mov,avi,wmv,webm,mkv,m4v|max:102400'];
        $this->assertFalse(validator(['video' => UploadedFile::fake()->create('video.mp4', 102400, 'video/mp4')], $rules)->fails());
        $this->assertTrue(validator(['video' => UploadedFile::fake()->create('video.mp4', 102401, 'video/mp4')], $rules)->fails());
        $this->assertTrue(validator(['video' => UploadedFile::fake()->create('video.pdf', 1, 'application/pdf')], $rules)->fails());
    }
}
