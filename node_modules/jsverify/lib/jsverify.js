/* @flow weak */
/**
  # JSVerify

  <img src="https://raw.githubusercontent.com/jsverify/jsverify/master/jsverify-300.png" align="right" height="100" />

  > Property-based checking. Like QuickCheck.

  [![Build Status](https://secure.travis-ci.org/jsverify/jsverify.svg?branch=master)](http://travis-ci.org/jsverify/jsverify)
  [![NPM version](https://badge.fury.io/js/jsverify.svg)](http://badge.fury.io/js/jsverify)
  [![Dependency Status](https://david-dm.org/jsverify/jsverify.svg)](https://david-dm.org/jsverify/jsverify)
  [![devDependency Status](https://david-dm.org/jsverify/jsverify/dev-status.svg)](https://david-dm.org/jsverify/jsverify#info=devDependencies)
  [![Code Climate](https://img.shields.io/codeclimate/github/jsverify/jsverify.svg)](https://codeclimate.com/github/jsverify/jsverify)

  ## Getting Started

  Install the module with: `npm install jsverify`

  ## Synopsis

  ```js
  var jsc = require("jsverify");

  // forall (f : bool -> bool) (b : bool), f (f (f b)) = f(b).
  var boolFnAppliedThrice =
    jsc.forall("bool -> bool", "bool", function (f, b) {
      return f(f(f(b))) === f(b);
    });

  jsc.assert(boolFnAppliedThrice);
  // OK, passed 100 tests
  ```
*/
"use strict";

/**
  ## Documentation

  ### Usage with [mocha](http://mochajs.org/)

  Using jsverify with mocha is easy, just define the properties and use `jsverify.assert`.

  Starting from version 0.4.3 you can write your specs without any boilerplate:

  ```js
  describe("sort", function () {
    jsc.property("idempotent", "array nat", function (arr) {
      return _.isEqual(sort(sort(arr)), sort(arr));
    });
  });
  ```

  Starting from version 0.8.0 you can write the specs in TypeScript. There are
  typings provided. The drawback is that you cannot use type DSL:

  ```typescript
  describe("basic jsverify usage", () => {
    jsc.property("(b && b) === b", jsc.bool, b => (b && b) === b);

    jsc.property("boolean fn thrice", jsc.fn(jsc.bool), jsc.bool, (f, b) =>
      f(f(f(b))) === f(b)
    );
  });
  ```

  You can also provide `--jsverifyRngState state` command line argument, to run tests with particular random generator state.

  ```
  $ mocha examples/nat.js

  1) natural numbers are less than 90:
   Error: Failed after 49 tests and 1 shrinks. rngState: 074e9b5f037a8c21d6; Counterexample: 90;

  $ mocha examples/nat.js --grep 'are less than' --jsverifyRngState 074e9b5f037a8c21d6

  1) natural numbers are less than 90:
     Error: Failed after 1 tests and 1 shrinks. rngState: 074e9b5f037a8c21d6; Counterexample: 90;
  ```

  Erroneous case is found with first try.

  ### Usage with [jasmine](https://jasmine.github.io/)

  Check [jasmineHelpers2.js](helpers/jasmineHelpers2.js) for jasmine 2.0.

  ## API Reference

  > _Testing shows the presence, not the absence of bugs._
  >
  > Edsger W. Dijkstra

  To show that propositions hold, we need to construct proofs.
  There are two extremes: proof by example (unit tests) and formal (machine-checked) proof.
  Property-based testing is somewhere in between.
  We formulate propositions, invariants or other properties we believe to hold, but
  only test it to hold for numerous (randomly generated) values.

  Types and function signatures are written in [Coq](http://coq.inria.fr/)/[Haskell](http://www.haskell.org/haskellwiki/Haskell)-influenced style:
  C# -style `List<T> filter(List<T> v, Func<T, bool> predicate)` is represented by
  `filter(v: array T, predicate: T -> bool): array T` in our style.

  Methods and objects live in `jsc` object, e.g. `shrink.bless` method is used by
  ```js
  var jsc = require("jsverify");
  var foo = jsc.shrink.bless(...);
  ```

  Methods starting with `.dot` are prototype methods:
  ```js
  var arb = jsc.nat;
  var arb2 = jsc.nat.smap(f, g);
  ```

  `jsverify` can operate with both synchronous and asynchronous-promise properties.
  Generally every property can be wrapped inside [functor](http://learnyouahaskell.com/functors-applicative-functors-and-monoids),
  for now in either identity or promise functor, for synchronous and promise properties respectively.
*/

