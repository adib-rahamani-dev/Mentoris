<?php
declare(strict_types=1);
namespace App\Services;
final class MessengerException extends \RuntimeException
{
    public function __construct(string $message,int $code,public readonly int $retryAfter=60) { parent::__construct($message,$code); }
}
