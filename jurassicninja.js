
const CREATE_PAGE_SLUG = '/create';
const SPECIALOPS_CREATE_PAGE_SLUG = '/specialops';

const defaultFeatures = {
	'runtime': 'php5.6',
	'ssl': false,
	'jetpack': false,
	'jetpack-beta': false,
	'subdir_multisite': false,
	'subdomain_multisite': false,
	'wordpress_beta_tester': false,
	'debug': false,
	'gutenberg': false,
	'woocommerce': false,
};

const originalProgressText = jQuery( '#progress' ).text();

function doIt( $, shortlived ) {
	$( function() {
		$( '#img1').show();
		$( '#img2').hide();

		createSite( shortlived )
			.then( response => {
				$('#progress').html( `<a href="${ response.data.url }">The new WP is ready to go, visit it!</a>` );
				$('#img1').hide();
				$('#img2').show();
			} )
			.catch( err => {
				$('#progress').text( `Oh No! There was a problem launching the new WP. (${ err.message }).` );
				$('#img2').hide();
				$('#img1').attr( 'src', 'https://i.imgur.com/vdyaxmx.gif');
			} );
	} );
}

function doItWithFeatures( $, features ) {
	$( function() {
		$( '#progress').show();
		$( '#img1').show();
		$( '#img2').hide();
		launchSiteWithFeatures( features )
			.then( response => {
				$('#progress').html( `<a href="${ response.data.url }">The new WP is ready to go, visit it!</a>` );
				$('#img1').hide();
				$('#img2').show();
			} )
			.catch( err => {
				$('#progress').text( `Oh No! There was a problem launching the new WP. (${ err.message }).` );
				$('#img2').hide();
				$('#img1').attr( 'src', 'https://i.imgur.com/vdyaxmx.gif');
			} );
	} );
}

function createSite( shortlived ) {
	const url = restApiSettings.root;
	const nonce = restApiSettings.nonce;
	return fetch( url + 'jurassic.ninja/create', {
		method: 'post',
		credentials: 'same-origin',
		body: shortlived ? JSON.stringify( { shortlived: true } ) : null,
		headers: {
			'X-WP-Nonce': nonce,
			'content-type': 'application/json',
		}
	} )
		.then( checkStatusAndErrors ).then( parseJson )
}

function launchSiteWithFeatures( features ) {
	features = Object.assign( {}, defaultFeatures, features );
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
	if ( response.status !== 200 ) {
		throw new Error( 'The API responded in a weird way' );
	}
	return response;
}

function parseJson( response ) {
	return response.json()
		.catch( () => {
			throw new Error ( 'There was en error with the response from the API' )
		} );
}

function collectFeatures() {
	const reduce = Array.prototype.reduce;
	const els = jQuery( 'input[type=checkbox][data-feature]' );
	const features = reduce.call( els, function( acc, el ) {
		return Object.assign( {}, acc, { [ jQuery( el ).data( 'feature' ) ] : jQuery( el ).is( ':checked' ) } );
	}, {} );
	return features;
}

function isSpecialOpsPage() {
	return window.location.pathname.startsWith( SPECIALOPS_CREATE_PAGE_SLUG );
}

function isCreatePage() {
	return window.location.pathname.startsWith( CREATE_PAGE_SLUG )
}

if ( isCreatePage() ) {
	const shortlived = null !==  param( 'shortlived' );
	setTimeout( () => {
		doIt( jQuery, shortlived );
	}, 1000 );
}

if ( isSpecialOpsPage() ) {
	jQuery( '[data-is-create-button]').click( function () {
		jQuery( '#img1').show();
		jQuery( '#img2').hide();
		jQuery( '#progress' ).text( originalProgressText );
		const $this = jQuery( this );
		const features = collectFeatures();
		// Buttons can declare a feature too
		if ( $this.data( 'feature' )  ) {
			features[ $this.data( 'feature' ) ] = true;
		}
		doItWithFeatures( jQuery, features );
		return false;
	} )
}

function param(name) {
	let params;
	if ( location.search ) {
		let params = location.search.split('?')[1].split('&');
		let ret = params.includes( name ) ? params[ params.indexOf( name ) ] : null;
		return ret;
	}
	return null;
}
