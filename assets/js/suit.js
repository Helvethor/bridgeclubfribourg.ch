const $ = require('jquery');

function suitPretty( objs )
{
	objs.each( function() {

		if (this.innerHTML.indexOf('Pass') >= 0)
			return;

		this.innerHTML = this.innerHTML.replace( 'P', "<span class='black'>♠</span>" );
		this.innerHTML = this.innerHTML.replace( 'C', "<span class='red'>♥</span>" );
		this.innerHTML = this.innerHTML.replace( 'T', "<span class='green'>♣</span>" );
		this.innerHTML = this.innerHTML.replace( 'K', "<span class='yellow'>♦</span>" );
	});
}

$(document).ready( function () {

	suitPretty( $('.suit-pretty') );

});
