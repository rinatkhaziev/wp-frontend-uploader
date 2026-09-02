# Frontend Uploader

⚠️ _This plugin is not actively maintained._ Any discovered security issues will be patched though. ⚠️

If you're interested in becoming a maintainer please let me know.

## Description

This plugin gives you an ability to easily accept, moderate and publish user generated content (currently, there are 3 modes: media, post, post + media). The plugin allows you to create a front end form with multiple fields (easily customizable with shortcodes). You can limit which MIME-types are supported for each field. All of the submissions are safely held for moderation in Media/Post/Custom Post Types menu under a special tab "Manage UGC". Review, moderate and publish. It's that easy!

## Installation

1. `git clone https://github.com/rinatkhaziev/wp-frontend-uploader.git` in your WP plugins directory
1. Activate the plugin
1. Set the settings
1. Enjoy

## Upgrade instructions

1. Pull as usual
2. ...
3. Profit

## Developers

Miss a feature? Pull requests are welcome.

## Local testing with wp-env

You can use [`wp-env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) to start a local WordPress site with this plugin mounted and activated.

Before starting, make sure Node.js and Docker with the Compose plugin are available. [Docker Desktop](https://www.docker.com/products/docker-desktop/) includes Docker Compose. Confirm that Docker is running and Compose can be found:

```sh
docker compose version
```

Then, from this repository, run:

```sh
npx --yes --package=@wordpress/env wp-env start
```

`wp-env` selects an available port and prints the local site URL. You can also display it with:

```sh
npx --yes --package=@wordpress/env wp-env status
```

Sign in with username `admin` and password `password`, then smoke-test the plugin:

1. Review the plugin options under **Settings > Frontend Uploader Settings**.
2. Create a page containing the `[fu-upload-form]` shortcode.
3. View the page and submit an allowed image.
4. Review the submission under **Media > Manage UGC**.

Stop the environment without deleting its data:

```sh
npx --yes --package=@wordpress/env wp-env stop
```

To remove the environment and its local WordPress data completely, run `npx --yes --package=@wordpress/env wp-env destroy`.
