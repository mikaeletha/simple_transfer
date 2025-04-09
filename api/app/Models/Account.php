<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $table = 'accounts';
    
    protected $primaryKey = 'id';

    protected $fillable = [  
        'account_number',      
        'user_id',
        'balance',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
