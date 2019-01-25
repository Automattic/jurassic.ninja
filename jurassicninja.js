
const CREATE_PAGE_SLUG = '/create';
const SPECIALOPS_CREATE_PAGE_SLUG = '/specialops';

const originalProgressText = jQuery( '#progress' ).text();
const originalProgressImage = jQuery( '#img1' ).attr( 'src' );

init();

function init() {
	if ( isCreatePage() ) {
		features = collectFeaturesFromQueryString();

		setTimeout( () => {
			launchSite( jQuery, features );
		}, 1000 );
	}

	if ( isSpecialOpsPage() ) {
		jQuery( '[data-is-create-button]' ).click( function () {
			const $this = jQuery( this );
			const features = collectFeaturesFromFormInputs();
			// Buttons can declare a feature too
			if ( $this.data( 'feature' )  ) {
				features[ $this.data( 'feature' ) ] = true;
			}
			launchSite( jQuery, features, true );
			return false;
		} )
	}
}

function launchSite( $, features, resetSpinner = false ) {
	$( function() {
		startSpinner();

		if ( resetSpinner ) {
			$( '#progress' ).text( originalProgressText );
			jQuery( '#img1' ).attr( 'src', originalProgressImage );
			$( '#progress' ).show();
		}
		jurassicNinjaApi().create( features )
			.then( response => {
				var successMessage = $( '#progress' ).data().successMessage;
				$( '#progress' ).html( `<a href="${ response.data.url }">${ successMessage }</a>` );
				stopSpinner();
				favicon_update_colour( 'red' );
			} )
			.catch( err => {
				var errorMessage = $( '#progress' ).data().errorMessage;
				$( '#progress' ).text( `${ errorMessage } (${ err.message }).` );
				stopSpinner( true );
				favicon_update_colour( 'green' );
			} );
	} );
}

function collectFeaturesFromFormInputs() {
	const reduce = Array.prototype.reduce;
	const els = jQuery( 'input[type=checkbox][data-feature]' );
	const features = reduce.call( els, function( acc, el ) {
		return Object.assign( {}, acc, { [ jQuery( el ).data( 'feature' ) ] : jQuery( el ).is( ':checked' ) } );
	}, {} );
	const selects = jQuery( 'select[data-feature]' );
	const features_in_selects = reduce.call( selects, function( acc, el ) {
		return Object.assign( {}, acc, { [ jQuery( el ).data( 'feature' ) ] : jQuery( el ).val() } );
	}, {} );
	return Object.assign( features, features_in_selects );
}

function collectFeaturesFromQueryString() {
	if ( ! location.search ) {
		return {};
	}

	let params = location.search.split( '?' )[1].split( '&' );
	let features = {};

	for ( var p of params ) {
		if ( p.includes( '=' ) ) {
			const splitParam = p.split( '=' )
			features[ splitParam[0] ] = splitParam[1]
		} else {
			if ( p.startsWith( 'no' ) ) {
				features[ p.slice( 2 ) ] = false
			} else {
				features[ p ] = true
			}
		}
	}

	return features;
}

function isSpecialOpsPage() {
	return window.location.pathname.startsWith( SPECIALOPS_CREATE_PAGE_SLUG );
}

function isCreatePage() {
	return window.location.pathname.startsWith( CREATE_PAGE_SLUG )
}

function startSpinner() {
	jQuery( '#img1' ).show();
	jQuery( '#img2' ).hide();
	favicon_update_colour( 'orange' );
}
function stopSpinner( error = false ) {
	if ( error ) {
		jQuery( '#img2' ).hide();
		jQuery( '#img1' ).attr( 'src', jQuery( '#img1' ).data().failureImgSrc );
	} else {
		jQuery( '#img1' ).hide();
		jQuery( '#img2' ).show();
	}
}

