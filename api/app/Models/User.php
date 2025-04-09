<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;
    
    protected $table = 'users';
    
    protected $primaryKey = 'id';

    protected $fillable = [        
        'name',
        'cpf_cnpj',
        'email',
        'password',
        'is_supplier',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }
}
