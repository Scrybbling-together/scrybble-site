{ pkgs ? import <nixpkgs> {} }:

let
  rev = "aa60dac8a8dbb1b4eb6a25f2caf2f3daea573373";

  src = pkgs.fetchgit {
    url = "https://github.com/ddvk/rmapi";
    inherit rev;
    deepClone = true;
    leaveDotGit = true;
    hash = "sha256-8LlFTxm/RO5bgzpUItBafHwocIkslXFyq3JjSHxuSNc=";
  };

  rmapi = pkgs.buildGoModule rec {
    pname = "rmapi";
    # Placeholder: Only used for the derivation name, any stable string works.
    version = "unstable";

    inherit src;

    vendorHash = "sha256-Qisfw+lCFZns13jRe9NskCaCKVj5bV1CV8WPpGBhKFc=";

    env = { CGO_ENABLED = 0; };

    # Needed for `git describe` in postPatch
    nativeBuildInputs = [ pkgs.git ];

    postPatch = ''
      # sets version for `rmapi version`
      VER="$(git describe --tags 2>/dev/null || echo unknown)"
      printf 'package version\n\nvar Version = "%s"\n' "$VER" > version/version.go
      echo "rmapi version: $VER"
    '';

    # Create a static binary
    ldflags = [
      "-w" # Strip DWARF symbol table
      "-extldflags '-static'"
    ];

    postInstall = ''
      # Create a directory with just the binary for easy extraction
      mkdir -p $out/portable
      cp $out/bin/rmapi* $out/portable/
    '';
  };
in
pkgs.stdenv.mkDerivation {
  name = "rmapi-portable";

  buildInputs = [];

  # Just copy the portable binary to the output
  buildCommand = ''
    mkdir -p $out/bin
    cp -r ${rmapi}/portable/* $out/bin/
    chmod +x $out/bin/rmapi*
  '';
}
