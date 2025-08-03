<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model {
    protected $table='ticket';

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        Ticket::where('updated_at','<',date('Y-m-d H:i:s',strtotime('- 7 day')))->where('status','answered')
            ->update(['status'=>'closed']);
    }

}
