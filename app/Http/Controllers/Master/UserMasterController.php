<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreUserRequest;
use App\Http\Requests\Master\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserMasterController extends Controller
{
    public function create(): RedirectResponse
    {
        $this->authorize('create', User::class);

        return redirect()->route('master.pengguna.index');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::query()->create($request->validated());

        return redirect()->route('master.pengguna.index')->with('status', 'Pengguna disimpan.');
    }

    public function edit(User $pengguna): RedirectResponse
    {
        $this->authorize('update', $pengguna);

        return redirect()->route('master.pengguna.index');
    }

    public function update(UpdateUserRequest $request, User $pengguna): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['kata_sandi'])) {
            unset($data['kata_sandi']);
        }
        $pengguna->update($data);

        return redirect()->route('master.pengguna.index')->with('status', 'Pengguna diperbarui.');
    }

    public function destroy(User $pengguna): RedirectResponse
    {
        $this->authorize('delete', $pengguna);

        if ($pengguna->peran === 'admin') {
            $pengguna->update(['aktif' => false]);

            return redirect()->route('master.pengguna.index')->with('status', 'Admin dinonaktifkan.');
        }

        $pengguna->update(['aktif' => false]);

        return redirect()->route('master.pengguna.index')->with('status', 'Pengguna dinonaktifkan.');
    }
}
