new sjcl.test.TestCase("SRP known-answer (RFC 5054) tests", function (cb) {
  if (!sjcl.keyexchange.srp) {
    this.unimplemented();
    cb && cb();
    return;
  }

  var i, kat = sjcl.test.vector.srp, tv, group, v, x;

  for (i=0; i<kat.length; i++) {
    tv = kat[i];
    group = sjcl.keyexchange.srp.knownGroup(tv.known_group_size);
    tv.s = sjcl.codec.hex.toBits(tv.s);
    x = sjcl.keyexchange.srp.makeX(tv.I, tv.P, tv.s);
    this.require(sjcl.codec.hex.fromBits(x).toUpperCase() === tv.x, "srpx #"+i);

    v = sjcl.keyexchange.srp.makeVerifier(tv.I, tv.P, tv.s, group);
    this.require(v.equals(new sjcl.bn(tv.v)), "srpv #"+i);
  }
  cb && cb();
});
