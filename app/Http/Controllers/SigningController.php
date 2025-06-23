<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SignatureRequestMail;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

class SigningController extends Controller
{
    /**
     * Display the public signing page.
     */
    public function show($token)
    {
        $participant = DocumentParticipant::where('token', $token)->firstOrFail();
        if ($participant->status === 'signed') {
            return 'لقد قمت بتوقيع هذا المستند بالفعل.';
        }
        $document = $participant->document;
        $fileContent = Storage::disk('private')->get($document->original_file_path);
        $base64Pdf = base64_encode($fileContent);
        return view('signing.show', [
            'participant' => $participant,
            'document' => $document,
            'base64Pdf' => $base64Pdf,
        ]);
    }

    /**
     * Store the signature and finalize the document.
     */
    public function store(Request $request, $token)
    {
        $participant = DocumentParticipant::where('token', $token)->firstOrFail();
        $document = $participant->document;

        $request->validate([
            'signature' => 'required|string',
        ]);

        // --- حفظ التوقيع في قاعدة البيانات ---
        $fields = $document->fields;
        $signatureDataUrl = $request->input('signature');
        $signatureFieldKey = null;

        if (is_array($fields)) {
            foreach ($fields as $key => $field) {
                if (isset($field['type']) && $field['type'] === 'signature' && isset($field['participant_id']) && $field['participant_id'] === $participant->id) {
                    $fields[$key]['value'] = $signatureDataUrl;
                    $signatureFieldKey = $key;
                    break;
                }
            }
        }
        $document->update(['fields' => $fields]);

        // --- ختم التوقيع على ملف الـ PDF ---
        if ($signatureFieldKey !== null) {
            $this->stampPdf($document, $participant, $signatureDataUrl);
        }

        // --- تحديث حالة المشارك ---
        $participant->update(['status' => 'signed']);

        // --- التحقق مما إذا كان الجميع قد وقع ---
        $allSigned = $document->participants()->where('status', '!=', 'signed')->count() === 0;

        if ($allSigned) {
            $document->update(['status' => 'completed']);
            event(new \App\Events\DocumentCompleted($document));
        } else {
            // إذا كان المسار تسلسليًا، أرسل للشخص التالي
            if ($document->flow_type === 'sequential') {
                $nextParticipant = $document->participants()
                    ->where('signing_order', '>', $participant->signing_order)
                    ->orderBy('signing_order', 'asc')
                    ->first();
                
                if ($nextParticipant) {
                    $document->load('user'); // Load user for email
                    Mail::to($nextParticipant->email)->send(new SignatureRequestMail($document, $nextParticipant));
                    $nextParticipant->update(['status' => 'sent']);
                }
            }
        }

        return view('signing.thank-you');
    }

    /**
     * Private function to stamp the PDF with the signature.
     */
    private function stampPdf(Document $document, DocumentParticipant $participant, string $signatureDataUrl)
    {
        // مسار المستند الأصلي، أو النسخة الموقعة سابقًا إذا وجدت
        $sourceFilePath = $document->signed_file_path ?? $document->original_file_path;
        $sourceFile = Storage::disk('private')->path($sourceFilePath);

        // إنشاء كائن PDF جديد
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($sourceFile);

        // استيراد كل صفحات المستند الأصلي
        for ($i = 1; $i <= $pageCount; $i++) {
            $templateId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        // تحويل صورة التوقيع من نص إلى ملف مؤقت
        $signatureImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureDataUrl));
        $tempImagePath = tempnam(sys_get_temp_dir(), 'sig');
        file_put_contents($tempImagePath, $signatureImage);

        // العثور على حقل التوقيع الخاص بهذا الشخص
        $fieldData = null;
        foreach($document->fields as $field) {
            if ($field['type'] === 'signature' && $field['participant_id'] === $participant->id) {
                $fieldData = $field;
                break;
            }
        }

        if ($fieldData) {
            // ملاحظة: الإحداثيات هنا تحتاج لمعايرة دقيقة لاحقًا
            // هذه مجرد قيم أولية
            $x = $fieldData['x'] * 0.26; // تحويل من بكسل إلى ملم (تقريبي)
            $y = $fieldData['y'] * 0.26;
            $w = $fieldData['width'] * 0.26;
            $h = $fieldData['height'] * 0.26;

            // اذهب إلى الصفحة الصحيحة (نفترض حاليًا أنها الأولى)
            $pdf->setPage(1); 
            // ضع صورة التوقيع في المكان المحدد
            $pdf->Image($tempImagePath, $x, $y, $w, $h, 'PNG');
        }

        // حذف الملف المؤقت
        unlink($tempImagePath);

        // حفظ النسخة الجديدة الموقعة
        $newSignedPath = 'signed/' . uniqid() . '_' . basename($document->original_file_path);
        Storage::disk('private')->put($newSignedPath, $pdf->Output('S'));

        // تحديث سجل المستند بالمسار الجديد
        $document->update(['signed_file_path' => $newSignedPath]);
    }
}