import * as jsc from "../lib/jsverify.js";

describe("basic jsverify usage", () => {
  jsc.property("(b && b) === b", jsc.bool, b => (b && b) === b);

  jsc.property("boolean fn thrice", jsc.fn(jsc.bool), jsc.bool, (f, b) =>
    f(f(f(b))) === f(b)
  );

  it("async evaluation has no effect on pure computation", done => {
    const property = jsc.forall(jsc.fn(jsc.nat), jsc.json, jsc.nat(20), (f, x, t) => {
      const sync = f(x);
      return new Promise(resolve => {
        setTimeout(() => resolve(f(x)), t);
      }).then(val => val === sync);
    });

    jsc.assert(property)
      .then(val => val ? done(val) : done())
      .catch(error => done(error));
  });

  // You may simply return a promise with mocha
  jsc.property("async evaluation...", jsc.fun(jsc.nat), jsc.json, jsc.nat(20), (f, x, t) => {
    const sync = f(x);

    return new Promise(resolve => {
      setTimeout(() => resolve(f(x)), t);
    }).then(val => val === sync);
  });
});

describe("fails before #204", () => {
  it('define only test size', () => {
    const property = jsc.forall(jsc.nat, n => n >= 0);
    jsc.assert(property, { size: 200 });
  });
});
