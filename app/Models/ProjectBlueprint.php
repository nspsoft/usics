<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectBlueprint extends Model
{
    protected $fillable = [
        'title',
        'content',
        'order_index',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(ProjectBlueprint::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProjectBlueprint::class, 'parent_id')->orderBy('order_index');
    }
}
