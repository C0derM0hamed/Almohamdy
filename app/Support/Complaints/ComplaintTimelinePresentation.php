<?php

namespace App\Support\Complaints;

final class ComplaintTimelinePresentation
{
    /**
     * @var array<int, string>
     */
    private const ICONS = [
        0 => 'bi-send',
        1 => 'bi-send',
        2 => 'bi-envelope',
        3 => 'bi-envelope-open',
        4 => 'bi-diagram-3',
        5 => 'bi-check-circle',
        6 => 'bi-check2-circle',
    ];

    /**
     * @var array<int, string>
     */
    private const HORIZONTAL_ICONS = [
        0 => 'bi-chat-square-text',
        1 => 'bi-chat-square-text',
        2 => 'bi-envelope',
        3 => 'bi-envelope-open',
        4 => 'bi-exclamation-triangle',
        5 => 'bi-check-circle',
        6 => 'bi-check2-circle',
    ];

    /**
     * @var array<int, string>
     */
    private const TONES = [
        4 => 'warning',
        5 => 'success',
        6 => 'success',
    ];

    public static function iconFor(int $statusId): string
    {
        return self::ICONS[$statusId] ?? 'bi-file-earmark-text';
    }

    public static function horizontalIconFor(int $statusId): string
    {
        return self::HORIZONTAL_ICONS[$statusId] ?? 'bi-file-earmark-text';
    }

    public static function toneFor(int $statusId): string
    {
        return self::TONES[$statusId] ?? 'default';
    }

    /**
     * Build a CSS linear-gradient for the timeline connector line.
     *
     * @param  list<array{reply: \App\Models\ComplaintReply, status_label: string, status_color: string}>  $timeline
     */
    public static function connectorGradient(array $timeline): string
    {
        $count = count($timeline);

        if ($count <= 1) {
            return 'linear-gradient(90deg, #2456e8, #2456e8)';
        }

        $warningPos = null;
        $dangerPos = null;
        $lastIndex = $count - 1;

        foreach ($timeline as $index => $event) {
            $statusId = (int) $event['reply']->complaint_status_id;
            $tone = self::toneFor($statusId);

            if ($index === $lastIndex && $statusId === 4) {
                $tone = 'danger';
            }

            if ($tone === 'warning' && $warningPos === null) {
                $warningPos = $index;
            }

            if ($tone === 'danger' && $dangerPos === null) {
                $dangerPos = $index;
            }
        }

        $warningPercent = $warningPos !== null
            ? (int) round((($warningPos + 0.5) / $count) * 100)
            : null;
        $dangerPercent = $dangerPos !== null
            ? (int) round((($dangerPos + 0.5) / $count) * 100)
            : ($warningPos !== null && $warningPos < $lastIndex
                ? (int) round((($lastIndex + 0.5) / $count) * 100)
                : null);

        if ($dangerPercent !== null && $warningPercent !== null) {
            return "linear-gradient(90deg, #2456e8 0 {$warningPercent}%, #ffbf26 {$warningPercent}% {$dangerPercent}%, #e63542 {$dangerPercent}% 100%)";
        }

        if ($warningPercent !== null) {
            return "linear-gradient(90deg, #2456e8 0 {$warningPercent}%, #ffbf26 {$warningPercent}% 100%)";
        }

        return 'linear-gradient(90deg, #2456e8, #0d43b8)';
    }
}
