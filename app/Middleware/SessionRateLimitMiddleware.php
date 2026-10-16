<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Core\{Request,Response,Session,Security,RateLimiter,FileRateLimiter};

// Complements the broader IP limit so attendees sharing venue Wi-Fi get separate budgets.
final class SessionRateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly int $maxAttempts=8,private readonly int $decaySeconds=60) {}
    public function handle(Request $request,callable $next): Response
    {
        $session=new Session();$key=$session->get('rate.identity');
        if(!is_string($key)){$key=Security::randomToken(16);$session->put('rate.identity',$key);}
        try {
            $limiter=env('RATE_LIMIT_DRIVER','files')==='database'?new RateLimiter():new FileRateLimiter();
            $bucket=$limiter->hit('session|'.$key.'|'.$request->method().'|'.$request->routePattern(),$this->maxAttempts,$this->decaySeconds);
        }catch(\Throwable){return Response::json(['message'=>'ارتباط موقتاً برقرار نیست؛ کمی بعد دوباره تلاش کنید.'],503)->withHeader('Retry-After','60');}
        if(!$bucket['allowed'])return Response::json(['message'=>'درخواست‌های زیادی فرستاده‌اید؛ یک دقیقه دیگر دوباره تلاش کنید.'],429)->withHeader('Retry-After',(string)$bucket['retry_after']);
        return $next($request);
    }
}
