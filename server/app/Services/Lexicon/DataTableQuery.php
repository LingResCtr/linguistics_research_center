<?php

namespace App\Services\Lexicon;

use App\Models\LexLexicon;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Answers a DataTables request against the lexicon data cache
 * (lex_lexicon_data_cache, one JSON row per reflex per viewer locale).
 *
 * Only the lexicon's own data columns (LexLexicon::getDataColumns()) may be
 * searched or ordered; anything else raises InvalidArgumentException. The
 * caller is expected to have validated shapes and lengths already
 * (App\Http\Requests\LexiconDataRequest).
 */
class DataTableQuery
{
    public const DEFAULT_LENGTH = 10;

    public const MAX_LENGTH = 100;

    /** @var list<string> */
    private array $allowedColumns;

    public function __construct(private LexLexicon $lexicon, private string $locale)
    {
        $this->allowedColumns = collect($lexicon->getDataColumns())->pluck('name')->values()->all();
    }

    /**
     * @param  array<string, mixed>  $params  validated DataTables parameters
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: Collection}
     *
     * @throws InvalidArgumentException when a column or order name is not a data column of this lexicon
     * @throws QueryException when the database rejects a search expression (e.g. a bad regular expression)
     */
    public function run(array $params): array
    {
        $columns = $this->columns($params['columns'] ?? []);
        $start = max(0, (int) ($params['start'] ?? 0));
        $length = min(self::MAX_LENGTH, max(1, (int) ($params['length'] ?? self::DEFAULT_LENGTH)));

        $base = DB::table('lex_lexicon_data_cache')
            ->where('lexicon_id', $this->lexicon->id)
            ->where('content_lang_code', $this->locale);

        $total = (clone $base)->count();
        $filtered = clone $base;

        foreach ($columns as $column) {
            $value = (string) ($column['search']['value'] ?? '');
            if ($value === '') {
                continue;
            }
            $this->applyMatch($filtered, $column['name'], $value, self::flag($column['search']['regex'] ?? false));
        }

        $global = (string) ($params['search']['value'] ?? '');
        if ($global !== '') {
            $regex = self::flag($params['search']['regex'] ?? false);
            $names = array_column($columns, 'name');
            $filtered->where(function (Builder $q) use ($names, $global, $regex) {
                foreach ($names as $name) {
                    $q->orWhere('data->'.$name, $regex ? 'REGEXP' : 'LIKE', $regex ? $global : '%'.$global.'%');
                }
            });
        }

        foreach ($params['order'] ?? [] as $order) {
            $name = $this->assertColumn((string) ($order['name'] ?? ''));
            $dir = strtolower((string) ($order['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
            $filtered->orderBy('data->'.$name, $dir);
        }

        $filteredCount = (clone $filtered)->count();
        $rows = $filtered->skip($start)->limit($length)->get()->map(function ($row) {
            $data = json_decode($row->data);
            $data->id = $row->reflex_id;

            return $data;
        });

        return [
            'draw' => (int) ($params['draw'] ?? 0),
            'recordsTotal' => $total,
            'recordsFiltered' => $filteredCount,
            'data' => $rows,
        ];
    }

    /**
     * The columns to search: the request's, each checked against the whitelist,
     * or every data column with no per-column search when none were sent.
     *
     * @param  array<int, array<string, mixed>>  $requested
     * @return list<array{name: string, search: array<string, mixed>}>
     */
    private function columns(array $requested): array
    {
        if ($requested === []) {
            return array_map(fn (string $name) => ['name' => $name, 'search' => []], $this->allowedColumns);
        }

        return array_values(array_map(fn (array $column) => [
            'name' => $this->assertColumn((string) ($column['name'] ?? '')),
            'search' => is_array($column['search'] ?? null) ? $column['search'] : [],
        ], $requested));
    }

    private function assertColumn(string $name): string
    {
        if (! in_array($name, $this->allowedColumns, true)) {
            throw new InvalidArgumentException("Unknown data column '{$name}' for lexicon '{$this->lexicon->slug}'.");
        }

        return $name;
    }

    private function applyMatch(Builder $query, string $name, string $value, bool $regex): void
    {
        $query->where('data->'.$name, $regex ? 'REGEXP' : 'LIKE', $regex ? $value : '%'.$value.'%');
    }

    /**
     * DataTables sends "true"/"false" strings; treat them as booleans rather than
     * as non-empty (truthy) strings.
     */
    private static function flag(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
