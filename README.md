# jurassic.ninja

A frontend to launching ephemeral WordPress instances that auto-destroy after some time.

## Usage

### Requirements

* An [ubuntu box managed by ServerPilot](https://serverpilot.io/community/articles/connect-a-digitalocean-server.html).
* A [ServerPilot plan](https://serverpilot.io/community/articles/how-to-upgrade-your-account.html). One of the paid plans. Coach plan is OK. This is to take advantage of multiple sysusers management.
* [sshpass](https://linux.die.net/man/1/sshpass) installed on the box. (Just `apt-get install sshpass` after you have the Ubuntu box set up).
* **Have a domain name you fully control**.
    * Add a wildcard A record for every subdomain under that domain, pointing to the box's IP addresss.

### Installation

This software is implemented as a WordPress plugin that can be added on top of WordPress and configured via wp-admin.

On plugin activation, this plugin will create two tables on the same database your WordPress is running. Named `sites` and `purged`.


1. Install the plugin and activate it.
1. Create the needed pages (home and `/create`).
1. Configure ServerPilot credentials.

#### 1. Install the Plugin

* Get the bundle from GitHub.

    ```sh
    wp plugin install https://github.com/Automattic/jurassic.ninja/archive/master.zip
    ```

* Install composer dependencies

    ```sh
    cd wp-content/plugins/jurassic.ninja
    composer install
    ```

* **Activate the plugin**

    ```sh
    wp plugin activate jurassic.ninja
    ```

#### 2. Create pages for launching the sites.

All of the frontend site launching is done by a little piece of Javascript that detects if current page is on the `/create` path and if it's the case it just launches a request in the background to this plugin's REST API in order to launch a new site.

**Create a page with the slug `create`**

Create a page titled **Create**. Make sure its slug is `/create`.

And add this content using the Text version of the editor:

```html
<img id="img1" class="aligncenter" style="display: none;" src="https://i.imgur.com/mKwWJQZ.gif" />
<img id="img2" class="aligncenter" style="display: none;" src="https://i.imgur.com/wWkoZGw.gif" />
<p id="progress" class="lead" style="text-align: center;" data-success-message="The new WP is ready to go, visit it!" data-error-message="Oh No! There was a problem launching the new WP.">Launching a fresh WP with a Jetpack ...</p>
```

**Create a home page with a link to `/create`**

1. Create a new page and configure it a static front page with a link to `/create`.

#### 3. Configure Jurassic Ninja Settings page in wp-admin

1. Visit the Jurassic Ninja Settings page in wp-admin.
2. Configure your Server pilot client id, client key and server id.
3. Configure the top-domain on which this is going to create sites.

### Using a docker container for testing

It may be convenient to use this plugin using a docker container for local development.

Make sure that [docker is installed and running](https://docs.docker.com/install/) before proceeding.

1. Create `jndb` folder in the root of this source code, where WordPress DB will be stored:

```sh
mkdir ./jndb
```

1. create and run docker containers:

```sh
docker-compose up
```

1. Navigate to <http://localhost> to create WordPress site, Activate Jurassic Ninja plugin. Other plugins are optional

1. Setup Jurassic Ninja plugin as menitioned above

