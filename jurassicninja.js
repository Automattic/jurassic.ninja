
const CREATE_PAGE_SLUG = '/create';
const SPECIALOPS_CREATE_PAGE_SLUG = '/specialops';

const originalProgressText = jQuery( '#progress' ).text();

init();

function init() {
	if ( isCreatePage() ) {
		const shortlived = param( 'shortlived' );
		const jetpack = param( 'jetpack' );
		const woocommerce = param( 'woocommerce' );
		const jetpack_beta = param( 'jetpack-beta' );
		const wp_debug_log = param( 'wp-debug-log' )
		const branch = param( 'branch' )
		const features = {};
		if ( shortlived !== null ) {
			features.shortlived = shortlived;
		}
		if ( jetpack !== null ) {
			features.jetpack = jetpack;
		}
		if ( woocommerce !== null ) {
			features.woocommerce = woocommerce;
		}
		if ( wp_debug_log !== null ) {
			features[ 'wp-debug-log' ] = wp_debug_log;
		}
		if ( jetpack_beta !== null ) {
			features[ 'jetpack-beta' ] = jetpack_beta;
			features.branch = ( branch !== null ? branch : 'stable' );
		}

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
			launchSiteWithFeatures( jQuery, features );
			return false;
		} )
	}
}

function launchSite( $, features ) {
	$( function() {
		startSpinner();

		jurassicNinjaApi().create( features )
			.then( response => {
				$( '#progress' ).html( `<a href="${ response.data.url }">The new WP is ready to go, visit it!</a>` );
				stopSpinner();
			} )
			.catch( err => {
				$( '#progress' ).text( `Oh No! There was a problem launching the new WP. (${ err.message }).` );
				stopSpinner( true );
			} );
	} );
}

function launchSiteWithFeatures( $, features ) {
	$( function() {
		$( '#progress' ).text( originalProgressText );
		$( '#progress' ).show();
		startSpinner();
		jurassicNinjaApi().specialops( features )
			.then( response => {
				$( '#progress' ).html( `<a href="${ response.data.url }">The new WP is ready to go, visit it!</a>` );
				stopSpinner();
			} )
			.catch( err => {
				$( '#progress' ).text( `Oh No! There was a problem launching the new WP. (${ err.message }).` );
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

	function specialops( features ) {
		const url = restApiSettings.root;
		const nonce = restApiSettings.nonce;
		return fetch( url + 'jurassic.ninja/specialops/create', {
			method: 'post',
			credentials: 'same-origin',
			body: JSON.stringify( features ),
			headers: {
				'X-WP-Nonce': nonce,
				'content-type': 'application/json',
			}
		} )
		.then( checkStatusAndErrors ).then( parseJson )

		;
	}

	function checkStatusAndErrors( response ) {
		if ( response.status === 503 ) {
			throw new Error( 'Site launching is turned off right now' );
		}
		if ( response.status === 403 ) {
			throw new Error( 'Launching sites is currently restricted to authenticated users' );
		}
		if ( response.status !== 200 && response.status !== 400 ) {
			throw new Error( 'The API responded in a weird way' );
		}
		return response;
	}

	function parseJson( response ) {
		let message = 'There was en error with the response from the API';
		return response.json()
		.then( data => {
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
	this.specialops = specialops;
}
