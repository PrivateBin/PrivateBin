"use strict";

var arbitraryAssert = require("./arbitraryAssert.js");
var arbitraryBless = require("./arbitraryBless.js");
var generator = require("./generator.js");
var show = require("./show.js");
var shrink = require("./shrink.js");
var utils = require("./utils.js");

function makeArray(flavour) {
  return function arrayImpl(arb) {
    arb = utils.force(arb);

    arbitraryAssert(arb);

    return arbitraryBless({
      generator: generator[flavour](arb.generator),
      shrink: shrink[flavour](arb.shrink),
      show: show.array(arb.show),
    });
  };
}

var array = makeArray("array");
var nearray = makeArray("nearray");

module.exports = {
  array: array,
  nearray: nearray,
};
