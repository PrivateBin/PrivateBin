"use strict";

var assert = require("assert");

/**
  ### either
*/

function Left(value) {
  this.value = value;
}

function Right(value) {
  this.value = value;
}

/**
  - `either.left(value: a): either a b`
*/
function left(value) {
  return new Left(value);
}

/**
  - `either.right(value: b): either a b`
*/
function right(value) {
  return new Right(value);
}

/**
  - `either.either(l: a -> x, r: b -> x): x`
*/
Left.prototype.either = function lefteither(l) {
  return l(this.value);
};

Right.prototype.either = function righteither(l, r) {
  return r(this.value);
};

/**
  - `either.isEqual(other: either a b): bool`

      TODO: add `eq` optional parameter
*/
Left.prototype.isEqual = function leftIsEqual(other) {
  assert(other instanceof Left || other instanceof Right, "isEqual: `other` parameter should be either");
  return other instanceof Left && this.value === other.value;
};

Right.prototype.isEqual = function rightIsEqual(other) {
  assert(other instanceof Left || other instanceof Right, "isEqual: `other` parameter should be either");
  return other instanceof Right && this.value === other.value;
};

/**
  - `either.bimap(f: a -> c, g: b -> d): either c d`

      ```js
      either.bimap(compose(f, g), compose(h, i)) ≡ either.bimap(g, i).bimap(f, h);
      ```

*/
Left.prototype.bimap = function leftBimap(f) {
  return new Left(f(this.value));
};

Right.prototype.bimap = function rightBimap(f, g) {
  return new Right(g(this.value));
};

/**
  - `either.first(f: a -> c): either c b`

      ```js
      either.first(f) ≡ either.bimap(f, utils.identity)
      ```
*/
Left.prototype.first = function leftFirst(f) {
  return new Left(f(this.value));
};

Right.prototype.first = function rightFirst() {
  return this;
};

/**
  - `either.second(g: b -> d): either a d`

      ```js
      either.second(g) === either.bimap(utils.identity, g)
      ```
*/
Left.prototype.second = function leftSecond() {
  return this;
};

Right.prototype.second = function rightSecond(g) {
  return new Right(g(this.value));
};

module.exports = {
  left: left,
  right: right,
};
