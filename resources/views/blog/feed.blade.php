<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0">
    <channel>
        <title>{{ setting('company_legal_name', 'پاسارگاد برس') }} — مقالات</title>
        <link>{{ route('blog.index') }}</link>
        <description>مقالات و راهنمای فنی برس و فرچه صنعتی</description>
        <language>fa</language>
        @foreach ($posts as $post)
        <item>
            <title>{{ $post->title }}</title>
            <link>{{ route('blog.show', $post) }}</link>
            <guid>{{ route('blog.show', $post) }}</guid>
            <pubDate>{{ $post->published_at?->toRfc2822String() }}</pubDate>
            <description><![CDATA[{{ $post->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($post->body), 200) }}]]></description>
        </item>
        @endforeach
    </channel>
</rss>
