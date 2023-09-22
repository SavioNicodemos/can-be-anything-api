<?php

namespace App\Services;

use App\Exceptions\NotAuthorizedException;
use App\Models\Image;
use Illuminate\Support\Facades\File;

class ImageService
{
    public function storeImage($imageFromRequest, string $folder): array
    {
        $originalName = $imageFromRequest->getClientOriginalName();
        $extension = $imageFromRequest->getClientOriginalExtension();

        $md5Name = md5_file($imageFromRequest->getRealPath());
        $guessExtension = $imageFromRequest->guessExtension();

        $newName = $md5Name.'.'.$guessExtension;
        $imageFromRequest->storeAs($folder, $newName, 'public');

        return [
            'name' => $newName,
            'original_name' => $originalName,
            'format' => $extension,
            'folder' => $folder,
        ];
    }

    public function deleteImageLocally(Image $image): bool
    {
        $imagePath = $this->getImagePath($image);
        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }

        return true;
    }

    public function getImagePath(Image $image): string
    {
        return public_path('storage').'/'.$image['folder'].'/'.$image['name'];
    }

    /**
     * @throws NotAuthorizedException
     */
    public function removeProductImages(array $imageIds): bool
    {
        $baseQuery = Image::with('imageable')
            ->whereIn('id', $imageIds)
            ->where('imageable_type', '=', 'App\Models\Product');

        $images = $baseQuery->get();

        $this->checkImagesOwnership($images);

        foreach ($images as $image) {
            $this->deleteImageLocally($image);
        }

        $baseQuery->delete();

        return true;
    }

    /**
     * @throws NotAuthorizedException
     */
    public function checkImagesOwnership($images): bool
    {
        foreach ($images as $image) {
            if ($image['imageable']['user_id'] !== auth()->user()->id) {
                throw new NotAuthorizedException('product images');
            }
        }

        return true;
    }
}
