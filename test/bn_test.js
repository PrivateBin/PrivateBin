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
