/* Official HMAC test vectors. */
sjcl.test.vector.hmac = [
{ key:  "0b0b0b0b0b0b0b0b0b0b0b0b0b0b0b0b0b0b0b0b",
  data: "4869205468657265",
  mac:  "b0344c61d8db38535ca8afceaf0bf12b881dc200c9833da726e9376c2e32cff7"
},
{ key:  "4a656665",
  data: "7768617420646f2079612077616e7420666f72206e6f7468696e673f",
  mac:  "5bdcc146bf60754e6a042426089575c75a003f089d2739839dec58b964ec3843"
},
{ key:  "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",
  data: "dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd",
  mac:  "773ea91e36800e46854db8ebd09181a72959098b3ef8c122d9635514ced565fe"
},
{ key:  "0102030405060708090a0b0c0d0e0f10111213141516171819",
  data: "cdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcd",
  mac:  "82558a389a443c0ea4cc819899f2083a85f0faa3e578f8077a2e3ff46729665b"
},
{ key:  "0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c",
  data: "546573742057697468205472756e636174696f6e",
  mac:  "a3b6167473100ee06e0c796c2955552b"
},
{ key:  "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",
  data: "5468697320697320612074657374207573696e672061206c6172676572207468616e20626c6f636b2d73697a65206b657920616e642061206c6172676572207468616e20626c6f636b2d73697a6520646174612e20546865206b6579206e6565647320746f20626520686173686564206265666f7265206265696e6720757365642062792074686520484d414320616c676f726974686d2e",
  mac:  "9b09ffa71b942fcb27635fbcd5b0e944bfdc63644f0713938a7f51535c3a35e2"
}
]
