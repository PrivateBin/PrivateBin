# Release

## Overview of Supply-Chain Security

As of the PrivateBin 1.0 release we [cryptographically sign](https://git-scm.com/book/uz/v2/Git-Tools-Signing-Your-Work) our git commits and tags, so that you can verify we actually developed the software. Later, we also [started signing the release archives on GitHub](https://github.com/PrivateBin/PrivateBin/issues/219) and retroactively signed all releases from 1.0 forward.

Since [release 1.6.2](https://github.com/PrivateBin/PrivateBin/releases/tag/1.6.2) our release assets additionally also are [verified with the SLSA (Supply-chain Levels for Software Artifacts) framework](https://slsa.dev/), providing an in-toto manifest of the release archive.

This achieves the following:
1. It ensures no maintainer has gone rogue and has modified/tampered with the source code before “building” the release.
2. It ensures the release is build exactly according to the source as defined by the branch that was used for the release.
   This includes the workflow file defining how the release is done itself.
3. Our release should achieve [SLSA build level 3](https://slsa.dev/spec/v1.0/levels#build-l3) as it [runs on GitHub](https://slsa.dev/spec/v1.0/threats). Some more properties [are thus achieved](https://slsa.dev/spec/v1.0/threats). 

For more information [see the corresponding issue](https://github.com/PrivateBin/PrivateBin/issues/1169) and [the GitHub workflow file](/.github/workflows/release.yml).

## Reproducible builds

All releases `.tar.gz` and `.zip` archives since 1.0 come with corresponding `.asc` signatures that can be used to confirm the authenticity of the fact that the release has been issued by a PrivateBin maintainer.

This uses traditional [PGP](https://en.wikipedia.org/wiki/Pretty_Good_Privacy) signatures.

## Verification

You can use the gpg signatures for verifying the reproducibility and that a maintainer in posession with that PGP private key created the release with that content:

```
$ gpg2 --verify 1.6.2.tar.gz.asc
gpg: assuming signed data in '1.6.2.tar.gz'
gpg: Signature made Fri Dec 15 06:21:08 2023 UTC
gpg:                using RSA key 28CA7C964938EA5C1481D42AE11B7950E9E183DB
gpg: Good signature from "PrivateBin release (solely used for signing releases)" [unknown]
gpg: WARNING: This key is not certified with a trusted signature!
gpg:          There is no indication that the signature belongs to the owner.
Primary key fingerprint: 28CA 7C96 4938 EA5C 1481  D42A E11B 7950 E9E1 83DB
```

For a more step-by-step guide in detail [see this FAQ](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#how-can-i-securely-clonedownload-your-project).

SLSA verification can be performed using the [SLSA verifier](https://github.com/slsa-framework/slsa-verifier?tab=readme-ov-file#verification-for-github-builders).

## Release process

The release process is outlined in the [release checklist](https://github.com/PrivateBin/PrivateBin/wiki/Release-Checklist). The key manual steps are performed using a [Makefile](https://github.com/PrivateBin/PrivateBin/blob/master/Makefile#L31-L43) and using a [shell script](https://github.com/rugk/gittools/blob/master/signrelease.sh).
