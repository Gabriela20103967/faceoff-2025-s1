<?php

namespace App\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
  /**
   * Display a listing of the Users
   *
   * @return void
   */
  public function index()
  {
    $users = User::paginate(5);
    return view('users.index', compact(['users',]));
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return void
   */
  public function create()
  {
      $roles = Role::all();

      return view('users.create', compact('roles'));
  }

  /**
   * Store a newly created resource in storage
   *
   * @param Request $request
   * @return void
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'given_name' => ['required_without:family_name', 'nullable', 'min:2', 'max:255', 'string', 'sometimes'],
      'family_name' => ['nullable', 'min:2', 'max:255', 'string', 'sometimes'],
      'name' => ['nullable', 'min:2', 'max:255', 'string'],
      'preferred_pronouns' => ['required', 'min:2', 'max:10', 'string'],
      'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class,],
      'password' => ['required', 'confirmed', 'min:4', 'max:255', Password::defaults(),],
      'profile_photo' => ['nullable', 'min:4', 'max:255'],
      'role' => ['required', 'exists:roles,id']
    ]);


    /**
     * Check if name is not provided use given / family name as default
     */
    if (empty($request->name)) {
      if ($validated['given_name'] != null) {
        $validated['name'] = $validated['given_name'];
      } else {
        $validated['name'] = $validated['family_name'];
      }
    }

    if ($request->hasFile('profile_photo')) {
      $path = $request->file('profile_photo')->store('profile_photos', 'public');
      $validated['profile_photo'] = $path;
    } else {
      $validated['profile_photo'] = "avatar.png";
    }

    /**
     * Create user after validated
     */
      $user = User::create($validated);

      // Assign the role to the user
      $user->roles()->attach($request->role);

    /**
     * Redirect with success message
     */
    return redirect(route('users.index'))->with('success', 'User created');

  }


  /**
   * Display the specified resource.
   *
   * @param string $id
   * @return void
   */
  public function show(string $id)
  {
    $user = User::whereId($id)->get()->first();

    if ($user) {
      return view('users.show', compact(['user',]))->with('success', 'User found');
    } else {
      return redirect(route('users.index'))->with('warning', 'User not found');
    }
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param string $id
   * @return void
   */
  public function edit(string $id)
  {
      $user = User::find($id);

      if ($user) {
          $roles = Role::all();
          return view('users.update', compact('user', 'roles'))->with('success', 'User Found');
      } else {
          return redirect(route('users.index'))->with('error', 'User not found');
      }
  }

  /**
   * Update the specified resource in storage.
   *
   * @param Request $request
   * @param string $id
   * @return void
   */
  public function update(Request $request, string $id)
  {
    if (!$request->password) {
      unset($request['password'], $request['password_confirmation']);
    }

    $validated = $request->validate([
      'given_name' => ['nullable', 'min:2', 'max:255', 'string', 'required_without:family_name'],
      'family_name' => ['nullable', 'min:2', 'max:255', 'string', 'required_without:given_name'],
      'name' => ['nullable', 'min:2', 'max:255', 'string'],
      'preferred_pronouns' => ['required', 'min:2', 'max:10', 'string'],
      'email' => ['required', 'min:5', 'max:255', 'email', Rule::unique(User::class)->ignore($id),],
      'password' => $request->password ? ['required', 'confirmed', 'min:4', 'max:255', Password::defaults()] : [],
      'profile_photo' => ['nullable', 'file', 'mimes:jpg,png,jpeg', 'max:51200'],
      'role' => ['required', 'exists:roles,id']
    ]);

    $user = User::where('id', '=', $id)->get()->first();

    /**
     * Check if name is not provided use given / family name as default
     */
    if (empty($request->name)) {
      if ($validated['given_name'] != null) {
        $validated['name'] = $validated['given_name'];
      } else {
        $validated['name'] = $validated['family_name'];
      }
    }

    // sync user role
    $user->roles()->sync([$request->role]);

    if ($request->user()->isDirty('email')) {
      $request->user()->email_verified_at = null;
    }

    if ($request->hasFile('profile_photo')) {
      $path = $request->file('profile_photo')->store('profile_photos', 'public');
      $user->profile_photo = $path;
      $user->save();
    }


    $user->update([
      'given_name' => $validated['given_name'],
      'family_name' => $validated['family_name'],
      'name' => $validated['name'] ?? ($validated['given_name'] ?? $validated['family_name']),
      'preferred_pronouns' => $validated['preferred_pronouns'],
      'email' => $validated['email'],
      'profile_photo' => $validated['profile_photo'] ?? $user->profile_photo,
    ]);

    if ($request->filled('password')) {
      $user->password = bcrypt($validated['password']);
      $user->save();
    }

    if ($request->hasFile('profile_photo')) {
      $path = $request->file('profile_photo')->store('profile_photos', 'public');
      $user->profile_photo = $path;
      $user->save();
    }

    return redirect(route('users.show', compact(['user'])))->with('success', 'User updated');
  }

  public function destroy(string $id)
  {
    $user = User::where('id', '=', $id)->get()->first();

    if ($user) {
      $user->delete();
      return redirect(route('users.index'))->with('success', 'User deleted');
    } else {
      return back()->with('error', 'User not Found');
    }
  }
}
