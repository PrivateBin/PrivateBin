/* @flow weak */
"use strict";

var arbitraryBless = require("./arbitraryBless.js");
var assert = require("assert");
var generator = require("./generator.js");
var random = require("./random.js");
var show = require("./show.js");
var shrink = require("./shrink.js");
var utils = require("./utils.js");

/**
  ### Primitive arbitraries
*/

function extendWithDefault(arb) {
  var def = arb();
  arb.generator = def.generator;
  arb.shrink = def.shrink;
  arb.show = def.show;
  arb.smap = def.smap;
}

function numeric(impl) {
  return function (minsize, maxsize) {
    if (arguments.length === 2) {
      var arb = arbitraryBless(impl(maxsize - minsize));
      var to = function to(x) {
        return Math.abs(x) + minsize;
      };
      var from = function from(x) {
        return x - minsize;
      };

      return arb.smap(to, from);
    } else if (arguments.length === 1) {
      return arbitraryBless(impl(minsize /* as maxsize */));
    } else {
      return arbitraryBless(impl());
    }
  };
}

/**
  - `integer: arbitrary integer`
  - `integer(maxsize: nat): arbitrary integer`
  - `integer(minsize: integer, maxsize: integer): arbitrary integer`

      Integers, ℤ
*/
var integer = numeric(function integer(maxsize) {
  return {
    generator: generator.bless(function (size) {
      size = maxsize === undefined ? size : maxsize;
      return random(-size, size);
    }),
    shrink: shrink.bless(function (i) {
      assert(typeof i === "number", "integer.shrink have to be a number");

      i = Math.abs(i);
      if (i === 0) {
        return [];
      } else {
        var arr = [0];
        var j = utils.div2(i);
        var k = Math.max(j, 1);
        while (j < i) {
          arr.push(j);
          arr.push(-j);
          k = Math.max(utils.div2(k), 1);
          j += k;
        }
        return arr;
      }
    }),

    show: show.def,
  };
});

extendWithDefault(integer);

/**
  - `nat: arbitrary nat`
  - `nat(maxsize: nat): arbitrary nat`

      Natural numbers, ℕ (0, 1, 2...)
*/
function nat(maxsize) {
  return arbitraryBless({
    generator: generator.bless(function (size) {
      size = maxsize === undefined ? size : maxsize;
      return random(0, size);
    }),
    shrink: shrink.bless(function (i) {
      assert(typeof i === "number", "nat.shrink have to be a number");

      var arr = [];
      var j = utils.div2(i);
      var k = Math.max(j, 1);
      while (j < i) {
        arr.push(j);
        k = Math.max(utils.div2(k), 1);
        j += k;
      }
      return arr;
    }),
    show: show.def,
  });
}

extendWithDefault(nat);

/**
  - `number: arbitrary number`
  - `number(maxsize: number): arbitrary number`
  - `number(min: number, max: number): arbitrary number`

      JavaScript numbers, "doubles", ℝ. `NaN` and `Infinity` are not included.
*/
var number = numeric(function number(maxsize) {
  return {
    generator: generator.bless(function (size) {
      size = maxsize === undefined ? size : maxsize;
      return random.number(-size, size);
    }),
    shrink: shrink.bless(function (x) {
      assert(typeof x === "number", "number.shrink have to be a number");

      if (Math.abs(x) > 1e-6) {
        return [0, x / 2, -x / 2];
      } else {
        return [];
      }
    }),
    show: show.def,
  };
});

extendWithDefault(number);

/**
  - `uint8: arbitrary nat`
  - `uint16: arbitrary nat`
  - `uint32: arbitrary nat`
*/
var uint8 = nat(0xff);
var uint16 = nat(0xffff);
var uint32 = nat(0xffffffff);

/**
  - `int8: arbitrary integer`
  - `int16: arbitrary integer`
  - `int32: arbitrary integer`
*/
var int8 = integer(-0x80, 0x7f);
var int16 = integer(-0x8000, 0x7fff);
var int32 = integer(-0x80000000, 0x7fffffff);

/**
  - `bool: arbitrary bool`

      Booleans, `true` or `false`.
*/
var bool = arbitraryBless({
  generator: generator.bless(function (/* size */) {
    var i = random(0, 1);
    return i === 1;
  }),

  shrink: shrink.bless(function (b) {
    assert(b === true || b === false, "bool.shrink excepts true or false");
    return b === true ? [false] : [];
  }),
  show: show.def,
});

/**
  - `datetime: arbitrary datetime`

      Random datetime
*/
var datetimeConst = 1416499879495; // arbitrary datetime

function datetime(from, to) {
  var toDate;
  var fromDate;
  var arb;

  if (arguments.length === 2) {
    toDate = function toDateFn(x) {
      return new Date(x);
    };
    fromDate = function fromDateFn(x) {
      return x.getTime();
    };
    from = fromDate(from);
    to = fromDate(to);
    arb = number(from, to);

    return arb.smap(toDate, fromDate);
  } else {
    toDate = function toDateFn(x) {
      return new Date(x * 768000000 + datetimeConst);
    };
    arb = number;

    return arbitraryBless({
      generator: arb.generator.map(toDate),
      shrink: shrink.noop,
      show: show.def,
    });
  }
}

extendWithDefault(datetime);

/**
  - `elements(args: array a): arbitrary a`

      Random element of `args` array.
*/
function elements(args) {
  assert(args.length !== 0, "elements: at least one parameter expected");

  return arbitraryBless({
    generator: generator.bless(function (/* size */) {
      var i = random(0, args.length - 1);
      return args[i];
    }),

    shrink: shrink.bless(function (x) {
      var idx = args.indexOf(x);
      if (idx <= 0) {
        return [];
      } else {
        return args.slice(0, idx);
      }
    }),
    show: show.def,
  });
}

/**
  - `falsy: arbitrary *`

      Generates falsy values: `false`, `null`, `undefined`, `""`, `0`, and `NaN`.
*/
var falsy = elements([false, null, undefined, "", 0, NaN]);
falsy.show = function (v) {
  if (v !== v) {
    return "falsy: NaN";
  } else if (v === "") {
    return "falsy: empty string";
  } else if (v === undefined) {
    return "falsy: undefined";
  } else {
    return "falsy: " + v;
  }
};

/**
  - `constant(x: a): arbitrary a`

      Returns an unshrinkable arbitrary that yields the given object.
*/
function constant(x) {
  return arbitraryBless({
    generator: generator.constant(x),
    shrink: shrink.noop,
    show: show.def,
  });
}

module.exports = {
  integer: integer,
  nat: nat,
  int8: int8,
  int16: int16,
  int32: int32,
  uint8: uint8,
  uint16: uint16,
  uint32: uint32,
  number: number,
  elements: elements,
  bool: bool,
  falsy: falsy,
  constant: constant,
  datetime: datetime,
};
