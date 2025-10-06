export = createMatcher;
/**
 * Creates a matcher object for the passed expectation
 *
 * @alias module:samsam.createMatcher
 * @param {*} expectation An expecttation
 * @param {string} message A message for the expectation
 * @returns {object} A matcher object
 */
declare function createMatcher(expectation: any, message: string, ...args: any[]): object;
declare namespace createMatcher {
    export { isMatcher };
    export let any: any;
    export let defined: any;
    export let truthy: any;
    export let falsy: any;
    export function same(expectation: any): any;
    function _in(arrayOfExpectations: any): any;
    export { _in as in };
    export function typeOf(type: any): any;
    export function instanceOf(type: any): any;
    export let has: any;
    export let hasOwn: any;
    export function hasNested(property: any, value: any, ...args: any[]): any;
    export function json(value: any): any;
    export function every(predicate: any): any;
    export function some(predicate: any): any;
    export let array: any;
    export let map: any;
    export let set: any;
    export let bool: any;
    export let number: any;
    export let string: any;
    export let object: any;
    export let func: any;
    export let regexp: any;
    export let date: any;
    export let symbol: any;
}
import isMatcher = require("./create-matcher/is-matcher");
