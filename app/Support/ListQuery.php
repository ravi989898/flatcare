<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Search / sort / rows-per-page for the listing pages, driven by the query
 * string the fc Blade components write (?search=, ?sort=, ?dir=,
 * ?per_page=). Sort keys are whitelisted per page, so a request can never
 * order by an arbitrary column.
 */
final class ListQuery
{
    /** Choices offered by the rows-per-page picker (components/fc/footer). */
    public const PER_PAGE = [10, 25, 50, 100];

    public static function perPage(Request $request, int $default = 25): int
    {
        $perPage = (int) $request->query('per_page', $default);

        return in_array($perPage, self::PER_PAGE, true) ? $perPage : $default;
    }

    public static function search(Request $request, string $key = 'search'): ?string
    {
        $term = trim((string) $request->query($key, ''));

        return $term === '' ? null : mb_substr($term, 0, 100);
    }

    /**
     * Where any of $columns matches the ?search= term. A column may be
     * 'relation.column' (or deeper) to search a related model.
     *
     * @param  array<int, string>  $columns
     */
    public static function applySearch(Builder $query, Request $request, array $columns, string $key = 'search'): void
    {
        $term = self::search($request, $key);
        if ($term === null) {
            return;
        }

        $like = '%'.addcslashes($term, '\\%_').'%';

        $query->where(function ($q) use ($columns, $like) {
            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    $relation = substr($column, 0, strrpos($column, '.'));
                    $field = substr($column, strrpos($column, '.') + 1);
                    $q->orWhereHas($relation, fn ($r) => $r->where($field, 'like', $like));
                } else {
                    $q->orWhere($column, 'like', $like);
                }
            }
        });
    }

    /**
     * The requested [sort key, direction] if the key is allowed, otherwise
     * the page's default.
     *
     * @param  array<int, string>  $allowed
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    public static function sortKey(Request $request, array $allowed, string $default, string $defaultDir = 'desc'): array
    {
        $sort = (string) $request->query('sort', '');
        if (!in_array($sort, $allowed, true)) {
            return [$default, $defaultDir === 'asc' ? 'asc' : 'desc'];
        }

        return [$sort, $request->query('dir') === 'asc' ? 'asc' : 'desc'];
    }

    /**
     * Orders $query by the requested sort key. Each entry of $sorts maps a
     * key to a column name or to fn (Builder $query, string $dir).
     *
     * @param  array<string, string|Closure>  $sorts
     */
    public static function applySort(Builder $query, Request $request, array $sorts, string $default, string $defaultDir = 'desc'): void
    {
        [$sort, $dir] = self::sortKey($request, array_keys($sorts), $default, $defaultDir);

        $by = $sorts[$sort];
        $by instanceof Closure ? $by($query, $dir) : $query->orderBy($by, $dir);
    }

    /**
     * Same as applySort, for pages that filter an in-memory collection.
     * Each entry maps a key to a field name or to fn ($item) => sort value.
     *
     * @param  array<string, string|Closure>  $sorts
     */
    public static function sortCollection(Collection $items, Request $request, array $sorts, string $default, string $defaultDir = 'desc'): Collection
    {
        [$sort, $dir] = self::sortKey($request, array_keys($sorts), $default, $defaultDir);

        return $items->sortBy($sorts[$sort], SORT_NATURAL | SORT_FLAG_CASE, $dir === 'desc')->values();
    }

    /** One page of an in-memory collection, keeping the query string. */
    public static function paginateCollection(Collection $items, Request $request, int $perPage): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }
}
