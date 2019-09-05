<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';

    public function user(){
        // RELACION DE MUCHOS A UNO N:1 (MUCHOS posts pertenecen a UN mismo usuario)

        return $this->belongsTo('App\User', 'user_id');
    }

    public function category(){
        // RELACION DE MUCHOS A UNO N:1 (MUCHOS posts pertenecen a UNA misma categoria)

        return $this->belongsTo('App\Category', 'category_id');
    }
}
