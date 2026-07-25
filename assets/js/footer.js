const $ = require('jquery');

function footer()
{
	fullHeightSidebar();
}

function fullHeightSidebar()
{
		const footer = $('#footer');
		const sidebar = footer.find('.sidebar');

		if ( sidebar.height() != footer.height() )
		{
			sidebar.css('height', footer.height());
		}
}

$(document).ready( footer );

$(window).resize( footer );
