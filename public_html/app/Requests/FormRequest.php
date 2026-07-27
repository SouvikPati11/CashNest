<?php

declare(strict_types=1);

namespace App\Requests;

use App\Validation\Validator;
use Core\Http\Request;

/**
 * Base form request.
 *
 * Feature modules extend this to declare validation rules for an endpoint's
 * input. Calling `validated()` runs the rules and returns only the validated
 * fields, throwing a ValidationException on failure. No concrete requests exist
 * yet — this is the foundation contract.
 */
abstract class FormRequest
{
    private Validator $validator;

    public function __construct(protected Request $request)
    {
    }

    /**
     * The validation rules for this request.
     *
     * @return array<string, string>
     */
    abstract protected function rules(): array;

    /**
     * Optional custom messages keyed by "field.rule".
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * The data under validation. Override to merge query/route params.
     *
     * @return array<string, mixed>
     */
    protected function data(): array
    {
        return $this->request->all();
    }

    /**
     * Validate and return only the validated fields.
     *
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        $this->validator = new Validator($this->data(), $this->rules(), $this->messages());
        $this->validator->validateOrFail();

        return $this->validator->validated();
    }

    /**
     * The underlying HTTP request.
     */
    public function request(): Request
    {
        return $this->request;
    }
}
