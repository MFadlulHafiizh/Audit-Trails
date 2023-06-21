<?php

use Carbon\Carbon;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

if (! function_exists('setActivityLog')) {
    function setActivityLog($description=null,$custom_user_id=null,$httpMethod=null){
        $authenticationLog = new ActivityLog;
        $authenticationLog->users_id = $custom_user_id ?? @Auth::user()->id;
        $authenticationLog->jenis_tindakan = $httpMethod ?? "READ";
        $authenticationLog->ip_address = request()->ip();
        $authenticationLog->waktu = Carbon::now();
        $authenticationLog->url = request()->url();
        $authenticationLog->keterangan = $description;
        $authenticationLog->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $authenticationLog->save();
    }
}
