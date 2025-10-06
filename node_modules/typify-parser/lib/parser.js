/**
  # typify type parser

  > Type signature parser for typify

  [![Build Status](https://secure.travis-ci.org/phadej/typify-parser.svg?branch=master)](http://travis-ci.org/phadej/typify-parser)
  [![NPM version](https://badge.fury.io/js/typify-parser.svg)](http://badge.fury.io/js/typify-parser)
  [![Dependency Status](https://david-dm.org/phadej/typify-parser.svg)](https://david-dm.org/phadej/typify-parser)
  [![devDependency Status](https://david-dm.org/phadej/typify-parser/dev-status.svg)](https://david-dm.org/phadej/typify-parser#info=devDependencies)
  [![Code Climate](https://img.shields.io/codeclimate/github/phadej/typify-parser.svg)](https://codeclimate.com/github/phadej/typify-parser)

  Turns `(foo, bar 42) -> quux` into
  ```js
  {
    "type": "function",
    "arg": {
      "type": "product",
      "args": [
        {
          "type": "ident",
          "value": "foo"
        },
        {
          "type": "application",
          "callee": {
            "type": "ident",
            "value": "bar"
          },
          "args": [
            {
              "type": "number",
              "value": 42
            }
          ]
        }
      ]
    },
    "result": {
      "type": "ident",
      "value": "quux"
    }
  }
  ```

  ## Synopsis

  ```js
  var parser = require("typify-parser");

  // Example from above
  var t = parser("(foo, bar 42) -> quux");

  // Free vars
  p.freeVars(t);                             // ['bar', 'foo', 'quux']
  p.freeVars(p("rec list -> () | a & list")) // ['a']
  ```
*/
"use strict";

function unescapeString(str) {
  return str.replace(/\\(?:'|"|\\|n|x[0-9a-fA-F]{2})/g, function (match) {
    switch (match[1]) {
      case "'": return "'";
      case "\"": return "\"";
      case "\\": return "\\";
      case "n": return "\n";
      case "x": return String.fromCharCode(parseInt(match.substr(2), 16));
    }
  });
}

