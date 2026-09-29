<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class UserController extends Controller
{
    /**
     * Display the list of users.
     */
    public function index()
    {
        $users = User::orderBy('approval')
            ->orderBy('name')
            ->get();

        return view(
            'app.usermanagement.index',
            compact('users')
        );
    }


    /**
     * Update user approval and role.
     */
    public function update(Request $request, string $id)
    {
        try {
            $userId = Crypt::decryptString($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'approval' => 'required|in:0,1',
            'role' => 'required|in:0,1',
        ]);

        $user->update([
            'approval' => $validated['approval'],
            'role' => $validated['role'],
        ]);

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'User access successfully updated.'
            );
    }
}
