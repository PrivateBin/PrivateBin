/* @flow weak */
"use strict";

var trampa = require("trampa");

/**
  #### isPromise p : bool

  Optimistic duck-type check for promises.
  Returns `true` if p is an object with `.then` function property.
*/
function isPromise(p) {
  /* eslint-disable no-new-object */
  return new Object(p) === p && typeof p.then === "function";
  /* eslint-enable non-new-object */
}

/**
  #### map (Functor f) => (p : f a) (g : a -> b) : f b

  This is functor map, known as `map` or `fmap`.
  Essentially `f(p)`. If `p` is promise, returns new promise.
  Using `map` makes code look very much [CPS-style](http://en.wikipedia.org/wiki/Continuation-passing_style).
*/
function map(p, g) {
  if (isPromise(p)) {
    return p.then(function (x) {
      return map(x, g);
    });
  } else if (trampa.isTrampoline(p)) {
    return p.jump(function (x) {
      return map(x, g);
    });
  } else {
    return g(p);
  }
}

/**
  #### bind (Functor f) => (k : a -> f b) (xs : a) (h : b -> f c) -> f c

  This is almost monadic bind.
*/
function bind(f, xs, h) {
  var r;
  var exc;
  try {
    r = f.apply(undefined, xs);
  } catch (e) {
    r = false;
    exc = e;
  }

  if (isPromise(r)) {
    return r.then(
      h,
      function (e) {
        // exc is always unset here
        return h(false, e);
      }
    );
  } else {
    return h(r, exc);
  }
}

// recursively unwrap trampoline and promises
function run(x) {
  if (isPromise(x)) {
    return x.then(run);
  } else if (trampa.isTrampoline(x)) {
    return run(x.run());
  } else {
    return x;
  }
}

function pure(x) {
  if (isPromise(x)) {
    return x;
  } else {
    return trampa.wrap(x);
  }
}

module.exports = {
  isPromise: isPromise,
  map: map,
  pure: pure,
  bind: bind,
  run: run,
};
