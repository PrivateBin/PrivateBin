# Release

## Properties

### Supply-chain security

Since [v1.6.2](https://github.com/PrivateBin/PrivateBin/releases/tag/1.6.2) our release assets are [verified with the SLSA framework](https://slsa.dev/).
SLSA stands for „Supply-chain Levels for Software Artifacts”.

This achieves the following:
1. It ensures no maintainer has gone rogue and has modified/tampered with the source code before “building” the release.
2. It ensures the release is build exactly according to the source as defined by the branch that was used for the release.
   This includes the workflow file defining how the release is done itself.
3. Our release should achieve [SLSA build level 3](https://slsa.dev/spec/v1.0/levels#build-l3) as it [runs on GitHub](https://slsa.dev/spec/v1.0/threats). Some more properties [are thus achieved](https://slsa.dev/spec/v1.0/threats). 

That said, here are some caveasts:
* None of our dependencies has any SLSA verification yet (as of Dec 2023) and thus we (cannot) verify any of our own supply chain.
  That said, this is mitigated by the fact that the project has not that many dependencies at all.

For more information [see the corresponding issue](https://github.com/PrivateBin/PrivateBin/issues/1169) and [the GitHub workflow file](/.github/workflows/release.yml).

#### How to verify

Git signature for verifying the reproducibility and that a maintainer in posession with that PGP private key created the release with that content:
```
$ gpg2 --verify PrivateBin-1.2.1.tar.gz.asc # TODO: update for v1.6.2
gpg: assuming signed data in 'PrivateBin-1.2.1.tar.gz'
gpg: Signature made Sat Aug 11 22:38:29 2018 CEST
gpg:                using RSA key 0xE11B7950E9E183DB
gpg: Good signature from "PrivateBin release (solely used for signing releases)" [<trust level here>]
gpg:                 aka "PrivateBin code-sign (solely used for code signing) <git@privatebin.net>" [<trust level here>]
Primary key fingerprint: 28CA 7C96 4938 EA5C 1481  D42A E11B 7950 E9E1 83DB
```

For a more step-by-step guide in detail [see this FAQ](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#how-can-i-securely-clonedownload-your-project).

## Reproducible builds

Since a long time, we signed release `.tar.gz` and `.zip` files with personal PGP keys confirming the authenticity of the fact that the release has been build by a PrivateBin maintainer.

This uses traditional [PGP](https://en.wikipedia.org/wiki/Pretty_Good_Privacy) signatures.

### How to verify

Git signature for verifying the reproducibility and that a maintainer in posession with that PGP private key created the release with that content:
```
$ gpg2 --verify PrivateBin-1.2.1.tar.gz.asc # TODO: update for v1.6.2
gpg: assuming signed data in 'PrivateBin-1.2.1.tar.gz'
gpg: Signature made Sat Aug 11 22:38:29 2018 CEST
gpg:                using RSA key 0xE11B7950E9E183DB
gpg: Good signature from "PrivateBin release (solely used for signing releases)" [<trust level here>]
gpg:                 aka "PrivateBin code-sign (solely used for code signing) <git@privatebin.net>" [<trust level here>]
Primary key fingerprint: 28CA 7C96 4938 EA5C 1481  D42A E11B 7950 E9E1 83DB
```

For a more step-by-step guide in detail [see this FAQ](https://github.com/PrivateBin/PrivateBin/wiki/FAQ#how-can-i-securely-clonedownload-your-project).

## How to release

1. Create a release branch (schema: `x.y.z`) and push that as a new branch to GitHub.
2. This will automatically trigger the worflow [/.github/workflows/release.yml](/.github/workflows/release.yml) on GitHub that will generate SLSA provenance _and_ draft a release with most artifacts attached at the end.
3. Now you will need to generate a release `.zip` and `.tar.gz` locally too. Just use `git archive` command for that (TODO: please add specifics).
   **Important:** Please don't just download them from GitHub, this bringes no security benefit as that would basically mean trusting GitHub and letting GitHub “build” the release. 
4. Sign that release with GPG with your private key:
   ```
   git sign # TODO
   ```
5. Upload the `.tar.gz.sig` and `.zip.sig` on GitHub/attach them to the release.
   **Note** It may also be a good idea to follow the above guide on how to verify the reproducible builds by verifying the hashes of the two release archives.
     Alternatively, you can also compare the hashes of the release file from GitHub and your local one to verify the are the same.
