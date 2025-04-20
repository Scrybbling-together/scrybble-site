# Scrybble

This is the back-end and front-end for [Scrybble](https://scrybble.ink). It's relatively easy to set-up locally, but does require some technical knowledge.

## Pre-requisites

1. Nix or NixOS, [installer](https://determinate.systems/posts/determinate-nix-installer/)
2. Docker

That's it.

## Set-up local dev environment

Run the following command in your shell

```bash
$ nix develop
# If it is your first time installing
# just press "yes" to the prompt command
# and everything will be set-up for you.

# run the frontend dev server when installation is finished
$ bun run dev
```

After running the last step, the site will be accessible on your [localhost](http://localhost)

## RMapi

We use https://github.com/ddvk/rmapi as a direct dependency for this project. This is used to authenticate and sync
with the reMarkable API.

You can build the binary with

```bash
$ nix build .#rmapi # this builds to ./result/bin/rmapi
# install it to the application
$ rm binaries/rmapi; mv ./result/bin/rmapi binaries/rmapi
```

## Remarks

Remarks is the library we use to stitch together pdfs and .rm files. It's what turns a downloaded zip from the RMapi
into an annotated pdf, or a bunch of markdown.

I host a dockerfile at hub.docker.com, tagged `laauurraaa/remarks-bin:latest`.

`docker run -v "$PWD/YOUR FOLDER WITH REMARKABLE STUFF/":/store laauurraaa/remarks-bin:0.2.1 /store/in/YOUR_NOTEBOOK /store/out`
