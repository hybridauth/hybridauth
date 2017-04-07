$(function () { 
    prettyPrint()

    // am lazy p
    $('#content img').each(function() {
        $(this).wrap("<a target='_blank' href='" + $(this).attr("src") + "'</a>")
    })

    $('#content').prepend( '<div class="alert alert-success" style="border: 0 none; border-left: 3px solid #179b90; background-color: #21b2a6;"><p style=" color: #fff; line-height: 24px; text-align:center; margin-bottom: 0px;"><b style="border: 0px none; margin-top:0;">Note:</b> Hybridauth 3.0 is currently in beta stage and it might NOT be suitable for production use.<br /><span style="margin-left:42px;">Hybridauth 2.9 documentation can be found at <a href="https://hybridauth.github.io/hybridauth/" target="_blank">https://hybridauth.github.io/hybridauth/</a></span></div>' ) 

    $('table').addClass('table');
    $('table').addClass('table-striped');
    $('table').addClass('table-bordered');
	$('pre').addClass('prettyprint');
	prettyPrint()
    $('header form').remove();

    // window.addEventListener("scroll", function() {
        // if ( window.scrollY > 50 ) {
            // $('.navbar, .githubico').css( 'opacity', 0.8 )
        // }
        // else {
            // $('.navbar, .githubico').css( 'opacity', 1 )
        // }
    // },false)
})
