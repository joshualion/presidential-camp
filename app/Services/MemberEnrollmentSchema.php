<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Validation\Rule;

class MemberEnrollmentSchema
{
    /** Shared by online profile completion and the blank offline enrollment form. */
    public function profileRules(User $user): array
    {
        $requireBank = (bool) SystemSetting::first()?->require_bank_details;

        return [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'required|string|max:15|unique:users,phone,'.$user->id,
            'validvoter' => 'required|in:yes,no',
            'vin' => 'nullable|string|max:255',
            'age_grade_id' => 'required|exists:age_grades,id',
            'gender' => 'required|in:male,female',
            'religion_id' => 'required|exists:religions,id',
            'bank' => $requireBank ? 'required|string|max:255' : 'nullable|string|max:255',
            'bank_account_number' => $requireBank ? 'required|string|size:10' : 'nullable|string|size:10',
            'address' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
