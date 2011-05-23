new sjcl.test.TestCase("CBC mode tests", function (cb) {
  ((sjcl.beware &&
  sjcl.beware["CBC mode is dangerous because it doesn't protect message integrity."]) ||
  function(){})();

  if (!sjcl.cipher.aes || !sjcl.mode.cbc) {
    this.unimplemented();
    cb && cb();
    return;
  }

  var i, kat = sjcl.test.vector.cbc, tv, iv, ct, aes, len, thiz=this, w=sjcl.bitArray, pt, h=sjcl.codec.hex;
  browserUtil.cpsIterate(function (j, cbb) {
    for (i=100*j; i<kat.length && i<100*(j+1); i++) {
      tv = kat[i];
      len = 32 * tv.key.length;
      aes = new sjcl.cipher.aes(h.toBits(tv.key));

      // Convert from strings
      iv = h.toBits(tv.iv);
      pt = h.toBits(tv.pt);
      ct = h.toBits(tv.ct);

      thiz.require(w.equal(sjcl.mode.cbc.encrypt(aes, pt, iv), ct), "aes-"+len+"-cbc-encrypt #"+i);
      try {
        thiz.require(w.equal(sjcl.mode.cbc.decrypt(aes, ct, iv), pt), "aes-"+len+"-cbc-decrypt #"+i);
      } catch (e) {
        thiz.fail("aes-cbc-decrypt #"+i+" (exn "+e+")");
      }
    }
    cbb();
  }, 0, kat.length / 100, true, cb);
});
