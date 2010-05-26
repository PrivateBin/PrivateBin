function testCore(coreName, cb) {
  var corePath = "../", testPath = "../test/", testFiles = [
    corePath + coreName,
    "test.js",
    "aes_test.js",
    "aes_vectors.js",
    "ccm_test.js",
    "ccm_vectors.js",
    "ocb2_test.js",
    "ocb2_vectors.js",
    "sha256_test.js",
    "sha256_vectors.js",
    "sha256_test_brute_force.js",
    "hmac_test.js",
    "hmac_vectors.js",
    "pbkdf2_test.js"
  ], i;
  
  for (i=1; i<testFiles.length; i++) {
    testFiles[i] = testPath + testFiles[i];
  }

  browserUtil.loadScripts(testFiles, function() {
    browserUtil.write("begin", coreName);
    browserUtil.status("Testing...");
    sjcl.test.run([], function () {
      browserUtil.status("");
      cb && cb();
    });
  },
  function (script, err) {
    browserUtil.allPassed = false;
    browserUtil.write("begin fail", coreName);
    browserUtil.write("fail", "Failed to load "+script+": "+err);
    cb();
  });
}

function testCores(coreNames, cb) {
  browserUtil.cpsMap(function (n,x,y,cb) { testCore(n,cb); }, coreNames, true, function() {
    if (browserUtil.allPassed) {
      browserUtil.write("pass all", "All tests passed.");
    } else {
      browserUtil.write("fail all", "All tests complete, but some failed!");
    }
    cb && cb();
  });
}
