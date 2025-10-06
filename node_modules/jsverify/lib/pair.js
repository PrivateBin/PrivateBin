"use strict";

var arbitraryAssert = require("./arbitraryAssert.js");
var arbitraryBless = require("./arbitraryBless.js");
var generator = require("./generator.js");
var show = require("./show.js");
var shrink = require("./shrink.js");
var utils = require("./utils.js");

function pair(a, b) {
  a = utils.force(a);
  b = utils.force(b);

  arbitraryAssert(a);
  arbitraryAssert(b);

  return arbitraryBless({
    generator: generator.pair(a.generator, b.generator),
    shrink: shrink.pair(a.shrink, b.shrink),
    show: show.pair(a.show, b.show),
  });
}

module.exports = {
  pair: pair,
};
