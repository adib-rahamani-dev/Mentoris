<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use App\Repositories\CommunicationRepository;
final class CommunicationNoticeService
{
    public function __construct(private readonly CommunicationRepository $hub=new CommunicationRepository()) {}
    public function engagement(string $type,string $id,string $status): void
    {
        if(!$this->hub->available()) return;
        $tables=['circle'=>'event_signups','courses'=>'course_requests','community'=>'community_memberships'];$table=$tables[$type] ?? null;if(!$table) return;
        $row=$this->hub->one("SELECT * FROM $table WHERE id=:id",['id'=>$id]);if(!$row) return;
        $label=['requested'=>'در انتظار بررسی','approved'=>'تأیید شد','rejected'=>'تأیید نشد','attended'=>'حضور ثبت شد','canceled'=>'لغو شد'][$status] ?? $status;
        $title=$type==='circle'?'درخواست حضور در حلقه':($type==='courses'?'درخواست دوره':'درخواست جامعه حرفه‌ای');
        $slug=$row['event_slug'] ?? $row['course_slug'] ?? '';
        $key='engagement:'.$type.':'.$id.':'.$status.':'.($row['updated_at'] ?? $row['created_at']);
        if(!empty($row['user_id'])) $this->hub->notifyUser($row['user_id'],$title,$label.($slug?"\n".rtrim((string)env('APP_URL'),'/').($type==='circle'?'/events/':'/courses/').$slug:''),$key);
        if($status==='requested') $this->hub->notifyAdmins($title.' تازه ثبت شد.',$key);
    }
    public function reminders(): int
    {
        if(!$this->hub->available()) return 0;$queued=0;
        foreach(PublicContentService::events() as $event) {
            $start=strtotime((string)($event['starts_at'] ?? ''));if(!$start || $start<=time() || ($event['status'] ?? '')==='canceled') continue;
            foreach([24,2] as $hours) {
                $scheduled=$start-$hours*3600;
                // Catch up briefly after a delayed worker; do not send missed day reminders just before the event.
                if(time()<$scheduled || time()>$scheduled+7200 || ($hours===24 && $start-time()<20*3600)) continue;
                foreach($this->hub->rows("SELECT DISTINCT user_id FROM event_signups WHERE event_slug=:slug AND status='approved' AND user_id IS NOT NULL",['slug'=>$event['slug']]) as $row) {
                    $this->hub->notifyUser($row['user_id'],'یادآوری رویداد منتوریس',$event['title']."\nزمان: ".$event['date'].' | '.$event['time']."\nمکان: ".$event['location'], 'reminder:'.$event['slug'].':'.$start.':'.$hours);$queued++;
                }
            }
        }
        return $queued;
    }
}
