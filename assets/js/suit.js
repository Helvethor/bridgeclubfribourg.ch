const $ = require('jquery');

function shouldApplySuitPretty()
{
	if (typeof window.matchMedia === 'function' && window.matchMedia('print').matches) {
		return false;
	}

	const params = new URLSearchParams(window.location.search || '');
	return params.get('pdf') !== '1' && params.get('pdf') !== 'true';
}

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

	if (!shouldApplySuitPretty()) {
		return;
	}

	suitPretty( $('.suit-pretty') );

});
