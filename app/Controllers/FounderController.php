<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class FounderController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('pages.founder', [
            'title' => 'بنیان‌گذار Mentoris',
            'description' => 'آشنایی با بنیان‌گذار، رویکرد و داستان شکل‌گیری Mentoris.',
        ]);
    }
}