var assert = require("assert");
var lazyseq = require("lazy-seq");

var api = require("./api.js");
var either = require("./either.js");
var environment = require("./environment.js");
var FMap = require("./finitemap.js");
var fn = require("./fn.js");
var functor = require("./functor.js");
var random = require("./random.js");
var show = require("./show.js");
var shrink = require("./shrink.js");
var suchthat = require("./suchthat.js");
var sum = require("./sum.js");
var typify = require("./typify.js");
var utils = require("./utils.js");

/**
  ### Properties
*/

function shrinkResult(arbs, x, test, size, shrinksN, exc, transform) {
  assert(arbs.length === x.length, "shrinkResult: arbs and x has to be of same size");
  assert(typeof size === "number", "shrinkResult: size should be number");
  assert(typeof shrinksN === "number", "shrinkResult: shrinkN should be number");

  var shrinks = utils.pluck(arbs, "shrink");
  var shows = utils.pluck(arbs, "show");

  var shrinked = shrink.tuple(shrinks, x);

  var shrinkP = lazyseq.fold(shrinked, true, function (y, rest) {
    var t = test(size, y, shrinksN + 1);
    return functor.map(t, function (tprime) {
      return tprime !== true ? tprime : rest();
    });
  });

  return functor.map(shrinkP, function (shrinkPPrime) {
    if (shrinkPPrime === true) {
      var res = {
        counterexample: x,
        counterexamplestr: show.tuple(shows, x),
        shrinks: shrinksN,
        exc: exc,
      };
      return transform(res);
    } else {
      return shrinkPPrime;
    }
  });
}

function isArbitrary(arb) {
  return (typeof arb === "object" || typeof arb === "function") &&
    typeof arb.generator === "function" &&
    typeof arb.shrink === "function" &&
    typeof arb.show === "function";
}

/**
  - `forall(arbs: arbitrary a ..., userenv: (map arbitrary)?, prop : a -> property): property`

      Property constructor
*/
function forall() {
  var args = Array.prototype.slice.call(arguments);
  var gens = args.slice(0, -1);
  var property = args[args.length - 1];
  var env;

  var lastgen = gens[gens.length - 1];

  if (!isArbitrary(lastgen) && typeof lastgen !== "string") {
    env = utils.merge(environment, lastgen);
    gens = gens.slice(0, -1);
  } else {
    env = environment;
  }

  assert(gens.length > 0, "forall requires at least single generator");

  // Map typify-dsl to hard generators
  gens = gens.map(function (g) {
    g = typeof g === "string" ? typify.parseTypify(env, g) : g;
    return utils.force(g);
  });

  assert(typeof property === "function", "property should be a function");

  function test(size, x, shrinks) {
    assert(Array.isArray(x), "generators results should be always tuple");

    return functor.bind(property, x, function (r, exc) {
      if (r === true) {
        return true;
      } else if (typeof r === "function") {
        var rRec = r(size);

        return functor.map(rRec, function (rRecPrime) {
          if (rRecPrime === true) {
            return true;
          } else {
            return shrinkResult(gens, x, test, size, shrinks, exc, function (rr) {
              return {
                counterexample: rr.counterexample.concat(rRecPrime.counterexample),
                counterexamplestr: rr.counterexamplestr, // + "; " + rRec.counterexamplestr,
                shrinks: rr.shrinks,
                exc: rr.exc || rRecPrime.exc,
              };
            });
          }
        });
      } else {
        return shrinkResult(gens, x, test, size, shrinks, exc || r, utils.identity);
      }
    });
  }

  return function (size) {
    var x = gens.map(function (arb) { return arb.generator(size); });
    var r = test(size, x, 0);
    return r;
  };
}

function formatFailedCase(r, state, includeStack) {
  var msg = "Failed after " + r.tests + " tests and " + r.shrinks + " shrinks. ";
  msg += "rngState: " + (r.rngState || state) + "; ";
  msg += "Counterexample: " + r.counterexamplestr + "; ";
  if (r.exc) {
    if (r.exc instanceof Error) {
      msg += "Exception: " + r.exc.message;
      if (includeStack) {
        msg += "\nStack trace: " + r.exc.stack;
      }
    } else {
      msg += "Error: " + r.exc;
    }
  }
  return msg;
}

