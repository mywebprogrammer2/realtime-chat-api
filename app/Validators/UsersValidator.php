<?php

namespace App\Validators;

use \Prettus\Validator\Contracts\ValidatorInterface;
use \Prettus\Validator\LaravelValidator;

/**
 * Class UsersValidator.
 *
 * @package namespace App\Validators;
 */
class UsersValidator extends LaravelValidator
{
    /**
     * Validation Rules
     *
     * @var array
     */
    protected $rules = [
        ValidatorInterface::RULE_CREATE => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:8',
            'role' => 'required|integer|exists:roles,id',
        ],
        ValidatorInterface::RULE_UPDATE => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,:id|max:255',
            'password' => 'string|min:8',
            'role' => 'required|integer|exists:roles,id'
        ],
    ];
}
