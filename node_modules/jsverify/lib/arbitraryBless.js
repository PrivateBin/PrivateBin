"use strict";

var show = require("./show.js");

/**
  ### Arbitrary data
*/

// Blessing: i.e adding prototype
/* eslint-disable no-use-before-define */
function arbitraryProtoSMap(f, g, newShow) {
  /* jshint validthis:true */
  var arb = this; // eslint-disable-line no-invalid-this
  return arbitraryBless({
    generator: arb.generator.map(f),
    shrink: arb.shrink.smap(f, g),
    show: newShow || show.def,
  });
}
/* eslint-enable no-use-before-define */

/**
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
*/
function arbitraryBless(arb) {
  arb.smap = arbitraryProtoSMap;
  return arb;
}

module.exports = arbitraryBless;
