<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';
    protected $primaryKey = 'id';

    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = null;

    protected $fillable = [
        'categoryName',
        'description',
        'slaHours',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'createdAt' => 'datetime',
        ];
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'categoryId', 'id');
    }
}
