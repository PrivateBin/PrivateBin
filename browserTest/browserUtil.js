browserUtil = {};

browserUtil.isRhino = (typeof(window) === 'undefined');

/**
 * Pause (for the graphics to update and the script timer to clear), then run the
 * specified action.
 */
browserUtil.pauseAndThen = function (cb) {
  cb && window.setTimeout(cb, 1);
};

/**
 * Iterate using continuation-passing style.
 */
browserUtil.cpsIterate = function (f, start, end, pause, callback) {
  var pat = pause ? browserUtil.pauseAndThen : function (cb) { cb && cb(); };
  function go() {
    var called = false;
    if (start >= end) {
      pat(callback);
    } else {
      pat(function () { f(start, function () {
        if (!called) { called = true; start++; go(); }
      }); });
    }
  }
  go (start);
};

/**
 * Map a function over an array using continuation-passing style.
 */
browserUtil.cpsMap = function (map, list, pause, callback) {
  browserUtil.cpsIterate(function (i, cb) { map(list[i], i, list.length, cb); },
                         0, list.length, pause, callback);
}

/** Cache for remotely loaded scripts. */
browserUtil.scriptCache = {}

/** Load several scripts, then call back */
browserUtil.loadScripts = function(scriptNames, cbSuccess, cbError) {
  var head = document.getElementsByTagName('head')[0];
  browserUtil.cpsMap(function (script, i, n, cb) {
    var scriptE = document.createElement('script'), xhr, loaded = false;
    
    browserUtil.status("Loading script " + script);
    
    if (window.location.protocol === "file:") {
      /* Can't make an AJAX request for files.
       * But, we know the load time will be short, so timeout-based error
       * detection is fine.
       */
      scriptE.onload = function () {
        loaded = true;
        cb();
      };
      scriptE.onerror = function(err) {
        cbError && cbError(script, err, cb);
      };
      script.onreadystatechange = function() {
        if (this.readyState == 'complete' || this.readyState == 'loaded') {
          loaded = true;
          cb();
        }
      };
      scriptE.type = 'text/javascript';
      scriptE.src = script+"?"+(new Date().valueOf());
      window.setTimeout(function () {
        loaded || cbError && cbError(script, "timeout expired", cb);
      }, 100);
      head.appendChild(scriptE);
    } else if (browserUtil.scriptCache[script] !== undefined) {
      try {
        scriptE.appendChild(document.createTextNode(browserUtil.scriptCache[script]));
      } catch (e) {
        scriptE.text = browserUtil.scriptCache[script];
      }
      head.appendChild(scriptE);
      cb();
    } else {
      var xhr;
      if (window.XMLHttpRequest) {
        xhr = new XMLHttpRequest;
      } else if (window.ActiveXObject) {
        xhr = new ActiveXObject("Microsoft.XMLHTTP");
      }
      xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
          if (xhr.status == 200) {
            browserUtil.scriptCache[script] = xhr.responseText;
            try {
              scriptE.appendChild(document.createTextNode(xhr.responseText));
            } catch (e) {
              scriptE.text = xhr.responseText;
            }
            head.appendChild(scriptE);
            cb();
          } else {
            cbError && cbError(script, xhr.status, cb);
          }
        }
      }
      xhr.open("GET", script+"?"+(new Date().valueOf()), true);
      xhr.send();
    }
  }, scriptNames, false, cbSuccess);
};

/** Write a message to the console */
browserUtil.write = function(type, message) {
  var d1 = document.getElementById("print"), d2 = document.createElement("div"), d3 = document.createElement("div");
  d3.className = type;
  d3.appendChild(document.createTextNode(message));
  d2.appendChild(d3);
  d1.appendChild(d2);
  return { update: function (type2, message2) {
    var d4 = document.createElement("div");
    d4.className = type2 + " also";
    d4.appendChild(document.createTextNode(message2));
    d2.insertBefore(d4, d3);
  }};
};

/** Write a newline.  Does nothing in the browser. */
browserUtil.writeNewline = function () { };

/** Write a message to the status line */
browserUtil.status = function(message) {
  var d1 = document.getElementById("status");
  d1.replaceChild(document.createTextNode(message), d1.firstChild);
};
