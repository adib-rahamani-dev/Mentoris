<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class LiveController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) env('LIVE_STREAM_STATUS', 'scheduled');
        if (!in_array($status, ['scheduled', 'live', 'ended'], true)) $status = 'scheduled';
        $videoId = trim((string) env('LIVE_STREAM_YOUTUBE_ID', ''));
        if (!preg_match('/^[A-Za-z0-9_-]{11}$/D', $videoId)) $videoId = '';
        $aparatUsername = trim((string) env('LIVE_STREAM_APARAT_USERNAME', ''));
        if (!preg_match('/^[A-Za-z0-9_]{3,60}$/D', $aparatUsername)) $aparatUsername = '';
        $provider = (string) env('LIVE_STREAM_PROVIDER', 'aparat');
        if (!in_array($provider, ['aparat', 'youtube'], true)) $provider = 'aparat';
        $embedUrl = $provider === 'aparat' && $aparatUsername !== ''
            ? 'https://www.aparat.com/embed/live/' . rawurlencode($aparatUsername)
            : ($provider === 'youtube' && $videoId !== '' ? 'https://www.youtube-nocookie.com/embed/' . rawurlencode($videoId) . '?autoplay=0&rel=0' : '');
        $fallbackUrl = $provider === 'aparat' && $aparatUsername !== ''
            ? 'https://www.aparat.com/' . rawurlencode($aparatUsername) . '/live'
            : ($provider === 'youtube' && $videoId !== '' ? 'https://www.youtube.com/watch?v=' . rawurlencode($videoId) : '');

        return $this->view('pages.live', [
            'title' => 'پخش زنده | منتوریس',
            'description' => 'فضای پخش زنده و گفت‌وگوی آنلاین منتوریس.',
            'indexable' => false,
            'streamStatus' => $status,
            'embedUrl' => $embedUrl,
            'fallbackUrl' => $fallbackUrl,
        ]);
    }
}
