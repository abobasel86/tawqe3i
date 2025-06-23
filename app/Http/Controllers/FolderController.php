<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class FolderController extends Controller
{
    public function store(Request $request) {
        $request->validate(['name' => 'required|string|max:100']);
        $request->user()->folders()->create(['name' => $request->name]);
        return back()->with('success', 'تم إنشاء المجلد بنجاح.');
    }
}