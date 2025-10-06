"use strict";

var assert = require("assert");

function arbitraryAssert(arb) {
  assert(arb !== undefined && arb !== null && typeof arb === "object", "arb should be an object");
  assert(typeof arb.generator === "function" && typeof arb.generator.map === "function",
    "arb.generator should be a function");
  assert(typeof arb.shrink === "function" && typeof arb.shrink.smap === "function",
    "arb.shrink should be a function");
  assert(typeof arb.show === "function", "arb.show should be a function");
  assert(typeof arb.smap === "function", "arb.smap should be a function");
}

module.exports = arbitraryAssert;
