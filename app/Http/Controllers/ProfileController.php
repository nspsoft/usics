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
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'photo' => ['nullable', 'image', 'max:10240'], // 10MB Max
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
