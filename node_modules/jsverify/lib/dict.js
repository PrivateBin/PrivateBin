/* @flow weak */
"use strict";

var arbitraryAssert = require("./arbitraryAssert.js");
var array = require("./array.js");
var generator = require("./generator.js");
var pair = require("./pair.js");
var string = require("./string.js");
var utils = require("./utils.js");

function makeMapShow(elShow) {
  return function (m) {
    return "{" + Object.keys(m).map(function (k) {
      return k + ": " + elShow(m[k]);
    }).join(", ") + "}";
  };
}

/**
  - `dict.generator(gen: generator a): generator (dict a)`
*/
function generateDict(gen) {
  var pairGen = generator.pair(string.string.generator, gen);
  var arrayGen = generator.array(pairGen);
  var result = arrayGen.map(utils.pairArrayToDict);

  return utils.curried2(result, arguments);
}

function dict(arb) {
  arb = utils.force(arb);
  arbitraryAssert(arb);

  var pairArbitrary = pair.pair(string.string, arb);
  var arrayArbitrary = array.array(pairArbitrary);

  return arrayArbitrary.smap(utils.pairArrayToDict, utils.dictToPairArray, makeMapShow(arb.show));
}

module.exports = {
  arbitrary: dict,
  generator: generateDict,
};
