import * as jsc from '../lib/jsverify.js';

const arb1: jsc.Arbitrary<jsc.Either<number, string>> =
  jsc.either(jsc.number, jsc.string);

const gen1: jsc.Generator<jsc.Either<string, Date>> =
  jsc.generator.either(
    jsc.generator.constant('testing either'),
    jsc.generator.constant(new Date()),
  );

const shrink1: jsc.Shrink<jsc.Either<boolean, RegExp>> =
  jsc.shrink.either(
    jsc.shrink.bless((x: boolean) => [ x ]),
    jsc.shrink.bless((x: RegExp) => [ x ]),
  );

const show1: string =
  jsc.show.either(
    x => x.toUpperCase(),
    y => y.toDateString(),
    gen1(1)
  );

const either1: jsc.Either<string, Date> = gen1(1);
const value1: string | Date = either1.value;

const either2: string = either1.either(x => x.toUpperCase(), y => y.toDateString());
const bool1: boolean = either1.isEqual(gen1(1));
const bimap1: jsc.Either<number, boolean> = either1.bimap(a => 4, b => true);
const first1: jsc.Either<RegExp, Date> = either1.first(a => /test/);
const second1: jsc.Either<string, Error> = either1.second(a => new Error('test'));
