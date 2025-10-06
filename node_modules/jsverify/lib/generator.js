/* @flow weak */
"use strict";

var assert = require("assert");
var either = require("./either.js");
var random = require("./random.js");
var sum = require("./sum.js");
var utils = require("./utils.js");

/**
  ### Generator functions

  A generator function, `generator a`, is a function `(size: nat) -> a`, which generates a value of given size.

  Generator combinators are auto-curried:

  ```js
  var xs = jsc.generator.array(jsc.nat.generator, 1); // ≡
  var ys = jsc.generator.array(jsc.nat.generator)(1);
  ```

  In purely functional approach `generator a` would be explicitly stateful computation:
  `(size: nat, rng: randomstate) -> (a, randomstate)`.
  *JSVerify* uses an implicit random number generator state,
  but the value generation is deterministic (tests are reproducible),
  if the primitives from *random* module are used.
*/

// Blessing: i.e adding prototype
/* eslint-disable no-use-before-define */
function generatorProtoMap(f) {
  /* jshint validthis:true */
  var generator = this; // eslint-disable-line no-invalid-this
  generatorAssert(generator);
  return generatorBless(function (size) {
    return f(generator(size));
  });
}

function generatorProtoFlatMap(f) {
  /* jshint validthis:true */
  var generator = this; // eslint-disable-line no-invalid-this
  generatorAssert(generator);
  return generatorBless(function (size) {
    return f(generator(size))(size);
  });
}
/* eslint-enable no-use-before-define */

function generatorAssert(generator) {
  assert(typeof generator === "function", "generator should be a function");
  assert(generator.map === generatorProtoMap, "generator.map should be a function");
  assert(generator.flatmap === generatorProtoFlatMap, "generator.flatmap should be a function");
  assert(generator.flatMap === generatorProtoFlatMap, "generator.flatMap should be a function");
}

/**
  - `generator.bless(f: nat -> a): generator a`

      Bless function with `.map` and `.flatmap` properties.

  - `.map(f: a -> b): generator b`

      Map `generator a` into `generator b`. For example:

      ```js
      positiveIntegersGenerator = nat.generator.map(
        function (x) { return x + 1; });
      ```

  - `.flatmap(f: a -> generator b): generator b`

      Monadic bind for generators. Also `flatMap` version is supported.
*/
function generatorBless(generator) {
  generator.map = generatorProtoMap;
  generator.flatmap = generatorProtoFlatMap;
  generator.flatMap = generatorProtoFlatMap;
  return generator;
}

/**
  - `generator.constant(x: a): generator a`
*/
function generateConstant(x) {
  return generatorBless(function () {
    return x;
  });
}

/**
  - `generator.combine(gen: generator a..., f: a... -> b): generator b`
*/
function generatorCombine() {
  var generators = Array.prototype.slice.call(arguments, 0, -1);
  var f = arguments[arguments.length - 1];

  return generatorBless(function (size) {
    var values = generators.map(function (gen) {
      return gen(size);
    });

    return f.apply(undefined, values);
  });
}

/**
  - `generator.oneof(gens: list (generator a)): generator a`
*/
function generateOneof(generators) {
  // TODO: generator
  generators.forEach(function (gen) {
    assert(typeof gen === "function");
  });

  var result = generatorBless(function (size) {
    var idx = random(0, generators.length - 1);
    var gen = generators[idx];
    return gen(size);
  });

  return utils.curried2(result, arguments);
}

// Helper, essentially: log2(size + 1)
function logsize(size) {
  return Math.max(Math.round(Math.log(size + 1) / Math.log(2), 0));
}

/**
  - `generator.recursive(genZ: generator a, genS: generator a -> generator a): generator a`
*/
function generatorRecursive(genZ, genS) {
  return generatorBless(function (size) {
    function rec(n, sizep) {
      if (n <= 0 || random(0, 3) === 0) {
        return genZ(sizep);
      } else {
        return genS(generatorBless(function (sizeq) {
          return rec(n - 1, sizeq);
        }))(sizep);
      }
    }

    return rec(logsize(size), size);
  });
}

/**
  - `generator.pair(genA: generator a, genB: generator b): generator (a, b)`
*/
function generatePair(genA, genB) {
  var result = generatorBless(function (size) {
    return [genA(size), genB(size)];
  });

  return utils.curried3(result, arguments);
}

/**
  - `generator.either(genA: generator a, genB: generator b): generator (either a b)`
*/
function generateEither(genA, genB) {
  var result = generatorBless(function (size) { // eslint-disable-line consistent-return
    var n = random(0, 1);
    switch (n) {
      case 0: return either.left(genA(size));
      case 1: return either.right(genB(size));
      // no default
    }
  });

  return utils.curried3(result, arguments);
}
/**
  - `generator.unit: generator ()`

      `unit` is an empty tuple, i.e. empty array in JavaScript representation. This is useful as a building block.
*/
var generateUnit = generatorBless(function () {
  return [];
});

/**
  - `generator.tuple(gens: (generator a, generator b...)): generator (a, b...)`
*/
function generateTuple(gens) {
  var len = gens.length;
  var result = generatorBless(function (size) {
    var r = [];
    for (var i = 0; i < len; i++) {
      r[i] = gens[i](size);
    }
    return r;
  });

  return utils.curried2(result, arguments);
}

/**
  - `generator.sum(gens: (generator a, generator b...)): generator (a | b...)`
*/
function generateSum(gens) {
  var len = gens.length;
  var result = generatorBless(function (size) {
    var idx = random(0, len - 1);
    return sum.addend(idx, len, gens[idx](size));
  });

  return utils.curried2(result, arguments);
}

/**
   - `generator.array(gen: generator a): generator (array a)`
*/
function generateArray(gen) {
  var result = generatorBless(function (size) {
    var arrsize = random(0, logsize(size));
    var arr = new Array(arrsize);
    for (var i = 0; i < arrsize; i++) {
      arr[i] = gen(size);
    }
    return arr;
  });

  return utils.curried2(result, arguments);
}

/**
   - `generator.nearray(gen: generator a): generator (array a)`
*/
function generateNEArray(gen) {
  var result = generatorBless(function (size) {
    var arrsize = random(1, Math.max(logsize(size), 1));
    var arr = new Array(arrsize);
    for (var i = 0; i < arrsize; i++) {
      arr[i] = gen(size);
    }
    return arr;
  });

  return utils.curried2(result, arguments);
}

/**
  - `generator.dict(gen: generator a): generator (dict a)`
*/

module.exports = {
  pair: generatePair,
  either: generateEither,
  unit: generateUnit,
  tuple: generateTuple,
  sum: generateSum,
  array: generateArray,
  nearray: generateNEArray,
  oneof: generateOneof,
  constant: generateConstant,
  bless: generatorBless,
  combine: generatorCombine,
  recursive: generatorRecursive,
};
