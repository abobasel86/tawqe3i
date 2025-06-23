<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'name',
        'email',
        'token',
        'signing_order',
        'status'
    ];

    /**
     * Get the document that this participant belongs to.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}