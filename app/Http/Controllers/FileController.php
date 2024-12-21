<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    /**
     * Delete a file from the given path.
     *
     * @param string $filePath
     * @return bool
     */
    public function deleteFile($filePath): bool
    {
        try {
            // Ensure the file path is absolute and not relative for security reasons
            $absolutePath = base_path($filePath);
            
            if (file_exists($absolutePath)) {
                // Delete the file
                unlink($absolutePath);
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Log::error('File deletion error: '.$e->getMessage());
            return false;
        }
    }
}
