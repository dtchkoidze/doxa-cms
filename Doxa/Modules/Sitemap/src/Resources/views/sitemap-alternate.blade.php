<?= '<'.'?'.'xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xsi="https://www.w3.org/2001/XMLSchema-instance"
    xmlns:xhtml="https://www.w3.org/1999/xhtml"
    xsi:schemaLocation="https://www.sitemaps.org/schemas/sitemap/0.9
    https://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
    https://www.w3.org/1999/xhtml
    https://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd">
@foreach($tags as $key => $tags_group)
    <url>
        <loc>{{ $tags_group['main']['loc'] }}</loc>
        <lastmod>{{ $tags_group['main']['lastmod'] }}</lastmod>
        @foreach($tags_group['alternates'] as $tag)
<xhtml:link rel="alternate" hreflang="{{ $tag['locale_code'] }}" href="{{ $tag['loc'] }}"/>
        @endforeach            
    </url>
@endforeach
</urlset>