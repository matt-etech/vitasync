<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\ControlledDrugRegisterEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreControlledDrugRegisterEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'home_id' => ['required', 'integer', Rule::exists('homes', 'id')->where('status', 'active')],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'transaction_type' => ['required', Rule::in(array_keys(ControlledDrugRegisterEntry::TRANSACTION_TYPES))],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'drug_name' => ['required', 'string', 'max:255'],
            'drug_name_other' => ['nullable', 'string', 'max:255'],
            'form' => ['nullable', 'string', 'max:120'],
            'form_other' => ['nullable', 'string', 'max:120'],
            'strength' => ['nullable', 'string', 'max:120'],
            'unit' => ['required', 'string', 'max:40'],
            'unit_other' => ['nullable', 'string', 'max:40'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'actual_balance_after' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'discrepancy_reason' => ['nullable', 'string', 'max:2000'],
            'discrepancy_reason_other' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'required_if:transaction_type,administered', 'string', 'max:2000'],
            'reason_other' => ['nullable', 'string', 'max:2000'],
            'source_or_destination' => ['nullable', 'string', 'max:255'],
            'source_or_destination_other' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:120'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'witness_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'witness_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $transactionType = (string) $this->input('transaction_type');

                if ($transactionType === 'administered' && blank($this->input('client_id'))) {
                    $validator->errors()->add('client_id', 'Select the service user who received the controlled drug.');
                }

                foreach ([
                    'drug_name' => ['other' => 'drug_name_other', 'label' => 'controlled drug name'],
                    'form' => ['other' => 'form_other', 'label' => 'form'],
                    'unit' => ['other' => 'unit_other', 'label' => 'unit'],
                    'source_or_destination' => ['other' => 'source_or_destination_other', 'label' => 'source or destination'],
                    'reason' => ['other' => 'reason_other', 'label' => 'reason'],
                    'discrepancy_reason' => ['other' => 'discrepancy_reason_other', 'label' => 'discrepancy reason'],
                ] as $field => $other) {
                    if ($this->input($field) === ControlledDrugRegisterEntry::OTHER_VALUE && blank($this->input($other['other']))) {
                        $validator->errors()->add($other['other'], 'Enter the '.$other['label'].' when Other is selected.');
                    }
                }

                if (filled($this->input('client_id')) && filled($this->input('home_id'))) {
                    $clientInHome = Client::query()
                        ->whereKey((int) $this->input('client_id'))
                        ->where('home_id', (int) $this->input('home_id'))
                        ->exists();

                    if (! $clientInHome) {
                        $validator->errors()->add('client_id', 'The selected service user must belong to the selected home.');
                    }
                }

                if ((int) $this->input('witness_user_id') === (int) $this->user()?->id) {
                    $validator->errors()->add('witness_user_id', 'The witness must be a second person, not the recorder.');
                }

                if (in_array($transactionType, ControlledDrugRegisterEntry::WITNESS_REQUIRED_TYPES, true)
                    && blank($this->input('witness_user_id'))
                    && blank($this->input('witness_name'))) {
                    $validator->errors()->add('witness_name', 'Record a second person witness for this controlled drug entry.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required_if' => 'Record the reason for administration.',
            'occurred_at.before_or_equal' => 'Controlled drug entries cannot be dated in the future.',
        ];
    }
}
