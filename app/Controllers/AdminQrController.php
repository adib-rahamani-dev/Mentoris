<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class AdminQrController extends Controller
{
    public function index(Request $request): Response
    {
        $baseUrl = rtrim((string) env('APP_URL', 'https://mentorisacademy.com'), '/');
        if (!preg_match('#^https?://#i', $baseUrl)) $baseUrl = 'https://' . $baseUrl;
        return $this->view('admin.qr', [
            'title' => 'استودیوی QR | Mentoris Admin',
            'admin' => (new AuthService())->user(),
            'baseUrl' => $baseUrl,
            'feedbackReady' => (new \App\Repositories\CircleRepository())->feedbackAvailable()
                && (trim((string) env('FEEDBACK_ACCESS_CODE', '')) ?: trim((string) env('LIVE_ACCESS_CODE', ''))) !== '',
        ], 'layouts.admin');
    }
}
