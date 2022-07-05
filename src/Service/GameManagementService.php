<?php

namespace App\Service;

class GameManagementService
{
    const LETTER_SCORES = [
        'A' => 1,
        'B' => 3,
        'C' => 3,
        'D' => 2,
        'E' => 1,
        'F' => 4,
        'G' => 2,
        'H' => 4,
        'I' => 1,
        'J' => 8,
        'K' => 5,
        'L' => 1,
        'M' => 3,
        'N' => 1,
        'O' => 1,
        'P' => 3,
        'Q' => 10,
        'R' => 1,
        'S' => 1,
        'T' => 1,
        'U' => 1,
        'V' => 4,
        'W' => 4,
        'X' => 8,
        'Y' => 4,
        'Z' => 10,
    ];

    const LETTER_COUNTS = [
        12 => ['E'],
        9 => ['A', 'I'],
        8 => ['O'],
        6 => ['N', 'R', 'T'],
        4 => ['L', 'S', 'U', 'D'],
        3 => ['G'],
        2 => ['B', 'C', 'M', 'P', 'F', 'H', 'V', 'W', 'Y'],
        1 => ['K', 'J', 'X', 'Q', 'Z'],
    ];

    public function fetchLetterScoresConfig(): array
    {
        return self::LETTER_SCORES;
    }

    public function fetchScoreForSingleLetter(string $letter): int
    {
        return self::LETTER_SCORES[$letter];
    }

    public function fetchScoreForSingleWord($word): int
    {
        $letters = str_split($word, 1);

        $accumulatedPoints = 0;

        foreach ($letters as $letter) {
            $accumulatedPoints += self::LETTER_SCORES[$letter];
        }
        return $accumulatedPoints;
    }

    /**
     * @param int $noOfLetters
     * @return array
     * @throws \Exception
     */
    public function chooseRandomLetters(int $noOfLetters): array
    {
        if (!$noOfLetters) throw new \Exception('You cannot pass zero / null as the number of letters to return!');

        $letters = array_keys(self::LETTER_SCORES);
        $chosenLetters = [];

        if ($noOfLetters > 1) {
            for ($i = 0; $i < $noOfLetters; $i++) {
                $randomKey = array_rand($letters);
                $chosenLetters[] = $letters[$randomKey];
            }
        } else {
            $randomKey = array_rand($letters);
            $chosenLetters[] = $letters[$randomKey];
        }

        return $chosenLetters;
    }
}