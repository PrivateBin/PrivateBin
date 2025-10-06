import * as jsc from '../lib/jsverify.js';

const arbitrary: jsc.Arbitrary<number> = jsc.oneof([
    jsc.constant(1),
    jsc.constant(2),
]);

const generator: jsc.Generator<number> = jsc.generator.oneof([
    jsc.generator.constant(1),
    jsc.generator.constant(2),
]);

const differentTypes1: jsc.Arbitrary<boolean> = jsc.oneof([
    jsc.bool,
]);

const differentTypes2: jsc.Arbitrary<boolean | number> = jsc.oneof([
    jsc.bool,
    jsc.number,
]);

const differentTypes3: jsc.Arbitrary<boolean | number | string> = jsc.oneof([
    jsc.bool,
    jsc.number,
    jsc.string,
]);

const differentTypes4: jsc.Arbitrary<boolean | number | string | { foo: boolean }> = jsc.oneof([
    jsc.bool,
    jsc.number,
    jsc.string,
    jsc.record({ foo: jsc.bool }),
]);

// tslint:disable-next-line:max-line-length
const differentTypes5: jsc.Arbitrary<boolean | number | string | { foo: boolean } | { bar: number }> = jsc.oneof([
    jsc.bool,
    jsc.number,
    jsc.string,
    jsc.record({ foo: jsc.bool }),
    jsc.record({ bar: jsc.number }),
]);
