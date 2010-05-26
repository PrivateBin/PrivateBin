new sjcl.test.TestCase("HMAC official test vectors", function (cb) {
  if (!sjcl.misc.hmac || !sjcl.hash.sha256) {
    this.unimplemented();
    cb && cb();
    return;
  }
  
  var i, kat = sjcl.test.vector.hmac, tv, h=sjcl.codec.hex, out;
  for (i=0; i<kat.length; i++) {
    tv = kat[i];
    out = h.fromBits((new sjcl.misc.hmac(h.toBits(tv.key))).mac(h.toBits(tv.data)));
    this.require (out.substr(0,tv.mac.length) == tv.mac, "hmac #"+i);
  }
  cb && cb();
});
