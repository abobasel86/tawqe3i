<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class AssetController extends Controller
{
    public function serve($folder, $file)
    {
        $path = public_path(implode(DIRECTORY_SEPARATOR, [$folder, $file]));

        if (!File::exists($path)) {
            abort(404);
        }

        $mime = 'application/javascript';

        return Response::file($path, ['Content-Type' => $mime]);
    }
}