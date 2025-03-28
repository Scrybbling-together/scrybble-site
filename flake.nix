{
  description = "Scrybble development environment";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-24.11";
    flake-utils.url = "github:numtide/flake-utils";
  };

  outputs = { self, nixpkgs, flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};

        php = (import ./nix/php.nix { inherit pkgs; });
        rmapi = (import ./nix/rmapi.nix { inherit pkgs; });

        setup-script = pkgs.writeScriptBin "setup-scrybble" ''
                    #!${pkgs.bash}/bin/bash
                    if [[ ! -d ./vendor ]]; then
                      ${pkgs.gum}/bin/gum format <<EOF
          # This is a new development environment

          You should read the README.md for more information on running Scrybble locally.

          We'll set up your development environment for you.
          EOF
                      if ${pkgs.gum}/bin/gum confirm; then
                        ${pkgs.gum}/bin/gum spin --title "Installing composer packages" -- ${php.scrybble-php.packages.composer}/bin/composer install
                        cp .env.example .env

                        ${pkgs.gum}/bin/gum spin --show-error --title "Migrating db" -- ./vendor/bin/sail artisan migrate
                        ${pkgs.gum}/bin/gum spin --show-error --title "Installing frontend packages" -- bun install
                        ${pkgs.gum}/bin/gum spin --show-error --title "Starting docker containers" -- sail up -d
                      else
                        exit 1
                      fi
                    else
                      ${php.scrybble-php.packages.composer}/bin/composer outdated -DM
                    fi
        '';
      in {
        devShells.default = pkgs.mkShell {
          buildInputs = [
            # TODO: Deprecated in favor of bun
            pkgs.nodejs_22
            pkgs.bun
            php.scrybble-php
            php.scrybble-php.packages.composer
            setup-script
          ];

          packages = with pkgs; [ gum ];

          WWWGROUP = 1000;
          WWWUSER = 1000;

          shellHook = ''
                        setup-scrybble
                        alias sail="./vendor/bin/sail "
                        ${pkgs.gum}/bin/gum format <<EOF
            # Welcome to the Scrybble development environment :)

            - Use [sail](https://laravel.com/docs/11.x/sail) for interacting with _Laravel_, _docker_ and _composer_
            - Use [bun](https://bun.sh/) for the frontend

            ## Running

            - \`sail up -d\` to start the backend
            - \`bun run dev\` to start the frontend

            EOF
          '';
        };

        packages.rmapi = rmapi;
        packages.php83-build-image = php.php-docker-image;
      });
}
