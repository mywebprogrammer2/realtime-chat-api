<?php

namespace App\Http\Controllers;

use App\Facades\ReusableFacades;
use App\Models\User;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Password;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{


    //
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email']);

        if($user->exists()){
            if($user->first()->status < 1) {
                return ReusableFacades::createResponse(false,[],'Account not activated,Contact Administrator',[],401);
            }
        }

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return ReusableFacades::createResponse(true,[
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->load(['user_detail','roles','permissions'])->append('all_permissions') ]);
        }
        return ReusableFacades::createResponse(false,[],'Invalid credentials',[],401);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete(); // revoke all tokens

        return ReusableFacades::createResponse(true,[],'Logged out successfully');
    }

    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole('User');


        return ReusableFacades::createResponse(true, [
            'user' => $user->load(['user_detail', 'roles', 'permissions'])->append('all_permissions'),
        ], 'User Created Successfully');
    }


     /**
     * Change the user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return ReusableFacades::createResponse(false, [], 'The current password is incorrect.', [], 400);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        return ReusableFacades::createResponse(true, [], 'Password has been changed successfully.');
    }


    /**
     * Reset the user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $response = $this->broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = bcrypt($password);
                $user->save();
            }
        );

        if ($response === Password::PASSWORD_RESET) {
            return ReusableFacades::createResponse(true, [], 'Password has been reset successfully.');
        } else {
            return $this->getFailedResponse($response);
        }
    }


    /**
     * Send a password reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->getFailedResponse(Password::INVALID_USER);
        }

        $user->sendPasswordResetNotification($this->broker()->createToken($user));

        return ReusableFacades::createResponse(true,[],'Reset link sent to your email address.');
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }


    /**
     * Get the failed response for the password reset request.
     *
     * @param  string  $response
     * @return \Illuminate\Http\JsonResponse
     */
    protected function getFailedResponse($response)
    {
        $errorMessages = [
            Password::INVALID_USER => 'We cannot find a user with that email address.',
            Password::INVALID_TOKEN => 'This password reset token is invalid.',
            Password::RESET_THROTTLED => 'Too many reset attempts. Please try again later.',
        ];

        return ReusableFacades::createResponse(false,[], $errorMessages[$response],[],400);
    }

}
