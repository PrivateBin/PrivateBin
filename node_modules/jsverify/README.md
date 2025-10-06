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

### Properties

- `forall(arbs: arbitrary a ..., userenv: (map arbitrary)?, prop : a -> property): property`

    Property constructor

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

- `assert(prop: property, opts: checkoptions?) : void`

    Same as `check`, but throw exception if property doesn't hold.

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

- `compile(desc: string, env: typeEnv?): arbitrary a`

    Compile the type description in provided type environment, or default one.

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

- `throws(block: () -> a, error: class?, message: string?): bool`

  Executes nullary function `block`. Returns `true` if `block` throws. See [assert.throws](https://nodejs.org/api/assert.html#assert_assert_throws_block_error_message)

- `assertForall(arbs: arbitrary a ..., userenv: (map arbitrary)?, prop : a -> property): void`

   Combines 'assert' and 'forall'.
   Constructs a property with forall from arguments, then throws an exception if the property doesn't hold.
   Options for 'assert' cannot be set here - use assert(forall(...)) if you need that.

- `checkForall(arbs: arbitrary a ..., userenv: (map arbitrary)?, prop : a -> property): result`

  Combines 'check' and 'forall'.
  Constructs a property with forall from arguments, and returns a value based on if the property holds or not.
  See 'check' for description of return value.

  Options for 'check' cannot be set here - use check(forall(...)) if you need that.

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

### Arbitrary data

- `.smap(f: a -> b, g: b -> a, newShow: (b -> string)?): arbitrary b`

    Transform `arbitrary a` into `arbitrary b`. For example:

    `g` should be a [right inverse](http://en.wikipedia.org/wiki/Surjective_function#Surjections_as_right_invertible_functions) of `f`, but doesn't need to be complete inverse.
    i.e. `f` doesn't need to be invertible, only surjective.

    ```js
    var positiveIntegersArb = nat.smap(
      function (x) { return x + 1; },
      function (x) { return x - 1; });
    ```

    ```js
    var setNatArb =  jsc.array(jsc.nat).smap(_.uniq, _.identity);
    ```

    Right inverse means that *f(g(y)) = y* for all *y* in *Y*. Here *Y* is a type of **arrays of unique natural numbers**. For them
    ```js
    _.uniq(_.identity(y)) = _.uniq(y) = y
    ```

    Opposite: *g(f(x))* for all *x* in *X*, doesn't need to hold. *X* is **arrays of natural numbers**:
    ```js
    _.identity(_uniq([0, 0])) = [0]] != [0, 0]
    ```

    We need an inverse for shrinking, and the right inverse is enough. We can always *pull back* `smap`ped value, shrink the preimage, and *map* or *push forward* shrunken preimages again.

- `bless(arb: {...}): arbitrary a`

  Bless almost arbitrary structure to be proper arbitrary. *Note*: this function mutates argument.

  #### Example:

  ```js
  var arbTokens = jsc.bless({
    generator: function () {
      switch (jsc.random(0, 2)) {
        case 0: return "foo";
        case 1: return "bar";
        case 2: return "quux";
      }
    }
  });
  ```

### Primitive arbitraries

- `integer: arbitrary integer`
- `integer(maxsize: nat): arbitrary integer`
- `integer(minsize: integer, maxsize: integer): arbitrary integer`

    Integers, ℤ

- `nat: arbitrary nat`
- `nat(maxsize: nat): arbitrary nat`

    Natural numbers, ℕ (0, 1, 2...)

- `number: arbitrary number`
- `number(maxsize: number): arbitrary number`
- `number(min: number, max: number): arbitrary number`

    JavaScript numbers, "doubles", ℝ. `NaN` and `Infinity` are not included.

- `uint8: arbitrary nat`
- `uint16: arbitrary nat`
- `uint32: arbitrary nat`

- `int8: arbitrary integer`
- `int16: arbitrary integer`
- `int32: arbitrary integer`

- `bool: arbitrary bool`

    Booleans, `true` or `false`.

- `datetime: arbitrary datetime`

    Random datetime

- `elements(args: array a): arbitrary a`

    Random element of `args` array.

- `falsy: arbitrary *`

    Generates falsy values: `false`, `null`, `undefined`, `""`, `0`, and `NaN`.

- `constant(x: a): arbitrary a`

    Returns an unshrinkable arbitrary that yields the given object.

### Arbitrary combinators

- `nonshrink(arb: arbitrary a): arbitrary a`

    Non shrinkable version of arbitrary `arb`.

- `unit: arbitrary ()`

- `either(arbA: arbitrary a, arbB : arbitrary b): arbitrary (either a b)`

- `pair(arbA: arbitrary a, arbB : arbitrary b): arbitrary (pair a b)`

    If not specified `a` and `b` are equal to `value()`.

- `tuple(arbs: (arbitrary a, arbitrary b...)): arbitrary (a, b...)`

- `sum(arbs: (arbitrary a, arbitrary b...)): arbitrary (a | b ...)`

- `dict(arb: arbitrary a): arbitrary (dict a)`

    Generates a JavaScript object with properties of type `A`.

- `array(arb: arbitrary a): arbitrary (array a)`

- `nearray(arb: arbitrary a): arbitrary (array a)`

- `json: arbitrary json`

     JavaScript Objects: boolean, number, string, null, array of `json` values or object with `json` values.

- `oneof(gs : array (arbitrary a)...) : arbitrary a`

    Randomly uses one of the given arbitraries.

- ```js
  letrec(
    (tie: key -> (arbitrary a | arbitrary b | ...))
    -> { key: arbitrary a, key: arbitrary b, ... }):
  { key: arbitrary a, key: arbitrary b, ... }
  ```

  Mutually recursive definitions. Every reference to a sibling arbitrary
  should go through the `tie` function.

  ```js
  { arb1, arb2 } = jsc.letrec(function (tie) {
    return {
      arb1: jsc.tuple(jsc.int, jsc.oneof(jsc.const(null), tie("arb2"))),
      arb2: jsc.tuple(jsc.bool, jsc.oneof(jsc.const(null), tie("arb1"))),
    }
  });
  ```

### Arbitrary records

- `record(spec: { key: arbitrary a... }, userenv: env?): arbitrary { key: a... }`

    Generates a javascript object with given record spec.

- `generator.record(gen: { key: generator a... }): generator { key: a... }`

- `shrink.record(shrs: { key: shrink a... }): shrink { key: a... }`

### Arbitrary strings

- `char: arbitrary char` &mdash; Single character

- `asciichar: arbitrary char` &mdash; Single ascii character (0x20-0x7e inclusive, no DEL)

- `string: arbitrary string`

- `nestring: arbitrary string` &mdash; Generates strings which are not empty.

- `asciistring: arbitrary string`

- `asciinestring: arbitrary string`

### Arbitrary functions

- `fn(arb: arbitrary a): arbitrary (b -> a)`
- `fun(arb: arbitrary a): arbitrary (b -> a)`

### Small arbitraries

- `generator.small(gen: generator a): generator a`
- `small(arb: arbitrary a): arbitrary a`

Create a generator (abitrary) which will generate smaller values, i.e. generator's `size` parameter is decreased logarithmically.

```js
jsc.property("small array of small natural numbers", "small (array nat)", function (arr) {
  return Array.isArray(arr);
});

jsc.property("small array of normal natural numbers", "(small array) nat", function (arr) {
  return Array.isArray(arr);
});
```

### Restricting arbitraries

- `suchthat(arb: arbitrary a, userenv: env?, p : a -> bool): arbitrary a`
    Arbitrary of values that satisfy `p` predicate. It's advised that `p`'s accept rate is high.

### Generator functions

A generator function, `generator a`, is a function `(size: nat) -> a`, which generates a value of given size.

Generator combinators are auto-curried:

```js
var xs = jsc.generator.array(jsc.nat.generator, 1); // ≡
var ys = jsc.generator.array(jsc.nat.generator)(1);
```

In purely functional approach `generator a` would be explicitly stateful computation:
`(size: nat, rng: randomstate) -> (a, randomstate)`.
*JSVerify* uses an implicit random number generator state,
but the value generation is deterministic (tests are reproducible),
if the primitives from *random* module are used.

- `generator.bless(f: nat -> a): generator a`

    Bless function with `.map` and `.flatmap` properties.

- `.map(f: a -> b): generator b`

    Map `generator a` into `generator b`. For example:

    ```js
    positiveIntegersGenerator = nat.generator.map(
      function (x) { return x + 1; });
    ```

- `.flatmap(f: a -> generator b): generator b`

    Monadic bind for generators. Also `flatMap` version is supported.

- `generator.constant(x: a): generator a`

- `generator.combine(gen: generator a..., f: a... -> b): generator b`

- `generator.oneof(gens: list (generator a)): generator a`

- `generator.recursive(genZ: generator a, genS: generator a -> generator a): generator a`

- `generator.pair(genA: generator a, genB: generator b): generator (a, b)`

- `generator.either(genA: generator a, genB: generator b): generator (either a b)`

- `generator.unit: generator ()`

    `unit` is an empty tuple, i.e. empty array in JavaScript representation. This is useful as a building block.

- `generator.tuple(gens: (generator a, generator b...)): generator (a, b...)`

- `generator.sum(gens: (generator a, generator b...)): generator (a | b...)`

- `generator.array(gen: generator a): generator (array a)`

- `generator.nearray(gen: generator a): generator (array a)`

- `generator.dict(gen: generator a): generator (dict a)`

### Shrink functions

A shrink function, `shrink a`, is a function `a -> [a]`, returning an array of *smaller* values.

Shrink combinators are auto-curried:

```js
var xs = jsc.shrink.array(jsc.nat.shrink, [1]); // ≡
var ys = jsc.shrink.array(jsc.nat.shrink)([1]);
```

- `shrink.bless(f: a -> [a]): shrink a`

    Bless function with `.smap` property.

- `.smap(f: a -> b, g: b -> a): shrink b`

    Transform `shrink a` into `shrink b`. For example:

    ```js
    positiveIntegersShrink = nat.shrink.smap(
      function (x) { return x + 1; },
      function (x) { return x - 1; });
    ```

- `shrink.noop: shrink a`

- `shrink.pair(shrA: shrink a, shrB: shrink b): shrink (a, b)`

- `shrink.either(shrA: shrink a, shrB: shrink b): shrink (either a b)`

- `shrink.tuple(shrs: (shrink a, shrink b...)): shrink (a, b...)`

- `shrink.sum(shrs: (shrink a, shrink b...)): shrink (a | b...)`

- `shrink.array(shr: shrink a): shrink (array a)`

- `shrink.nearray(shr: shrink a): shrink (nearray a)`

### Show functions

- `show.def(x : a): string`

    Currently implemented as `JSON.stringify`.

- `show.pair(showA: a -> string, showB: b -> string, x: (a, b)): string`

- `show.either(showA: a -> string, showB: b -> string, e: either a b): string`

- `show.tuple(shrinks: (a -> string, b -> string...), x: (a, b...)): string`

- `show.sum(shrinks: (a -> string, b -> string...), x: (a | b ...)): string`

- `show.array(shrink: a -> string, x: array a): string`

### Random functions

- `random(min: int, max: int): int`

    Returns random int from `[min, max]` range inclusively.

    ```js
    getRandomInt(2, 3) // either 2 or 3
    ```

- `random.number(min: number, max: number): number`

    Returns random number from `[min, max)` range.

### either

- `either.left(value: a): either a b`

- `either.right(value: b): either a b`

- `either.either(l: a -> x, r: b -> x): x`

- `either.isEqual(other: either a b): bool`

    TODO: add `eq` optional parameter

- `either.bimap(f: a -> c, g: b -> d): either c d`

    ```js
    either.bimap(compose(f, g), compose(h, i)) ≡ either.bimap(g, i).bimap(f, h);
    ```

- `either.first(f: a -> c): either c b`

    ```js
    either.first(f) ≡ either.bimap(f, utils.identity)
    ```

- `either.second(g: b -> d): either a d`

    ```js
    either.second(g) === either.bimap(utils.identity, g)
    ```

### Utility functions

Utility functions are exposed (and documented) only to make contributions to jsverify more easy.
The changes here don't follow semver, i.e. there might be backward-incompatible changes even in patch releases.

Use [underscore.js](http://underscorejs.org/), [lodash](https://lodash.com/), [ramda](http://ramda.github.io/ramdocs/docs/), [lazy.js](http://danieltao.com/lazy.js/) or some other utility belt.

- `utils.isEqual(x: json, y: json): bool`

    Equality test for `json` objects.

- `utils.isApproxEqual(x: a, y: b, opts: obj): bool`

    Tests whether two objects are approximately and optimistically equal.
    Returns `false` only if they are distinguishable not equal.
    Returns `true` when `x` and `y` are `NaN`.
    This function works with cyclic data.

    Takes optional 'opts' parameter with properties:

    - `fnEqual` - whether all functions are considered equal (default: yes)
    - `depth` - how deep to recurse until treating as equal (default: 5)

- `utils.force(x: a | () -> a) : a`

    Evaluate `x` as nullary function, if it is one.

- `utils.merge(x... : obj): obj`

  Merge two objects, a bit like `_.extend({}, x, y)`.

## FAQ

### Why do all the examples import the library as jsc instead of jsv?

Does JSC originate with [JSCheck](http://www.jscheck.org/)?

**A:** Yes

### smap requires an inverse function, which isn't always practical. Is this complexity related to shrinking?

**A:** Yes. We don't want to give an easy-to-use interface which forgets
shrinking altogether. Note, that *right* inverse is enough, which is most
likely easy to write, even *complete* inverse doesn't exist.

## Contributing

- `README.md` is generated from the source with [ljs](https://github.com/phadej/ljs), say `make literate`.
- `jsverify.standalone.js` is also generated by the build process
- Before creating a pull request run `make test`, yet travis will do it for you.

## Release History

- **0.8.4** &mdash; *2018-10-31* &mdash; Updates
    - More typings: `oneof`, `tuple`, `either`
    - Documentation grammar fixes
- **0.8.3** &mdash; *2017-09-11* &mdash; Updates
    - Remove Jasmine 1 helper
    - Support async tests in Jasmine 2 helper
    - Add `suchthat` docs
    - Update typings: `suchthat`, and type `jsc.record`.
- **0.8.2** &mdash; *2017-04-01* &mdash; Typescript updates
    - Typings fixes
    - Sources are `tslint`ed
- **0.8.1** &mdash; *2017-03-31* &mdash; Typescript updates
- **0.8.0** &mdash; *2017-03-12* &mdash; TypeScript typings
    - Provide TypeScript typings
      [#202](https://github.com/jsverify/jsverify/pull/202)
      [#196](https://github.com/jsverify/jsverify/pull/196)
- **0.7.5** &mdash; *2017-03-08* &mdash; International Women's Day
    - Add `letrec` combinator
      [#193](https://github.com/jsverify/jsverify/pull/193)
    - Add `null` to `json` arbitrary
      [#201](https://github.com/jsverify/jsverify/pull/201)
    - Fix typos and outdated links in documentation
- **0.7.4** &mdash; *2016-09-07* &mdash; Bless `suchthat`
    - Fix "arbitraries created with `suchthat` are missing `.smap`"
      [#184](https://github.com/jsverify/jsverify/issues/184)
- **0.7.3** &mdash; *2016-08-26* &mdash; Remove lodash
    - Fixed accidental use of `lodash`. We have our own `isNaN` now.
- **0.7.2** &mdash; *2016-08-25* &mdash; One year since the last release
    - `jsc.utils.isEqual` returns true if both arguments are `NaN`.
    - Add `jsc.assertForall` and `jsc.checkForall`
- **0.7.1** &mdash; *2015-08-24* &mdash; jsc.throws
    - Add `jsc.throws` [#133](https://github.com/jsverify/jsverify/pull/133)
- **0.7.0** &mdash; *2015-08-23* &mdash; More experiments
    - `jsc.sum` - generate arbitrary sum types (generalisation of either) [#125](https://github.com/jsverify/jsverify/pull/125)
        - *BREAKING CHANGE:* bar (`|`) in DSL generates `jsc.sum`
    - experimental support of recursive types in DSL (especially no shrinking yet) [#109](https://github.com/jsverify/jsverify/issues/109) [#126](https://github.com/jsverify/jsverify/pull/126)
    - fail early when `jsc.forall` is given zero generators [#128](https://github.com/jsverify/jsverify/issues/128)
    - `jsc.json` has shrink [#122](https://github.com/jsverify/jsverify/issues/122)
    - non-true non-function results from properties are treated as exceptions [#127](https://github.com/jsverify/jsverify/issues/127)
- **0.6.3** &mdash; *2015-07-27* &mdash; Bug fixes
    - `jsc.utils.isEqual` doesn't care about key ordering [#123](https://github.com/jsverify/jsverify/issues/123)
    - tuple's shrink is blessed [#124](https://github.com/jsverify/jsverify/issues/124)
- **0.6.2** &mdash; *2015-07-13* &mdash; Trampolines
- **0.6.1** &mdash; *2015-07-13* &mdash; Bug fixes
    - Print stacktrace of catched exceptions
    - `maxsize = 0` for numeric generators works
    - Issue with non-parametric jsc.property returning property.
- **0.6.0** &mdash; *2015-06-19* &mdash; Minor but major release!
    - added `jsc.utils.isApproxEqual`
- **0.6.0-beta.2** &mdash; *2015-05-31* &mdash; Beta!
    - Fix issue [#113](https://github.com/jsverify/jsverify/issues/113) - Shrink of tuple with arrays failed.
- **0.6.0-beta.1** &mdash; *2015-05-04* &mdash; Beta!
    - FAQ section
    - Improved `smap` documentation
    - `flatmap` is also `flatMap`
    - Fix function arbitrary
    - `small` arbitraries
    - `jsc.generator.record`
    - Thanks to @peterjoel for reporting issues
- **0.6.0-alpha.6** &mdash; *2015-04-25* &mdash; Fix issues #98
    - Documentation improvements
    - Fix issue [#98](https://github.com/jsverify/jsverify/issues/98) - error while generating `int32` values
- **0.6.0-alpha.5** &mdash; *2015-04-23* &mdash; Fix issue #99
    - Documentation improvements
    - Fix issue #99 (`suchthat` shrink)
- **0.6.0-alpha.4** &mdash; *2015-04-26* &mdash; Fix issue #87
    - jsc.property didn't fail with asynchronous properties
    - thanks @Ezku for reporting
- **0.6.0-alpha.3** &mdash; *2015-04-24* &mdash; promise shrink fixed
- **0.6.0-alpha.2** &mdash; *2015-04-24* &mdash; jsc.bless
    - Added `jsc.bless`
- **0.6.0-alpha.1** &mdash; *2015-04-22* &mdash; Preview
    - Using lazy sequences for shrink results
    - *Breaking changes:*
         - `jsc.map` renamed to `jsc.dict`
         - `jsc.value` removed, use `jsc.json`
         - `jsc.string()` removed, use `jsc.string`
         - `shrink.isomap` renamed to `shrink.smap`
- **0.5.3** &mdash; *2015-04-21* &mdash; More algebra
    - `unit` and `either` arbitraries
    - `arbitrary.smap` to help creating compound data
- **0.5.2** &mdash; *2015-04-10* &mdash; `show.def` -change
- **0.5.1** &mdash; *2015-02-19* &mdash; Dependencies bump
    - We also work on 0.12 and iojs!
- **0.5.0** &mdash; *2014-12-24* &mdash; Merry Chrismas 2014!
    - Documentation cleanup
- **0.5.0-beta.2** &mdash; *2014-12-21* &mdash; Beta 2!
    - Pair &amp; tuple related code cleanup
    - Update `CONTRIBUTING.md`
    - Small documentation type fixes
    - Bless `jsc.elements` shrink
- **0.5.0-beta.1** &mdash; *2014-12-20* &mdash; Beta!
    - `bless` don't close over (uses `this`)
    - Cleanup generator module
    - Other code cleanup here and there
- **0.4.6** &mdash; *2014-11-30*  &mdash; better shrinks &amp; recursive
    - Implemented shrinks: [#51](https://github.com/jsverify/jsverify/issues/51)
    - `jsc.generator.recursive`: [#37](https://github.com/jsverify/jsverify/issues/37)
    - array, nearray &amp; map generators return a bit smaller results (*log2* of size)
- **0.4.5** &mdash; *2014-11-22*  &mdash; stuff
    - `generator.combine` &amp; `.flatmap`
    - `nat`, `integer`, `number` &amp; and `string` act as objects too
- **0.4.4** &mdash; *2014-11-22*  &mdash; new generators
    - New generators: `nearray`, `nestring`
    - `generator.constant`
    - zero-ary `jsc.property` (it ∘ assert)
    - `jsc.sampler`
- **0.4.3** &mdash; *2014-11-08*  &mdash; jsc.property
    - Now you can write your bdd specs without any boilerplate
    - support for nat-litearls in dsl [#36](https://github.com/jsverify/jsverify/issues/36)
        ```js
        describe("Math.abs", function () {
          jsc.property("result is non-negative", "integer 100", function (x) {
            return Math.abs(x) >= 0;
          });
        });
        ```
    - Falsy generator [#42](https://github.com/jsverify/jsverify/issues/42)
- **0.4.2** &mdash; *2014-11-03*  &mdash; User environments for DSL
    - User environments for DSL
    - Generator prototype `map`, and shrink prototype `isomap`
    - JSON generator works with larger sizes
- **0.4.1** Move to own organization in GitHub
- **0.4.0**  &mdash; *2014-10-27*  &mdash; typify-dsl &amp; more arbitraries.
    Changes from **0.3.6**:
    - DSL for `forall` and `suchthat`
    - new primitive arbitraries
    - `oneof` behaves as in QuickCheck (BREAKING CHANGE)
    - `elements` is new name of old `oneof`
    - Other smaller stuff under the hood
- **0.4.0**-beta.4 generator.oneof
- **0.4.0**-beta.3 Expose shrink and show modules
- **0.4.0**-beta.2 Move everything around
    - Better looking README.md!
- **0.4.0**-beta.1 Beta!
    - Dev Dependencies update
- **0.4.0**-alpha8 oneof &amp; record -dsl support
    - also `jsc.compile`
    - record is shrinkable!
- **0.4.0**-alpha7 oneof &amp; record
    - *oneof* and *record* generator combinators ([@fson](https://github.com/fson))
    - Fixed uint\* generators
    - Default test size increased to 10
    - Numeric generators with size specified are independent of test size ([#20](https://github.com/phadej/jsverify/issues/20))
- **0.4.0**-alpha6 more primitives
    - int8, int16, int32, uint8, uint16, uint32
    - char, asciichar and asciistring
    - value &rarr; json
    - use eslint
- **0.4.0**-alpha5 move david to be devDependency
- **0.4.0**-alpha4 more typify
    - `suchthat` supports typify dsl
    - `oneof` &rarr; `elements` to be in line with QuickCheck
    - Added versions of examples using typify dsl
- **0.4.0**-alpha3 David, npm-freeze and jscs
- **0.4.0**-alpha2 Fix typo in readme
- **0.4.0**-alpha1 typify
   - DSL for `forall`
       ```js
       var bool_fn_applied_thrice = jsc.forall("bool -> bool", "bool", check);
       ```

   - generator arguments, which are functions are evaluated. One can now write:
       ```js
       jsc.forall(jsc.nat, check) // previously had to be jsc.nat()
       ```

- **0.3.6** map generator
- **0.3.5** Fix forgotten rngState in console output
- **0.3.4** Dependencies update
- **0.3.3** Dependencies update
- **0.3.2** `fun` &rarr; `fn`
- **0.3.1** Documentation typo fixes
- **0.3.0** Major changes
    - random generate state handling
    - `--jsverifyRngState` parameter value used when run on node
    - karma tests
    - use make
    - dependencies update
- **0.2.0** Use browserify
- **0.1.4** Mocha test suite
    - major cleanup
- **0.1.3** gen.show and exception catching
- **0.1.2** Added jsc.assert
- **0.1.1** Use grunt-literate
- **0.1.0** Usable library
- **0.0.2** Documented preview
- **0.0.1** Initial preview

## Related work

### JavaScript

- [JSCheck](http://www.jscheck.org/)
- [claire](https://npmjs.org/package/claire)
- [gent](https://npmjs.org/package/gent)
- [fatcheck](https://npmjs.org/package/fatcheck)
- [quickcheck](https://npmjs.org/package/quickcheck)
- [qc.js](https://bitbucket.org/darrint/qc.js/)
- [quick\_check](https://www.npmjs.org/package/quick_check)
- [gentest](https://github.com/graue/gentest)
- [node-quickcheck](https://github.com/mcandre/node-quickcheck)

### Others

- [Wikipedia - QuickCheck](http://en.wikipedia.org/wiki/QuickCheck)
- [Haskell - QuickCheck](http://hackage.haskell.org/package/QuickCheck) [Introduction](http://www.haskell.org/haskellwiki/Introduction_to_QuickCheck1)
- [Erlang - QuviQ](http://www.quviq.com/index.html)
- [Erlang - triq](https://github.com/krestenkrab/triq)
- [Scala - ScalaCheck](https://github.com/rickynils/scalacheck)
- [Clojure - test.check](https://github.com/clojure/test.check)
- [Python - Hypothesis](https://github.com/DRMacIver/hypothesis)

The MIT License (MIT)

Copyright (c) 2013-2015 Oleg Grenrus

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
