<?php
namespace Kuncen\Audittrails;
use Carbon\Carbon;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Logout;

class LogLogoutListener{
    /**
     *
     * @var \Illuminate\Http\Request
     */
    public $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(Logout $event)
    {
        $user = $event->user;
        $ip = $this->request->ip();
        $userAgent = $this->request->userAgent();

        $authenticationLog = new ActivityLog;
        $authenticationLog->users_id = $event->user->id;
        $authenticationLog->jenis_tindakan = "AUTH LOGOUT";
        $authenticationLog->ip_address =  $ip;
        $authenticationLog->waktu = Carbon::now();
        $authenticationLog->url = request()->url();
        $authenticationLog->keterangan = "User Melakukan Logout Pada Sistem";
        $authenticationLog->user_agent = $userAgent;
        $authenticationLog->new_values = json_encode($user);
        $authenticationLog->save();

    }

}