<?php

namespace App\Http\Requests;

use App\Reverb\FileApplicationProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAppRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'key' => ['sometimes', 'string', 'min:16', 'max:255'],
            'secret' => ['sometimes', 'string', 'min:32', 'max:255'],
            'allowed_origins' => ['sometimes', 'array'],
            'allowed_origins.*' => ['string'],
            'enable_client_messages' => ['sometimes', 'boolean'],
            'max_connections' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'max_message_size' => ['sometimes', 'integer', 'min:1', 'max:10000000'],
            'options' => ['sometimes', 'array'],
            'options.host' => ['sometimes', 'string'],
            'options.port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'options.scheme' => ['sometimes', 'string', 'in:http,https'],
            'options.useTLS' => ['sometimes', 'boolean'],
            'options.ping_interval' => ['sometimes', 'integer', 'min:1', 'max:3600'],
            'options.activity_timeout' => ['sometimes', 'integer', 'min:1', 'max:3600'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('key')) {
                $provider = app(FileApplicationProvider::class);
                $appId = $this->route('app');
                if ($provider->keyExists($this->input('key'), $appId)) {
                    $validator->errors()->add('key', 'The key has already been taken.');
                }
            }
        });
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'secret.min' => 'The secret must be at least 32 characters for security.',
            'key.min' => 'The key must be at least 16 characters.',
        ];
    }
}
