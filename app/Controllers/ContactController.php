<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

final class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('pages.contact', [
            'title' => t('nav.contact') . ' | Mentoris Academy',
            'description' => t('footer.tagline'),
            'errors' => [],
            'old' => [],
            'success' => false,
        ]);
    }

    public function store(Request $request): Response
    {
        $validator = new Validator();
        $data = $request->only(['name', 'email', 'phone', 'subject', 'message']);
        $valid = $validator->validate($data, [
            'name' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:120',
            'phone' => 'max:20',
            'subject' => 'required|string|max:120',
            'message' => 'required|string|min:10|max:2000',
        ]);

        return $this->view('pages.contact', [
            'title' => t('nav.contact') . ' | Mentoris Academy',
            'description' => t('footer.tagline'),
            'errors' => $validator->errors(),
            'old' => $data,
            'success' => $valid,
        ]);
    }
}
