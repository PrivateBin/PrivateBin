"use strict";

var environment = require("./environment.js");
var record = require("./record.js");
var typify = require("./typify.js");
var utils = require("./utils.js");

/**
  ### Arbitrary records

  - `record(spec: { key: arbitrary a... }, userenv: env?): arbitrary { key: a... }`

      Generates a javascript object with given record spec.
*/
function recordWithEnv(spec, userenv) {
  var env = userenv ? utils.merge(environment, userenv) : environment;

  var parsedSpec = {};
  Object.keys(spec).forEach(function (k) {
    var arb = spec[k];
    parsedSpec[k] = typeof arb === "string" ? typify.parseTypify(env, arb) : arb;
  });

  return record.arbitrary(parsedSpec);
}

module.exports = recordWithEnv;
