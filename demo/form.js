/* Hackish form handling system. */
function hasClass(e, cl) {
  return (" "+e.className+" ").match(" "+cl+" ");
}

function stopPropagation(e) {
  e.preventDefault && e.preventDefault();
  e.cancelBubble = true;
}

/* proxy for a form object, with appropriate encoder/decoder */
function formElement(el) {
  this.el = el;
}
formElement.prototype = {
  get: function() {
    var el = this.el;
    if (el.type == "checkbox") {
      return el.checked;
    } else if (hasClass(el, "numeric")) {
      return parseInt(el.value);
    } else if (hasClass(el, "hex")) {
      return sjcl.codec.hex.toBits(el.value);
    } else if (hasClass(el, "base64")) {
      return sjcl.codec.base64.toBits(el.value);
    } else {
      return el.value;
    }
  },
  
  set: function(x) {
    var el = this.el;
    if (el.type == "checkbox") {
      el.checked = x; return;
    } else if (hasClass(el, "hex")) {
      if (typeof x !== 'string') {
        x = sjcl.codec.hex.fromBits(x);
      }
      x = x.toUpperCase().replace(/ /g,'').replace(/(.{8})/g, "$1 ").replace(/ $/, '');
    } else if (hasClass(el, "base64")) {
      if (typeof x !== 'string') {
        x = sjcl.codec.base64.fromBits(x);
      }
      x = x.replace(/\s/g,'').replace(/(.{32})/g, "$1\n").replace(/\n$/, '');
    }
    el.value = x;
  }
}

function radioGroup(name) {
  this.name = name;
}
radioGroup.prototype = {
  get: function() {
    var els = document.getElementsByName(this.name), i;
    for (i=0; i<els.length; i++) {
      if (els[i].checked) {
        return els[i].value;
      }
    }
  },
  
  set: function(x) {
    var els = document.getElementsByName(this.name), i;
    for (i=0; i<els.length; i++) {
      els[i].checked = (els[i].value == x);
    }
  }
}

function formHandler(formName, enterActions) {
  var i, els = [], tmp, name;
  this._elNames = [];
  
  tmp = document.getElementById(formName).getElementsByTagName('input');
  for (i=0; i<tmp.length; i++) { els.push(tmp[i]); }
  
  tmp = document.getElementById(formName).getElementsByTagName('textarea');
  for (i=0; i<tmp.length; i++) { els.push(tmp[i]); }
  
  for (i=0; i<els.length; i++) {
    name = els[i].name  || els[i].id;
    
    /* enforce numeric properties of element */
    els[i].onkeypress = (function(e) {
      return function(ev) {
        ev = ev || window.event;
        var key = ev.keyCode || ev.which,
            keyst = String.fromCharCode(ev.charCode || ev.keyCode),
            ente = enterActions[e.name||e.id];
        
        if (ev.ctrlKey || ev.metaKey) {
          return;
        }
        
        (key == 13) && ente && ente();
        
        if (hasClass(e, 'numeric') && ev.charCode && !keyst.match(/[0-9]/)) {
          stopPropagation(ev); return false;
        } else if (hasClass(e, 'hex') && ev.charCode && !keyst.match(/[0-9a-fA-F ]/)) {
          stopPropagation(ev); return false;
        }
      }
    })(els[i]);
    
    if (els[i].type == 'radio') {
      if (this[name] === undefined) {
        this[name] = new radioGroup(name);
        this._elNames.push(name);
      }
    } else {
      /* code to get the value of an element */
      this[name] = new formElement(els[i]);
      this._elNames.push(name);
    }
  }
}

formHandler.prototype = {
  get:function() {
    var i, out = {}, en = this._elNames;
    for (i=0; i<en.length; i++) {
      out[en[i]] = this[en[i]].get();
    }
    return out;
  },
  
  set:function(o) {
    var i;
    for (i in o) {
      if (o.hasOwnProperty(i) && this.hasOwnProperty(i)) {
        this[i].set(o[i]);
      }
    }
  }
}

