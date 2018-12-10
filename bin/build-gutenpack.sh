#!/bin/sh

CALYPSO_BRANCH="${1:-master}"
JETPACK_DIRNAME="${2:-jetpack}"
curl -o- https://raw.githubusercontent.com/creationix/nvm/v0.33.11/install.sh | bash

export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"  # This loads nvm bash_completion
nvm install 10

echo "Getting wp-calypso branch $CALYPSO_BRANCH"
cd $HOME \
&& git clone https://github.com/automattic/wp-calypso --depth=1 -b "$CALYPSO_BRANCH" \
&& cd wp-calypso \
&& npx lerna bootstrap --ci \
&& echo "Building jetpack-editor for wp-calypso branch $CALYPSO_BRANCH" \
&& npm run sdk -- gutenberg client/gutenberg/extensions/presets/jetpack \
  --output-dir=$HOME/apps/$USER/public/wp-content/plugins/"$JETPACK_DIRNAME"/_inc/blocks \
&& echo -e "\nadd_filter( 'jetpack_gutenberg', '__return_true', 10 );\n" >> $HOME/apps/$USER/public/wp-content/plugins/companion/companion.php \
&& echo -e "add_filter( 'jetpack_gutenberg_cdn', '__return_false', 10 );\n" >> $HOME/apps/$USER/public/wp-content/plugins/companion/companion.php \
&& cd $HOME/apps/$USER/public \
&& rm -rf $HOME/wp-calypso \
&& rm -rf $HOME/.npm
