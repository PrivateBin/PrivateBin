/* @flow weak */
"use strict";

var assert = require("assert");
var either = require("./either.js");
var lazyseq = require("lazy-seq");
var sum = require("./sum.js");
var utils = require("./utils.js");

/**
  ### Shrink functions

  A shrink function, `shrink a`, is a function `a -> [a]`, returning an array of *smaller* values.

  Shrink combinators are auto-curried:

  ```js
  var xs = jsc.shrink.array(jsc.nat.shrink, [1]); // ≡
  var ys = jsc.shrink.array(jsc.nat.shrink)([1]);
  ```
*/

// Blessing: i.e adding prototype
/* eslint-disable no-use-before-define */
function shrinkProtoIsoMap(f, g) {
  /* jshint validthis:true */
  var shrink = this; // eslint-disable-line no-invalid-this
  return shrinkBless(function (value) {
    return shrink(g(value)).map(f);
  });
}
/* eslint-enable no-use-before-define */

/**
  - `shrink.bless(f: a -> [a]): shrink a`

      Bless function with `.smap` property.

  - `.smap(f: a -> b, g: b -> a): shrink b`

      Transform `shrink a` into `shrink b`. For example:

      ```js
      positiveIntegersShrink = nat.shrink.smap(
        function (x) { return x + 1; },
        function (x) { return x - 1; });
      ```
*/
function shrinkBless(shrink) {
  shrink.smap = shrinkProtoIsoMap;
  return shrink;
}

/**
  - `shrink.noop: shrink a`
*/
var shrinkNoop = shrinkBless(function shrinkNoop() {
  return [];
});

/**
  - `shrink.pair(shrA: shrink a, shrB: shrink b): shrink (a, b)`
*/
function shrinkPair(shrinkA, shrinkB) {
  var result = shrinkBless(function (pair) {
    assert(pair.length === 2, "shrinkPair: pair should be an Array of length 2");

    var a = pair[0];
    var b = pair[1];

    var shrinkedA = shrinkA(a);
    var shrinkedB = shrinkB(b);

    var pairA = shrinkedA.map(function (ap) {
      return [ap, b];
    });

    if (Array.isArray(pairA)) {
      pairA = lazyseq.fromArray(pairA);
    }

    return pairA.append(function () {
      var pairB = shrinkedB.map(function (bp) {
        return [a, bp];
      });
      return pairB;
    });
  });

  return utils.curried3(result, arguments);
}

/**
  - `shrink.either(shrA: shrink a, shrB: shrink b): shrink (either a b)`
*/
function shrinkEither(shrinkA, shrinkB) {
  function shrinkLeft(value) {
    return shrinkA(value).map(either.left);
  }

  function shrinkRight(value) {
    return shrinkB(value).map(either.right);
  }

  var result = shrinkBless(function (e) {
    return e.either(shrinkLeft, shrinkRight);
  });

  return utils.curried3(result, arguments);
}

// We represent non-empty linked list as
// singl x = [x]
// cons h t = [h, t]
function fromLinkedList(ll) {
  assert(ll.length === 1 || ll.length === 2, "linked list must be either [] or [x, linkedlist]");
  if (ll.length === 1) {
    return [ll[0]];
  } else {
    return [ll[0]].concat(fromLinkedList(ll[1]));
  }
}

function toLinkedList(arr) {
  assert(Array.isArray(arr) && arr.length > 0, "toLinkedList expects non-empty array");
  if (arr.length === 1) {
    return [arr[0]];
  } else {
    return [arr[0], toLinkedList(arr.slice(1))];
  }
}

function toSingleton(x) {
  return [x];
}

// Vec a 1 → a
function fromSingleton(a) {
  return a[0];
}

function flattenShrink(shrinksLL) {
  if (shrinksLL.length === 1) {
    return shrinksLL[0].smap(toSingleton, fromSingleton);
  } else {
    var head = shrinksLL[0];
    var tail = shrinksLL[1];
    return shrinkPair(head, flattenShrink(tail));
  }
}

/**
  - `shrink.tuple(shrs: (shrink a, shrink b...)): shrink (a, b...)`
*/
function shrinkTuple(shrinks) {
  assert(shrinks.length > 0, "shrinkTuple needs > 0 values");
  var shrinksLL = toLinkedList(shrinks);
  var shrink = flattenShrink(shrinksLL);
  var result = shrinkBless(function (tuple) {
    assert(tuple.length === shrinks.length, "shrinkTuple: not-matching params");
    var ll = toLinkedList(tuple);
    return shrink(ll).map(fromLinkedList);
  });

  return utils.curried2(result, arguments);
}

/**
  - `shrink.sum(shrs: (shrink a, shrink b...)): shrink (a | b...)`
*/
function shrinkSum(shrinks) {
  assert(shrinks.length > 0, "shrinkTuple needs > 0 values");
  var result = shrinkBless(function (s) {
    return s.fold(function (idx, len, value) {
      assert(len === shrinks.length, "shrinkSum: not-matching params");
      return shrinks[idx](value).map(function (shrinked) {
        return sum.addend(idx, len, shrinked);
      });
    });
  });

  return utils.curried2(result, arguments);
}

function shrinkArrayWithMinimumSize(size) {
  function shrinkArrayImpl(shrink) {
    var result = shrinkBless(function (arr) {
      assert(Array.isArray(arr), "shrinkArrayImpl() expects array, got: " + arr);
      if (arr.length <= size) {
        return lazyseq.nil;
      } else {
        var x = arr[0];
        var xs = arr.slice(1);

        return lazyseq.cons(xs, lazyseq.nil)
          .append(shrink(x).map(function (xp) { return [xp].concat(xs); }))
          .append(shrinkArrayImpl(shrink, xs).map(function (xsp) { return [x].concat(xsp); }));
      }
    });

    return utils.curried2(result, arguments);
  }

  return shrinkArrayImpl;
}

/**
  - `shrink.array(shr: shrink a): shrink (array a)`
*/
var shrinkArray = shrinkArrayWithMinimumSize(0);

/**
  - `shrink.nearray(shr: shrink a): shrink (nearray a)`
*/
var shrinkNEArray = shrinkArrayWithMinimumSize(1);

module.exports = {
  noop: shrinkNoop,
  pair: shrinkPair,
  either: shrinkEither,
  tuple: shrinkTuple,
  sum: shrinkSum,
  array: shrinkArray,
  nearray: shrinkNEArray,
  bless: shrinkBless,
};
