<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show()
    {
        return Inertia::render('Profile/Show', [
            'user' => Auth::user(),
            'mustChangePassword' => session('must_change_password') === true,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'photo' => ['nullable', 'image', 'max:10240'], // 10MB Max
            'signature' => ['nullable', 'image', 'max:10240'], // 10MB Max
            'signature_data' => ['nullable', 'string'], // Base64 from canvas
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $file = $request->file('photo');
            $filename = hash_file('sha256', $file->getRealPath()) . '.jpg';
            $path = 'profile-photos/' . $filename;

            // Compress and Resize Image
            $sourceImage = imagecreatefromstring(file_get_contents($file));
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);
            
            $maxWidth = 800;
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = floor($height * ($maxWidth / $width));
                $tempImage = imagescale($sourceImage, $newWidth, $newHeight);
                imagedestroy($sourceImage);
                $sourceImage = $tempImage;
            }

            // Capture output buffer
            ob_start();
            imagejpeg($sourceImage, null, 75); // 75% Quality
            $imageContents = ob_get_clean();
            imagedestroy($sourceImage);

            Storage::disk('public')->put($path, $imageContents);

            $validated['profile_photo_path'] = $path;
        }

        // Handle signature from file upload
        if ($request->hasFile('signature')) {
            // Delete old signature if exists
            if ($user->signature_path) {
                Storage::disk('public')->delete($user->signature_path);
            }

            $file = $request->file('signature');
            $filename = hash_file('sha256', $file->getRealPath()) . '.png';
            $path = 'signatures/' . $filename;

            // Compress and Resize Image
            $sourceImage = imagecreatefromstring(file_get_contents($file));
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);
            
            $maxWidth = 800;
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = floor($height * ($maxWidth / $width));
                $tempImage = imagescale($sourceImage, $newWidth, $newHeight);
                imagedestroy($sourceImage);
                $sourceImage = $tempImage;
            }

            // Capture output buffer for PNG with transparency
            ob_start();
            imagepng($sourceImage, null, 9); // PNG compression 9
            $imageContents = ob_get_clean();
            imagedestroy($sourceImage);

            Storage::disk('public')->put($path, $imageContents);

            $validated['signature_path'] = $path;
        }
        // Handle signature from canvas drawing (base64)
        elseif (!empty($validated['signature_data'])) {
            $base64 = $validated['signature_data'];

            // Validate and extract base64 data
            if (preg_match('/^data:image\/png;base64,(.+)$/', $base64, $matches)) {
                $imageData = base64_decode($matches[1]);

                if ($imageData !== false) {
                    // Delete old signature if exists
                    if ($user->signature_path) {
                        Storage::disk('public')->delete($user->signature_path);
                    }

                    $filename = hash('sha256', $imageData) . '.png';
                    $path = 'signatures/' . $filename;

                    Storage::disk('public')->put($path, $imageData);

                    $validated['signature_path'] = $path;
                }
            }
        }

        // Remove non-model fields before update
        unset($validated['signature_data']);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        session()->forget('must_change_password');

        return back()->with('success', 'Password updated successfully.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
