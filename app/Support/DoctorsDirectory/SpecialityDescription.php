<?php

namespace App\Support\DoctorsDirectory;

use App\Data\SpecialityOverviewContent;
use App\Data\SpecialityOverviewUnit;
use App\Models\Speciality;
use App\Support\LocaleText;

class SpecialityDescription
{
    public static function localized(?Speciality $speciality): ?string
    {
        if ($speciality === null) {
            return null;
        }

        return LocaleText::localizedHtml(
            isset($speciality->post_ar) ? (string) $speciality->post_ar : null,
            isset($speciality->post_en) ? (string) $speciality->post_en : null,
        );
    }

    /**
     * Parse legacy speciality HTML (paragraphs with inline styles) into
     * intro text, units heading, and a two-column ordered unit list.
     */
    public static function overviewContent(?Speciality $speciality): SpecialityOverviewContent
    {
        $html = self::localized($speciality);

        if ($html === null || trim($html) === '') {
            return new SpecialityOverviewContent(null, null, []);
        }

        $blocks = self::extractBlocks($html);

        if ($blocks === []) {
            $plain = trim(strip_tags($html));

            return new SpecialityOverviewContent($plain !== '' ? $plain : null, null, []);
        }

        $intro = [];
        $unitsHeading = null;
        $units = [];
        $phase = 'intro';

        foreach ($blocks as $text) {
            if ($phase === 'intro' && self::isUnitsHeading($text)) {
                $unitsHeading = $text;
                $phase = 'units';

                continue;
            }

            $unit = self::parseUnitLine($text);

            if ($unit !== null) {
                $units[] = $unit;
                $phase = 'units';

                continue;
            }

            if ($phase === 'intro') {
                $intro[] = $text;
            }
        }

        return new SpecialityOverviewContent(
            intro: self::joinParagraphs($intro),
            unitsHeading: $unitsHeading,
            units: self::columnMajorUnits($units, 2),
        );
    }

    /**
     * @return list<string>
     */
    private static function extractBlocks(string $html): array
    {
        if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $html, $listMatches) && ($listMatches[1] ?? []) !== []) {
            return self::cleanBlocks($listMatches[1]);
        }

        if (! preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $paragraphMatches)) {
            $plain = trim(strip_tags($html));

            return $plain !== '' ? [$plain] : [];
        }

        return self::cleanBlocks($paragraphMatches[1] ?? []);
    }

    /**
     * @param  list<string>  $blocks
     * @return list<string>
     */
    private static function cleanBlocks(array $blocks): array
    {
        $cleaned = [];

        foreach ($blocks as $block) {
            $text = html_entity_decode(strip_tags((string) $block), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $text)) ?? '');

            if ($text !== '') {
                $cleaned[] = $text;
            }
        }

        return $cleaned;
    }

    private static function isUnitsHeading(string $text): bool
    {
        if (preg_match('/^\d+\s*[-–.]/u', $text)) {
            return false;
        }

        if (preg_match('/^\s*يتكون\b/u', $text)) {
            return true;
        }

        if (preg_match('/specialized\s+units?\s*:?\s*$/iu', $text)) {
            return true;
        }

        return (bool) preg_match('/وحدات\s+المتخصصة\s*:?\s*$/u', $text);
    }

    private static function parseUnitLine(string $text): ?SpecialityOverviewUnit
    {
        if (! preg_match('/^(\d+)\s*[-–.]\s*(.+)$/u', $text, $matches)) {
            return null;
        }

        $label = trim($matches[2]);

        if ($label === '') {
            return null;
        }

        return new SpecialityOverviewUnit(
            number: (int) $matches[1],
            text: $label,
        );
    }

    /**
     * @param  list<string>  $paragraphs
     */
    private static function joinParagraphs(array $paragraphs): ?string
    {
        if ($paragraphs === []) {
            return null;
        }

        return implode("\n\n", $paragraphs);
    }

    /**
     * Reorder units for a two-column grid (1,5 / 2,6 / 3,7 / 4,8).
     *
     * @param  list<SpecialityOverviewUnit>  $units
     * @return list<SpecialityOverviewUnit>
     */
    private static function columnMajorUnits(array $units, int $columns): array
    {
        $count = count($units);

        if ($count <= $columns) {
            return $units;
        }

        $rows = (int) ceil($count / $columns);
        $ordered = [];

        for ($row = 0; $row < $rows; $row++) {
            for ($column = 0; $column < $columns; $column++) {
                $index = ($column * $rows) + $row;

                if ($index < $count) {
                    $ordered[] = $units[$index];
                }
            }
        }

        return $ordered;
    }
}
