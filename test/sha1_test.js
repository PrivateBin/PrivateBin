new sjcl.test.TestCase("SHA-1 from sha1sum", function (cb) {
  if (!sjcl.hash.sha1) {
    this.unimplemented();
    cb && cb();
    return;
  }
  
  var i, kat=sjcl.test.vector.sha1, p=0, f=0;
  for (i=0; i<kat.length; i++) {
    var out = sjcl.hash.sha1.hash(kat[i][0]);
    this.require(sjcl.codec.hex.fromBits(out) == kat[i][1], i);
  }
  cb && cb();
});
