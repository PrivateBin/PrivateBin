/* @flow weak */
"use strict";

var isArray = Array.isArray;
function isObject(o) {
  /* eslint-disable no-new-object */
  return new Object(o) === o;
  /* eslint-enable no-new-object */
}

/* undefined-safe isNaN */
function isNaN(n) {
  return typeof n === "number" && n !== n;
}

/**
  ### Utility functions

  Utility functions are exposed (and documented) only to make contributions to jsverify more easy.
  The changes here don't follow semver, i.e. there might be backward-incompatible changes even in patch releases.

  Use [underscore.js](http://underscorejs.org/), [lodash](https://lodash.com/), [ramda](http://ramda.github.io/ramdocs/docs/), [lazy.js](http://danieltao.com/lazy.js/) or some other utility belt.
*/

/* Simple sort */
function sort(arr) {
  var res = arr.slice();
  res.sort();
  return res;
}

/**
  - `utils.isEqual(x: json, y: json): bool`

      Equality test for `json` objects.
*/
function isEqual(a, b) {
  var i;

  if (isNaN(a) && isNaN(b)) {
    return true;
  }

  if (a === b) {
    return true;
  } else if (isArray(a) && isArray(b) && a.length === b.length) {
    for (i = 0; i < a.length; i++) {
      if (!isEqual(a[i], b[i])) {
        return false;
      }
    }
    return true;
  } else if (isObject(a) && isObject(b) && !isArray(a) && !isArray(b)) {
    var akeys = Object.keys(a);
    var bkeys = Object.keys(b);
    if (!isEqual(sort(akeys), sort(bkeys))) {
      return false;
    }

    for (i = 0; i < akeys.length; i++) {
      if (!isEqual(a[akeys[i]], b[akeys[i]])) {
        return false;
      }
    }
    return true;
  }

  return false;
}

/**
  - `utils.isApproxEqual(x: a, y: b, opts: obj): bool`

      Tests whether two objects are approximately and optimistically equal.
      Returns `false` only if they are distinguishable not equal.
      Returns `true` when `x` and `y` are `NaN`.
      This function works with cyclic data.

      Takes optional 'opts' parameter with properties:

      - `fnEqual` - whether all functions are considered equal (default: yes)
      - `depth` - how deep to recurse until treating as equal (default: 5)
*/
function isApproxEqual(x, y, opts) {
  opts = opts || {};
  var fnEqual = opts.fnEqual !== false;
  var depth = opts.depth || 5; // totally arbitrary

  // state contains pairs we checked (or are still checking, but assume equal!)
  var state = [];

  // eslint-disable-next-line complexity
  function loop(a, b, n) {
    if (isNaN(a) && isNaN(b)) {
      return true;
    }

    // trivial check
    if (a === b) {
      return true;
    }

    // depth check
    if (n >= depth) {
      return true;
    }

    var i;

    // check if pair already occured
    for (i = 0; i < state.length; i++) {
      if (state[i][0] === a && state[i][1] === b) {
        return true;
      }
    }

    // add to state
    state.push([a, b]);

    if (typeof a === "function" && typeof b === "function") {
      return fnEqual;
    }

    if (isArray(a) && isArray(b) && a.length === b.length) {
      for (i = 0; i < a.length; i++) {
        if (!loop(a[i], b[i], n + 1)) {
          return false;
        }
      }
      return true;
    } else if (isObject(a) && isObject(b) && !isArray(a) && !isArray(b)) {
      var akeys = Object.keys(a);
      var bkeys = Object.keys(b);
      if (!loop(sort(akeys), sort(bkeys), n + 1)) {
        return false;
      }

      for (i = 0; i < akeys.length; i++) {
        if (!loop(a[akeys[i]], b[akeys[i]], n + 1)) {
          return false;
        }
      }
      return true;
    }

    return false;
  }
  return loop(x, y, 0);
}

function identity(x) {
  return x;
}

function pluck(arr, key) {
  return arr.map(function (e) {
    return e[key];
  });
}

/**
  - `utils.force(x: a | () -> a) : a`

      Evaluate `x` as nullary function, if it is one.
*/
function force(arb) {
  return (typeof arb === "function") ? arb() : arb;
}

/**
  - `utils.merge(x... : obj): obj`

    Merge two objects, a bit like `_.extend({}, x, y)`.
*/
function merge() {
  var res = {};

  for (var i = 0; i < arguments.length; i++) {
    var arg = arguments[i];
    var keys = Object.keys(arg);
    for (var j = 0; j < keys.length; j++) {
      var key = keys[j];
      res[key] = arg[key];
    }
  }

  return res;
}

function div2(x) {
  return Math.floor(x / 2);
}

function log2(x) {
  return Math.log(x) / Math.log(2);
}

function ilog2(x) {
  return x <= 0 ? 0 : Math.floor(log2(x));
}

function curriedN(n) {
  var n1 = n - 1;
  return function curriedNInstance(result, args) {
    if (args.length === n) {
      return result(args[n1]);
    } else {
      return result;
    }
  };
}

var curried2 = curriedN(2);
var curried3 = curriedN(3);

function charArrayToString(arr) {
  return arr.join("");
}

function stringToCharArray(str) {
  return str.split("");
}

function pairArrayToDict(arrayOfPairs) {
  var res = {};
  arrayOfPairs.forEach(function (p) {
    res[p[0]] = p[1];
  });
  return res;
}

function dictToPairArray(m) {
  var res = [];
  Object.keys(m).forEach(function (k) {
    res.push([k, m[k]]);
  });
  return res;
}

function partition(arr, pred) {
  var truthy = [];
  var falsy = [];

  for (var i = 0; i < arr.length; i++) {
    var x = arr[i];
    if (pred(x)) {
      truthy.push(x);
    } else {
      falsy.push(x);
    }
  }

  return [truthy, falsy];
}

module.exports = {
  isArray: isArray,
  isObject: isObject,
  isEqual: isEqual,
  isApproxEqual: isApproxEqual,
  identity: identity,
  pluck: pluck,
  force: force,
  merge: merge,
  div2: div2,
  ilog2: ilog2,
  curried2: curried2,
  curried3: curried3,
  charArrayToString: charArrayToString,
  stringToCharArray: stringToCharArray,
  pairArrayToDict: pairArrayToDict,
  dictToPairArray: dictToPairArray,
  partition: partition,
};
