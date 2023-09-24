<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Support\Facades\File;
use Response;

class ViewImageController extends Controller
{
    use ApiResponser;

    /**
     * Handle the incoming request.
     */
    public function __invoke(string $imageName)
    {
        $path = public_path() . "/storage/avatars/" . $imageName;

        if (!File::exists($path)) {
            return $this->errorResponse('File not found.', 404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        return Response::make($file)->header('Content-Type', $type);
    }
}
