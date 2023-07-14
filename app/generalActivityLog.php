<?php

use Carbon\Carbon;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

if (! function_exists('setActivityLog')) {
    function setActivityLog($description=null,$custom_user_id=null,$httpMethod=null, $customNewValues=[], $customOldValues=[]){
        $authenticationLog = new ActivityLog;
        $authenticationLog->users_id = $custom_user_id ?? @Auth::user()->id;
        $authenticationLog->jenis_tindakan = $httpMethod ?? "READ";
        $authenticationLog->ip_address = request()->url();
        $authenticationLog->waktu = Carbon::now();
        $authenticationLog->url = request()->url();
        $authenticationLog->keterangan = $description;
        $authenticationLog->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $authenticationLog->old_values = !empty($customOldValues) ? json_encode($customOldValues) : null;
        $authenticationLog->new_values = !empty($customNewValues) ? json_encode($customNewValues) : null;
        $authenticationLog->save();
    }
}