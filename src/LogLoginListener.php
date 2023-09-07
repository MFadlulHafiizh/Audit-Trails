<?php
namespace Kuncen\Audittrails;
use Carbon\Carbon;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Login;

class LogLoginListener{
    /**
     *
     * @var \Illuminate\Http\Request
     */
    public $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(Login $event)
    {
        $user = $event->user;
        $ip = $this->request->ip();
        $userAgent = $this->request->userAgent();
        $authenticationLog = new ActivityLog;
        $authenticationLog->users_id = $event->user->{$event->user->getKeyName()};
        $authenticationLog->jenis_tindakan = "AUTH LOGIN";
        $authenticationLog->ip_address =  $ip;
        $authenticationLog->waktu = Carbon::now();
        $authenticationLog->url = request()->url();
        $authenticationLog->keterangan = "User Melakukan Login Pada Sistem";
        $authenticationLog->user_agent = $userAgent;
        $authenticationLog->new_values = json_encode($user);
        $authenticationLog->save();

    }

}