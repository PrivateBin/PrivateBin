"use strict";

var arbitraryBless = require("./arbitraryBless.js");
var generator = require("./generator.js");
var utils = require("./utils.js");
var shrink = require("./shrink.js");

/**
  - `generator.record(gen: { key: generator a... }): generator { key: a... }`
*/
function generatorRecord(spec) {
  var keys = Object.keys(spec);
  var result = generator.bless(function (size) {
    var res = {};
    keys.forEach(function (k) {
      res[k] = spec[k](size);
    });
    return res;
  });

  return utils.curried2(result, arguments);
}

/**
  - `shrink.record(shrs: { key: shrink a... }): shrink { key: a... }`
*/
function shrinkRecord(shrinksRecord) {
  var keys = Object.keys(shrinksRecord);
  var shrinks = keys.map(function (k) { return shrinksRecord[k]; });

  var result = shrink.bless(function (rec) {
    var values = keys.map(function (k) { return rec[k]; });
    var shrinked = shrink.tuple(shrinks, values);

    return shrinked.map(function (s) {
      var res = {};
      keys.forEach(function (k, i) {
        res[k] = s[i];
      });
      return res;
    });
  });

  return utils.curried2(result, arguments);
}

function arbitraryRecord(spec) {
  var generatorSpec = {};
  var shrinkSpec = {};
  var showSpec = {};

  Object.keys(spec).forEach(function (k) {
    var arb = utils.force(spec[k]);
    generatorSpec[k] = arb.generator;
    shrinkSpec[k] = arb.shrink;
    showSpec[k] = arb.show;
  });

  return arbitraryBless({
    generator: generatorRecord(generatorSpec),
    shrink: shrinkRecord(shrinkSpec),
    show: function (m) {
      return "{" + Object.keys(m).map(function (k) {
        return k + ": " + showSpec[k](m[k]);
      }).join(", ") + "}";
    },
  });
}

module.exports = {
  generator: generatorRecord,
  arbitrary: arbitraryRecord,
  shrink: shrinkRecord,
};
