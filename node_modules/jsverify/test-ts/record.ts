import * as jsc from "../lib/jsverify.js";

interface Record {
    string: string;
    boolean: boolean;
};

const arbitrary: jsc.Arbitrary<Record> = jsc.record({
    string: jsc.string,
    boolean: jsc.bool
});
