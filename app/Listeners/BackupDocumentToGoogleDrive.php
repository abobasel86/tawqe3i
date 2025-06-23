<?php
namespace App\Listeners;
use App\Events\DocumentCompleted;
use Illuminate\Support\Facades\Storage;
class BackupDocumentToGoogleDrive {
    public function handle(DocumentCompleted $event) {
        $document = $event->document;
        // تأكد من أن المستند مكتمل وله ملف موقع
        if ($document->status !== 'completed' || !$document->signed_file_path) {
            return;
        }
        $fileContent = Storage::disk('private')->get($document->signed_file_path);
        $fileName = 'موقع - ' . $document->name . '.pdf';
        // رفع الملف إلى Google Drive
        Storage::disk('google')->put($fileName, $fileContent);
    }
}