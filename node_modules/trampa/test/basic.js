/* global describe:false it:false */
"use strict";

var trampa = require("../index.js");
var jsc = require("jsverify");
var assert = require("assert");

describe("wrapping", function () {
  jsc.property("unwraps", "nat", function (n) {
    return trampa.wrap(n).run() === n;
  });

  jsc.property("nesting wrap", "nat", function (n) {
    return trampa.wrap(trampa.wrap(n)).run() === n;
  })
  jsc.property("lazy", "nat", function (n) {
    return trampa.lazy(function () { return n; }).run() === n;
  })
})

describe("jump", function () {
  jsc.property("works like map", "nat -> nat", "nat", function (f, n) {

    return trampa.wrap(n).jump(f).run() === f(n);
  });
});

describe("monad-like", function () {
  jsc.property("left identity: return a >>= f ≡ f a", "nat", function (n) {
    var f = function (n) {
      return trampa.wrap(1 + n);
    };
    var lhs = trampa.wrap(n).jump(f).run();
    var rhs = f(n).run();

    return lhs === rhs && lhs === 1 + n;
  });

  jsc.property("right identity: m >>= return ≡ m", "nat", function (n) {
    var m = trampa.wrap(n);
    var lhs = m.jump(trampa.wrap).run();
    var rhs = m.run()

    return lhs === rhs && lhs === n;
  });

  // TODO: test associativity
});

function identity(x) {
  return x;
}

function compose(f, g) {
  return function (x) {
    return f(g(x));
  }
}

describe("functor-like", function () {
  jsc.property("identity: fmap id x ≡ x", "nat", function (n) {
    var x = trampa.wrap(n);
    var lhs = x.jump(identity).run();
    var rhs = x.run();

    return lhs === rhs && lhs === n;
  });

  jsc.property("composition: fmap f (fmap g x) ≡ fmap (f ∘ g) x", "nat", "nat -> nat", "nat -> nat", function (n, f, g) {
    var x = trampa.wrap(n);
    var lhs = x.jump(g).jump(f).run();
    var rhs = x.jump(compose(f, g)).run();

    return lhs === rhs && lhs === f(g(n));
  });
});

describe("host stack explosion", function () {
  var BIGN = 100000;
  it("non trampoline throws", function () {
    function loop(n, acc) {
      return n === 0 ? acc : loop(n - 1, acc + 1);
    }

    assert.throws(function () {
      loop(BIGN, 0);
    });
  });

  it("trampoline works", function () {
    function loop(n, acc) {
      return n === 0 ? trampa.wrap(acc) : trampa.wrap(1).jump(function () {
        return loop(n - 1, acc + 1);
      });
    }

    assert.equal(loop(BIGN, 0).run(), BIGN);
  });
});

describe("internal stack explosion", function () {
  var BIGN = 1000;

  it("trampoline works", function () {
    function loop(n, acc) {
      return n === 0 ? trampa.wrap(acc) : trampa.lazy(function () {
        return loop(n - 1, acc + 1);
      });
    }

    function log(str) {
      var m = str.match(/\s+(\d+)\s*$/, str);
      assert(m);
      var stackSize = parseInt(m[1], 10);

      // Stack size should stay bounded
      assert(stackSize < 2);
    }

    assert.equal(loop(BIGN, 0).run({ log: log, debug: true}), BIGN);
  });
});

