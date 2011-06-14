new sjcl.test.TestCase("OCB 2.0 mode tests", function (cb) {
  if (!sjcl.cipher.aes || !sjcl.mode.ocb2) {
    this.unimplemented();
    cb && cb();
    return;
  }
  
  var i, kat = sjcl.test.vector.ocb2, tv, iv, ct, aes, len, tlen, thiz=this, w=sjcl.bitArray, pt, iv, h = sjcl.codec.hex, ad, tlen;
  browserUtil.cpsIterate(function (j, cbb) {
    for (i=100*j; i<kat.length && i<100*(j+1); i++) {
      tv = kat[i];
      len = 32 * tv.key.length;
      aes = new sjcl.cipher.aes(h.toBits(tv.key));
    
      // sort of a hack because of the format of the vectors file
      pt = h.toBits(tv.pt);
      ct = h.toBits(tv.ct+tv.tag);
      iv = h.toBits(tv.iv);
      ad = h.toBits(tv.adata);
      tlen = tv.tag.length * 4;
    
      thiz.require(w.equal(sjcl.mode.ocb2.encrypt(aes, pt, iv, ad, tlen), ct), "aes-"+len+"-ocb2-encrypt #"+i);
      try {
        thiz.require(w.equal(sjcl.mode.ocb2.decrypt(aes, ct, iv, ad, tlen), pt), "aes-"+len+"-ocb2-decrypt #"+i);
      } catch (e) {
        thiz.fail("aes-ocb-decrypt #"+i+" (exn " + e + ")");
      }
    }
    cbb();
  }, 0, kat.length / 100, true, cb);
});
