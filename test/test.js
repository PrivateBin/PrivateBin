sjcl.test = { vector: {}, all: {} };

/* A bit of a hack.  Because sjcl.test will be reloaded several times
 * for different variants of sjcl, but browserUtils will not, this
 * variable keeps a permanent record of whether anything has failed.
 */
if (typeof browserUtil.allPassed === 'undefined') {
    browserUtil.allPassed = true;
}

sjcl.test.TestCase = function(name, doRun) {
  this.doRun = doRun;
  this.name = name;
  this.passes = 0;
  this.failures = 0;
  this.isUnimplemented = false;
  sjcl.test.all[name] = this;
};

sjcl.test.TestCase.prototype = {
  /** Pass some subtest of this test */
  pass: function () { this.passes ++; },
  
  /** Fail some subtest of this test */
  fail: function (message) {
    if (message !== undefined) {
      this.log("fail", "*** FAIL *** " + this.name + ": " + message);
    } else {
      this.log("fail", "*** FAIL *** " + this.name);
    }
    this.failures ++;
    browserUtil.allPassed = false;
  },
  
  unimplemented: function() {
    this.isUnimplemented = true;
  },
  
  /** Log a message to the console */
  log: browserUtil.write,
  
  /** Require that the first argument is true; otherwise fail with the given message */
  require: function (bool, message) {
    if (bool) {
      this.pass();
    } else if (message !== undefined) {
      this.fail(message);
    } else {
      this.fail("requirement failed");
    }
  },

  /** Pause and then take the specified action. */
  pauseAndThen: browserUtil.pauseAndThen,
  
  /** Continuation-passing-style iteration */
  cpsIterate: browserUtil.cpsIterate,
  
  /** Continuation-passing-style iteration */
  cpsMap: browserUtil.cpsMap,

  /** Report the results of this test. */
  report: function (repo) {
    var t = (new Date()).valueOf() - this.startTime;
    if (this.failures !== 0) {
      repo.update("fail", "failed " + this.failures + " / " +
                  (this.passes + this.failures) + " tests. (" + t + " ms)");
    } else if (this.passes === 1) {
      repo.update("pass", "passed. (" + t + " ms)");
    } else if (this.isUnimplemented) {
      repo.update("unimplemented", "unimplemented");
    } else {
      repo.update("pass", "passed all " + this.passes + " tests. (" + t + " ms)");
    }
    browserUtil.writeNewline();
  },
  

  /** Run the test. */
  run: function (ntests, i, cb) {
    var thiz = this, repo = this.log("info", "Running " + this.name + "...");
    this.startTime = (new Date()).valueOf();
    this.pauseAndThen(function () {
      thiz.doRun(function () {
        thiz.report(repo);
        cb && cb();
      })
    });
  }
};

// pass a list of tests to run, or pass nothing and it will run them all
sjcl.test.run = function (tests, callback) {
  var t;
    
  if (tests === undefined || tests.length == 0) {
    tests = [];
    for (t in sjcl.test.all) {
      if (sjcl.test.all.hasOwnProperty(t)) {
        tests.push(t);
      }
    }
  }
  
  browserUtil.cpsMap(function (t, i, n, cb) {
    sjcl.test.all[tests[i]].run(n, i+1, cb);
  }, tests, true, callback);
};

/* Several test scripts rely on sjcl.codec.hex to parse their test
 * vectors, but we are not guaranteed that sjcl.codec.hex is
 * implemented.
 */
sjcl.codec = sjcl.codec || {};
sjcl.codec.hex = sjcl.codec.hex ||
{
  fromBits: function (arr) {
    var out = "", i, x;
    for (i=0; i<arr.length; i++) {
      out += ((arr[i]|0)+0xF00000000000).toString(16).substr(4);
    }
    return out.substr(0, sjcl.bitArray.bitLength(arr)/4);//.replace(/(.{8})/g, "$1 ");
  },
  toBits: function (str) {
    var i, out=[], len;
    str = str.replace(/\s|0x/g, "");
    len = str.length;
    str = str + "00000000";
    for (i=0; i<str.length; i+=8) {
      out.push(parseInt(str.substr(i,8),16)^0);
    }
    return sjcl.bitArray.clamp(out, len*4);
  }
};
