new sjcl.test.TestCase("SHA-256 from catameringue", function (cb) {
  if (!sjcl.hash.sha256) {
    this.unimplemented();
    cb && cb();
    return;
  }
  
  var i, kat=sjcl.test.vector.sha256, p=0, f=0;
  for (i=0; i<kat.length; i++) {
    var out = sjcl.hash.sha256.hash(kat[i][0]);
    this.require(sjcl.codec.hex.fromBits(out) == kat[i][1], i);
  }
  cb && cb();
});
