# rc4

[RC4](http://en.wikipedia.org/wiki/RC4) random number generator

[![Build Status](https://secure.travis-ci.org/phadej/rc4.svg?branch=master)](http://travis-ci.org/phadej/rc4)
[![NPM version](https://badge.fury.io/js/rc4.svg)](http://badge.fury.io/js/rc4)
[![Dependency Status](https://gemnasium.com/phadej/rc4.svg)](https://gemnasium.com/phadej/rc4)
[![Code Climate](https://img.shields.io/codeclimate/github/phadej/rc4.svg)](https://codeclimate.com/github/phadej/rc4)

## Synopsis

```js
var RC4 = require("rc4");

var generator = new RC4("my seed"); // string or array of integers
// without the seed RNG is seeded with random data from Math.random

// byte := integer ∈ [0, 255]
console.log(generator.randomByte());

// float := number ∈ [0, 1)
console.log(generator.randomFloat());

// save & load state
var state = generator.currentState();
console.log(generator.randomFloat()); // 0.14815412228927016
generator.setState(state);
console.log(generator.randomFloat()); // 0.14815412228927016
```

## RC4small

There is also `RC4small` generator, with smaller internal state:

```
var RC4small = require("rc4").RC4small;

var generator = new RC4small("my seed");

var stateString = generator.currentStateString(); // 18 character hexadecimal string
console.log(generator.randomFloat());  // 0.9362740234937519
generator.setStateString(stateString);
console.log(generator.randomFloat()); // 0.9362740234937519
```

## API

Both `RC4` and `RC4small` have following random value generating methods:

```
randomByte   : () ⇒ { x : ℕ | x ∈ [0, 255] }
randomUInt32 : () ⇒ { x : ℕ | x ∈ [0, 2^32-1] }
randomFloat  : () ⇒ { x : R | x ∈ [0, 1) }
random       : (a : ℤ)        ⇒ { x : ℤ | x ∈ [0, a] }
random       : (a : ℤ, b : ℤ) ⇒ { x : ℤ | x ∈ [a, b] }
```

## Changelog

- **0.1.5** &mdash; *2015-04-24* &mdash; Better isInteger, random works with bigger ranges
- **0.1.4** &mdash; *2015-02-25* &mdash; Dev dependencies update
