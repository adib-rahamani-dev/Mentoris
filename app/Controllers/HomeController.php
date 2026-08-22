<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('pages.home', [
            'title' => 'Mentoris Design System',
            'description' => 'راهنمای زنده زبان بصری و کامپوننت‌های Mentoris',
        ]);
    }
}
