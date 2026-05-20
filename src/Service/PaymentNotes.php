<?php
declare(strict_types=1);

namespace App\Service;

final class PaymentNotes
{
    /**
     * Merge.
     *
     * @param mixed $existingNotes Existingnotes.
     * @param mixed $newNotes Newnotes.
     */
    public static function merge(?string $existingNotes, array $newNotes): string
    {
        if (!$existingNotes) {
            return (string)json_encode($newNotes);
        }

        $decoded = json_decode($existingNotes, true);
        if (!is_array($decoded)) {
            $decoded = ['legacy_notes' => $existingNotes];
        }

        return (string)json_encode(array_merge($decoded, $newNotes));
    }
}
