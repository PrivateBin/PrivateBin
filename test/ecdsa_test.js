new sjcl.test.TestCase("ECSA test", function (cb) {
  if (!sjcl.ecc) {
    this.unimplemented();
    cb && cb();
    return;
  }
  
  var keys = sjcl.ecc.ecdsa.generateKeys(192,0),
      hash = sjcl.hash.sha256.hash("The quick brown fox jumps over the lazy dog."),
      signature = keys.sec.sign(hash,0);
      
  try {
    keys.pub.verify(hash, signature);
    this.pass();
  } catch (e) {
    this.fail("good message rejected");
  }
  
  hash[1] ^= 8; // minor change to hash
  
  try {
    keys.pub.verify(hash, signature);
    this.fail();
  } catch (e) {
    this.pass("bad message accepted");
  }
  
  cb && cb();
});
