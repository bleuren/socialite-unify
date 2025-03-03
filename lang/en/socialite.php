<?php

return [
    'login_with' => 'Login with :provider',
    'bind' => [
        'title' => 'Social Accounts',
        'description' => 'Manage your connected social accounts for easy login across different platforms.',
        'link' => 'Bind :provider',
        'bound' => 'Bound',
        'connect' => '連結帳號',
        'connected' => 'Connected to this social account',
        'not_connected' => 'Not connected to this social account',
        'unbind' => 'Disconnect',
        'success' => 'Successfully bound :provider account',
        'already_bound' => 'This :provider account is already bound to another user',
        'confirm_unbind_title' => 'Unbind Social Account',
        'confirm_unbind_message' => 'Are you sure you want to unbind your :provider account?',
        'confirm_unbind' => 'Confirm Unbind',
        'unbound_success' => 'Successfully unbound :provider account',
    ],
    'errors' => [
        'unsupported_provider' => 'Unsupported login method',
        'login_failed' => 'Social login failed, please try again later',
        'last_account' => 'Cannot unbind the last social account. Please set a password or bind another account first',
        'unbind_failed' => 'Failed to unbind account, please try again later',
    ],
    'providers' => [
        'line' => 'LINE',
    ],
    'yes' => 'Yes',
    'no' => 'No',
    'social_authenticated_accounts' => 'Connected Social Accounts',
    'password' => [
        'set_description' => 'You are currently using social login. Setting a password will allow you to log in with your email.',
        'set_password' => 'Set Password',
    ],
];
