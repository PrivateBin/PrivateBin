"use strict";

var generator = require("./generator.js");
var arbitraryBless = require("./arbitraryBless.js");
var arbitraryAssert = require("./arbitraryAssert.js");
var utils = require("./utils.js");

/**
  ### Small arbitraries

  - `generator.small(gen: generator a): generator a`
  - `small(arb: arbitrary a): arbitrary a`

  Create a generator (abitrary) which will generate smaller values, i.e. generator's `size` parameter is decreased logarithmically.

  ```js
  jsc.property("small array of small natural numbers", "small (array nat)", function (arr) {
    return Array.isArray(arr);
  });

  jsc.property("small array of normal natural numbers", "(small array) nat", function (arr) {
    return Array.isArray(arr);
  });
  ```
*/

function smallGenerator(gen) {
  // TODO: assertGenerator(gen)
  return generator.bless(function (size) {
    return gen(utils.ilog2(size));
  });
}

function smallArbitraryImpl(arb) {
  arbitraryAssert(arb);
  return arbitraryBless({
    generator: smallGenerator(arb.generator),
    shrink: arb.shrink,
    show: arb.show,
  });
}

function smallArbitrary(arb) {
  if (typeof arb === "function") {
    return function () {
      var resArb = arb.apply(arb, arguments);
      return smallArbitraryImpl(resArb);
    };
  } else { /* if (typeof arb === "object") */
    return smallArbitraryImpl(arb);
  }
}

module.exports = {
  generator: smallGenerator,
  arbitrary: smallArbitrary,
};
