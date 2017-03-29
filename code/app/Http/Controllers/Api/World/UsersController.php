<?php

namespace Thaliak\Http\Controllers\Api\World;

use Cookie;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Thaliak\Http\Controllers\Controller;
use Thaliak\Models\User;
use Thaliak\Support\User as UserSupport;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('api-auth', ['except' => ['create', 'verify']]);
        $this->middleware('guest', ['only' => ['create', 'verify']]);
    }

    public function index(Request $request): LengthAwarePaginator
    {
        return User::paginate();
    }

    public function totals(): Array
    {
        return [
            'total' => User::count(),
            'unverified' => User::unverified()->count()
        ];
    }

    public function search(Request $request): Collection
    {
        $this->validate($request, ['name' => 'required|string']);
        return User::where('name', 'LIKE', "%{$request->name}%")->get();
    }

    public function create(Request $request): User
    {
        $this->validate($request, [
            'name' => 'required|alpha_num_spaces|max:255|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = UserSupport::create(
            $request->only(['name', 'email', 'password'])
        );

        return $user->makeHidden('verification');
    }

    public function verify(Request $request): User
    {
        $user = User::byVerification($request->code)->firstOrFail();
        return $user->verify()->activate();
    }

    public function get(Request $request): User
    {
        return $request->user;
    }

    public function characters(Request $request): Collection
    {
        return $request
            ->user
            ->characters()
            ->with('verification', 'profile')
            ->world($request->route('world'))
            ->get();
    }

    public function update(Request $request)
    {
        $this->validate($request, ['password' => 'min:6|confirmed']);

        if (!Hash::check($request->current_password, $request->user->password)) {
            return response([
                'current_password' => ["Doesn't match current password"]
            ], 422);
        }

        $user = $request->user;

        if ($request->name && $request->name != $request->user->name) {
            $this->validate($request, ['name' => 'max:255|unique:users']);
            $user->name = $request->name;
        }

        if ($request->email && $request->email != $request->user->email) {
            $this->validate($request, ['email' => 'email|max:255|unique:users']);
            $user->email = $request->email;
            $user->verified = false;
            UserSupport::createVerificationCode($user);
        }

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return $user;
    }

    public function updateState(Request $request): User
    {
        $this->validate($request, [
            'verified' => 'boolean',
            'active' => 'boolean'
        ]);

        $request->user->update($request->only(['verified', 'active']));

        return $request->user->fresh();
    }

    public function clearToken(Request $request)
    {
        Cookie::forget('auth');
        $request->user->token()->revoke();
    }

    public function delete(Request $request): User
    {
        $request->user->delete();
        return $request->user;
    }
}