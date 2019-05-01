/* #PRODUIRE{fond=socialtags.js}
   md5:dfc3c562043322a75e54c7403a518d69 */
// socialtags.js

// 'ajouter un bookmark' ne marche que sous IE
// les tuyaux trouves pour FF
//	window.sidebar.addPanel(t,u,'');
//	cf https://bugzilla.mozilla.org/show_bug.cgi?id=214530
// ou Opera sont creves
;var socialtags_addfavorite = function(u,t){
	if(document.all)window.external.AddFavorite(u,t);
};

(function($) {
	var socialtags_init = function() {
		var selector = $('header:nth-child(1) > p:nth-child(2)');
		if (!selector.length) return;
		var socialtags = [
{ a: 'facebook', n: 'Facebook', i: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAABnRSTlMAAAAAAABupgeRAAAAV0lEQVR42mNgIAMkVq6xjpxBEAGVQTUQoxqCCGhIqFj9+t2X/2BAlAa4amI1ICultob/GICABqDr4R6AsAfcDwQ0uCbNy23ZgokgGuBcoDJyNZCc+EgCALf2LCgEnyVyAAAAAElFTkSuQmCC', u: 'http://www.facebook.com/sharer/sharer.php?u=%u&t=%t', u_site: 'https://oa.dnc.global'},
{ a: 'linkedin', n: 'LinkedIn', i: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAAAZiS0dEAP8A/wD/oL2nkwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB9kHEhctOk6akusAAAIiSURBVDjLpZO9axRBGMZ/s7t3bhI3dyTmJGlO/MphE0nATgsbxUZrkQi2FhaCra1/g2IhBCF/gUVAQVujJAqJChpBYw7jZTZ7m83uzOxY7JrkYrDxhYd33pd5nnk/GHF7Zm7y6cLPR8bxJgX7zAIKMIAuoQrvGM31KyNTIrg3a69enODk0drfZF2SzR4RA7m2fPiywbO59zgCOD4yCIDvOtSr7j/JGHBywenROmiNB+A4RfF3zhzBdx2eLHdYkdmB5D+xa+2ugMotvivwXQcAD4HK7MEVaMCYEgrPAmFmCC08eNNmuOKxuJb0Dm4vWeuCrDVoVVQgU0PTr3LtWA0sWAXNoEqzVuFrR7G0us35EwMMVAQvl0JevftVCmg8cghjQ+5ZWkM+AG+/J4wFHq2Gz3jD51Ir2FlOa6yPrSjh+es2VmkcNMjY0E3ynUvbaY7WxTnezrn18DMX7i+ytpEBMD7Wj5QJGFUIhF1D3CNgMcYCsLyaMP9JEoYJ39YTAEYGK4QyLmegIYwM3f5dgTTN0boQ0CYnlFugFFqZIqcNYScGwOE/zUOD3DR0D5ueFrTOd16TMoE0282pHBkV8xDBzVkbNU5Rc1zONvpAwcqPmHolp161yDBhYWkdsoyJZj91XyCjjIWPHQLRRgQ3Zm0UDcHoMKRAlpVIC5/ui/dYINp405eHph6/ODSvlMB01hlMV8v9bWK7m70N7/vv03fPTf0GFBI+IEBE4vMAAAAASUVORK5CYII=', u: 'http://www.linkedin.com/shareArticle?mini=true&url=%u&title=%t&source=%u_site&summary=%d', u_site: 'https://oa.dnc.global'},
{ a: 'twitter', n: 'Twitter', i: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAABwUlEQVR42lWSO09VURCFv7X3PgcuiAkBE0wMBB+Fpb+AxsLfY2Krdrb+GH+CtSRa2Fn5QB5B5HEv9+w9y+IAwclUkzVrvsyMprMqoazLaksJ56TM/xEAxjZFouvytMXX0MHglaxnC/RJtvF1QyJAIeQCwj5ufJq2CXwb2Mh5s7gIxOh8K1TG4qV9UF1gBhUUMYxyI6lkkomEgmIManAWdKKa3VnsFcW1tfBWp82MwLiAanWY0+ZOEv543uIaYOwq8Ppet5oAFWykCn9Nj21fYUuywZJ+V/8IVgFcgFI0DD5uXgASBB5XAUCzHy7qaS+qgTIMre9Sw38iFqWLwa/W+53FNPUVUMX3S/K8NdNalBF1Zo6CJfm8cSexkljNaZwRqA7tZq/JYNPDUfNe42f489ydcAQ2NuG+z4bAhjKSPu7VSb+aEW8O5vu12yoaIKDA86W8bQgwOjmZpaTlSXl5WN/vX5J18zxXd7ZWMl8eLa3VGkFq4TAhvVsrL+52VGNINykyp9W784igtUijz8XFUMSHB/3bjYX1Tskkk83I/mSSdyajUjo8miahrCQtL3d1aOek7+F26/e2sxYT07N5BP8A8lwEjSU+7RkAAAAASUVORK5CYII=', u: 'http://twitter.com/intent/tweet?text=%t&url=%u', u_site: 'https://oa.dnc.global'}
];
		var title = $('title').text() ||'';
		var description = ($('meta[name=description]').attr('content') || '').substr(0,250);
		var cano = $('link[rel=canonical]')[0];
		var url = cano ? cano.href : document.location.href;
		var ul = $('<ul><\/ul>');
		var esc = function(x){return encodeURIComponent(x).replace(/\s/g,' ');};
		var ref = document.referrer.match(/^.*\/\/([^\/]+)\//);

		if (ref && ref[1].match(/\.facebook\./))
			$.cookie('social_facebook', 1, { path: '/', expires: 30 }); // 30 jours

		$.each(socialtags, function(){ if (this.u) {
			if (this.a == 'bookmark' && !document.all) return;

			

			$('<a rel="nofollow"><img class="socialtags-hovers" src="'+ this.i +'" alt="'+this.a+'"\/><\/a>')
			.attr('href',
				this.u
				.replace(/%u/g, esc(url))
				.replace(/%t/g, esc(title))
				.replace(/%d/g, esc(description))
				.replace(/%u_site/g, esc(this.u_site))
			)
			.attr('title', this.n).wrap('<li class="'+this.a+'"><\/li>')
			.parent().appendTo(ul);
		}});
		selector.after(ul.wrap('<div class="socialtags"><\/div>').parent());
		
		};
	$(function(){
		$(socialtags_init);
	});
})(jQuery);
