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
&& npm ci \
&& echo "Building jetpack-editor for wp-calypso branch $CALYPSO_BRANCH" \
&& npx lerna bootstrap --concurrency=2 --scope '@automattic/jetpack-blocks' \
&& npx lerna run prepublishOnly --stream --scope '@automattic/jetpack-blocks' \
&& mv -f packages/jetpack-blocks/dist $HOME/apps/$USER/public/wp-content/plugins/"$JETPACK_DIRNAME"/_inc/blocks \
&& echo "Forcing jetpack_gutenberg and jetpack_gutenberg_cdn filters to true" \
&& echo -e "\nadd_filter( 'jetpack_gutenberg', '__return_true', 10 );\n" >> $HOME/apps/$USER/public/wp-content/plugins/companion/companion.php \
&& echo -e "add_filter( 'jetpack_gutenberg_cdn', '__return_false', 10 );\n" >> $HOME/apps/$USER/public/wp-content/plugins/companion/companion.php \
&& cd $HOME/apps/$USER/public \
&& echo "Removing temporary ~/wp-calypso directory" \
&& rm -rf $HOME/wp-calypso \
&& echo "Removing ~/.npm cache directory" \
&& rm -rf $HOME/.npm
