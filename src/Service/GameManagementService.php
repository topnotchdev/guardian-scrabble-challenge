<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
        if (!$noOfLetters) {
            throw new \Exception('You cannot pass zero / null as the number of letters to return!');
        }

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

        $varyingCountDictionaryWords = $this->loadDictionaryWordsUpToLengthX($lettersCount);
        $invalidatedStringPermutations = $this->makeCharacterArraySimpleStrings($this->getReArrangedLetterArraySets($letters));

        $validWords = [];

        foreach ($varyingCountDictionaryWords as $key => $xLengthDictionaryWords) {
            foreach ($xLengthDictionaryWords as $word) {
                $validWords[$key]= $this->extractWordsContaining($invalidatedStringPermutations, $word);
            }
            if ($key === '6-letter-words') {
                die('stop');
                //dd($validWords);
            }
        }

        return $this->flattenMultiLengthWordsArray($validWords);
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

    public function flattenMultiLengthWordsArray(array $elements): array
    {
        $singleLevelElements = [];
        foreach ($elements as $element) {
            if (!is_array($element)) {
                $singleLevelElements[] = $element;
                continue;
            }

            $foundArrayElements = $this->flattenMultiLengthWordsArray($element);
            $singleLevelElements = array_merge($singleLevelElements, $foundArrayElements);
        }

        return $singleLevelElements;
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

    private function extractWordsContaining(array $strings, string $characters): array
    {
        $extraction = [];
        $characters = strtolower($characters);

        foreach ($strings as $string) {
            if (strpos($string, $characters) !== false && !in_array($string, $extraction)) {
                $extraction[] = $string;
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

    private function loadDictionaryWordsUpToLengthX(int $lettersCount): array
    {
        $dictionaryWords = [];
        for ($i = $lettersCount; $i > 0; $i--) {
            // get $i letter words from dictionary
            $dictionaryWords[$i . '-letter-words'] = $this->fetchWordsOfLengthFromDictionary($i);
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
}
