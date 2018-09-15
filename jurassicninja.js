
const CREATE_PAGE_SLUG = '/create';
const SPECIALOPS_CREATE_PAGE_SLUG = '/specialops';

const originalProgressText = jQuery( '#progress' ).text();

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
			$( '#progress' ).show();
		}
		jurassicNinjaApi().create( features )
			.then( response => {
				var successMessage = $( '#progress' ).data().successMessage;
				$( '#progress' ).html( `<a href="${ response.data.url }">${ successMessage }</a>` );
				stopSpinner();
			} )
			.catch( err => {
				var errorMessage = $( '#progress' ).data().errorMessage;
				$( '#progress' ).text( `${ errorMessage } (${ err.message }).` );
				stopSpinner( true );
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
}
function stopSpinner( error = false ) {
	if ( error ) {
		jQuery( '#img2' ).hide();
		jQuery( '#img1' ).attr( 'src', 'https://i.imgur.com/vdyaxmx.gif' );
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
