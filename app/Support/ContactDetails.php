<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContactDetails
{
    public static function validate(array $input, User $owner, ?Contact $contact = null): array
    {
        foreach ($input as $field => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $input[$field] = $value === '' ? null : $value;
            }
        }

        if (is_string($input['email'] ?? null)) {
            $input['email'] = mb_strtolower($input['email']);
        }

        $uniqueEmail = Rule::unique('contacts', 'email')->where('user_id', $owner->id);
        if ($contact) {
            $uniqueEmail->ignore($contact);
        }

        return Validator::make($input, [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:254', $uniqueEmail],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:40'],
            'organisation' => ['nullable', 'string', 'max:160'],
        ])->validate();
    }
}
