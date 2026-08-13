<?php

namespace Tests\Unit\RAG;

use App\Services\RAG\Ingestion\Parsers\MarkdownParser;
use App\Services\RAG\Ingestion\Parsers\TextParser;
use Tests\TestCase;

class ParsersTest extends TestCase
{
    public function test_text_parser_supports_txt_extension(): void
    {
        $parser = new TextParser();
        $this->assertTrue($parser->supports('txt'));
        $this->assertTrue($parser->supports('csv'));
        $this->assertFalse($parser->supports('pdf'));
    }

    public function test_markdown_parser_supports_md_extension(): void
    {
        $parser = new MarkdownParser();
        $this->assertTrue($parser->supports('md'));
        $this->assertTrue($parser->supports('markdown'));
        $this->assertFalse($parser->supports('txt'));
    }
}
