<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorySubmitScore extends Model
{
    use HasFactory;
    protected $table = 'history_submit_score';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', 
        'level',
        'score',
    ];

    /**
     * Relasi dengan User (setiap history submit score milik satu user)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
