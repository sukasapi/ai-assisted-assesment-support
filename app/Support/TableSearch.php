<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;

final class TableSearch
{
    /**
     * @param  list<string|Closure(Builder, string): void>  $handlers
     */
    public static function apply(Builder $query, ?string $term, array $handlers): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term, $handlers) {
            foreach ($handlers as $handler) {
                if ($handler instanceof Closure) {
                    $handler($q, $term);
                } else {
                    $q->orWhere($handler, 'like', '%'.$term.'%');
                }
            }
        });
    }
}
