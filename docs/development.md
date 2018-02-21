

### Adding Features

Basically you can go an peek the `lib/features` directory


The regular life cycle of a Jurassic Ninja site


```
Create page -> REST API endpoint /create -> launch_wordpress -> Site purge
```

The `launch_wordpress` step can be subdivided like:

```
1. Identify available features and their defaults (`jurassic_ninja_features` filter).
2. Get requested features and merge with default_features.
3. Decide if we need to do something regarding the requested Features (`jurassic_ninja_do_feature_conditions` action).
4. Generate a random subdomain.
5. Create a user( `jurassic_ninja_create_sysuser` action).
6. Launch a PHP app and install WordPress (`jurassic_ninja_create_app` action).
7. Add features that need to be added before enabling the autologin mechanism (`jurassic_ninja_add_features_before_auto_login` action).
8. Enable autologin.
9. Add features that need to be added after enabling the autologin mechanism (`jurassic_ninja_add_features_before_auto_login` action).
```
