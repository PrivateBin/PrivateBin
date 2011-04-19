new sjcl.test.TestCase("SRP known-answer (RFC 5054) tests", function (cb) {
  if (!sjcl.keyexchange.srp) {
    this.unimplemented();
    cb && cb();
    return;
  }

  var i, kat = sjcl.test.vector.srp, tv, N, g, v;

  for (i=0; i<kat.length; i++) {
    tv = kat[i];
    N = sjcl.keyexchange.srp.knownGroups[tv.known_group_size].N;
    g = sjcl.keyexchange.srp.knownGroups[tv.known_group_size].g;
    v = sjcl.keyexchange.srp.makeVerifier(tv.I, tv.P, tv.s, N, g)
      print("-> v = " + v);
    this.require(sjcl.bitArray.equal(v, tv.v), "srpv #"+i);
  }
  cb && cb();
});
