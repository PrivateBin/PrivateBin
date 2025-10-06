import * as jsc from "../lib/jsverify.js";

const arbitrary1: jsc.Arbitrary<[number]> = jsc.tuple([
    jsc.integer,
]);

const arbitrary2: jsc.Arbitrary<[number, string]> = jsc.tuple([
    jsc.integer,
    jsc.string,
]);

const arbitrary3: jsc.Arbitrary<[number, string, boolean]> = jsc.tuple([
    jsc.integer,
    jsc.string,
    jsc.bool,
]);

const arbitrary4: jsc.Arbitrary<[number, string, boolean, number]> = jsc.tuple([
    jsc.integer,
    jsc.string,
    jsc.bool,
    jsc.integer,
]);

const arbitrary5: jsc.Arbitrary<[number, string, boolean, number, string]> = jsc.tuple([
    jsc.integer,
    jsc.string,
    jsc.bool,
    jsc.integer,
    jsc.string,
]);

// Falls back to any[] if no overloads are available.
const arbitrary6: jsc.Arbitrary<any[]> = jsc.tuple([
    jsc.integer,
    jsc.string,
    jsc.bool,
    jsc.integer,
    jsc.string,
    jsc.bool,
]);
