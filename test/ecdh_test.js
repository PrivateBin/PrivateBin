new sjcl.test.TestCase("ECDH test", function (cb) {
  if (!sjcl.ecc) {
    this.unimplemented();
    cb && cb();
    return;
  }
  
  var keys = sjcl.ecc.elGamal.generateKeys(192,0),
      keyTag = keys.pub.kem(0),
      key2 = keys.sec.unkem(keyTag.tag);
      
  this.require(sjcl.bitArray.equal(keyTag.key, key2));
  
  cb && cb();
});
