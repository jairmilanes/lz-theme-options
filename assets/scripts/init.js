(function($){
  $(function(){
    var window_width = $(window).width();


    $('.button-collapse').sideNav();

    /**
     * Get the lattest commit from GitHub
     */
    if ($('.last-commit').length) { // Checks if widget div exists (Index only)
      $.ajax({
        url: "https://api.github.com/repos/layoutzweb/lz-theme-options/commits/master",
        dataType: "json",
        success: function (data) {
          var sha = data.sha,
              date = jQuery.timeago(data.commit.author.date);
          if (window_width < 1120) {
            sha = sha.substring(0,7);
          }
          $('.last-commit').find('.date').html(date);
          $('.last-commit').find('.sha').html(sha).attr('href', data.html_url);
        }
      });
    }

    $('.scrollspy').scrollSpy();

    $('.pushpin').each(function(idx, elem){
      $(elem).pushpin({ top: $($(elem).data('parent')).offset().top });
    });

  }); // end of document ready
})(jQuery); // end of jQuery name space
