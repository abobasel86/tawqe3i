<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the user's dashboard.
     */
    public function index(Request $request) {
    $user = Auth::user();
    $query = $user->documents();

    if ($request->has('folder')) {
        $folderId = $request->input('folder');
        $query->whereHas('folders', function ($q) use ($folderId) {
            $q->where('folders.id', $folderId);
        });
    }

    $documents = $query->latest()->get();
    
    return view('dashboard', [
        'documents' => $documents
    ]);
}
    
}