
function doitforme() {
        $( function() {
                $( '#img1').show();
                $( '#img2').hide();
                setTimeout( () => {
                        createSite()
                                .then( data => {
                                        $('#progress').html( `<a href="${ data.url }">Your site is ready to go, visit it!</a>` );
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
	return fetch( '/api/create', { method: 'post' } )
		.then( response => response.json() )
}