function findRngState(argv) { // eslint-disable-line consistent-return
  for (var i = 0; i < argv.length - 1; i++) {
    if (argv[i] === "--jsverifyRngState") {
      return argv[i + 1];
    }
  }
}

/**
  - `check (prop: property, opts: checkoptions?): result`

      Run random checks for given `prop`. If `prop` is promise based, result is also wrapped in promise.

      Options:
      - `opts.tests` - test count to run, default 100
      - `opts.size`  - maximum size of generated values, default 50
      - `opts.quiet` - do not `console.log`
      - `opts.rngState` - state string for the rng

      The `result` is `true` if check succeeds, otherwise it's an object with various fields:
      - `counterexample` - an input for which property fails.
      - `tests` - number of tests run before failing case is found
      - `shrinks` - number of shrinks performed
      - `exc` - an optional exception thrown by property function
      - `rngState` - random number generator's state before execution of the property
*/
function check(property, opts) {
  opts = opts || {};
  opts.size = opts.size || 50;
  opts.tests = opts.tests || 100;
  opts.quiet = opts.quiet || false;

  assert(typeof property === "function", "property should be a function");

  var state;

  if (opts.rngState) {
    random.setStateString(opts.rngState);
  } else if (typeof process !== "undefined") {
    var argvState = findRngState(process.argv);
    if (argvState) {
      random.setStateString(argvState);
    }
  }

  function loop(i) {
    state = random.currentStateString();
    if (i > opts.tests) {
      return true;
    }

    var size = random(0, opts.size);

    // wrap non-promises in trampoline
    var r = functor.pure(property(size));

    return functor.map(r, function (rPrime) {
      if (rPrime === true) {
        return loop(i + 1);
      } else {
        rPrime.tests = i;
        /* global console */
        if (!opts.quiet) {
          console.error(formatFailedCase(rPrime, state, true), rPrime.counterexample);
        }
        return rPrime;
      }
    });
  }

  return functor.run(functor.map(loop(1), function (r) {
    if (r === true) {
      if (!opts.quiet) { console.info("OK, passed " + opts.tests + " tests"); }
    } else {
      r.rngState = state;
    }

    return r;
  }));
}

/**
  - `assert(prop: property, opts: checkoptions?) : void`

      Same as `check`, but throw exception if property doesn't hold.
*/
function checkThrow(property, opts) {
  opts = opts || {};
  if (opts.quiet === undefined) {
    opts.quiet = true;
  }

  return functor.run(functor.map(check(property, opts), function (r) {
    if (r !== true) {
      if (r.exc instanceof Error) {
        r.exc.message = formatFailedCase(r);
        throw r.exc;
      } else {
        throw new Error(formatFailedCase(r));
      }
    }
  }));
}

/**
   - `property(name: string, ...)`

      Assuming there is globally defined `it`, the same as:

      ```js
      it(name, function () {
        jsc.assert(jsc.forall(...));
      }
      ```

      You can use `property` to write facts too:
      ```js
      jsc.property("+0 === -0", function () {
        return +0 === -0;
      });
      ```
*/
function bddProperty(name) {
  /* global it: true */
  var args = Array.prototype.slice.call(arguments, 1);
  if (args.length === 1) {
    it(name, function () {
      return functor.run(functor.map(args[0](), function (result) { // eslint-disable-line consistent-return
        if (typeof result === "function") {
          return checkThrow(result);
        } else if (result !== true) {
          throw new Error(name + " doesn't hold");
        }
      }));
    });
  } else {
    var prop = forall.apply(undefined, args);
    it(name, function () {
      return checkThrow(prop);
    });
  }
  /* global it: false */
}

/**
  - `compile(desc: string, env: typeEnv?): arbitrary a`

      Compile the type description in provided type environment, or default one.
*/
function compile(str, env) {
  env = env ? utils.merge(environment, env) : environment;
  return typify.parseTypify(env, str);
}

