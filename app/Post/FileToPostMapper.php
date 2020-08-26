<?php

namespace App\Post;

use App\HeadingRenderer;
use App\QuoteRenderer;
use App\TabbedCodeBlock;
use App\TabbedCodeParser;
use App\TabbedCodeRenderer;
use Illuminate\Support\Facades\Storage;
use League\CommonMark\Block\Element\BlockQuote;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\Block\Element\Heading;
use League\CommonMark\Block\Element\IndentedCode;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Extension\Table\TableExtension;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class FileToPostMapper
{

    public static function map(string $fileName): Post
    {
        $filePath = Storage::disk('posts')
                ->getAdapter()
                ->getPathPrefix().$fileName;

        $postMetaData = YamlFrontMatter::parse(file_get_contents($filePath));
        [
            $date,
            $slug,
        ] = explode('.', $fileName);

        $environment = Environment::createCommonMarkEnvironment();
        $languages = ['html', 'php', 'js', 'shell', 'shell'];
        $environment->addBlockRenderer(FencedCode::class, new FencedCodeRenderer($languages));
        $environment->addBlockRenderer(IndentedCode::class, new IndentedCodeRenderer($languages));
        $environment->addExtension(new TableExtension());

        $commonMarkConverter = new CommonMarkConverter([], $environment);

        return (new Post)->create([
            'path' => $filePath,
            'title' => $postMetaData->matter('title'),
            'categories' => explode(', ', strtolower($postMetaData->matter('categories'))),
            'preview_image' => $postMetaData->matter('preview_image'),
            'preview_image_twitter' => $postMetaData->matter('preview_image_twitter'),
            'content' => $commonMarkConverter->convertToHtml($postMetaData->body()),
            'date' => $date,
            'slug' => $slug,
            'summary' => $postMetaData->matter('summary'),
            'old' => $postMetaData->matter('old') ?? false,
            'hidden' => $postMetaData->matter('hidden'),
        ]);
    }
}
