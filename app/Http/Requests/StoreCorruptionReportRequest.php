<?php

namespace App\Http\Requests;

use App\Http\Controllers\CorruptionReportController;
use App\Models\Entity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCorruptionReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'intake_type' => ['required', 'in:complaint,report'],
            'issue_area' => ['required', 'string', 'max:80', Rule::in(array_keys(CorruptionReportController::issueAreas()))],
            'entity_id' => ['nullable', 'integer', 'exists:entities,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'title' => ['required', 'string', 'min:8', 'max:180'],
            'body' => ['required', 'string', 'min:50', 'max:12000'],
            'reporter_name' => ['nullable', 'string', 'max:160', 'required_if:identity_disclosed,1'],
            'reporter_contact' => ['nullable', 'string', 'max:180'],
            'identity_disclosed' => ['sometimes', 'boolean'],
            'disclosure_consent' => ['exclude_unless:identity_disclosed,1', 'accepted'],
            'evidence_files' => ['nullable', 'array', 'max:5'],
            'evidence_files.*' => [
                'file',
                'max:25600',
                'mimetypes:application/pdf,image/jpeg,image/png,video/mp4',
                'extensions:pdf,jpg,jpeg,png,mp4',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('issue_area') === 'citizenship_residency') {
                if (! $this->filled('entity_id')) {
                    $validator->errors()->add('entity_id', 'Vatandaşlık, muhaceret ve oturum başvuruları için ilgili kurumu seçmelisin.');
                }

                return;
            }

            if ($this->input('issue_area') === 'health') {
                if (! $this->filled('entity_id')) {
                    $validator->errors()->add('entity_id', 'Sağlık hizmetleri için hastane, sağlık ocağı veya ilgili sağlık kurumunu seçmelisin.');

                    return;
                }

                $entity = Entity::query()->find($this->integer('entity_id'));
                $category = $entity ? str($entity->category)->lower()->replace('_', ' ')->toString() : '';
                $name = $entity ? str($entity->name)->lower()->ascii()->toString() : '';

                if ($entity && $category !== 'sağlık' && ! str_contains($name, 'saglik') && ! str_contains($name, 'hastane') && ! str_contains($name, 'klinik') && ! str_contains($name, 'ocak')) {
                    $validator->errors()->add('entity_id', 'Bu konu için yalnızca sağlıkla ilgili kurum seçilebilir.');
                }

                return;
            }

            if (in_array($this->input('issue_area'), ['roads_asphalt', 'municipal_services', 'garbage_environment', 'water_sewerage'], true)) {
                if (! $this->filled('region_id')) {
                    $validator->errors()->add('region_id', 'Bu konu için önce bölge seçmelisin.');
                }

                if (! $this->filled('entity_id')) {
                    $validator->errors()->add('entity_id', 'Bu konu için ilgili belediyeyi seçmelisin.');
                }

                if ($this->filled('entity_id')) {
                    $entity = Entity::query()->find($this->integer('entity_id'));
                    if ($entity && str($entity->category)->lower()->replace('_', ' ')->toString() !== 'belediye') {
                        $validator->errors()->add('entity_id', 'Bu konu için yalnızca ilgili belediye seçilebilir.');
                    }

                    if ($entity && filled($entity->region_id) && (int) $entity->region_id !== $this->integer('region_id')) {
                        $validator->errors()->add('entity_id', 'Seçilen belediye seçilen bölgeyle eşleşmiyor.');
                    }
                }

                return;
            }

            if (! $this->filled('region_id') && ! $this->filled('entity_id')) {
                $validator->errors()->add('region_id', 'Lütfen en az bir bölge veya kurum/şirket seç.');
            }

            if ($this->filled('region_id') && $this->filled('entity_id')) {
                $entity = Entity::query()->find($this->integer('entity_id'));

                if ($entity && filled($entity->region_id) && (int) $entity->region_id !== $this->integer('region_id')) {
                    $validator->errors()->add('entity_id', 'Seçilen kurum/şirket seçilen bölgeyle eşleşmiyor.');
                }
            }
        });
    }
}
