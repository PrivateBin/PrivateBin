"use strict";

var array = require("./array.js");
var primitive = require("./primitive.js");
var utils = require("./utils.js");

/**
  ### Arbitrary strings
*/

function fromCode(code) {
  return String.fromCharCode(code);
}

function toCode(c) {
  return c.charCodeAt(0);
}

/**
  - `char: arbitrary char` &mdash; Single character
*/
var char = primitive.nat(0xff).smap(fromCode, toCode);

/**
  - `asciichar: arbitrary char` &mdash; Single ascii character (0x20-0x7e inclusive, no DEL)
*/
var asciichar = primitive.integer(0x20, 0x7e).smap(fromCode, toCode);

/**
  - `string: arbitrary string`
*/
var string = array.array(char).smap(utils.charArrayToString, utils.stringToCharArray);

/**
  - `nestring: arbitrary string` &mdash; Generates strings which are not empty.
*/
var nestring = array.nearray(char).smap(utils.charArrayToString, utils.stringToCharArray);

/**
  - `asciistring: arbitrary string`
*/
var asciistring = array.array(asciichar).smap(utils.charArrayToString, utils.stringToCharArray);

/**
  - `asciinestring: arbitrary string`
*/
var asciinestring = array.nearray(asciichar).smap(utils.charArrayToString, utils.stringToCharArray);

module.exports = {
  char: char,
  asciichar: asciichar,
  string: string,
  nestring: nestring,
  asciistring: asciistring,
  asciinestring: asciinestring,
};
