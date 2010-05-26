browserUtil = {
  isRhino: true,
  
  pauseAndThen: function (cb) { cb(); },
  
  cpsIterate: function (f, start, end, pause, callback) {
    function go() {
      var called = false;
      if (start >= end) {
        callback && callback();
      } else {
        f(start, function () {
          if (!called) { called = true; start++; go(); }
        });
      }
    }
    go (start);
  },
  
  cpsMap: function (map, list, pause, callback) {
    browserUtil.cpsIterate(function (i, cb) { map(list[i], i, list.length, cb); },
                           0, list.length, pause, callback);
  },

  loadScripts: function(scriptNames, callback) {
    for (i=0; i<scriptNames.length; i++) {
      load(scriptNames[i]);
      callback && callback();
    }
  },
  
  write: function(type, message) {
    print(message);
    return { update: function (type2, message2) {
      if (type2 === 'pass') { print("  + " + message2); }
      else if (type2 === 'unimplemented') { print("  ? " + message2); }
      else { print("  - " + message2); }
    }};
  },
  
  writeNewline: function () { print(""); },
  
  status: function(message) {}
};
