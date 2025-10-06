/* eslint strict:[2,"function"] */
module.exports = function (config) {
  "use strict";
  config.set({
    basePath: "",
    frameworks: ["jasmine"],
    files: [
      "helpers/jasmineHelpers2.js",
      "dist/jsverify.standalone.js",
      "spec/*.js",
    ],
    exclude: [
    ],
    preprocessors: {
    },
    reporters: ["progress"],
    port: 9876,
    colors: true,
    logLevel: config.LOG_INFO,
    autoWatch: false,
    browsers: ["Chrome", "Firefox"],
    singleRun: true,
  });
};
