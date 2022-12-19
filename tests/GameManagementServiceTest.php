<?php

namespace App\Tests;

use App\Exception\InvalidUseOfLettersException;
use App\Exception\TooManyLettersSelectedException;
use App\Service\GameManagementService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GameManagementServiceTest extends TestCase
{
    public const CORRECT_SCORES = [
        1 => ['E', 'A', 'I', 'O', 'N', 'R', 'T', 'L', 'S', 'U'],
        2 => ['D', 'G'],
        3 => ['B', 'C', 'M', 'P'],
        4 => ['F', 'H', 'V', 'W', 'Y'],
        5 => ['K'],
        8 => ['J', 'X'],
        10 => ['Q', 'Z'],
    ];

    private GameManagementService $gameManagementService;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->gameManagementService = new GameManagementService();
    }

    public function testItCanRetrieveListOfIndividualLetterScores(): void
    {
        $letterScoresArray = $this->gameManagementService->fetchLetterScoresConfig();

        self::assertIsArray($letterScoresArray);
        self::assertArrayHasKey("A", $letterScoresArray);
        self::assertCount(26, $letterScoresArray);
    }

    public function testItLetterScoresConfigHasCorrectScores()
    {
        $letterScoresArray = $this->gameManagementService->fetchLetterScoresConfig();
        $correctScores = self::CORRECT_SCORES;

        foreach ($letterScoresArray as $letter => $score) {
            $realLetterPositionString = $this->array_search_r($letter, $correctScores);
            $arrayDepthSequenceKeys = array_filter(explode("]", str_replace("[", "", $realLetterPositionString)));
            $realLetterScore = reset($arrayDepthSequenceKeys);
            self::assertEquals($realLetterScore, $score);
        }
    }

    public function testItCanGetScoreForASingleLetter()
    {
        $singleLetterScore = $this->gameManagementService->fetchScoreForSingleLetter("A");

        self::assertIsNumeric($singleLetterScore);
        self::assertEquals(1, $singleLetterScore);
    }

    /**
     * @throws TooManyLettersSelectedException|InvalidUseOfLettersException
     */
    public function testItComplainsIfTooManyChosenLetters()
    {
        $ourLetters = ["L","G","K","K","Q","T","Z","X"];
        self::expectException(TooManyLettersSelectedException::class);
        $this->gameManagementService->checkSelectedLettersAreValid($ourLetters);
    }

    /**
     * @throws TooManyLettersSelectedException
     */
    public function testItComplainsIfIllegalUseOfLetters()
    {
        $newLetters = ["L","G","K","K","Q","T","Z"];
        $this->expectException(InvalidUseOfLettersException::class);
        $this->gameManagementService->checkSelectedLettersAreValid($newLetters);
    }

    public function testItCanGetCorrectScoreForWord()
    {
        $word = "GUARDIAN";
        $word2 = "SUREST";
        $word3 = "WELCOME";
        $expectedScore = 10;
        $expectedScore2 = 6;
        $expectedScore3 = 14;

        $actualScore = $this->gameManagementService->fetchScoreForSingleWord($word);
        $actualScore2 = $this->gameManagementService->fetchScoreForSingleWord($word2);
        $actualScore3 = $this->gameManagementService->fetchScoreForSingleWord($word3);

        self::assertEquals($expectedScore, $actualScore);
        self::assertEquals($expectedScore2, $actualScore2);
        self::assertEquals($expectedScore3, $actualScore3);
    }

    /**
     * @throws \Exception
     */
    public function testItCanRandomlySelectSevenLetters()
    {
        $sevenLettersArray = $this->gameManagementService->chooseRandomLetters(7);

        self::assertCount(7, $sevenLettersArray);

        foreach ($sevenLettersArray as $letter) {
            self::assertMatchesRegularExpression("/^[a-zA-Z]{1}$/", $letter);
        }
    }

    /**
     * @throws \Exception
     */
    public function testItCanRandomlySelectOneLetter()
    {
        $oneLetterArray = $this->gameManagementService->chooseRandomLetters(1);

        self::assertCount(1, $oneLetterArray);
        self::assertMatchesRegularExpression("/^[a-zA-Z]{1}$/", $oneLetterArray[0]);
    }

    /**
     * @throws \ReflectionException
     */
    public function testItCanValidateAlphabeticalCharactersOnly()
    {
        $word1 = 'peac£';
        $word2 = 'peace';

        $method = self::getMethod('hasAlphabeticalCharsOnly');
        $assertion1 = $method->invokeArgs($this->gameManagementService, [$word1]);
        self::assertFalse($assertion1);
        $assertion2 = $method->invokeArgs($this->gameManagementService, [$word2]);
        self::assertTrue($assertion2);
    }

    /**
     * @throws \ReflectionException
     */
    public function testItCanGetCharAtIndexN()
    {
        $index = 3;
        $word = 'figure';
        $expectedChar = 'u';

        $method = self::getMethod('getCharAtIndex');
        $actualChar = $method->invokeArgs($this->gameManagementService, [$word, $index]);
        self::assertEquals($expectedChar, $actualChar);
    }

    /**
     * @throws \ReflectionException
     */
    public function testItCanExtractWordsBeginningWith()
    {
        $words = [
            'and',
            'big',
            'buffoon',
            'cheat',
            'fix',
            'fight',
            'freedom',
            'figure',
            'fought',
            'five',
            'fever',
            'Jungle',
            'mean',
            'proof',
            'zealous',
        ];
        $startChar = 'F';

        $method = self::getMethod('extractWordsBeginningWith');
        $extractedWords = $method->invokeArgs($this->gameManagementService, [$words, $startChar]);
        self::assertCount(7, $extractedWords);
    }

    public function testItCanValidateDictionaryWord()
    {
        $word1 = 'peace';
        self::assertTrue($this->gameManagementService->isValidWord($word1));

        $word2 = 'frivolous';
        self::assertTrue($this->gameManagementService->isValidWord($word2));

        $nonsenseWord = 'banthynueman';
        self::assertFalse($this->gameManagementService->isValidWord($nonsenseWord));
    }

    /**
     * @throws \Exception
     */
    public function testItCanGenerateAValidWordFromGivenTiles()
    {
        $ourLetters = ["L","G","U","W","Q","T","Z"];
        $validWords = $this->gameManagementService->generateWordsFromGivenLetters($ourLetters, true);
        self::assertEquals("ut", $validWords[0]);
    }

    /**
     * @return void
     */
    public function testItCanFlattenMultiLevelArray()
    {
        $array = [
            'a',
            'b',
            'c' => [
                'd',
                'e',
                'f',
            ],
        ];

        $flattenedArray = $this->gameManagementService->flattenMultiLengthWordsArray($array);

        self::assertTrue(!isset($flattenedArray['c']));
        self::assertCount(5, $flattenedArray);
        self::assertEquals('f', $flattenedArray[4]);
    }

    /**
     * @return void
     */
    public function testItCanFindAllValidWordsFromGivenTiles()
    {
        $ourLetters = ["I","W","T","H","E","E","R"];
        $validWords = $this->gameManagementService->generateWordsFromGivenLetters($ourLetters);
        self::assertCount(75, $validWords);
        foreach ($validWords as $validWord) {
            self::assertTrue($this->gameManagementService->isValidWord($validWord));
        }
    }

    /**
     * @throws \Exception
     */
    public function testItFindsLongestValidWordFromGivenTiles()
    {
        $ourLetters = ["I","W","T","H","E","E","R"];
        $longestWord = $this->gameManagementService->getLongestWordFromLetterSet($ourLetters);
        self::assertEquals(7, strlen($longestWord));
        self::assertEquals('thewier', $longestWord);
    }

    public function testItCanMakeShorterValidWordFromGivenLetters()
    {
        $knownValidWord = 'humble';
        $knownValidWord2 = 'blur';
        $sevenLetters = ["u","b","m","h","l","r","e"];

        $validWords = $this->gameManagementService->generateWordsFromGivenLetters($sevenLetters);
        self::assertTrue(in_array($knownValidWord, $validWords));
        self::assertTrue(in_array($knownValidWord2, $validWords));
    }

    /**
     * @return void
     */
    public function testItCanGetHighestScoringWord()
    {
        $expectedHighestScoreWord = 'humbler';
        $expectedHighestScoreWordValue = 14;
        $ourLetters = ["u","b","m","h","l","r","e"];
        $validWords = $this->gameManagementService->generateWordsFromGivenLetters($ourLetters);
        $highestScoreWord = $this->gameManagementService->fetchHighestScoreWordFromSet($validWords);

        self::assertEquals($expectedHighestScoreWord, $highestScoreWord);

        $highestScoreWordValue = $this->gameManagementService->fetchScoreForSingleWord($highestScoreWord);
        self::assertEquals($expectedHighestScoreWordValue, $highestScoreWordValue);
    }

    //********************* Private Methods ***********************//
    /***************************************************************/

    /**
     * @param $needle
     * @param array $haystack
     * @param string $currentKey
     * @return false|string
     */
    private function array_search_r($needle, array $haystack, string $currentKey = '')
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $nextKey = $this->array_search_r($needle, $value, $currentKey . '[' . $key . ']');
                if ($nextKey) {
                    return $nextKey;
                }
            } elseif ($value === $needle) {
                return is_numeric($key)
                    ? $currentKey . '[' .$key . ']'
                    : $currentKey . '["' .$key . '"]';
            }
        }
        return false;
    }

    /**
     * @throws \ReflectionException
     */
    private static function getMethod(string $name): \ReflectionMethod
    {
        $class = new ReflectionClass(GameManagementService::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}
