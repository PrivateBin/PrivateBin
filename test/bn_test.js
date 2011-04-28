new sjcl.test.TestCase("Bignum modular reduction test", function (cb) {
  if (!sjcl.bn) {
    this.unimplemented();
    cb && cb();
    return;
  }

  var a, N, r;
  for (i=0; i < sjcl.test.vector.bn_mod.length; i++) {
    tv = sjcl.test.vector.bn_mod[i];
    try {
      a = new sjcl.bn(tv.a);
      N = new sjcl.bn(tv.N);
      r = a.mod(N);
      this.require(r.equals(new sjcl.bn(tv.r)));
    } catch(e) {
      this.fail(e);
    }
  }
  cb && cb();
});

new sjcl.test.TestCase("Bignum modular multiplication test", function (cb) {
  if (!sjcl.bn) {
    this.unimplemented();
    cb && cb();
    return;
  }

  var a, b, N, r;
  for(var j=0;j<10;j++)for (i=0; i < sjcl.test.vector.bn_mulmod.length; i++) {
      tv = sjcl.test.vector.bn_mulmod[i];
    try {
      a = new sjcl.bn(tv.a);
      b = new sjcl.bn(tv.b);
      N = new sjcl.bn(tv.N);
      r = a.mulmod(b, N);
      this.require(r.equals(new sjcl.bn(tv.r)));
    } catch(e) {
      this.fail(e);
    }
  }
  cb && cb();
});

new sjcl.test.TestCase("Bignum modular exponentiation test", function (cb) {
  if (!sjcl.bn) {
    this.unimplemented();
    cb && cb();
    return;
  }

  var i, tv, g, x, N, v;
  for (i=0; i < sjcl.test.vector.bn_powermod.length; i++) {
    tv = sjcl.test.vector.bn_powermod[i];
    try {
      g = new sjcl.bn(tv.g);
      x = new sjcl.bn(tv.x);
      N = new sjcl.bn(tv.N);
      v = g.powermod(x, N);
      this.require(v.equals(new sjcl.bn(tv.v)));
    } catch(e) {
      this.fail(e);
    }
  }
  cb && cb();
});
