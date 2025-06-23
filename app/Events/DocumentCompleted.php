<?php
namespace App\Events;
use App\Models\Document;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class DocumentCompleted {
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $document;
    public function __construct(Document $document) {
        $this->document = $document;
    }
}