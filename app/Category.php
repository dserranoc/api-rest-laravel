<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    // RELACION DE UNO A MUCHOS 1:N (UNA categoría puede tener MUCHOS posts)

    public function posts(){
        return $this->hasMany('App\Post');
    }
}
