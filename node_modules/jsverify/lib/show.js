
/* @flow weak */
"use strict";

/**
  ### Show functions
*/

var utils = require("./utils.js");

/**
  - `show.def(x : a): string`

      Currently implemented as `JSON.stringify`.
*/
function showDef(obj) {
  return JSON.stringify(obj);
}

/**
  - `show.pair(showA: a -> string, showB: b -> string, x: (a, b)): string`
*/
function showPair(showA, showB) {
  var result = function (p) {
    return "(" + showA(p[0]) + ", " + showB(p[1]) + ")";
  };

  return utils.curried3(result, arguments);
}

/**
  - `show.either(showA: a -> string, showB: b -> string, e: either a b): string`
*/
function showEither(showA, showB) {
  function showLeft(value) {
    return "Left(" + showA(value) + ")";
  }

  function showRight(value) {
    return "Right(" + showB(value) + ")";
  }

  var result = function (e) {
    return e.either(showLeft, showRight);
  };

  return utils.curried3(result, arguments);
}

/**
  - `show.tuple(shrinks: (a -> string, b -> string...), x: (a, b...)): string`
*/
function showTuple(shows) {
  var result = function (objs) {
    var strs = [];
    for (var i = 0; i < shows.length; i++) {
      strs.push(shows[i](objs[i]));
    }
    return strs.join("; ");
  };

  return utils.curried2(result, arguments);
}

/**
  - `show.sum(shrinks: (a -> string, b -> string...), x: (a | b ...)): string`
*/
function showSum(shows) {
  var result = function (sum) {
    return sum.fold(function (idx, n, value) {
      return "Sum(" + idx + "/" + n + ": " + shows[idx](value) + ")";
    });
  };

  return utils.curried2(result, arguments);
}

/**
  - `show.array(shrink: a -> string, x: array a): string`
*/
function showArray(show) {
  var result = function (arr) {
    return "[" + arr.map(show).join(", ") + "]";
  };

  return utils.curried2(result, arguments);
}

module.exports = {
  def: showDef,
  pair: showPair,
  either: showEither,
  tuple: showTuple,
  sum: showSum,
  array: showArray,
};
