<?php

namespace App\Services;

use App\Exceptions\NotAuthorizedException;
use App\Models\Image;
use App\Models\User;
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
    public function checkImagesOwnership($images): bool
    {
        $userId = User::getLoggedUserId();

        foreach ($images as $image) {
            if ($image['imageable']['user_id'] !== $userId) {
                throw new NotAuthorizedException('product images');
            }
        }

        return true;
    }
}
