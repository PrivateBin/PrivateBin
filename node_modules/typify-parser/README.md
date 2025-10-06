# typify type parser

> Type signature parser for typify

[![Build Status](https://secure.travis-ci.org/phadej/typify-parser.svg?branch=master)](http://travis-ci.org/phadej/typify-parser)
[![NPM version](https://badge.fury.io/js/typify-parser.svg)](http://badge.fury.io/js/typify-parser)
[![Dependency Status](https://david-dm.org/phadej/typify-parser.svg)](https://david-dm.org/phadej/typify-parser)
[![devDependency Status](https://david-dm.org/phadej/typify-parser/dev-status.svg)](https://david-dm.org/phadej/typify-parser#info=devDependencies)
[![Code Climate](https://img.shields.io/codeclimate/github/phadej/typify-parser.svg)](https://codeclimate.com/github/phadej/typify-parser)

Turns `(foo, bar 42) -> quux` into
```js
{
  "type": "function",
  "arg": {
    "type": "product",
    "args": [
      {
        "type": "ident",
        "value": "foo"
      },
      {
        "type": "application",
        "callee": {
          "type": "ident",
          "value": "bar"
        },
        "args": [
          {
            "type": "number",
            "value": 42
          }
        ]
      }
    ]
  },
  "result": {
    "type": "ident",
    "value": "quux"
  }
}
```

## Synopsis

```js
var parser = require("typify-parser");

// Example from above
var t = parser("(foo, bar 42) -> quux");

// Free vars
p.freeVars(t);                             // ['bar', 'foo', 'quux']
p.freeVars(p("rec list -> () | a & list")) // ['a']
```