/**
  - `sampler(arb: arbitrary a, genSize: nat = 10): (sampleSize: nat?) -> a`

      Create a sampler for a given arbitrary with an optional size. Handy when used in
      a REPL:
      ```
      > jsc = require('jsverify') // or require('./lib/jsverify') w/in the project
      ...
      > jsonSampler = jsc.sampler(jsc.json, 4)
      [Function]
      > jsonSampler()
      0.08467432763427496
      > jsonSampler()
      [ [ [] ] ]
      > jsonSampler()
      ''
      > sampledJson(2)
      [-0.4199344692751765, false]
      ```
*/
function sampler(arb, size) {
  size = typeof size === "number" ? Math.abs(size) : 10;
  return function (count) {
    if (typeof count === "number") {
      var acc = [];
      count = Math.abs(count);
      for (var i = 0; i < count; i++) {
        acc.push(arb.generator(size));
      }
      return acc;
    } else {
      return arb.generator(size);
    }
  };
}

/**
  - `throws(block: () -> a, error: class?, message: string?): bool`

    Executes nullary function `block`. Returns `true` if `block` throws. See [assert.throws](https://nodejs.org/api/assert.html#assert_assert_throws_block_error_message)
*/
function throws(block, error, message) {
  assert(error === undefined || typeof error === "function", "throws: error parameter must be a constructor");
  assert(message === undefined || typeof message === "string", "throws: message parameter must be a string");

  try {
    block();
    return false;
  } catch (e) {
    if (error !== undefined) {
      if (e instanceof error) {
        return message === undefined || e.message === message;
      } else {
        return false;
      }
    } else {
      return true;
    }
  }
}

/**
  - `assertForall(arbs: arbitrary a ..., userenv: (map arbitrary)?, prop : a -> property): void`

     Combines 'assert' and 'forall'.
     Constructs a property with forall from arguments, then throws an exception if the property doesn't hold.
     Options for 'assert' cannot be set here - use assert(forall(...)) if you need that.
*/
function assertForall() {
  return checkThrow(forall.apply(null, arguments));
}

/**
  - `checkForall(arbs: arbitrary a ..., userenv: (map arbitrary)?, prop : a -> property): result`

    Combines 'check' and 'forall'.
    Constructs a property with forall from arguments, and returns a value based on if the property holds or not.
    See 'check' for description of return value.

    Options for 'check' cannot be set here - use check(forall(...)) if you need that.
*/
function checkForall() {
  return check(forall.apply(null, arguments));
}

/**
  ### Types

  - `generator a` is a function `(size: nat) -> a`.
  - `show` is a function `a -> string`.
  - `shrink` is a function `a -> [a]`, returning *smaller* values.
  - `arbitrary a` is a triple of generator, shrink and show functions.
      - `{ generator: nat -> a, shrink : a -> array a, show: a -> string }`

  ### Blessing

  We chose to represent generators and shrinks by functions, yet we would
  like to have additional methods on them. Thus we *bless* objects with
  additional properties.

  Usually you don't need to bless anything explicitly, as all combinators
  return blessed values.

  See [perldoc for bless](http://perldoc.perl.org/functions/bless.html).
*/

/// include ./typify.js
/// include ./arbitraryBless.js
/// include ./bless.js
/// include ./primitive.js
/// include ./arbitrary.js
/// include ./recordWithEnv.js
/// include ./record.js
/// include ./string.js
/// include ./fn.js
/// include ./small.js
/// include ./suchthat.js
/// include ./generator.js
/// include ./shrink.js
/// include ./show.js
/// include ./random.js
/// include ./either.js
/// include ./utils.js

// Export
var jsc = {
  forall: forall,
  check: check,
  assert: checkThrow,
  assertForall: assertForall,
  checkForall: checkForall,
  property: bddProperty,
  sampler: sampler,
  throws: throws,

  // generators
  fn: fn.fn,
  fun: fn.fn,
  suchthat: suchthat.suchthat,

  // either
  left: either.left,
  right: either.right,

  // sum
  addend: sum.addend,

  // compile
  compile: compile,

  generator: api.generator,
  shrink: api.shrink,

  // internal utility lib
  random: random,

  show: show,
  utils: utils,
  _: {
    FMap: FMap,
  },
};

/* primitives */
/* eslint-disable guard-for-in */
for (var k in api.arbitrary) {
  jsc[k] = api.arbitrary[k];
}
/* eslint-enable guard-for-in */

module.exports = jsc;

/// plain ../FAQ.md
/// plain ../CONTRIBUTING.md
/// plain ../CHANGELOG.md
/// plain ../related-work.md
/// plain ../LICENSE
