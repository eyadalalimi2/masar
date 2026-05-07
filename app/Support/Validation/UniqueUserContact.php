<?php

namespace App\Support\Validation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UniqueUserContact implements ValidationRule
{
    /**
     * @var array<string, array<int, array{table:string,column:string}>>
     */
    private const SOURCES = [
        'phone' => [
            ['table' => 'accounts', 'column' => 'phone'],
            ['table' => 'suppliers', 'column' => 'phone'],
            ['table' => 'agents', 'column' => 'phone'],
            ['table' => 'customers', 'column' => 'phone'],
            ['table' => 'consumers', 'column' => 'phone'],
            ['table' => 'admins', 'column' => 'phone'],
        ],
        'email' => [
            ['table' => 'suppliers', 'column' => 'email'],
            ['table' => 'agents', 'column' => 'email'],
        ],
    ];

    /**
     * @var array<int, array{table:string,id:int|string|null}>
     */
    private array $ignores;

    /**
     * @param 'phone'|'email' $type
     * @param array<int, array{table:string,id:int|string|null}> $ignores
     */
    public function __construct(
        private readonly string $type,
        array $ignores = []
    ) {
        $this->ignores = array_values(array_filter(
            $ignores,
            fn(array $ignore): bool => isset($ignore['table'])
        ));
    }

    /**
     * @return array{table:string,id:int|string|null}
     */
    public static function ignore(string $table, int|string|null $id): array
    {
        return [
            'table' => $table,
            'id' => $id,
        ];
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $candidate = is_string($value) ? trim($value) : '';
        if ($candidate === '') {
            return;
        }

        $sources = self::SOURCES[$this->type] ?? [];
        foreach ($sources as $source) {
            if (! Schema::hasTable($source['table']) || ! Schema::hasColumn($source['table'], $source['column'])) {
                continue;
            }

            $query = DB::table($source['table'])->where($source['column'], $candidate);
            foreach ($this->ignores as $ignore) {
                if ($ignore['table'] !== $source['table'] || $ignore['id'] === null || $ignore['id'] === '') {
                    continue;
                }

                $query->where('id', '!=', $ignore['id']);
            }

            if ($query->exists()) {
                $translated = trans('validation.unique', ['attribute' => $attribute]);

                if (! is_string($translated) || trim($translated) === '' || $translated === 'validation.unique') {
                    $translated = 'القيمة المدخلة في حقل ' . $attribute . ' مستخدمة مسبقًا.';
                }

                $fail($translated);

                return;
            }
        }
    }
}
