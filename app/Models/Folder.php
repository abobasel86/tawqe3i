<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Folder extends Model {
    use HasFactory;
    protected $fillable = ['name', 'user_id'];
    public function documents() {
        return $this->belongsToMany(Document::class);
    }
}