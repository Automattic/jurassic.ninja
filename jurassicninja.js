
const CREATE_PAGE_SLUG = '/create';

function doitforme( $ ) {
	$( function() {
		$( '#img1').show();
		$( '#img2').hide();
		setTimeout( () => {
			createSite()
				.then( response => {
					if ( ! response.data ) {
						$('#progress').html( `Oh No! There was a problem launching a new WP: ${ data.message } (${ data.code }).` );
						$('#img1').attr( 'src', 'https://i.imgur.com/vdyaxmx.gif');
						return;
					}
					$('#progress').html( `<a href="${ response.data.url }">The new WP is ready to go, visit it!</a>` );
					$('#img1').hide();
					$('#img2').show();
				} )
				.catch( err => {
					$('#progress').text( err.message );
					$('#img2').hide();
				} );
		}, 1000 );
	} );
}

function createSite() {
	return fetch( '/wp-json/jurassic.ninja/create', { method: 'post' } )
		.then( parseJson )
}

function parseJson( response ) {
	return response.json()
		.catch( () => {
			return {
				status: 'error',
				message: 'There was en error creating the site'
			};
		} );
}

if ( window.location.pathname.startsWith( CREATE_PAGE_SLUG ) ) {
	doitforme( jQuery);
}
