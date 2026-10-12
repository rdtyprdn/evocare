<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    public function update(Request $request)
    {
        $request->validate(
            ['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']],
            ['avatar.required' => 'Pilih foto terlebih dahulu.', 'avatar.image' => 'File harus berupa gambar.',
             'avatar.mimes' => 'Format foto harus JPG, PNG, atau WEBP.', 'avatar.max' => 'Ukuran foto maksimal 2 MB.']
        );

        $user = $request->user();
        $old = $user->avatar_path;
        $user->update(['avatar_path' => $request->file('avatar')->store('avatars', 'public')]);
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return redirect()->to(route('profile.edit').'#foto-profil')->with('status', 'avatar-updated');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return redirect()->to(route('profile.edit').'#foto-profil')->with('status', 'avatar-removed');
    }
}
