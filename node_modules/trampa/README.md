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

- `isTrampoline(t: obj): bool` &mdash; Returns, whether `t` is a trampolined object.

- `wrap(t: Trampoline a | a): Trampoline a` &mdash; Wrap `t` into trampoline, if it's not already one.

- `lazy(t : () -> Trampoline a | a)` &mdash; Wrap lazy computation into trampoline. Useful when constructing computations.

- `Trampoline.jump(f : a -> b | Trampoline b)` &mdash; *map* or *flatmap* trampoline computation. Like `.then` for promises.

- `Trampoline.run(): a` &mdash; Run the trampoline synchronously resulting a value.

## Changelog

- **1.0.0** &mdash; *2015-07-14* &mdash; Initial release