function lex(input) {
  // Unicode
  // top: 22a4
  // bottom: 22a5
  // and: 2227
  // or: 2228
  // times: \u00d7
  // to: 2192
  // ellipsis: 2026
  // blackboard 1: d835 dfd9
  var m = input.match(/^([ \t\r\n]+|[\u22a4\u22a5\u2227\u2228\u00d7\u2192\u2026]|\ud835\udfd9|_\|_|\*|\(\)|"(?:[^"\\]|\\[\\'"n]|\\x[0-9a-fA-F]{2})*"|'(?:[^'\\]|\\[\\'"n]|\\x[0-9a-fA-F]{2})*'|[0-9a-zA-Z_\$@]+|,|->|:|;|&|\||\.\.\.|\(|\)|\[|\]|\{|\}|\?)*$/);
  if (m === null) {
    throw new SyntaxError("Cannot lex type signature");
  }
  m = input.match(/([ \t\r\n]+|[\u22a4\u22a5\u2227\u2228\u00d7\u2192\u2026]|\ud835\udfd9|_\|_|\*|\(\)|"(?:[^"\\]|\\[\\'"n]|\\x[0-9a-fA-F]{2})*"|'(?:[^'\\]|\\[\\'"n]|\\x[0-9a-fA-F]{2})*'|[0-9a-zA-Z_\$@]+|,|->|:|;|&|\||\.\.\.|\(|\)|\[|\]|\{|\}|\?)/g);

  return m
  .map(function (token) {
    switch (token) {
      case "_|_": return { type: "false" };
      case "\u22a5": return { type: "false" };
      case "*": return { type: "true" };
      case "\u22a4": return { type: "true" };
      case "()": return { type: "unit" };
      case "\ud835\udfd9": return { type: "unit" };
      case "true": return { type: "bool", value: true };
      case "false": return { type: "bool", value: false };
      case "rec": return { type: "rec" };
      case "&": return { type: "&" };
      case "\u2227": return { type: "&" };
      case "|": return { type: "|" };
      case "\u2228": return { type: "|" };
      case ",": return { type: "," };
      case "\u00d7": return { type: "," };
      case ";": return { type: ";" };
      case ":": return { type: ":" };
      case "(": return { type: "(" };
      case ")": return { type: ")" };
      case "[": return { type: "[" };
      case "]": return { type: "]" };
      case "{": return { type: "{" };
      case "}": return { type: "}" };
      case "?": return { type: "?" };
      case "->": return { type: "->" };
      case "\u2192": return { type: "->" };
      case "...": return { type: "..." };
      case "\u2026": return { type: "..." };
    }

    // Whitespace
    if (token.match(/^[ \r\r\n]+$/)) {
      return null;
    }

    if (token.match(/^[0-9]+/)) {
      return { type: "number", value: parseInt(token, 10) };
    }

    if (token[0] === "'" || token[0] === "\"") {
      token = token.slice(1, -1);
      return { type: "string", value: unescapeString(token) };
    }

    return { type: "ident", value: token };
  })
  .filter(function (token) {
    return token !== null;
  });
}

function makePunctParser(type) {
  return function (state) {
    if (state.pos >= state.len) {
      throw new SyntaxError("Expecting identifier, end-of-input found");
    }

    var token = state.tokens[state.pos];
    if (token.type !== type) {
      throw new SyntaxError("Expecting '" + type + "', found: " + token.type);
    }
    state.pos += 1;

    return type;
  };
}

var colonParser = makePunctParser(":");
var openCurlyParser = makePunctParser("{");
var closeCurlyParser = makePunctParser("}");
var semicolonParser = makePunctParser(";");
var openParenParser = makePunctParser("(");
var closeParenParser = makePunctParser(")");
var openBracketParser = makePunctParser("[");
var closeBracketParser = makePunctParser("]");
var recKeywordParser = makePunctParser("rec");
var arrowParser = makePunctParser("->");

function nameParser(state) {
  if (state.pos >= state.len) {
    throw new SyntaxError("Expecting identifier, end-of-input found");
  }

  var token = state.tokens[state.pos];
  if (token.type !== "ident") {
    throw new SyntaxError("Expecting 'ident', found: " + token.type);
  }
  state.pos += 1;

  return token.value;
}

function recursiveParser(state) {
  recKeywordParser(state);
  var name = nameParser(state);
  arrowParser(state);
  var value = typeParser(state); // eslint-disable-line no-use-before-define
  return {
    type: "recursive",
    name: name,
    arg: value,
  };
}

function recordParser(state) {
  openCurlyParser(state);

  var token = state.tokens[state.pos];
  if (token && token.type === "}") {
    closeCurlyParser(state);
    return { type: "record", fields: {} };
  }

  var fields = {};

  while (true) { // eslint-disable-line no-constant-condition
    // read
    var name = nameParser(state);
    colonParser(state);
    var value = typeParser(state); // eslint-disable-line no-use-before-define

    // assign to fields
    fields[name] = value;

    // ending token
    token = state.tokens[state.pos];

    // break if }
    if (token && token.type === "}") {
      closeCurlyParser(state);
      break;
    } else if (token && token.type === ";") {
      semicolonParser(state);
    } else {
      throw new SyntaxError("Expecting '}' or ';', found: " + token.type);
    }
  }

  return { type: "record", fields: fields };
}

function postfix(parser, postfixToken, constructor) {
  return function (state) {
    var arg = parser(state);

    var token = state.tokens[state.pos];
    if (token && token.type === postfixToken) {
      state.pos += 1;
      return {
        type: constructor,
        arg: arg,
      };
    } else {
      return arg;
    }
  };
}

// this ties the knot
var optionalParser = postfix(terminalParser, "?", "optional"); // eslint-disable-line no-use-before-define

function applicationParser(state) {
  var rator = optionalParser(state);
  var rands = [];

  while (true) { // eslint-disable-line no-constant-condition
    var pos = state.pos;
    // XXX: we shouldn't use exceptions for this
    try {
      var arg = optionalParser(state);
      rands.push(arg);
    } catch (err) {
      state.pos = pos;
      break;
    }
  }

  if (rands.length === 0) {
    return rator;
  } else {
    return {
      type: "application",
      callee: rator,
      args: rands,
    };
  }
}

function separatedBy(parser, separator, constructor) {
  return function (state) {
    var list = [parser(state)];
    while (true) { // eslint-disable-line no-constant-condition
      // separator
      var token = state.tokens[state.pos];
      if (token && token.type === separator) {
        state.pos += 1;
      } else {
        break;
      }

      // right argument
      list.push(parser(state));
    }

    if (list.length === 1) {
      return list[0];
    } else {
      return {
        type: constructor,
        args: list,
      };
    }
  };
}

var conjunctionParser = separatedBy(applicationParser, "&", "conjunction");
var disjunctionParser = separatedBy(conjunctionParser, "|", "disjunction");

// TODO: combine with optional
var variadicParser = postfix(disjunctionParser, "...", "variadic");

function namedParser(state) {
  var token1 = state.tokens[state.pos];
  var token2 = state.tokens[state.pos + 1];
  if (token1 && token2 && token1.type === "ident" && token2.type === ":") {
    state.pos += 2;
    var arg = namedParser(state);
    return {
      type: "named",
      name: token1.value,
      arg: arg,
    };
  } else {
    return variadicParser(state);
  }
}

var productParser = separatedBy(namedParser, ",", "product");

function functionParser(state) {
  var v = productParser(state);

  var token = state.tokens[state.pos];
  if (token && token.type === "->") {
    state.pos += 1;
    var result = functionParser(state);
    return {
      type: "function",
      arg: v,
      result: result,
    };
  } else {
    return v;
  }
}

function typeParser(state) {
  return functionParser(state);
}

function parenthesesParser(state) {
  openParenParser(state);
  var type = typeParser(state);
  closeParenParser(state);
  return type;
}

function bracketParser(state) {
  openBracketParser(state);
  var type = typeParser(state);
  closeBracketParser(state);
  return {
    type: "brackets",
    arg: type,
  };
}

function terminalParser(state) {
  if (state.pos >= state.len) {
    throw new SyntaxError("Expecting terminal, end-of-input found");
  }

  var token = state.tokens[state.pos];
  switch (token.type) {
    case "false":
    case "true":
    case "unit":
    case "string":
    case "number":
    case "bool":
    case "ident":
      state.pos += 1;
      return token;
    case "{":
      return recordParser(state);
    case "(":
      return parenthesesParser(state);
    case "[":
      return bracketParser(state);
    case "rec":
      return recursiveParser(state);
    default:
      throw new SyntaxError("Expecting terminal, " + token.type + " found");
  }
}

function parse(input) {
  // console.log(input);
  var tokens = lex(input);
  // console.log(tokens);
  var state = {
    pos: 0,
    len: tokens.length,
    tokens: tokens,
  };

  var res = typeParser(state);
  // console.log(state);
  if (state.pos !== state.len) {
    throw new SyntaxError("expecting end-of-input, " + tokens[state.pos].type + " found");
  }
  return res;
}

function recordFreeVars(fields) {
  var res = [];
  for (var k in fields) {
    var t = fields[k];
    res = res.concat(freeVarsImpl(t)); // eslint-disable-line no-use-before-define
  }
  return res;
}

function concatFreeVars(ts) {
  var res = [];
  for (var i = 0; i < ts.length; i++) {
    var t = ts[i];
    res = res.concat(freeVarsImpl(t)); // eslint-disable-line no-use-before-define
  }
  return res;
}

function freeVarsImpl(t) {
  switch (t.type) {
    case "false":
    case "true":
    case "unit":
    case "string":
    case "number":
    case "bool":
      return [];
    case "ident": return [t.value];
    case "record": return recordFreeVars(t.fields);
    case "named": return freeVarsImpl(t.arg);
    case "conjunction": return concatFreeVars(t.args);
    case "disjunction": return concatFreeVars(t.args);
    case "product": return concatFreeVars(t.args);
    case "recursive": return freeVarsImpl(t.arg).filter(function (n) {
      return n !== t.name;
    });
    case "optional": return freeVarsImpl(t.arg);
    case "brackets": return freeVarsImpl(t.arg);
    case "variadic": return freeVarsImpl(t.arg);
    case "application": return freeVarsImpl(t.callee).concat(concatFreeVars(t.args));
    case "function": return freeVarsImpl(t.arg).concat(freeVarsImpl(t.result));
    //default: throw new Error("Unknown type " + t.type);
  }
}

function uniq(arr) {
  var res = [];
  for (var i = 0; i < arr.length; i++) {
    var x = arr[i];
    if (res.indexOf(x) === -1) {
      res.push(x);
    }
  }
  return res;
}

function freeVars(t) {
  var fvs = freeVarsImpl(t);
  fvs.sort();
  return uniq(fvs);
}

parse.freeVars = freeVars;

module.exports = parse;
