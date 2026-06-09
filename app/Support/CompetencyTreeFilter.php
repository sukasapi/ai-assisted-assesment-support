<?php

namespace App\Support;

use App\Models\Competency;
use App\Models\CompetencyGroup;
use App\Models\CompetencyLevel;
use Illuminate\Support\Collection;

final class CompetencyTreeFilter
{
    /**
     * @param  Collection<int, CompetencyGroup>  $kelompok
     * @return Collection<int, CompetencyGroup>
     */
    public static function filterGroups(Collection $kelompok, ?string $search, bool $includeLevels = true): Collection
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $kelompok;
        }

        $term = mb_strtolower($search);

        return $kelompok->map(function (CompetencyGroup $g) use ($term, $includeLevels) {
            if (self::matches($g->kode, $term) || self::matches($g->nama, $term)) {
                return $g;
            }

            $competencies = $g->competencies->map(function (Competency $c) use ($term, $includeLevels) {
                if (self::matches($c->kode_kompetensi, $term) || self::matches($c->nama, $term) || self::matches($c->definisi, $term)) {
                    return $c;
                }

                if (! $includeLevels) {
                    return null;
                }

                $levels = $c->levels->filter(fn (CompetencyLevel $l) => self::matches($l->indikator_perilaku, $term)
                    || self::matches($l->etiket, $term)
                    || str_contains((string) $l->tingkat, $term));

                if ($levels->isEmpty()) {
                    return null;
                }

                $clone = clone $c;
                $clone->setRelation('levels', $levels);

                return $clone;
            })->filter();

            if ($competencies->isEmpty()) {
                return null;
            }

            $clone = clone $g;
            $clone->setRelation('competencies', $competencies);

            return $clone;
        })->filter()->values();
    }

    private static function matches(?string $value, string $term): bool
    {
        return $value !== null && str_contains(mb_strtolower($value), $term);
    }
}
