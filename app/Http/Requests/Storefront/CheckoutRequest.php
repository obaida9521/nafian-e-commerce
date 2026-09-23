<?php

namespace App\Http\Requests\Storefront;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise Bangla digits and spacing in the phone number before validating.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => normalize_phone((string) $this->input('phone')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^01[3-9][0-9]{8}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', Rule::in(bd_districts())],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in($this->enabledPaymentMethods())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'সঠিক মোবাইল নম্বর দিন (যেমন ০১৭১২৩৪৫৬৭৮)।',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'পুরো নাম',
            'phone' => 'মোবাইল নম্বর',
            'email' => 'ইমেইল',
            'address' => 'সম্পূর্ণ ঠিকানা',
            'city' => 'জেলা',
            'payment_method' => 'পেমেন্ট পদ্ধতি',
        ];
    }

    /**
     * The inside-Dhaka rate applies to the inside district; every other district pays the outside rate.
     */
    public function deliveryZone(): string
    {
        return $this->input('city') === config('shop.inside_city') ? 'inside' : 'outside';
    }

    /**
     * @return list<string>
     */
    private function enabledPaymentMethods(): array
    {
        $general = app(SettingsService::class)->group('general');

        return array_values(array_filter([
            $general['cod_enabled'] ? 'cod' : null,
            $general['mobile_banking_enabled'] ? 'mobile_banking' : null,
            $general['card_enabled'] ? 'card' : null,
        ]));
    }
}
