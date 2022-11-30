/**
 * JS for SeminarDesk Component
 */

(function ($) {
  
  $( document ).ready(function() {
    
    //-- Events filter
    
    function filterEvents() {
      let filterStartDate = $('#sd-filter-date-from').val().trim();
      let filterSearchTerms = $('#sd-filter-search-term').val().trim().replace(',', '').toLowerCase().split(' ');
      let areSearchTermsEmpty = (filterSearchTerms.length === 0)
      
      // Hide all events not matching ALL of the search terms
      $('.sd-eventlist .sd-event').each(function() {
        let eventStartDate = $(this).data('start-date');
        let eventSearchableText = $(this).data('title') + ' ' + $(this).data('fascilitators') + ' ' + $(this).data('labels');
        let areSearchTermsMatching = areSearchTermsEmpty || filterSearchTerms.every( 
          substring=>eventSearchableText.toLowerCase().includes( substring ) 
        );
        // Show events if filters are matching, otherwise hide them
        if (eventStartDate >= filterStartDate && areSearchTermsMatching) {
          $(this).removeClass('hidden');
        } else {
          $(this).addClass('hidden');
        }
      });
      
      // Remove empty month headings
      $('.sd-eventlist .sd-month').each(function() {
        if ($(this).children('.sd-event:not(.hidden)').length > 0) {
          $(this).removeClass('hidden');
        } else {
          $(this).addClass('hidden');
        }
      });
      
      // Show "no events found" message if no results are left
      $('.sd-eventlist').each(function() {
        if ($(this).find('.sd-event:not(.hidden)').length === 0) {
          $(this).find('.no-events-found').removeClass('hidden');
        } else {
          $(this).find('.no-events-found').addClass('hidden');
        }
      });
    }
    
    // Init form with current date and min date
    let today = new Date()
    let todaysDate = today.toISOString().split('T')[0];
    $('#sd-filter-date-from').val(todaysDate).attr('min', todaysDate);
    
    // Filter on date changed
    $('.sd-filter-form #sd-filter-date-from').on('change', function() {
      filterEvents();
    });
    
    // Filter on search term changed
    $('.sd-filter-form #sd-filter-search-term').on('keyup keypress blur change', function() {
      filterEvents();
    });
    
    // Filter on submit
    $('.sd-filter-form [type="submit"]').on('click', function(e){
      e.preventDefault();
      // Filter
      filterEvents();
    });
    
  });
  
})(jQuery);