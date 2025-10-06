/* @flow weak */
"use strict";

var rc4 = new (require("rc4").RC4small)();

/**
  ### Random functions
*/

/**
  - `random(min: int, max: int): int`

      Returns random int from `[min, max]` range inclusively.

      ```js
      getRandomInt(2, 3) // either 2 or 3
      ```
*/
function randomInteger(min, max) {
  return rc4.random(min, max);
}

/**
  - `random.number(min: number, max: number): number`

      Returns random number from `[min, max)` range.
*/
function randomNumber(min, max) {
  return rc4.randomFloat() * (max - min) + min;
}

randomInteger.integer = randomInteger;
randomInteger.number = randomNumber;

randomInteger.currentStateString = rc4.currentStateString.bind(rc4);
randomInteger.setStateString = rc4.setStateString.bind(rc4);

module.exports = randomInteger;