function param( name ) {
	let params;
	if ( location.search ) {
		let params = location.search.split( '?' )[1].split( '&' );
		// branch option is only valid when jetpack-beta is used.
		if ( name == 'branch' && params.includes( 'jetpack-beta' ) ) {
			let branch = params.filter( param => param.startsWith( 'branch' ) )
			return branch.length ? branch[0].split( '=' )[1] : 'master'
		}
		if ( params.includes( name ) ) {
			return true;
		}
		if ( params.includes( 'no' + name ) ) {
			return false;
		}
		return null;
	}
	return null;
}

function jurassicNinjaApi() {
	if ( ! ( this instanceof jurassicNinjaApi ) ) {
		return new jurassicNinjaApi();
	}

	function create( features ) {
		const url = restApiSettings.root;
		const nonce = restApiSettings.nonce;
		return fetch( url + 'jurassic.ninja/create', {
			method: 'post',
			credentials: 'same-origin',
			body: Object.keys( features ).length ? JSON.stringify( features ) : null,
			headers: {
				'X-WP-Nonce': nonce,
				'content-type': 'application/json',
			}
		} )
		.then( checkStatusAndErrors ).then( parseJson )
	}

	function checkStatusAndErrors( response ) {
		if ( response.status === 503 ) {
			throw new Error( 'Site launching is turned off right now' );
		}
		if ( response.status === 403 ) {
			throw new Error( 'Launching sites is currently restricted to authenticated users' );
		}
		// 400 status are custom WP_Error when features are requested in a bad way
		// so we still parse the json there.
		if ( response.status !== 200 && response.status !== 400 ) {
			throw new Error( 'The API responded in a weird way' );
		}
		return response;
	}

	function parseJson( response ) {
		let message = 'There was en error with the response from the API';
		return response.json()
		.then( data => {
			// 400 status are custom WP_Error when features are requested in a bad way
			if ( response.status === 400 ) {
				message = data.message;
				throw new Error();
			}
			return data;
		} )
		.catch( () => {
			throw new Error ( message )
		} );
	}

	this.create = create;
}

/**
 * Adds a dot to the favicon so that you know the status of a build
 */
function favicon_update_colour( colour ) {

    function getLocation( href ) {
        var l = document.createElement( 'a' );
        l.href = href;
        return l;
    }
    function get_current_favicon() {
        //get link element
        function getLinks() {
            var icons = [];
            var links = document.getElementsByTagName('head')[0].getElementsByTagName('link');
            for (var i = 0; i < links.length; i++) {
                if ((/(^|\s)icon(\s|$)/i).test(links[i].getAttribute('rel'))) {
                    icons.push(links[i]);
                }
            }
            return icons;
        };
        //if link element
        var elms = getLinks();
        if (elms.length === 0) {
            elms = [document.createElement('link')];
            elms[0].setAttribute('rel', 'icon');
            document.getElementsByTagName('head')[0].appendChild(elms[0]);
        }

        elms.forEach( function(item) {
            item.setAttribute( 'type', 'image/png' );
        } );
        return elms[ elms.length -1 ];
    }

    var favicon = get_current_favicon();

    var canvas = document.createElement( 'canvas' );
    canvas.width = 32;canvas.height = 32;
    var ctx = canvas.getContext( '2d' );
    var img = new Image();
	if ( favicon.href ) {
        var location = getLocation( favicon.href  );
        if ( ['i1.wp.com', 'i2.wp.com','i3.wp.com', 'i4.wp.com'].indexOf( location.host ) >= 0 ) {
           var pathSplit = location.pathname.split( '/' );
           pathSplit.splice( 0, 2); // removes the domain part
           img.src = pathSplit.join( '/' );
        } else {
            img.src = favicon.href;
        }
    }

    img.onload = function() {
        ctx.drawImage( img, 0, 0, 32, 32 );
        ctx.arc(20, 20, 6, 0, 2 * Math.PI, false);
        ctx.fillStyle = colour;
        ctx.fill();
        var link = favicon;
        link.type = 'image/x-icon';
        link.rel = 'shortcut icon';
        link.href = canvas.toDataURL( 'image/x-icon' );
        document.getElementsByTagName( 'head' )[0].appendChild( link );
    }
}
