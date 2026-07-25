const $ = require('jquery');

function eventBox()
{
	maxHeightEvent();
}

function maxHeightEvent()
{
		colsRight = $('.event .col-right');

		for(i = 0; i < colsRight.length; i += 2)
		{
			col1 = $(colsRight[i]);
			col2 = $(colsRight[i+1]);

			height = Math.max(col1.height(), col2.height());

			col1.css('min-height', height);
			col2.css('min-height', height);
		}
}

$(document).ready( eventBox );
$(window).resize( eventBox );
