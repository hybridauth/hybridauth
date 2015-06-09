$(function () { 
    prettyPrint()

    // am lazy p
    $('#content img').each(function() {
        $(this).wrap("<a target='_blank' href='" + $(this).attr("src") + "'</a>")
    })

    $('#content').prepend( '<div class="alert alert-danger"><h4 style="border: 0px none; margin-top:0;">Important:</h4><p style="line-height: 24px;">This current website is part of an ongoing effort toward making the next Hybridauth 3, and it\'s and subject to change at any time. Please avoid using this documentation as a reference until the new Hybridauth library is officially released and publicly announced.</p></div>' ) 

    window.addEventListener("scroll", function() {
        if ( window.scrollY > 50 ) {
            $('.navbar, .githubico').css( 'opacity', 0.8 )
        }
        else {
            $('.navbar, .githubico').css( 'opacity', 1 )
        }
    },false)
})
