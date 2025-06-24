<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\SignatureRequestMail;

class DocumentController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        // Security check
        if (auth()->id() !== $document->user_id) {
            abort(403);
        }

        $filePath = $document->original_file_path;

        // Check if file exists
        if (!\Illuminate\Support\Facades\Storage::disk('private')->exists($filePath)) {
            abort(404);
        }

        // Read the file content and encode it to Base64
        $fileContent = \Illuminate\Support\Facades\Storage::disk('private')->get($filePath);
        $base64Pdf = base64_encode($fileContent);

        // Pass both the document and the base64 data to the view
        return view('documents.show', [
            'document' => $document,
            'base64Pdf' => $base64Pdf,
        ]);
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('documents.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255', 'document' => 'required|file|mimes:pdf|max:10240']);
        $path = $request->file('document')->store('documents', 'private');
        $request->user()->documents()->create(['name' => $request->name, 'original_file_path' => $path, 'status' => 'draft']);
        return redirect()->route('dashboard')->with('success', 'تم رفع المستند بنجاح!');
    }

    /**
     * Add a participant to the document.
     */
    public function addParticipant(Request $request, Document $document)
    {
        if (auth()->id() !== $document->user_id) { abort(403); }
        $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|max:255']);
        $document->participants()->create(['name' => $request->name, 'email' => $request->email, 'token' => Str::uuid()]);
        return back()->with('success', 'تمت إضافة الموقّع بنجاح.');
    }

    /**
     * Delete a participant from the document.
     */
    public function deleteParticipant(DocumentParticipant $participant)
    {
        $document = $participant->document;
        if (auth()->id() !== $document->user_id) { abort(403); }
        $participant->delete();
        return back()->with('success', 'تم حذف الموقّع بنجاح.');
    }

    /**
     * Save the fields' positions and sizes.
     */
    public function saveFields(Request $request, Document $document)
    {
        if (auth()->id() !== $document->user_id) { abort(403); }
        $document->update(['fields' => $request->input('fields', [])]);
        return response()->json(['message' => 'تم حفظ أماكن الحقول بنجاح!']);
    }

    /**
     * Download the specified document file.
     * It serves the signed version if available, otherwise the original.
     */
    public function download(Request $request, Document $document)
    {
        // Security check
        if ($request->user()->id !== $document->user_id) {
            abort(403);
        }

        // Determine which file to download
        $filePath = $document->signed_file_path ?? $document->original_file_path;

        // Check if the file exists
        if (!Storage::disk('private')->exists($filePath)) {
            abort(404, 'الملف المطلوب غير موجود.');
        }

        // Create a user-friendly filename
        $fileName = $document->status === 'completed' 
            ? 'موقع - ' . $document->name . '.pdf' 
            : $document->name . '.pdf';

        return Storage::disk('private')->download($filePath, $fileName);
    }

/**
 * Send the document for signatures.
 */
public function send(Request $request, Document $document)
{
    if (auth()->id() !== $document->user_id) { abort(403); }

    if ($document->participants->count() === 0) {
        return back()->withErrors(['message' => 'يجب إضافة موقّع واحد على الأقل قبل الإرسال.']);
    }

    // تحميل بيانات المستخدم المرتبطة بالمستند
    $document->load('user');

    // تحديث حالة المستند
    $document->update(['status' => 'sent']);

    // إرسال الإيميلات وفق ترتيب التوقيع
    $minOrder = $document->participants()->min('signing_order');
    $participantsToSend = $minOrder === null
        ? $document->participants
        : $document->participants()->where('signing_order', $minOrder)->get();

    foreach ($participantsToSend as $participant) {
        Mail::to($participant->email)->send(new SignatureRequestMail($document, $participant));
        $participant->update(['status' => 'sent']);
    }

    return redirect()->route('dashboard')->with('success', 'تم إرسال المستند بنجاح!');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Document $document)
    {
        if ($request->user()->id !== $document->user_id) { abort(403); }
        Storage::disk('private')->delete($document->original_file_path);
        $document->delete();
        return redirect()->route('dashboard')->with('success', 'تم حذف المستند بنجاح.');
    }

    public function assignFolder(Request $request, Document $document) {
    if (auth()->id() !== $document->user_id) { abort(403); }
    $request->validate(['folder_id' => 'required|exists:folders,id']);
    // syncWithoutDetaching prevents duplicates
    $document->folders()->syncWithoutDetaching($request->folder_id);
    return back()->with('success', 'تم إسناد المستند إلى المجلد.');
}

}