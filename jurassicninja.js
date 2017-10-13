
const CREATE_PAGE_SLUG = '/create';

function doitforme( $ ) {
	$( function() {
		$( '#img1').show();
		$( '#img2').hide();
		setTimeout( () => {
			createSite()
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
		}, 1000 );
	} );
}

function createSite() {
	return fetch( '/wp-json/jurassic.ninja/create', { method: 'post' } )
		.then( checkStatusAndErrors ).then( parseJson )
}

function checkStatusAndErrors( response ) {
	if ( response.status === 503 ) {
		throw new Error( 'Site launching is turned off right now' );
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

if ( window.location.pathname.startsWith( CREATE_PAGE_SLUG ) ) {
	doitforme( jQuery);
}
