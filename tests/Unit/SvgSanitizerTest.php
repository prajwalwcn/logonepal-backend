<?php

namespace Tests\Unit;

use App\Services\Logo\SvgSanitizer;
use PHPUnit\Framework\TestCase;

class SvgSanitizerTest extends TestCase
{
    public function test_it_strips_scripts_and_event_handlers(): void
    {
        $sanitizer = new SvgSanitizer();

        $dirty = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">'
            .'<script>alert("xss")</script>'
            .'<path d="M0 0h10v10H0z" onclick="steal()" fill="#000"/>'
            .'</svg>';

        $clean = $sanitizer->sanitize($dirty);

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onload', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringContainsString('<path', $clean);
    }

    public function test_it_keeps_text_content(): void
    {
        $sanitizer = new SvgSanitizer();

        $clean = $sanitizer->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><text font-family="Poppins" font-size="20">Nexora</text></svg>'
        );

        $this->assertStringContainsString('Nexora', $clean);
    }

    public function test_it_rejects_external_references(): void
    {
        $sanitizer = new SvgSanitizer();

        $clean = $sanitizer->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g"/></defs>'
            .'<image href="javascript:alert(1)"/><use xlink:href="http://evil.test/x.svg"/></svg>'
        );

        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('evil.test', $clean);
        $this->assertStringNotContainsString('<image', $clean);
    }

    public function test_it_returns_empty_svg_for_invalid_markup(): void
    {
        $sanitizer = new SvgSanitizer();

        $clean = $sanitizer->sanitize('<div>not an svg</div>');

        $this->assertStringContainsString('<svg', $clean);
        $this->assertStringNotContainsString('not an svg', $clean);
    }
}
