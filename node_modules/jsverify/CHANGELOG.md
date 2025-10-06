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
