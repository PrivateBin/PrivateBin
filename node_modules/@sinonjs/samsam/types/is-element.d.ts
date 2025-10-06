export = isElement;
/**
 * Returns `true` when `object` is a DOM element node.
 *
 * Unlike Underscore.js/lodash, this function will return `false` if `object`
 * is an *element-like* object, i.e. a regular object with a `nodeType`
 * property that holds the value `1`.
 *
 * @alias module:samsam.isElement
 * @param {object} object The object to examine
 * @returns {boolean} Returns `true` for DOM element nodes
 */
declare function isElement(object: object): boolean;
