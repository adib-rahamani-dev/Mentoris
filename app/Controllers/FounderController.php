<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;

final class FounderController extends Controller
{
    public function index(Request $request): Response
    {
        $founder = PublicContentService::founder();
        foreach(PublicContentService::mentors() as $mentor) if($mentor['slug']===$founder['slug']) {
            $founder['name']=$mentor['name']; $founder['role']=$mentor['role']; $founder['short_bio']=$mentor['specialty'];
            $founder['paragraphs']=array_values(array_filter(preg_split('/\n\s*\n/u',$mentor['bio']) ?: []));
            if($mentor['image']!=='') $founder['image']=$mentor['image'];
            break;
        }
        return $this->view('pages.founder', [
            'title' => $founder['name'] . ' | ' . t('nav.founder') . ' Mentoris',
            'description' => $founder['short_bio'],
            'seoImage' => '/assets/'.$founder['image'],
            'founder' => $founder,
        ]);
    }
}
