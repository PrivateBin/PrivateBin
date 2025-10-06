"use strict";

/**

# trampa

Trampolines, to emulate tail-call recursion.

[![Build Status](https://secure.travis-ci.org/phadej/trampa.svg?branch=master)](http://travis-ci.org/phadej/trampa)
[![NPM version](https://badge.fury.io/js/trampa.svg)](http://badge.fury.io/js/trampa)
[![Dependency Status](https://david-dm.org/phadej/trampa.svg)](https://david-dm.org/trampa/trampa)
[![devDependency Status](https://david-dm.org/phadej/trampa/dev-status.svg)](https://david-dm.org/trampa/trampa#info=devDependencies)
[![Code Climate](https://img.shields.io/codeclimate/github/phadej/trampa.svg)](https://codeclimate.com/github/phadej/trampa)

## Synopsis

```js
var trampa = require("trampa");

function loop(n, acc) {
  return n === 0 ? trampa.wrap(acc) : trampa.lazy(function () {
    return loop(n - 1, acc + 1);
  });
}

loop(123456789, 0).run(); // doesn't cause stack overflow!
```

## API

*/

// loosely based on https://apocalisp.wordpress.com/2011/10/26/tail-call-elimination-in-scala-monads/

var assert = require("assert");

function Done(x) {
  this.x = x;
}

function Cont(tramp, cont) {
  assert(typeof cont === "function");
  this.tramp = tramp;
  this.cont = cont;
}

/**
- `isTrampoline(t: obj): bool` &mdash; Returns, whether `t` is a trampolined object.
*/
function isTrampoline(t) {
  return t instanceof Done || t instanceof Cont;
}

/**
- `wrap(t: Trampoline a | a): Trampoline a` &mdash; Wrap `t` into trampoline, if it's not already one.
*/
function wrap(t) {
  return isTrampoline(t) ? t : new Done(t);
}

/**
- `lazy(t : () -> Trampoline a | a)` &mdash; Wrap lazy computation into trampoline. Useful when constructing computations.
*/
function lazy(computation) {
  assert(typeof computation === "function", "lazy: computation should be function");
  return wrap().jump(computation);
}

/**
- `Trampoline.jump(f : a -> b | Trampoline b)` &mdash; *map* or *flatmap* trampoline computation. Like `.then` for promises.
*/
Done.prototype.jump = function (f) {
  return new Cont(this, function (x) {
    return wrap(f(x));
  });
};

Cont.prototype.jump = Done.prototype.jump;

function execute(curr, params) {
  params = params || {};
  var debug = params.debug || false;
  var log = params.log || console.log;
  var stack = [];

  while (true) { // eslint-disable-line no-constant-condition
    if (debug) {
      log("trampoline execute: stack size " + stack.length);
    }

    if (curr instanceof Done) {
      if (stack.length === 0) {
        return curr.x;
      } else {
        curr = stack[stack.length - 1](curr.x);
        stack.pop();
      }
    } else {
      assert(curr instanceof Cont);
      stack.push(curr.cont);
      curr = curr.tramp;
    }
  }
}

/**
- `Trampoline.run(): a` &mdash; Run the trampoline synchronously resulting a value.
*/
Done.prototype.run = Cont.prototype.run = function (params) {
  return execute(this, params);
};

module.exports = {
  isTrampoline: isTrampoline,
  wrap: wrap,
  lazy: lazy,
};

/**
## Changelog

- **1.0.0** &mdash; *2015-07-14* &mdash; Initial release
*/
