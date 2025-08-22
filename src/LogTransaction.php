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
    protected static $xx_batch_data;
    protected static $xx_keterangan_audit;
    protected static $xx_retrieved_buffer = [];
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
    public static function setBatchAudit($unique_batch){
        self::$xx_batch_data = $unique_batch;
        return new static();
    }
    public static function setDescAudit($keterangan_audit){
        self::$xx_keterangan_audit = $keterangan_audit;
        return new static();
    }
    public static function  bootLogTransaction(){
        self::$xx_hide_replaced_foreign = true;
        $runningModel = static::class;
        if ((new $runningModel)->allowLogForRetrieve) {
            static::retrieved(function ($model) {
                self::$xx_retrieved_buffer[] = $model->toArray();
            });

            App::terminating(function () {
                if (!empty(self::$xx_retrieved_buffer)) {
                    setActivityLog("", auth()->user()->id ?? null, "GET", self::$xx_retrieved_buffer);
                    self::$xx_retrieved_buffer = [];
                }
            });
        }

        if ((new $runningModel)->useUserIdentityForTransaction) {
            static::creating(function($model){
                $model->created_by = auth()->user()->id;
            });
            static::updating(function($model){
                $model->updated_by = auth()->user()->id;
            });
            static::deleting(function($model){
                $model->delete_by = auth()->user()->id;
            }); 
        }
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
                    if(Str::contains($foreignItems['target_value'], " as ")) {
                        $arrayString = explode(" as ",$foreignItems['target_value']);
                        $value[$arrayString[1]] = $target_values->{$arrayString[1]};
                    } else {
                        $value[$foreignItems['target_value']] = $target_values->{$foreignItems['target_value']};
                    }
                    if(self::$xx_hide_replaced_foreign == true){
                        unset($value[$foreignItems['foreign']]);
                    }
                }
            }
        }
    }

    public function transformAudit(array $data):array{
        return $data;
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
                $rawValues = $model->getOriginal();
                $oldValues = [];
                foreach ($model->getChanges() as $key => $value) {
                    $oldValues[$key] = $rawValues[$key];
                }
            }
            $primaryUser = null;
            if(Auth::check()){
                $primaryUser = @Auth::user()->{@Auth::user()->getKeyName()};
            }
            self::replaceForeignValue($model, $oldValues);
            self::replaceForeignValue($model, $newValues);
            $data = [
                'old_values' => &$oldValues,
                'new_values' => &$newValues
            ];
            $model->transformAudit($data);
            if(isset($oldValues['created_at'])){
                $oldValues['created_at'] = Carbon::parse($oldValues['created_at'])->format($model->logDateTimeFormat ?? 'd-m-Y H:i:s');
            }
            if(isset($oldValues['updated_at'])){
                $oldValues['updated_at'] = Carbon::parse($oldValues['updated_at'])->format($model->logDateTimeFormat ?? 'd-m-Y H:i:s');
            }
            if(isset($newValues['created_at'])){
                $newValues['created_at'] = Carbon::parse($newValues['created_at'])->format($model->logDateTimeFormat ?? 'd-m-Y H:i:s');
            }
            if(isset($newValues['updated_at'])){
                $newValues['updated_at'] = Carbon::parse($newValues['updated_at'])->format($model->logDateTimeFormat ?? 'd-m-Y H:i:s');
            }
            if($model->logPassword != true){
                if(isset($oldValues['password'])){
                    $oldValues['password'] = "***";
                }
                if(isset($newValues['password'])){
                    $newValues['password'] = "***";
                }
            }
            if($model->dateTimeColumn){
                foreach ($model->dateTimeColumn as $key => $value) {
                    if(isset($newValues[$value['column_name']])) $newValues[$value['column_name']] = Carbon::parse($newValues[$value['column_name']])->format($value['format'] ?? 'd-m-Y H:i:s');
                    if(isset($oldValues[$value['column_name']])) $oldValues[$value['column_name']] = Carbon::parse($oldValues[$value['column_name']])->format($value['format'] ?? 'd-m-Y H:i:s');
                }
            }
            $logTable = new ActivityLog;
            $logTable->users_id = self::$xx_custom_user_auth ?? $primaryUser;
            $logTable->jenis_tindakan = $action;
            $logTable->ip_address = request()->ip();
            $logTable->waktu = Carbon::now();
            $logTable->url = request()->url();
            $logTable->keterangan = self::$xx_keterangan_audit ?? null;
            $logTable->model_path = $modelPath;
            $logTable->batch = self::$xx_batch_data ?? null;
            $logTable->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
            $logTable->old_values = !empty($oldValues) ? json_encode($oldValues) : null;
            $logTable->new_values = !empty($newValues) ? json_encode($newValues) : null;
            $logTable->save();
        }
    }
}