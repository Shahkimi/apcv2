<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('admin::kawalan.user.index');
    }

    public function datatable()
    {
        return DataTables::of(User::query())
            ->addColumn('role_label', function (User $user) {
                $label = match ($user->role) {
                    User::ROLE_ADMIN => __('Admin'),
                    User::ROLE_MEDIA => __('Media'),
                    default => __('User'),
                };

                $classes = match ($user->role) {
                    User::ROLE_ADMIN => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                    User::ROLE_MEDIA => 'bg-primary/10 text-primary',
                    default => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                };

                return '<span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium '.$classes.'">'.e($label).'</span>';
            })
            ->addColumn('created_at', fn (User $user) => $user->created_at?->format('d M Y') ?? '')
            ->addColumn('action', fn (User $user) => view('admin::kawalan.user.actions', ['rowUser' => $user])->render())
            ->rawColumns(['action', 'role_label'])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)],
            'role' => ['required', 'integer', Rule::in([User::ROLE_USER, User::ROLE_MEDIA, User::ROLE_ADMIN])],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'role' => (int) $validated['role'],
            'password' => $validated['password'],
        ]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $passwordRules = $request->filled('password')
            ? ['required', 'confirmed', Password::defaults()]
            : ['nullable'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'role' => ['required', 'integer', Rule::in([User::ROLE_USER, User::ROLE_MEDIA, User::ROLE_ADMIN])],
            'password' => $passwordRules,
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->role = (int) $validated['role'];

        if ($request->filled('password')) {
            $user->password = $validated['password'];
        }

        $user->save();

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()?->id) {
            abort(403);
        }

        $user->delete();

        return response()->json(['success' => true]);
    }
}
