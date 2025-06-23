<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Template extends Model {
    use HasFactory;
    protected $fillable = ['name', 'description', 'original_file_path', 'fields', 'user_id'];
    protected $casts = ['fields' => 'array'];
}