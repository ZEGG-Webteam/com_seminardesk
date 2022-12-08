/**
 * JS for SeminarDesk Component
 */

(function ($) {
  
  $( document ).ready(function() {
    
    //-- Events filter
    
    function filterEvents() {
      let filterStartDate = $('#sd-filter-date-from').val().trim();
      let filterSearchTerms = $('#sd-filter-search-term').val().trim().replace(',', ' ').toLowerCase().split(' ').filter(Boolean);
      let filterCategory = $('#sd-filter-category').val();
      let areSearchTermsEmpty = (filterSearchTerms.length === 0);
      
      // Add filter values to url
      var url = new URL(document.location);
      url.searchParams.set('date', filterStartDate);
      if (!areSearchTermsEmpty) {
        url.searchParams.set('term', filterSearchTerms);
      }
      else {
        url.searchParams.delete('term');
      }
      if (filterCategory > 0) {
        url.searchParams.set('cat', filterCategory);
      }
      else {
        url.searchParams.delete('cat');
      }
      window.history.pushState({}, '', url);
      
      // Hide all events not matching ALL of the search terms
      $('.sd-eventlist .sd-event').each(function() {
        let eventStartDate = $(this).data('start-date');
        let eventSearchableText = $(this).data('title') + ' ' + $(this).data('fascilitators') + ' ' + $(this).data('labels');
        let areSearchTermsMatching = areSearchTermsEmpty || filterSearchTerms.every( 
          substring=>eventSearchableText.toLowerCase().includes( substring ) 
        );
        let isCategoryMatching = filterCategory == '0' || $(this).data('categories').includes(parseInt(filterCategory));
        
        // Show events if filters are matching, otherwise hide them
        if (eventStartDate >= filterStartDate && areSearchTermsMatching && isCategoryMatching) {
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
    
    // Init form with values from url params
    var url_params = new URL(document.location).searchParams;
    if(url_params.has('date')) {
      $('#sd-filter-date-from').val(url_params.get('date'));
    }
    else {
      // Init form with current date and min date
      let today = new Date()
      let todaysDate = today.toISOString().split('T')[0];
      $('#sd-filter-date-from').val(todaysDate).attr('min', todaysDate);
    }
    if(url_params.has('term')) {
      $('#sd-filter-search-term').val(url_params.get('term'));
    }
    if(url_params.has('cat')) {
      $('#sd-filter-category').val(url_params.get('cat'));
    }
    
    // Filter on any filter field changed
    $('.sd-filter-form #sd-filter-date-from').on('change', filterEvents);
    $('.sd-filter-form #sd-filter-search-term').on('keyup blur change', filterEvents);
    $('.sd-filter-form #sd-filter-category').on('change', filterEvents);
    // Filter on submit
    $('.sd-filter-form [type="submit"]').on('click', function(e){
      e.preventDefault();
      // Filter
      filterEvents();
    });
    
    // Start filtering
    filterEvents();
  });
  
})(jQuery);