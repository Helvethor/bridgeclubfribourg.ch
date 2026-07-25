const $ = require('jquery');

function suitPretty( objs )
{
	objs.each( function() {

		if (this.innerHTML.indexOf('Pass') >= 0)
			return;

		this.innerHTML = this.innerHTML.replace( 'P', '♠' );
		this.innerHTML = this.innerHTML.replace( 'C', "<span style='color:#B40000'>♥</span>" );
		this.innerHTML = this.innerHTML.replace( 'T', '♣' );
		this.innerHTML = this.innerHTML.replace( 'K', "<span style='color:#B40000'>♦</span>" );
	});
}

$(document).ready( function () {

	suitPretty( $('.suit-pretty') );

});
