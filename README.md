# guardian-scrabble-challenge
An attempt at the coding challenge from https://github.com/guardian/coding-exercises/tree/main/scrabble

The challenge is based on the popular board game "Scrabble" and starts of by introducing the rules to the game. Simply put you have a number of 'letter' tiles and each 'letter' scores a certain amount of points.

When playing the game you choose 7 random letters and then try to make a word that will score you as many points in total as possible.

The tasks has multiple requirements but like the board game the computer will select 7 random letters from a bag and work out the biggest dictionary word it can find along with some other shorter words. The requirements are list as follows:

1. Calculate the score for a word. The score is the sum of the points for the letters that make up a word. For example: GUARDIAN = 2 + 1 + 1 + 1 + 2 + 1 + 1 + 1 = 10. 
2. Assign seven tiles chosen randomly from the English alphabet to a player's rack. 
3. In the real game, tiles are taken at random from a 'bag' containing a fixed number of each tile. Assign seven tiles to a rack using a bag containing the above distribution. 
4. Find a valid word formed from the seven tiles. A list of valid words can be found in dictionary.txt. 
5. Find the longest valid word that can be formed from the seven tiles. 
6. Find the highest scoring word that can be formed. 
7. Find the highest scoring word if any one of the letters can score triple.

This example uses PHPUnit (The tests take a while to run). The commands you can run are below:

```
./vendor/bin/phpunit ./tests

or

./vendor/bin/phpunit --group Task1 (through Task 7)
```