<?php
namespace Kuncen\Audittrails;

use Carbon\Carbon;
use App\Models\ActivityLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

trait LogTransaction
{
    /**
     * Handle model event
     */
    protected static $xx_custom_user_auth;
    protected static $xx_hide_replaced_foreign;
    protected static $xx_disabled_audit;
    /**
     * jika event dilakukan sebelum adanya autentikasi maka secara opsional bisa mengisi withauth dengan cast id user
     */
    public static function withAuth($custom_user_auth=null){
        self::$xx_custom_user_auth = $custom_user_auth;
        return new static();
    }
    public static function disableAudit($is_disable=false){
        self::$xx_disabled_audit = $is_disable;
        return new static();
    }
    public static function hideForeignId($hide_replaced_foreign=null){
        self::$xx_hide_replaced_foreign = $hide_replaced_foreign;
        return new static();
    }
    public static function  booted(){
        self::$xx_hide_replaced_foreign = true;
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

    public static function replaceForeignValue(Model $model, &$value){
        if($model->setForeignValues){
            foreach ($model->setForeignValues as $index => $foreignItems) {
                if(!empty($value[$foreignItems['foreign']])){
                    $target_values = DB::table($foreignItems['reference_table'])->select($foreignItems['target_value'])->where($foreignItems['reference_table_primary'] ?? 'id', $value[$foreignItems['foreign']])->first();
                    $value[$foreignItems['target_value']] = $target_values->{$foreignItems['target_value']};
                    if(self::$xx_hide_replaced_foreign == true){
                        unset($value[$foreignItems['foreign']]);
                    }
                }
            }
        }
    }

    public static function insertActivityLog($model, $modelPath, $action, $type=null){
        if(!self::$xx_disabled_audit){
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
            $primaryUser = @Auth::user()->{@$model->getKeyName()};
            self::replaceForeignValue($model, $oldValues);
            self::replaceForeignValue($model, $newValues);
            $logTable = new ActivityLog;
            $logTable->users_id = self::$xx_custom_user_auth ?? $primaryUser;
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
}