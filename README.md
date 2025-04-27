# Scrybble

This is the back-end and front-end for [Scrybble](https://scrybble.ink). It's relatively easy to set-up locally, but does require some technical knowledge.

## Development

### Pre-requisites

1. Nix or NixOS, [installer](https://determinate.systems/posts/determinate-nix-installer/)
2. Docker

That's it.

### Set-up local dev environment

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

### RMapi

We use https://github.com/ddvk/rmapi as a direct dependency for this project. This is used to authenticate and sync
with the reMarkable API.

You can build the binary with

```bash
$ nix build .#rmapi # this builds to ./result/bin/rmapi
# install it to the application
$ rm binaries/rmapi; mv ./result/bin/rmapi binaries/rmapi
```

### Remarks

Remarks is the library we use to stitch together pdfs and .rm files. It's what turns a downloaded zip from the RMapi
into an annotated pdf, or a bunch of markdown.

I host a dockerfile at hub.docker.com, tagged `laauurraaa/remarks-bin:latest`.

`docker run -v "$PWD/YOUR FOLDER WITH REMARKABLE STUFF/":/store laauurraaa/remarks-bin:0.2.1 /store/in/YOUR_NOTEBOOK /store/out`


## Self-hosting

Because Scrybble is entirely open-source, you are free to self-host Scrybble if you prefer.

In short, you'll have to follow these steps:

1. Pick a computer to deploy Scrybble to
2. Install the software prerequisites
3. Configure Scrybble
4. Configure the Scrybble plugin for Obsidian

### Picking a host

Scrybble is a server. You will need to pick a computer that will host the server.

There are two options here

1. Deploy Scrybble on your primary desktop or laptop
2. Deploy Scrybble on a server at home or on a hosted server

Either will work.

The most convenient is to host it on a server somewhere, so you can sync at any time. Alternatively, you can host it on a local computer.

There are two considerations here.

1. To sync from reMarkable to Obsidian, the Scrybble server needs to be running. So if you want to host it on your own computer, you need to make sure that it is turned on when you want to use the Obsidian plugin to sync
2. The Obsidian plugin needs to be able to communicate with the Scrybble server. If you choose to host Scrybble on your own computer, you need to make additional configurations to your network if you want to access your Scrybble server from outside your own home (ie, at work or when travelling)

So the first thing you need to do is make a choice, do you host locally and deal with the tradeoff that Scrybble sync might not always be available?

Or do you find a hosting provider to host Scrybble on?

Of course, you could always choose to host it on a homelab like a raspberry pi.

### Software prerequisites

Scrybble is deployed using Docker Compose. So you will need to install Docker on your target computer.

We strongly recommend using Linux or MacOS, because it works best with Docker, but you can use Docker on Windows with WSL too if you so desire.
Note that Docker will always run better on Linux-native computers.

- [Install docker](https://docs.docker.com/engine/install/)

### Configuring Scrybble

Once you have docker installed, you can go ahead and start configuring.

In this step we'll:

1. Download the docker compose configuration for Scrybble
2. Write a `.env` file
3. Make any modifications to the docker-compose file, if preferred
4. Start the server
5. Run the set-up

#### Download the docker-compose file

Find the `docker-compose.selfhosted.yml` file in this repository and download it to a location of choice.

#### Configuring the server with the environment file

The `.env` file is your configuration file for the server.
The basic configuration will work reasonably well for a local set-up, but you might want to tweak things.

To get started with the `.env` file, copy the `.env.example` file and name the new file `.env`.

For security, you are recommended to choose a username, password and root password for your database.

#### Make modifications to the docker-compose file if you wish

The docker-compose file is configured to be secure by default, so the database and redis ports aren't opened.

If you want to host on a server with your own domain name, the docker-compose file includes commented out code for a reverse-proxy that provides SSL for you automatically.

#### Start the server

Next, the `docker-compose.selfhosted.yml` file describes your server's configuration. This is where you can open, close or change ports for the internal database, redis and web app.

Last, to start your Scrybble server, run `docker compose -f docker-compose.selfhosted.yml up -d`

#### Set-up

We're almost done configuring the server!

The last thing you need to do is run the set-up on the server itself.

When you create the admin account, the username and password matter.
They are used to connect the Obsidian plugin with your server.

Make sure to pick a good username and password!

Run `docker compose exec app php artisan app:setup-admin-account`

You're done! You can now visit http://localhost to see your Scrybble set-up, and you can log in to your admin account.

### Configuring your Obsidian plugin

In Obsidian, go to the plugin settings and enable the "Self hosted" toggle.

In here, you need to configure your hostname. If you have it on a server, fill in the server's ip address or the domain name if you set it up using a domain name. Otherwise fill in "http://localhost"

If you are using a different port that port 80, you can configure that in the same Endpoint field.

For the client secret, you need to visit "http://{YOUR DOMAIN}/client-secret".

That's it! You should be able to use your own self-hosted Scrybble now :)
