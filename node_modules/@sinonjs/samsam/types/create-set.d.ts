export = createSet;
/**
 * This helper makes it convenient to create Set instances from a
 * collection, an overcomes the shortcoming that IE11 doesn't support
 * collection arguments
 *
 * @private
 * @param  {Array} array An array to create a set from
 * @returns {Set} A set (unique) containing the members from array
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Set
 */
declare function createSet(array: any[], ...args: any[]): Set<any>;
