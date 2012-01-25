/* keep track of which salts have been used. */
var form, usedIvs = {'':1}, usedSalts = {'':1};

/* enter actions */
var enterActions = {
  password: doPbkdf2,
  salt: doPbkdf2,
  iter: doPbkdf2
};

function loaded() {
  form = new formHandler('theForm', enterActions);
  form._extendedKey = [];
  sjcl.random.startCollectors();
  document.getElementById("password").focus();
}

/* there's probaby a better way to tell the user something, but oh well */
function error(x) {
  alert(x);
}

/* compute PBKDF2 on the password. */
function doPbkdf2(decrypting) {
  var v = form.get(), salt=v.salt, key, hex = sjcl.codec.hex.fromBits, p={},
      password = v.password;
  
  p.iter = v.iter;
  
  if (password.length == 0) {
    if (decrypting) { error("Can't decrypt: need a password!"); }
    return;
  }
  
  if (salt.length === 0 && decrypting) {
    error("Can't decrypt: need a salt for PBKDF2!");
    return;
  }
  
  if (decrypting || !v.freshsalt || !usedSalts[v.salt]) {
    p.salt = v.salt;
  }
  
  p = sjcl.misc.cachedPbkdf2(password, p);
  form._extendedKey = p.key;
  v.key = p.key.slice(0, v.keysize/32);
  v.salt = p.salt;
  
  form.set(v);
  form.plaintext.el.select();
}
/* Encrypt a message */
function doEncrypt() {
  var v = form.get(), iv = v.iv, password = v.password, key = v.key, adata = v.adata, aes, plaintext=v.plaintext, rp = {}, ct, p;
  
  if (plaintext === '' && v.ciphertext.length) { return; }
  if (key.length == 0 && password.length == 0) {
    error("need a password or key!");
    return;
  }
  
  p = { adata:v.adata,
        iter:v.iter,
        mode:v.mode,
        ts:parseInt(v.tag),
        ks:parseInt(v.keysize) };
  if (!v.freshiv || !usedIvs[v.iv]) { p.iv = v.iv; }
  if (!v.freshsalt || !usedSalts[v.salt]) { p.salt = v.salt; }
  ct = sjcl.encrypt(password || key, plaintext, p, rp).replace(/,/g,",\n");

  v.iv = rp.iv;
  usedIvs[rp.iv] = 1;
  if (rp.salt) {
    v.salt = rp.salt;
    usedSalts[rp.salt] = 1;
  }
  v.key = rp.key;
  
  if (v.json) {
    v.ciphertext = ct;
    v.adata = '';
  } else {
    v.ciphertext = ct.match(/"ct":"([^"]*)"/)[1]; //"
  }
  
  v.plaintext = '';
  
  form.set(v);
  form.ciphertext.el.select();
}

/* Decrypt a message */
function doDecrypt() {
  var v = form.get(), iv = v.iv, key = v.key, adata = v.adata, aes, ciphertext=v.ciphertext, rp = {};
  
  if (ciphertext.length === 0) { return; }
  if (!v.password && !v.key.length) {
    error("Can't decrypt: need a password or key!"); return;
  }
  
  if (ciphertext.match("{")) {
    /* it's jsonized */
    try {
      v.plaintext = sjcl.decrypt(v.password || v.key, ciphertext, {}, rp);
    } catch(e) {
      error("Can't decrypt: "+e);
      return;
    }
    v.mode = rp.mode;
    v.iv = rp.iv;
    v.adata = rp.adata;
    if (v.password) {
      v.salt = rp.salt;
      v.iter = rp.iter;
      v.keysize = rp.ks;
      v.tag = rp.ts;
    }
    v.key = rp.key;
    v.ciphertext = "";
    document.getElementById('plaintext').select();
  } else {
    /* it's raw */
    ciphertext = sjcl.codec.base64.toBits(ciphertext);
    if (iv.length === 0) {
      error("Can't decrypt: need an IV!"); return;
    }
    if (key.length === 0) {
      if (v.password.length) {
        doPbkdf2(true);
        key = v.key;
      }
    }
    aes = new sjcl.cipher.aes(key);
    
    try {
      v.plaintext = sjcl.codec.utf8String.fromBits(sjcl.mode[v.mode].decrypt(aes, ciphertext, iv, v.adata, v.tag));
      v.ciphertext = "";
      document.getElementById('plaintext').select();
    } catch (e) {
      error("Can't decrypt: " + e);
    }
  }
  form.set(v);
}

function extendKey(size) {
  form.key.set(form._extendedKey.slice(0,size));
}

function randomize(field, words, paranoia) {
  form[field].set(sjcl.random.randomWords(words, paranoia));
  if (field == 'salt') { form.key.set([]); }
}
