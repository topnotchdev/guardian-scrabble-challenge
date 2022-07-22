<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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

    const ALPHA_ONLY_REGEX = "/^[a-zA-Z]+$/";

    private array $dictionary = [];
    private ParameterBagInterface $parameterBag;
    /**
     * @var array|mixed
     */
    private $wordPermutations = [];

    /**
     * @throws \Exception
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
        $this->init();
    }

    /**
     * @throws \Exception
     */
    protected function init()
    {
        // load dictionary words from file
        $DS = DIRECTORY_SEPARATOR;
        $filename = $this->parameterBag->get('kernel.project_dir') . $DS .  'public' . $DS . 'data' . $DS . 'dictionary.txt';

        if (!file_exists($filename)) {
            throw new \Exception('This service is temporarily unavailable due to a missing dependency!');
        }

        $fileLines = file($filename, FILE_IGNORE_NEW_LINES);

        foreach (array_keys(self::LETTER_SCORES) as $letter) {
            $this->dictionary[$letter] = $this->extractWordsBeginningWith($fileLines, $letter);
        }
    }

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

    public function isValidWord(string $input): bool
    {
        if (!$this->hasAlphabeticalCharsOnly($input)) {
            return false;
        }

        $word = strtolower($input);
        $firstCharOfInput = strtoupper($this->getCharAtIndex($input, 0));

        $wordsBeginningWithChar = $this->dictionary[$firstCharOfInput]; // we don't need the whole dictionary
        return in_array($word, $wordsBeginningWithChar);
    }

    public function generateWordsFromGivenLetters(array $letters, $returnFirstWordOnly = false): array
    {
        $lettersCount = count($letters);
        $varyingCountUnvalidatedWordPermutations = [];

        for ($i = $lettersCount; $i > 0; $i--) {
            $useLetters = array_slice($letters, 0, $i);
            $varyingCountUnvalidatedWordPermutations[$i . '-letters'] = $this->getReArrangedLetterArraySets($useLetters);
            if ($i !== $lettersCount) {
                $otherPerms = $this->rotateLettersAndFindNewPerms($useLetters, $letters[$i]);
                $varyingCountUnvalidatedWordPermutations[$i . '-letters'] = array_merge($varyingCountUnvalidatedWordPermutations[$i . '-letters'], $otherPerms);
            }
        }

        foreach ($varyingCountUnvalidatedWordPermutations['5-letters'] as $letters) {
            $word = implode('', $letters);
            echo PHP_EOL. $word;
        }
        die;

        $validWords = [];

        foreach ($varyingCountUnvalidatedWordPermutations as $key => $varyingCountUnvalidatedWordPermutation) {
            $formedWords = [];
            foreach ($varyingCountUnvalidatedWordPermutation as $charArray) {
                $formedWords[] = implode('',$charArray);
            }
            $validWords[$key][] = $this->extractValidWordsFromList($formedWords, $returnFirstWordOnly);
        }

        dd($validWords);

        return $validWords;
    }

    //********************* Private Methods ***********************//
    /***************************************************************/

    private function hasAlphabeticalCharsOnly(string $input): bool
    {
        $match = preg_match(self::ALPHA_ONLY_REGEX, $input);
        return (bool) $match;
    }

    private function getCharAtIndex(string $input, int $index)
    {
        $wordChars = str_split($input, 1);
        return $wordChars[$index];
    }

    private function generateWordPermutationsFromCharArray(array $letters, $perms = [])
    {
        if (empty($letters)) {
            $this->wordPermutations[] = strtolower( implode('', $perms) );
        } else {
            for ($i = count($letters) - 1; $i >= 0; --$i) {
                $newLetters = $letters;
                $newPerms = $perms;
                list($char) = array_splice($newLetters, $i, 1);
                array_unshift($newPerms, $char);
                $this->generateWordPermutationsFromCharArray($newLetters, $newPerms);
            }
        }

        return $this->wordPermutations;
    }

    private function extractValidWordsFromList(array $possibleEnglishWords, bool $returnFirstWordOnly = false): array
    {
        $foundWords = [];

        foreach ($possibleEnglishWords as $possibleWord) {
            if ($this->isValidWord($possibleWord) && !in_array($possibleWord, $foundWords)) {
                $foundWords[] = $possibleWord;
                if ($returnFirstWordOnly) {
                    break;
                }
            }
        }

        return $foundWords;
    }

    private function extractWordsBeginningWith(array $words, string $character): array
    {
        $extraction = [];
        $character = strtolower($character);
        foreach ($words as $word) {
            if(strpos( $word, $character ) === 0){
                $extraction[] = $word;
            }
            if (strpos( $word, $character ) !== 0 && count($extraction)) { // because the list in alphabetical order
                break;
            }
        }

        return $extraction;
    }

    private function getReArrangedLetterArraySets(array $letters): array
    {
        $sets = [];

        unset($this->wordPermutations);

        $fullLengthStringSet = $this->generateWordPermutationsFromCharArray($letters);

        foreach ($fullLengthStringSet as $string) {
            $sets[] = str_split($string);
        }

        return $sets;
    }

    private function rotateLettersAndFindNewPerms(array $useLetters, string $removedLetter): array
    {
        $otherPerms = [];

        unset($this->wordPermutations);

        foreach ($useLetters as $key => $letter) {
            // the last element of the $useLetter array with last element in $droppedLetters
            $swapLetters[$key] = $removedLetter;
            $newLetters = array_replace($useLetters, $swapLetters);
            $removedLetter = $newRemovedLetter = $letter;
            $otherPerms[] = $this->getReArrangedLetterArraySets($newLetters);
        }

        return $this->flattenResults($otherPerms);
    }

    private function flattenResults(array $elements): array
    {
        $results = [];

        foreach ($elements as $innerElements) {
            foreach ($innerElements as $letterSet) {
                $results[] = $letterSet;
            }
        }

        return $results;
    }
}