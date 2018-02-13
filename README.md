# jurassic.ninja
A frontend to launching ephemeral WordPress instances that auto-destroy after some time

## Usage

### Requirements

* An ubuntu box managed by ServerPilot
* A ServerPilot plan. One of the paid plans. Coach plan is OK. This is to take advantage of multiple sysusers management
* `sshpass` installed on the box.
* `wp cli` and `composer` (which is already installed by ServerPilot).
* **Have a domain name you fully control**.
	* Add wildcard A record for every subdomain under that domain, pointing to the box's IP addresss.

### Installation

**Warning**: On plugin activation, this plugin will create two tables on the same DB your WordPress is running.

Basically the steps are

1. Install the plugin and activate it.
2. Create the needed pages (home and `/create`).
3. 1. Configure ServerPilot credentials.

#### Install the Plugin


Install it

```sh
wp plugin install https://github.com/oskosk/jurassic.ninja/archive/master.zip
```

Install composer dependencies
```sh
cd wp-content/plugins/jurassic.ninja
composer install
```

**Activate the plugin**
```
wp plugin activate jurassic.ninja
```
#### Create needed pages

All of the frontend magic is done by a little piece of Javascript that detects if current page is
on the `/create` route and if it's the case it just launches a request in the background
to the plugin's REST API in order to launch a new site.

#### Create a /create page_title

Create a page titled **Create**. Make sure its slug is `/create`.

And add this using the Text version of the editor:

```
<img id="img1" src="https://media.giphy.com/media/uIRyMKFfmoHyo/giphy.gif" style="display:none" />
<img id="img2" src="https://i1.wp.com/media.giphy.com/media/KF3r4Q6YCtfOM/giphy.gif?ssl=1" style="display:none" />
<p class="lead" id="progress">Launching a fresh WP with a Jetpack ...</p>
```

#### Create a home page with a link to `/create`.

1. Create a new page and configure it a static front page with a link to `/create`.

#### Configure Jurassic Ninja Settings page in wp-admin

1. Visit the Jurassic Ninja Settings page in wp-admin.
2. Configure your Server pilot client id, client key and server id.
3. Configure the top-domain on which this is going to create sites.
