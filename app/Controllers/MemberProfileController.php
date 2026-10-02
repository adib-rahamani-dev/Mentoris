<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Authorization;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Repositories\MemberProfileRepository;
use App\Repositories\UserRepository;
use App\Services\MemberProfileService;
use App\Services\AuthService;
use App\Services\AvatarService;

final class MemberProfileController extends Controller
{
    public function save(Request $request): Response { return $this->persist($request,(new AuthService())->user() ?? [],false); }
    public function adminSave(Request $request, string $id): Response
    {
        $user=(new UserRepository())->findById($id);
        if (!$user) return Response::html('<h1>کاربر پیدا نشد</h1>',404);
        $admin=(new AuthService())->user();
        if (Authorization::role($user)==='super_admin' && Authorization::role($admin)!=='super_admin') return Response::html('<h1>دسترسی ندارید</h1>',403);
        return $this->persist($request,$user,true);
    }
    private function persist(Request $request, array $user, bool $admin): Response
    {
        $repo=new MemberProfileRepository();
        if (!$repo->available()) return Response::html('<h1>ابتدا مایگریشن ۰۰۴ را اجرا کنید.</h1>',503);
        $complete=$request->input('mode')==='complete';
        [$data,$errors]=MemberProfileService::validate((array)$request->input(),$complete);
        $old=$repo->find($user['id']);
        $data['avatar_path']=$old['avatar_path'] ?? '';
        if (!$errors) {
            try { $data['avatar_path']=(new AvatarService())->store($request->file('avatar')) ?? $data['avatar_path']; }
            catch (\RuntimeException $e) { $errors['avatar']=[$e->getMessage()]; }
        }
        if (!$errors) {
            try {
                Database::transaction(Database::connection(),function() use ($repo,$user,$data,$complete): void {
                    $repo->save($user['id'],$data,$complete);
                    (new UserRepository())->updateProfile($user['id'],['bio'=>$data['bio'],'role'=>MemberProfileService::OPTIONS['member_type'][$data['member_type']]]);
                });
            } catch (\Throwable $e) {
                if ($data['avatar_path']!==($old['avatar_path'] ?? '') && $data['avatar_path']!=='') @unlink(BASE_PATH.'/public/assets/'.$data['avatar_path']);
                throw $e;
            }
            if ($admin) Audit::record('user.member_profile.updated','user',$user['id'],(new AuthService())->user()['id'] ?? null,[],['member_type'=>$data['member_type'],'complete'=>$complete],$request->ip());
            return $this->redirect($admin ? '/admin/users/'.$user['id'].'?member_saved=1' : '/profile?saved=1');
        }
        $extra=['member'=>$data,'memberErrors'=>$errors,'memberEnabled'=>true];
        if ($admin) return $this->view('admin.user-details',['title'=>'پرونده کاربر','admin'=>(new AuthService())->user(),'user'=>$user,'operations'=>(new \App\Repositories\AdminRepository())->userOperations($user['id']),'roles'=>Authorization::ROLES,...$extra],'layouts.admin');
        $circle=new \App\Repositories\CircleRepository();
        return $this->view('user.profile',['title'=>'پروفایل من','user'=>$user,'old'=>[],'errors'=>[],'success'=>false,'therapist'=>$circle->profile($user['id']),'therapistEnabled'=>$circle->therapistAvailable(),...$extra]);
    }
}
