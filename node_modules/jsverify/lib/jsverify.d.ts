declare namespace JSVerify {
  type Arbitrary<T> = ArbitraryLike<T> & ArbitraryFns<T>;

  interface ArbitraryLike<T> {
    generator: Generator<T>;
    show?: Show<T>;
    shrink?: Shrink<T>;
  }

  interface ArbitraryFns<T> {
    smap<U>(f: (t: T) => U, g: (u: U) => T, newShow?: Show<U>): Arbitrary<U>;
  }

  function bless<U>(arb: ArbitraryLike<U>): Arbitrary<U>;
  function sampler<U>(arb: Arbitrary<U>, genSize?: number): (sampleSize: number) => U;
  function small<U>(arb: Arbitrary<U>): Arbitrary<U>;
  function suchthat<U>(arb: Arbitrary<U>, predicate: (u: U) => boolean): Arbitrary<U>;

  interface Options {
    tests?: number;
    size?: number;
    quiet?: boolean;
    rngState?: string;
  }

  interface Result<T> {
    counterexample: T;
    tests: number;
    shrinks: number;
    exc?: string;
    rngState: string;
  }

  type Generator<T> = GeneratorFn<T> & GeneratorFns<T>;
  type GeneratorFn<T> = (size: number) => T;
  interface GeneratorFns<T> {
    map<U>(f: (t: T) => U): Generator<U>;
    flatmap<U>(f: (t: T) => Generator<U>): Generator<U>;
  }

  type Shrink<T> = ShrinkFn<T> & ShrinkFns<T>;
  type ShrinkFn<T> = (t: T) => T[];
  interface ShrinkFns<T> {
    smap<U>(f: (t: T) => U, g: (u: U) => T): Shrink<U>;
  }

  type Show<T> = (t: T) => string;
  type Property<T> = boolean | void | T;

  interface Either<T, U> {
    value: T | U;
    either<X>(l: (a: T) => X, r: (b: U) => X): X;
    isEqual(other: Either<T, U>): boolean;
    bimap<X, Y>(f: (a: T) => X, g: (b: U) => Y): Either<X, Y>;
    first<X>(f: (a: T) => X): Either<X, U>;
    second<Y>(g: (b: U) => Y): Either<T, Y>;
  }

  type integerFn = (maxsize: number) => Arbitrary<number>;
  type integerFn2 = (minsize: number, maxsize: number) => Arbitrary<number>;

  const integer: Arbitrary<number> & integerFn & integerFn2;
  const nat: Arbitrary<number> & integerFn;
	// tslint:disable-next-line:variable-name
  const number: Arbitrary<number> & integerFn & integerFn2;

  const uint8: Arbitrary<number>;
  const uint16: Arbitrary<number>;
  const uint32: Arbitrary<number>;
  const int8: Arbitrary<number>;
  const int16: Arbitrary<number>;
  const int32: Arbitrary<number>;

  const bool: Arbitrary<boolean>;
  const datetime: Arbitrary<Date>;

  function elements<T>(args: T[]): Arbitrary<T>;
  const falsy: Arbitrary<any>;
  function constant<T>(x: T): Arbitrary<T>;

  const char: Arbitrary<string>;
  const asciichar: Arbitrary<string>;
	// tslint:disable-next-line:variable-name
  const string: Arbitrary<string>;
  const nestring: Arbitrary<string>;
  const asciistring: Arbitrary<string>;
  const asciinestring: Arbitrary<string>;

  //Combinators
  function nonshrink<T>(arb: Arbitrary<T>): Arbitrary<T>;
  function either<T, U>(arbA: Arbitrary<T>, arbB: Arbitrary<U>): Arbitrary<Either<T,U>>;
  function pair<T, U>(arbA: Arbitrary<T>, arbB: Arbitrary<U>): Arbitrary<[T, U]>;

  function tuple<A>(arbs: [Arbitrary<A>]): Arbitrary<[A]>;
  function tuple<A, B>(arbs: [Arbitrary<A>, Arbitrary<B>]): Arbitrary<[A, B]>;
  function tuple<A, B, C>(arbs: [Arbitrary<A>, Arbitrary<B>, Arbitrary<C>]): Arbitrary<[A, B, C]>;
  function tuple<A, B, C, D>(arbs: [Arbitrary<A>, Arbitrary<B>, Arbitrary<C>, Arbitrary<D>]): Arbitrary<[A, B, C, D]>;
  function tuple<A, B, C, D, E>(arbs: [Arbitrary<A>, Arbitrary<B>, Arbitrary<C>, Arbitrary<D>, Arbitrary<E>]): Arbitrary<[A, B, C, D, E]>;
  function tuple(arbs: Arbitrary<any>[]): Arbitrary<any[]>;

  function sum(arbs: Arbitrary<any>[]): Arbitrary<any>;

  function dict<T>(arb: Arbitrary<T>): Arbitrary<{ [s: string]: T }>;
  function array<T>(arb: Arbitrary<T>): Arbitrary<T[]>;
  function nearray<T>(arb: Arbitrary<T>): Arbitrary<T[]>;
  function fn<T>(arb: Arbitrary<T>): Arbitrary<(a: any) => T>;
  function fun<T>(arb: Arbitrary<T>): Arbitrary<(a: any) => T>;
  const json: Arbitrary<any>;
  const unit: Arbitrary<any>;

  function oneof<A, B>(gs: [Arbitrary<A>, Arbitrary<B>]): Arbitrary<A | B>;
  function oneof<A, B, C>(gs: [Arbitrary<A>, Arbitrary<B>, Arbitrary<C>]): Arbitrary<A | B | C>;
  function oneof<A, B, C, D>(gs: [Arbitrary<A>, Arbitrary<B>, Arbitrary<C>, Arbitrary<D>]): Arbitrary<A | B | C | D>;
  function oneof<A, B, C, D, E>(gs: [Arbitrary<A>, Arbitrary<B>, Arbitrary<C>, Arbitrary<D>, Arbitrary<E>]): Arbitrary<A | B | C | D | E>;
  function oneof<T>(gs: Arbitrary<T>[]): Arbitrary<T>;
  function record<T>(arbs: { [P in keyof T]: Arbitrary<T[P]> }): Arbitrary<T>;

	/* tslint:disable:max-line-length */
  function forall<A, T>(arb1: Arbitrary<A>, prop: (t: A) => Property<T>): Property<T>;
  function forall<A, B, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, prop: (t: A, u: B) => Property<T>): Property<T>;
  function forall<A, B, C, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, prop: (t: A, u: B, v: C) => Property<T>): Property<T>;
  function forall<A, B, C, D, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, prop: (t: A, u: B, v: C, w: D) => Property<T>): Property<T>;
  function forall<A, B, C, D, E, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, prop: (t: A, u: B, v: C, w: D, e: E) => Property<T>): Property<T>;
  function forall<A, B, C, D, E, F, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, prop: (t: A, u: B, v: C, w: D, e: E, a: F) => Property<T>): Property<T>;
  function forall<A, B, C, D, E, F, G, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G) => Property<T>): Property<T>;
  function forall<A, B, C, D, E, F, G, H, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, arb8: Arbitrary<H>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H) => Property<T>): Property<T>;
  function forall<A, B, C, D, E, F, G, H, I, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, arb8: Arbitrary<H>, arb9: Arbitrary<I>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H, d: I) => Property<T>): Property<T>;
  function forall<A, B, C, D, E, F, G, H, I, J, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, arb8: Arbitrary<H>, arb9: Arbitrary<I>, arb10: Arbitrary<J>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H, d: I, f: J) => Property<T>): Property<T>;
	/* tslint:enable:max-line-length */
  function forall(...args: any[]): Property<any>;

	/* tslint:disable:max-line-length */
  function assertForall<A, T>(arb1: Arbitrary<A>, prop: (t: A) => Property<T>): T;
  function assertForall<A, B, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, prop: (t: A, u: B) => Property<T>): T;
  function assertForall<A, B, C, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, prop: (t: A, u: B, v: C) => Property<T>): T;
  function assertForall<A, B, C, D, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, prop: (t: A, u: B, v: C, w: D) => Property<T>): T;
  function assertForall<A, B, C, D, E, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, prop: (t: A, u: B, v: C, w: D, e: E) => Property<T>): T;
  function assertForall<A, B, C, D, E, F, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, prop: (t: A, u: B, v: C, w: D, e: E, a: F) => Property<T>): T;
  function assertForall<A, B, C, D, E, F, G, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G) => Property<T>): T;
  function assertForall<A, B, C, D, E, F, G, H, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, arb8: Arbitrary<H>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H) => Property<T>): T;
  function assertForall<A, B, C, D, E, F, G, H, I, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, arb8: Arbitrary<H>, arb9: Arbitrary<I>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H, d: I) => Property<T>): T;
  function assertForall<A, B, C, D, E, F, G, H, I, J, T>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, arb8: Arbitrary<H>, arb9: Arbitrary<I>, arb10: Arbitrary<J>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H, d: I, f: J) => Property<T>): T;
	/* tslint:enable:max-line-length */
  function assertForall(...args: any[]): any;

	/* tslint:disable:max-line-length */
  function checkForall<A>(arb1: Arbitrary<A>, prop: (t: A) => Property<A>): Result<A>;
  function checkForall<A, B>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, prop: (t: A, u: B) => Property<any>): Result<any>;
  function checkForall<A, B, C>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, prop: (t: A, u: B, v: C) => Property<any>): Result<any>;
  function checkForall<A, B, C, D>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, prop: (t: A, u: B, v: C, w: D) => Property<any>): Result<any>;
  function checkForall<A, B, C, D, E>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, prop: (t: A, u: B, v: C, w: D, e: E) => Property<any>): Result<any>;
  function checkForall<A, B, C, D, E, F>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, prop: (t: A, u: B, v: C, w: D, e: E, a: F) => Property<any>): Result<any>;
  function checkForall<A, B, C, D, E, F, G>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G) => Property<any>): Result<any>;
  function checkForall<A, B, C, D, E, F, G, H>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, arb8: Arbitrary<H>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H) => Property<any>): Result<any>;
  function checkForall<A, B, C, D, E, F, G, H, I>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, arb8: Arbitrary<H>, arb9: Arbitrary<I>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H, d: I) => Property<any>): Result<any>;
  function checkForall<A, B, C, D, E, F, G, H, I, J>(arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, arb8: Arbitrary<H>, arb9: Arbitrary<I>, arb10: Arbitrary<J>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H, d: I, f: J) => Property<any>): Result<any>;
	/* tslint:enable:max-line-length */
  function checkForall(...args: any[]): Result<any>;

	/* tslint:disable:max-line-length */
  function property<A>(description: String, arb1: Arbitrary<A>, prop: (t: A) => Property<A>): any;
  function property<A, B>(description: String, arb1: Arbitrary<A>, arb2: Arbitrary<B>, prop: (t: A, u: B) => Property<any>): any;
  function property<A, B, C>(description: String, arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, prop: (t: A, u: B, v: C) => Property<any>): any;
  function property<A, B, C, D>(description: String, arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, prop: (t: A, u: B, v: C, w: D) => Property<any>): any;
  function property<A, B, C, D, E>(description: String, arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, prop: (t: A, u: B, v: C, w: D, e: E) => Property<any>): any;
  function property<A, B, C, D, E, F>(description: String, arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, prop: (t: A, u: B, v: C, w: D, e: E, a: F) => Property<any>): any;
  function property<A, B, C, D, E, F, G>(description: String, arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G) => Property<any>): any;
  function property<A, B, C, D, E, F, G, H>(description: String, arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, arb8: Arbitrary<H>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H) => Property<any>): any;
  function property<A, B, C, D, E, F, G, H, I>(description: String, arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, arb8: Arbitrary<H>, arb9: Arbitrary<I>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H, d: I) => Property<any>): any;
  function property<A, B, C, D, E, F, G, H, I, J>(description: String, arb1: Arbitrary<A>, arb2: Arbitrary<B>, arb3: Arbitrary<C>, arb4: Arbitrary<D>, arb5: Arbitrary<E>, arb6: Arbitrary<F>, arb7: Arbitrary<G>, arb8: Arbitrary<H>, arb9: Arbitrary<I>, arb10: Arbitrary<J>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H, d: I, f: J) => Property<any>): any;
	/* tslint:enable:max-line-length */
  function property(...args: any[]): Result<any>;

  function check<T>(prop: Property<T>, opts?: Options): Result<T>;
  function assert<T>(prop: Property<T>, opts?: Options): T;

  const generator: GeneratorFunctions;
  const shrink: ShrinkFunctions;
  const show: ShowFunctions;
  const random: Random;

  interface GeneratorFunctions {
    constant<U>(u: U): Generator<U>;
    oneof<U>(gens: Generator<U>[]): Generator<U>;
    recursive<U>(genZ: Generator<U>, f: (u: U) => U): Generator<U>;
    pair<T, U>(genA: Generator<T>, genB: Generator<U>): Generator<[T, U]>;
    either<T, U>(genA: Generator<T>, genB: Generator<U>): Generator<Either<T, U>>;

    tuple(gens: Generator<any>[]): Generator<any[]>;
    sum(gens: Generator<any>[]): Generator<any>;

    array<U>(gen: Generator<U>): Generator<U[]>;
    nearray<U>(gen: Generator<U>): Generator<U[]>;
    dict<U>(gen: Generator<U>): Generator<{ [key: string]: U }>;

    unit: Generator<any>;

    bless<T>(genLike: GeneratorFn<T>): Generator<T>;
    small<T>(gen: Generator<T>): Generator<T>;

		/* tslint:disable:max-line-length */
    combine<A, R>(arb1: Generator<A>, prop: (t: A) => R): Generator<R>;
    combine<A, B, R>(arb1: Generator<A>, arb2: Generator<B>, prop: (t: A, u: B) => R): Generator<R>;
    combine<A, B, C, R>(arb1: Generator<A>, arb2: Generator<B>, arb3: Generator<C>, prop: (t: A, u: B, v: C) => R): Generator<R>;
    combine<A, B, C, D, R>(arb1: Generator<A>, arb2: Generator<B>, arb3: Generator<C>, arb4: Generator<D>, prop: (t: A, u: B, v: C, w: D) => R): Generator<R>;
    combine<A, B, C, D, E, R>(arb1: Generator<A>, arb2: Generator<B>, arb3: Generator<C>, arb4: Generator<D>, arb5: Generator<E>, prop: (t: A, u: B, v: C, w: D, e: E) => R): Generator<R>;
    combine<A, B, C, D, E, F, R>(arb1: Generator<A>, arb2: Generator<B>, arb3: Generator<C>, arb4: Generator<D>, arb5: Generator<E>, arb6: Generator<F>, prop: (t: A, u: B, v: C, w: D, e: E, a: F) => R): Generator<R>;
    combine<A, B, C, D, E, F, G, R>(arb1: Generator<A>, arb2: Generator<B>, arb3: Generator<C>, arb4: Generator<D>, arb5: Generator<E>, arb6: Generator<F>, arb7: Generator<G>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G) => R): Generator<R>;
    combine<A, B, C, D, E, F, G, H, R>(arb1: Generator<A>, arb2: Generator<B>, arb3: Generator<C>, arb4: Generator<D>, arb5: Generator<E>, arb6: Generator<F>, arb7: Generator<G>, arb8: Generator<H>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H) => R): Generator<R>;
    combine<A, B, C, D, E, F, G, H, I, R>(arb1: Generator<A>, arb2: Generator<B>, arb3: Generator<C>, arb4: Generator<D>, arb5: Generator<E>, arb6: Generator<F>, arb7: Generator<G>, arb8: Generator<H>, arb9: Generator<I>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H, d: I) => R): Generator<R>;
    combine<A, B, C, D, E, F, G, H, I, J, R>(arb1: Generator<A>, arb2: Generator<B>, arb3: Generator<C>, arb4: Generator<D>, arb5: Generator<E>, arb6: Generator<F>, arb7: Generator<G>, arb8: Generator<H>, arb9: Generator<I>, arb10: Generator<J>, prop: (t: A, u: B, v: C, w: D, e: E, a: F, b: G, c: H, d: I, f: J) => R): Generator<R>;
		/* tslint:enable:max-line-length */
    combine<R>(...args: any[]): Generator<R>;
  }

  interface ShrinkFunctions {
    noop: Shrink<any>;
    pair<T, U>(shrA: Shrink<T>, shrB: Shrink<U>): Shrink<[T, U]>;
    either<T, U>(shrA: Shrink<T>, shrB: Shrink<U>): Shrink<Either<T, U>>;

    tuple(shrs: Shrink<any>[]): Shrink<any[]>;
    sum(shrs: Shrink<any>[]): Shrink<any>;

    array<T>(shr: Shrink<T>): Shrink<T[]>;
    nearray<T>(shr: Shrink<T>): Shrink<T[]>;

    bless<T>(shrinkLike: ShrinkFn<T>): Shrink<T>;
  }

  interface ShowFunctions {
    def<T>(x: T): string;
    pair<T, U>(sA: Show<T>, sB: Show<U>, x: [T, U]): string;
    either<T, U>(sA: Show<T>, sB: Show<U>, x: Either<T, U>): string;

    tuple(shs: Show<any>[], x: any[]): string;
    sum(shs: Show<any>[], x: any): string;

    array<T>(sh: Show<T>, x: T[]): string;
  }

  type Random = RandomInt & RandomFunctions;
  type RandomInt = (min: number, max: number) => number;
  interface RandomFunctions {
    number(min: number, max: number): number;
  }
}

export = JSVerify;
