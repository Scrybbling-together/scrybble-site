{ pkgs ? import <nixpkgs> {} }:

let
  rmapi = pkgs.buildGoModule rec {
    pname = "rmapi";
    version = "unstable-${builtins.substring 0 8 src.rev}";

    src = pkgs.fetchFromGitHub {
      owner = "scrybbling-together";
      repo = "rmapi";
      rev = "797a932c22d569d20d65cc1019099d18bd071fb4";
      sha256 = "sha256-uOJdyLq0KqkerIaMyP2raP/glVn4cWgzim38I+jJYVY=";
    };

    vendorHash = "sha256-Qisfw+lCFZns13jRe9NskCaCKVj5bV1CV8WPpGBhKFc=";

    CGO_ENABLED = 0;

    GOOS = "linux";
    GOARCH = "amd64";

    # Create a static binary
    ldflags = [
      "-w" # Strip DWARF symbol table
      "-extldflags '-static'"
    ];

    postInstall = ''
      # Rename the binary if building for Windows
      ${if (GOOS == "windows") then "mv $out/bin/rmapi $out/bin/rmapi.exe" else ""}

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
