<?php

namespace Bleuren\SocialiteUnify\Actions\Fortify;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    public function update(User $user, array $input): void
    {
        $rules = [
            'password' => $this->passwordRules(),
        ];

        if ($user->has_password) {
            $rules['current_password'] = ['required', 'string', 'current_password:web'];
        }

        Validator::make($input, $rules, [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        $user->forceFill([
            'password' => Hash::make($input['password']),
            'has_password' => true,
        ])->save();
    }
}
