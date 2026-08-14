<?php
/**
 * SEO helpers: titles, robots, JSON-LD, and marketing tags.
 */

function seoBrand(): string
{
    return 'Short Circuit Company';
}

function seoFullTitle(string $pageTitle): string
{
    $brand = seoBrand();
    $pageTitle = trim($pageTitle);
    if ($pageTitle === '' || stripos($pageTitle, 'Lighting Technical Data') === 0) {
        return 'Lighting Technical Data & Standards Blog | ' . $brand;
    }
    if (stripos($pageTitle, $brand) !== false) {
        return $pageTitle;
    }
    return $pageTitle . ' | Lighting Blog | ' . $brand;
}

function seoDefaultDescription(): string
{
    return 'Lighting technical data and standards from Short Circuit Company: CRI, CCT, lux, UGR, EN 12464-1, WELL, and practical design guides.';
}

function seoCfg(string $key): string
{
    if (defined($key)) {
        return (string)constant($key);
    }
    $v = $_ENV[$key] ?? getenv($key);
    return is_string($v) ? trim($v) : '';
}

function seoMarketingHeadHtml(): string
{
    $html = '';
    $verifyGoogle = seoCfg('GOOGLE_SITE_VERIFICATION');
    $verifyBing = seoCfg('BING_SITE_VERIFICATION');
    $verifyFb = seoCfg('FACEBOOK_DOMAIN_VERIFICATION');
    if ($verifyGoogle !== '') {
        $html .= '<meta name="google-site-verification" content="' . e($verifyGoogle) . '">' . "\n";
    }
    if ($verifyBing !== '') {
        $html .= '<meta name="msvalidate.01" content="' . e($verifyBing) . '">' . "\n";
    }
    if ($verifyFb !== '') {
        $html .= '<meta name="facebook-domain-verification" content="' . e($verifyFb) . '">' . "\n";
    }

    $gtm = seoCfg('GTM_ID');
    $ga4 = seoCfg('GA4_MEASUREMENT_ID');
    $pixel = seoCfg('FB_PIXEL_ID');

    if ($gtm !== '' || $ga4 !== '') {
        $html .= '<link rel="preconnect" href="https://www.googletagmanager.com">' . "\n";
        $html .= '<link rel="dns-prefetch" href="https://www.googletagmanager.com">' . "\n";
    }
    if ($pixel !== '') {
        $html .= '<link rel="preconnect" href="https://connect.facebook.net">' . "\n";
        $html .= '<link rel="dns-prefetch" href="https://connect.facebook.net">' . "\n";
    }

    if ($gtm !== '') {
        $html .= '<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':'
            . 'new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],'
            . 'j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src='
            . '\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);'
            . '})(window,document,\'script\',\'dataLayer\',\'' . e($gtm) . '\');</script>' . "\n";
    } elseif ($ga4 !== '') {
        $html .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($ga4) . '"></script>' . "\n"
            . '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
            . 'gtag(\'js\',new Date());gtag(\'config\',\'' . e($ga4) . '\');</script>' . "\n";
    }

    if ($pixel !== '') {
        $html .= '<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?'
            . 'n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;'
            . 'n.push=n;n.loaded=!0;n.version=\'2.0\';n.queue=[];t=b.createElement(e);t.async=!0;'
            . 't.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'
            . '\'script\',\'https://connect.facebook.net/en_US/fbevents.js\');fbq(\'init\',\'' . e($pixel) . '\');'
            . 'fbq(\'track\',\'PageView\');</script>' . "\n";
    }

    return $html;
}

function seoMarketingBodyHtml(): string
{
    $html = '';
    $gtm = seoCfg('GTM_ID');
    $pixel = seoCfg('FB_PIXEL_ID');
    if ($gtm !== '') {
        $html .= '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . e($gtm) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';
    }
    if ($pixel !== '') {
        $html .= '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=' . e($pixel) . '&ev=PageView&noscript=1" alt=""></noscript>';
    }
    return $html;
}

function seoJsonLd(array $pageNodes = []): string
{
    $brand = seoBrand();
    $home = publicSiteUrl();
    $logo = defaultLogoUrl();
    $graph = [
        [
            '@type' => 'Organization',
            '@id' => $home . '#organization',
            'name' => $brand,
            'url' => $home,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logo,
            ],
            'sameAs' => array_values(array_filter([
                seoCfg('SOCIAL_FACEBOOK'),
                seoCfg('SOCIAL_LINKEDIN'),
                seoCfg('SOCIAL_INSTAGRAM'),
                seoCfg('SOCIAL_X'),
                seoCfg('SOCIAL_YOUTUBE'),
            ])),
        ],
        [
            '@type' => 'WebSite',
            '@id' => $home . '#website',
            'url' => $home,
            'name' => $brand . ' Lighting Blog',
            'publisher' => ['@id' => $home . '#organization'],
            'inLanguage' => 'en',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $home . '/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ];
    foreach ($pageNodes as $node) {
        if (is_array($node) && $node) {
            $graph[] = $node;
        }
    }
    return json_encode([
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function seoTwitterHandle(): string
{
    $url = seoCfg('SOCIAL_X');
    if ($url === '') {
        return '';
    }
    if (preg_match('~(?:x\\.com|twitter\\.com)/@?([A-Za-z0-9_]+)~', $url, $m)) {
        return '@' . $m[1];
    }
    return '';
}

function seoBreadcrumbList(array $crumbs): array
{
    $items = [];
    $pos = 1;
    foreach ($crumbs as $crumb) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'name' => $crumb['name'],
            'item' => $crumb['url'],
        ];
    }
    return [
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items,
    ];
}
