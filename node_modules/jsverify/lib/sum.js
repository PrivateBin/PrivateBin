"use strict";

var assert = require("assert");

/**
  ### sum (n-ary either)

  See: [Wikipedia](https://en.wikipedia.org/wiki/Tagged_union)
*/

function Addend(idx, len, value) {
  assert(len > 0, "Addend: 0 < len"); // empty sum is void - cannot create such
  assert(idx >= 0 && idx < len, "Addend: 0 <= idx < len");
  this.idx = idx;
  this.len = len;
  this.value = value;
}

/**
  - `sum.addend(idx: nat, n: nat, value: a): sum (... a ...)`
*/
function addend(idx, len, value) {
  return new Addend(idx, len, value);
}

/**
  - `.fold(f: (idx: nat, n: nat, value: a) -> b): b`
*/
Addend.prototype.fold = function (f) {
  return f(this.idx, this.len, this.value);
};

module.exports = {
  addend: addend,
};
