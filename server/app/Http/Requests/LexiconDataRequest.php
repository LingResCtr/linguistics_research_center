<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * DataTables-shaped query for the public lexicon data endpoint
 * (GET /api/v1/lexicon/{lex_slug}/data). Every parameter is optional; the
 * service applies defaults. Column names are checked against the lexicon's
 * own data columns in App\Services\Lexicon\DataTableQuery, since they depend
 * on the lexicon being queried.
 */
class LexiconDataRequest extends FormRequest
{
    public const MAX_LENGTH = 100;

    public const MAX_SEARCH_LENGTH = 200;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        // DataTables sends booleans as the strings "true" / "false".
        $flag = ['nullable', 'in:true,false,1,0'];
        $name = ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'];

        return [
            'draw' => ['nullable', 'integer', 'min:0'],
            'start' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_LENGTH],
            'search' => ['nullable', 'array'],
            'search.value' => ['nullable', 'string', 'max:'.self::MAX_SEARCH_LENGTH],
            'search.regex' => $flag,
            'columns' => ['nullable', 'array', 'max:80'],
            'columns.*.name' => $name,
            'columns.*.search' => ['nullable', 'array'],
            'columns.*.search.value' => ['nullable', 'string', 'max:'.self::MAX_SEARCH_LENGTH],
            'columns.*.search.regex' => $flag,
            'order' => ['nullable', 'array', 'max:3'],
            'order.*.name' => $name,
            'order.*.dir' => ['nullable', 'in:asc,desc'],
        ];
    }

    /**
     * Always answer with JSON: the endpoint is only ever called by script, and a
     * redirect-with-errors would be meaningless to DataTables.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'draw' => (int) $this->input('draw', 0),
            'error' => 'Invalid request parameters.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
