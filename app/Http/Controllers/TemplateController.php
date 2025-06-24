<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

class TemplateController extends Controller
{
    /**
     * Display a listing of the templates.
     */
    public function index()
    {
        $templates = auth()->user()->templates()->latest()->get();
        return view('templates.index', ['templates' => $templates]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function create()
    {
        return view('templates.create');
    }

    /**
     * Store a newly created template in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'document' => 'required|file|mimes:pdf|max:10240',
        ]);

        $path = $request->file('document')->store('templates', 'private');

        $template = $request->user()->templates()->create([
            'name' => $request->name,
            'original_file_path' => $path,
        ]);

        // بعد إنشاء القالب، نوجهه مباشرة لصفحة التعديل لوضع الحقول
        return redirect()->route('templates.edit', $template);
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(Template $template)
    {
        if (auth()->id() !== $template->user_id) {
            abort(403);
        }

        $fileContent = Storage::disk('private')->get($template->original_file_path);
        $base64Pdf = base64_encode($fileContent);

        // Determine total pages of the template PDF
        $pdf = new Fpdi();
        $numPages = $pdf->setSourceFile(Storage::disk('private')->path($template->original_file_path));

        return view('templates.edit', [
            'template'  => $template,
            'base64Pdf' => $base64Pdf,
            'numPages'  => $numPages,
        ]);
    }

    /**
     * Update the specified template in storage.
     */
    public function update(Request $request, Template $template)
    {
        if (auth()->id() !== $template->user_id) {
            abort(403);
        }

        // هذا الجزء مخصص لحفظ الحقول فقط من صفحة التعديل
        if ($request->has('fields')) {
            $template->update(['fields' => $request->input('fields', [])]);
            return response()->json(['message' => 'تم حفظ أماكن الحقول بنجاح!']);
        }

        // يمكن إضافة منطق تعديل اسم القالب هنا لاحقًا
        return redirect()->route('templates.index');
    }

    /**
     * Remove the specified template from storage.
     */
    public function destroy(Template $template)
    {
        if (auth()->id() !== $template->user_id) {
            abort(403);
        }
        
        Storage::disk('private')->delete($template->original_file_path);
        $template->delete();

        return back()->with('success', 'تم حذف القالب بنجاح.');
    }
}