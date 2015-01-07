$(function () {
	prettyPrint()

	gr = [ 'white_ffffff', 'red_aa0000', 'green_007200', 'darkblue_121621', 'orange_ff7600', 'gray_6d6d6d' ]

	$('nav').append( "<a href='https://github.com/hybridauth/hybridauth'><img id='ribbon' alt='Fork me on GitHub' style='position: fixed; top: 0px; right: 0pt; z-index: 2; border: 0pt none; margin: 0; padding: 0;' src='http://s3.amazonaws.com/github/ribbons/forkme_right_" + gr[ 1 ]+ ".png' /></a>" )

  //$('.nav').after( '<ul class="nav secondary-nav"><form action="http://hybridauth.sourceforge.net/search.html" method="get"><input type="text" name="q" placeholder="Search" /></form></ul>' )

	// $('#page').prepend( '<div class="alert-message info" style="margin-bottom:10px"><p><strong>A discussion about the coming HybridAuth major release 3.x is now taking place at Github. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a class="btn" href="https://github.com/hybridauth/hybridauth/issues/34#issuecomment-14306883" target="_blank">Join the Discussion!</a> <a class="btn" href="https://github.com/hybridauth/hybridauth/wiki/HybridAuth-3.x-Roadmap" target="_blank">3.x Roadamp</a></p></div>' )
})
