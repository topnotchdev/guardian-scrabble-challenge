<?php

namespace App\Service;

use App\Exception\InvalidUseOfLettersException;
use App\Exception\TooManyLettersSelectedException;

class GameManagementService
{
    public const LETTER_SCORES = [
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

    public const LETTER_COUNTS = [
        12 => ['E'],
        9 => ['A', 'I'],
        8 => ['O'],
        6 => ['N', 'R', 'T'],
        4 => ['L', 'S', 'U', 'D'],
        3 => ['G'],
        2 => ['B', 'C', 'M', 'P', 'F', 'H', 'V', 'W', 'Y'],
        1 => ['K', 'J', 'X', 'Q', 'Z'],
    ];

    public const ALPHA_ONLY_REGEX = "/^[a-zA-Z]+$/";
    const LETTER_SELECT_ON_GAME_INIT = 7;

    private array $dictionary = [];

    /**
     * @var array|mixed
     */
    private $wordPermutations = [];
    private bool $isInitialized = false;
    private array $bagOfLetters;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * @throws \Exception
     */
    protected function init()
    {
        // load dictionary words from file
        $DS = DIRECTORY_SEPARATOR;
        $filename =  realpath('./public' . $DS . 'data' . $DS . 'dictionary.txt'); // from project root
        if (!file_exists($filename)) {
            throw new \Exception('This service is temporarily unavailable due to a missing dependency!');
        }

        $fileLines = file($filename, FILE_IGNORE_NEW_LINES);

        foreach (array_keys(self::LETTER_SCORES) as $letter) {
            $this->dictionary[$letter] = $this->extractWordsBeginningWith($fileLines, $letter);
        }

        $this->initialiseBagOfLetters();

        $this->isInitialized = true;
    }

    public function fetchLetterScoresConfig(): array
    {
        return self::LETTER_SCORES;
    }

    public function fetchScoreForSingleLetter(string $letter): int
    {
        return self::LETTER_SCORES[$letter];
    }

    /**
     * @throws TooManyLettersSelectedException
     * @throws InvalidUseOfLettersException
     */
    public function checkSelectedLettersAreValid(array $letters): bool
    {
        if (count($letters) < self::LETTER_SELECT_ON_GAME_INIT || count($letters) > self::LETTER_SELECT_ON_GAME_INIT) {
            throw new TooManyLettersSelectedException("You have picked too few or too many letters. There should be 7 letters on your tray.");
        }

        $countedLetters = [];

        foreach ($letters as $letter) {
            $countedLetters[$letter][] = 1;
        }

        foreach ($countedLetters as $letter => $appearances) {
            $uppercase = strtoupper($letter);
            $allowedNumberOfUses = $this->getNumberOfAllowedUsagesForLetter($uppercase);
            if (count($appearances) > $allowedNumberOfUses) {
                throw new InvalidUseOfLettersException("Illegal use of the letter $uppercase detected.");
            }
        }

        return true;
    }

    public function fetchScoreForSingleWord($word): int
    {
        $letters = str_split($word, 1);

        $accumulatedPoints = 0;

        foreach ($letters as $letter) {
            $accumulatedPoints += self::LETTER_SCORES[strtoupper($letter)];
        }
        return $accumulatedPoints;
    }

    public function fetchHighestScoreWordFromSet(array $words): string
    {
        $wordScores = [];

        foreach ($words as $word) {
            $wordScores[$word] = $this->fetchScoreForSingleWord($word);
        }

        return array_search(max($wordScores),$wordScores);
    }

    /**
     * @param int $noOfLetters
     * @return array
     * @throws \Exception
     */
    public function chooseRandomLetters(int $noOfLetters): array
    {
        if (!$noOfLetters) {
            throw new \Exception('You cannot pass zero / null as the number of letters to return!');
        }

        $this->initialiseBagOfLetters();
        $chosenLetters = [];

        for ($i = 0; $i < $noOfLetters; $i++) {
            $totalLetterCount = count($this->bagOfLetters);
            $randomBagLetterIndex = array_keys($this->bagOfLetters)[rand(0,$totalLetterCount-1)];
            $chosenLetters[] = $this->bagOfLetters[$randomBagLetterIndex];
            unset($this->bagOfLetters[$randomBagLetterIndex]);
            if ($noOfLetters === 1) {
                break;
            }
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

    public function generateWordsFromGivenLetters(array $letters, bool $returnMaxSizeWordsOnly = false, $returnFirstWordOnly = false): array
    {
        $lettersCount = count($letters);

        $varyingCountDictionaryWords = array_reverse($this->loadDictionaryWordsUpToLengthX($lettersCount, $returnMaxSizeWordsOnly), true);
        $invalidatedStringPermutations = $this->makeCharacterArraySimpleStrings($this->getReArrangedLetterArraySets($letters));

        $validWords = [];

        foreach ($varyingCountDictionaryWords as $key => $xLengthDictionaryWords) {

            $validWords[$key . '-letter-words'] = []; // initialise array

            foreach ($xLengthDictionaryWords as $word) {
                $validWord = $this->extractWordFromReSequencedCharacters($invalidatedStringPermutations, $word);

                if (!is_null($validWord) && !in_array($validWord, $validWords[$key . '-letter-words'])) {
                    $validWords[$key . '-letter-words'][] = $validWord;
                }
            }
        }

        $flattenedWordList = $this->flattenMultiLengthWordsArray($validWords);

        return ($returnFirstWordOnly) ? [end($flattenedWordList)] : $flattenedWordList;
    }

    public function getLongestWordFromLetterSet(array $letters): string
    {
        $foundWords = $this->generateWordsFromGivenLetters($letters);
        $foundWordsLengthKeys = [];
        foreach ($foundWords as $foundWord) {
            $foundWordsLengthKeys[$foundWord] = strlen($foundWord);
        }
        $letterCountBiggestWord = max(array_values($foundWordsLengthKeys));
        return array_search($letterCountBiggestWord, $foundWordsLengthKeys);
    }

    public function flattenMultiLengthWordsArray(array $initialArrayElements, array $flattenedArray = []): array
    {
        foreach ($initialArrayElements as $element) {
            if (!is_array($element)) {
                $flattenedArray[] = $element;
                continue;
            }
            elseif (empty($element)) {
                continue;
            }
            $innerResults = $this->flattenMultiLengthWordsArray($element, $flattenedArray);
            $flattenedArray = $this->mergeNewResultsWithArray($flattenedArray, $innerResults);
        }

        return $flattenedArray;
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
            $this->wordPermutations[] = strtolower(implode('', $perms));
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

    private function extractWordsBeginningWith(array $strings, string $characters): array
    {
        $extraction = [];
        $characters = strtolower($characters);

        foreach ($strings as $string) {
            if (strpos($string, $characters) === 0) {
                $extraction[] = $string;
            }
        }

        return $extraction;
    }

    private function extractWordFromReSequencedCharacters(array $strings, string $characters): ?string
    {
        $extraction = null;
        $characters = strtolower($characters);

        foreach ($strings as $string) {
            if (strpos($string, $characters) !== false) {
                $extraction = $characters;
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
        $cachedStrings = [];

        foreach ($fullLengthStringSet as $string) {
            if (in_array($string, $cachedStrings)) {
                continue;
            }
            $cachedStrings[] = $string;
            $sets[] = str_split($string);
        }

        return $sets;
    }

    private function loadDictionaryWordsUpToLengthX(int $lettersCount, bool $returnOnlyMaxSizeWords = false): array
    {
        $dictionaryWords = [];
        for ($i = $lettersCount; $i > 0; $i--) {
            // get $i letter words from dictionary
            $dictionaryWords[$i] = $this->fetchWordsOfLengthFromDictionary($i);
            if ($returnOnlyMaxSizeWords) {
                break;
            }
        }

        return $dictionaryWords;
    }

    private function fetchWordsOfLengthFromDictionary(int $stringLength): array
    {
        $foundWords = [];

        foreach ($this->dictionary as $letter => $letterWords) {
            foreach ($letterWords as $word) {
                if (strlen($word) !== $stringLength) {
                    continue;
                }
                $foundWords[] = $word;
            }
        }

        return $foundWords;
    }

    private function makeCharacterArraySimpleStrings(array $characterSequenceArray): array
    {
        $strings = [];
        foreach ($characterSequenceArray as $characterSequence) {
            $strings[] = implode("", $characterSequence);
        }
;
        return $strings;
    }

    private function getNumberOfAllowedUsagesForLetter(string $uppercase): int
    {
        foreach (self::LETTER_COUNTS as $allowedUsages => $letters) {
            if (in_array($uppercase, $letters, true)) {
                return $allowedUsages;
            }
        }

        return 1;
    }

    private function mergeNewResultsWithArray($flattenedArray, array $innerResults)
    {
        foreach ($innerResults as $innerResult) {
            if (!in_array($innerResult, $flattenedArray)) {
                $flattenedArray[] = $innerResult;
            }
        }

        return $flattenedArray;
    }

    private function initialiseBagOfLetters(): void
    {
        if (!$this->isInitialized || !$this->bagOfLetters) {
            foreach (self::LETTER_COUNTS as $numberInBag => $letters) {
                foreach ($letters as $letter) {
                    for ($i = $numberInBag; $i > 0; $i--) {
                        $this->bagOfLetters[] = $letter;
                    }
                }
            }
        }
    }
}
