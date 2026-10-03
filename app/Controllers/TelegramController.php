<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\TelegramRepository;

final class TelegramController extends Controller
{
    public function webhook(Request $request): Response
    {
        $secret=(string)env('TELEGRAM_WEBHOOK_SECRET','');
        if($secret==='' || !hash_equals($secret,(string)$request->header('X-Telegram-Bot-Api-Secret-Token',''))) return $this->json(['ok'=>false],401);
        if(strlen($request->raw())>65536) return $this->json(['ok'=>false],413);
        $update=json_decode($request->raw(),true);
        if(!is_array($update) || !is_int($update['update_id'] ?? null) || $update['update_id']<0) return $this->json(['ok'=>false],422);
        $repository=new TelegramRepository();
        if(!$repository->available()) return $this->json(['ok'=>false],503);
        $repository->enqueueUpdate($update);
        return $this->json(['ok'=>true]);
    }
}
