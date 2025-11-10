<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|min:3',
            'phone' => 'required|string|unique:contacts,phone|regex:/^[0-9\s\-\(\)]{8,20}$/',
            'email' => 'nullable|email|max:255|unique:contacts,email',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'name.min' => 'Name must have at least 3 characters',
            'phone.required' => 'Phone is required',
            'phone.unique' => 'This phone number is already registered',
            'phone.regex' => 'Invalid phone format',
            'email.email' => 'Invalid email format',
            'email.unique' => 'This email is already registered',
            'image.image' => 'File must be an image',
            'image.mimes' => 'Image must be JPEG, PNG, JPG or GIF',
            'image.max' => 'Image cannot exceed 2MB',
        ];
    }
}
