# jurassic.ninja
A frontend to launching ephemeral WordPress instances that auto-destroy after some time

## Usage

Install the Plugin

```sh
wp plugin install https://github.com/oskosk/jurassic.ninja/archive/master.zip
cd wp-content/plugins/jurassic.ninja-master
composer install
wp plugin activate jurassic.ninja
```

### Create a /create page_title

Create a page titled Created, make sure its slug is `/created`.

Add this using the Text version of the editor:

```
<img id="img1" src="https://media.giphy.com/media/uIRyMKFfmoHyo/giphy.gif" style="display:none" />
<img id="img2" src="https://i1.wp.com/media.giphy.com/media/KF3r4Q6YCtfOM/giphy.gif?ssl=1" style="display:none" />
<p class="lead" id="progress">Launching a fresh WP with a Jetpack ...</p>
```

### Configure Jurassic Ninja Settings page in wp-admin

1. Visit the Jurassic Ninja Settings page in wp-admin.
2. Configure your Server pilot client id, client key and server id.
3.