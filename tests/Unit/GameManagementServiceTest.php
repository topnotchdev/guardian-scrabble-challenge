<?php

namespace App\Tests\Unit;

use App\Service\GameManagementService;
use App\Tests\BaseConsoleApplicationTestClass;

class GameManagementServiceTest extends BaseConsoleApplicationTestClass
{
    const CORRECT_SCORES = [
            1 => ['E','A','I','O','N','R','T','L','S','U'],
            2 => ['D','G'],
            3 => ['B','C','M','P'],
            4 => ['F','H','V','W','Y'],
            5 => ['K'],
            8 => ['J','X'],
            10 => ['Q','Z'],
    ];

    private GameManagementService $gameManagementService;
    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
        $container = static::getContainer();

        /** @var GameManagementService gameManagementService */
        $this->gameManagementService = $container->get('app.service.game_management');
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
            $arrayDepthSequenceKeys  = array_filter( explode("]", str_replace("[", "", $realLetterPositionString)) );
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

    //********************* Private Methods ***********************//
    /***************************************************************/

    /**
     * @param $needle
     * @param array $haystack
     * @return mixed
     */
    private function array_search_r($needle, array $haystack, $currentKey = '')
    {
        foreach($haystack as $key => $value) {
            if (is_array($value)) {
                $nextKey = $this->array_search_r($needle,$value, $currentKey . '[' . $key . ']');
                if ($nextKey) {
                    return $nextKey;
                }
            }
            elseif($value === $needle) {
                return is_numeric($key)
                    ? $currentKey . '[' .$key . ']'
                    : $currentKey . '["' .$key . '"]';
            }
        }
        return false;
    }
}
