{ pkgs ? import <nixpkgs> {} }:

let
  rmapi = pkgs.buildGoModule rec {
    pname = "rmapi";
    version = "unstable-${builtins.substring 0 8 src.rev}";

    src = pkgs.fetchFromGitHub {
      owner = "ddvk";
      repo = "rmapi";
      rev = "aa60dac8a8dbb1b4eb6a25f2caf2f3daea573373";
      sha256 = "sha256-AP6ba2lUZAAowtmX52zC97HI8U9nvbd8HyUqMqdxA2o=";
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
