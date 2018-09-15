

##The regular life cycle of a Jurassic Ninja site

```
* User gets to the Create page
* The JS on the create page fires a request to REST API endpoint: `/wp-json/jurassicninja/create`.
* The PHP function `launch_wordpress()` gets called and the new site is launched with all the features.
* The REST request finishes with an URL for the created site.
* That URL is shown to the user.
* The user visits the site, gets auto-logged in, acquiring a WordPress cookie.
* The site is purged 7 days after that first auto login or 7 days after the user entered credentials the last time. This can happen if the user signed out and signed in again.
```

## The `launch_wordpress()` function can be explained as:

```
2. Collects requested features and merges with defaults.
3. Decide if we need to do something regarding the requested Features (`jurassic_ninja_do_feature_conditions` action).
4. Generates a random subdomain.
5. Creates a user. Hookable via the  `jurassic_ninja_create_sysuser` action.
6. Launches a new environment for a PHP/MysQL app and installs WordPress. Hookable via the  `jurassic_ninja_create_app` action.
7. Add features that need to be added before enabling the autologin mechanism (`jurassic_ninja_add_features_before_auto_login` action).
8. Enable autologin.
9. Add features that need to be added after enabling the autologin mechanism (`jurassic_ninja_add_features_before_auto_login` action).
```


## Adding Features

Basically you can go an peek the `features` directory which contains a few files that make use of the available actions and filters.

But the main hooks to look are:

* jurassic_ninja_init (Action).
* jurassic_ninja_admin_init (Action).
* jurassic_ninja_settings_options_page (Filter).
* jurassic_ninja_settings_options_page_default_plugins (Filter).
* jurassic_ninja_rest_feature_defaults (Filter).
* jurassic_ninja_rest_create_request_features (Filter ).
* jurassic_ninja_do_feature_conditions (Action).
* jurassic_ninja_add_features_before_auto_login (Action).
* jurassic_ninja_add_features_after_auto_login (Action).
* jurassic_ninja_feature_command (Filter).
* jurassic_ninja_created_site_url (Filter).
