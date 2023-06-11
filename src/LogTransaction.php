<?php
namespace Kuncen\Audittrails;

use Carbon\Carbon;
use App\Models\ActivityLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

trait LogTransaction
{
    /**
     * Handle model event
     */
    protected static $custom_user_auth;
    /**
     * jika event dilakukan sebelum adanya autentikasi maka secara opsional bisa mengisi withauth dengan cast id user
     */
    public static function withAuth($custom_user_auth=null){
        self::$custom_user_auth = $custom_user_auth;
        return new static();
    }
    public static function  booted(){
        static::saved(function ($model) {
            /** 
             * Event ketika update atau create menggunakan eloquent 
            */
            if ($model->wasRecentlyCreated) {
                static::insertActivityLog($model, static::class, "CREATE");
            } else {
                if (!$model->getChanges()) {
                    return;
                }
                static::insertActivityLog($model, static::class, "UPDATE");
            }
        });

        /**
         * Event ketika delete
         */
        static::deleted(function (Model $model) {
            static::insertActivityLog($model, static::class, "DELETE");
        });
    }

    public static function insertActivityLog($model, $modelPath, $action, $type=null){
        $newValues = null;
        $oldValues = null;
        if ($action === 'CREATE') {
            $newValues = $model->getAttributes();
        } elseif ($action === 'UPDATE') {
            $newValues = $model->getChanges();
        }

        if ($action !== 'CREATE') {
            $oldValues = $model->getOriginal();
        }
        $logTable = new ActivityLog;
        $logTable->users_id = self::$custom_user_auth ?? @Auth::user()->id;
        $logTable->jenis_tindakan = $action;
        $logTable->ip_address = request()->ip();
        $logTable->waktu = Carbon::now();
        $logTable->url = request()->url();
        $logTable->keterangan = $action . " data pada table ".$model->getTable()." (".$modelPath.")";
        $logTable->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $logTable->old_values = !empty($oldValues) ? json_encode($oldValues) : null;
        $logTable->new_values = !empty($newValues) ? json_encode($newValues) : null;
        $logTable->save();
    }
}