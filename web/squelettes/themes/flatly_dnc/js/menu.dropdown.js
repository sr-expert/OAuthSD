jQuery(function(){
    // detecter les navbar avec deroulant
    // poser les class qui vont bien
    // et lancer le dropdown BootStrap dessus
    jQuery('.navbar .menu-items .menu-items').closest('.navbar').each(function(){
        jQuery(this)
        .find('.menu-items').eq(0)
        .children('.item').children('.menu-items').parent().addClass('dropdown')
        .children('.menu-items').addClass('dropdown-menu').removeClass('nav')
        .siblings('a').addClass('dropdown-toggle').attr('data-toggle', 'dropdown').dropdown() //dgy
        .siblings('.menu-items')
        .find('.menu-items').hide();
        //dgy
        jQuery(this)
        .find('.menu-items .menu-items').addClass('dropdown-menu')
        .removeAttr( 'style')
        .parent().addClass('dropdown-submenu');
    });
});



