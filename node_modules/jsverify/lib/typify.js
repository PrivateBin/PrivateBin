/* @flow weak */
"use strict";

/**
  ### DSL for input parameters

  There is a small DSL to help with `forall`. For example the two definitions below are equivalent:
  ```js
  var bool_fn_applied_thrice = jsc.forall("bool -> bool", "bool", check);
  var bool_fn_applied_thrice = jsc.forall(jsc.fn(jsc.bool), jsc.bool, check);
  ```

  The DSL is based on a subset of language recognized by [typify-parser](https://github.com/phadej/typify-parser):
  - *identifiers* are fetched from the predefined environment.
  - *applications* are applied as one could expect: `"array bool"` is evaluated to `jsc.array(jsc.bool)`.
  - *functions* are supported: `"bool -> bool"` is evaluated to `jsc.fn(jsc.bool)`.
  - *square brackets* are treated as a shorthand for the array type: `"[nat]"` is evaluated to `jsc.array(jsc.nat)`.
  - *union*: `"bool | nat"` is evaluated to `jsc.sum([jsc.bool, jsc.nat])`.
      - **Note** `oneof` cannot be shrunk, because the union is untagged, we don't know which shrink to use.
  - *conjunction*: `"bool & nat"` is evaluated to `jsc.tuple(jsc.bool, jsc.nat)`.
  - *anonymous records*: `"{ b: bool; n: nat }"` is evaluated to `jsc.record({ b: jsc.bool, n: jsc.nat })`.
  - *EXPERIMENTAL: recursive types*: `"rec list -> unit | (nat & list)"`.
*/

var arbitrary = require("./arbitrary.js");
var assert = require("assert");
var record = require("./record.js");
var array = require("./array.js");
var fn = require("./fn.js");
var typifyParser = require("typify-parser");
var utils = require("./utils.js");

// Forward declarations
var compileType;
var compileTypeArray;

function compileIdent(env, type) {
  var g = env[type.value];
  if (!g) {
    throw new Error("Unknown arbitrary: " + type.value);
  }
  return g;
}

function compileApplication(env, type) {
  var callee = compileType(env, type.callee);
  var args = compileTypeArray(env, type.args);

  return callee.apply(undefined, args);
}

function compileFunction(env, type) {
  // we don't care about argument type
  var result = compileType(env, type.result);
  return fn.fn(result);
}

function compileBrackets(env, type) {
  var arg = compileType(env, type.arg);
  return array.array(arg);
}

function compileDisjunction(env, type) {
  var args = compileTypeArray(env, type.args);
  return arbitrary.sum(args);
}

function compileConjunction(env, type) {
  var args = compileTypeArray(env, type.args);
  return arbitrary.tuple(args);
}

function compileRecord(env, type) {
  // TODO: use mapValues
  var spec = {};
  Object.keys(type.fields).forEach(function (key) {
    spec[key] = compileType(env, type.fields[key]);
  });
  return record.arbitrary(spec);
}

function compileRecursive(env, type) {
  assert(type.arg.type === "disjunction", "recursive type's argument should be disjunction");

  // bound variable
  var name = type.name;

  var par = utils.partition(type.arg.args, function (t) {
    return typifyParser.freeVars(t).indexOf(name) === -1;
  });

  var terminal = par[0];

  if (terminal.length === 0) {
    throw new Error("Recursive type without non-recursive branch");
  }

  var terminalArb = compileType(env, {
    type: "disjunction",
    args: terminal,
  });

  return arbitrary.recursive(terminalArb, function (arb) {
    var arbEnv = {};
    arbEnv[name] = arb;
    var newEnv = utils.merge(env, arbEnv);
    return compileType(newEnv, type.arg);
  });
}

compileType = function compileTypeFn(env, type) {
  switch (type.type) {
    case "ident": return compileIdent(env, type);
    case "application": return compileApplication(env, type);
    case "function": return compileFunction(env, type);
    case "brackets": return compileBrackets(env, type);
    case "disjunction": return compileDisjunction(env, type);
    case "conjunction": return compileConjunction(env, type);
    case "record": return compileRecord(env, type);
    case "number": return type.value;
    case "recursive": return compileRecursive(env, type);
    default: throw new Error("Unsupported typify ast type: " + type.type);
  }
};

compileTypeArray = function compileTypeArrayFn(env, types) {
  return types.map(function (type) {
    return compileType(env, type);
  });
};

function parseTypify(env, str) {
  var type = typifyParser(str);
  return compileType(env, type);
}

module.exports = {
  parseTypify: parseTypify,
};
