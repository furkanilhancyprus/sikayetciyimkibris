<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($staticUrls as $url)
        <url>
            <loc>{{ $url['loc'] }}</loc>
            <lastmod>{{ optional($url['lastmod'])->toAtomString() }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach

    @foreach ($entities as $entity)
        <url>
            <loc>{{ route('entities.show', $entity) }}</loc>
            <lastmod>{{ $entity->updated_at->toAtomString() }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>0.7</priority>
        </url>
    @endforeach

    @foreach ($reports as $report)
        <url>
            <loc>{{ route('reports.show', $report) }}</loc>
            <lastmod>{{ ($report->published_at ?: $report->updated_at)->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.6</priority>
        </url>
    @endforeach
</urlset>
