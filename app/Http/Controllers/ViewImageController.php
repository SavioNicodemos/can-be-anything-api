<?php

namespace App\Http\Controllers;

use App\Models\Image;
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
        $image = Image::where('name', $imageName)->first();
        if (! $image) {
            abort(404);
        }

        $path = public_path()."/storage/{$image->folder}/".$imageName;

        if (! File::exists($path)) {
            abort(404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        return Response::make($file, 200)->header('Content-Type', $type);
    }
}
