/**
 * JS for SeminarDesk Component
 */

(function ($) {
  
  $( document ).ready(function() {
    
    //-- Events filter
    function filterEvents() {
      // Get field values
      let filterStartDate = $('#sd-filter-date-from').val().trim();
      let filterSearchTerms = $('#sd-filter-search-term').val().trim().replace(',', ' ').toLowerCase().split(' ').filter(Boolean);
      let areSearchTermsEmpty = (filterSearchTerms.length === 0);
      let filterOrganisers = $('#sd-filter-organisers').val();
      let filterCategory = $('#sd-filter-category').val();
      
      // Field dependencies: Currently categories are only available for zegg events
      filterCategory   = (filterOrganisers == 'external')?0:filterCategory;
      $('#sd-filter-category').val(filterCategory).prop( "disabled", (filterOrganisers == 'external') );
      filterOrganisers = (filterCategory > 0)?'zegg':filterOrganisers;
      $('#sd-filter-organisers').val(filterOrganisers).prop( "disabled", (filterCategory > 0) );
      
      // Add filter values to url
      var url = new URL(document.location);
      url.searchParams.set('date', filterStartDate);
      if (!areSearchTermsEmpty) {
        url.searchParams.set('term', filterSearchTerms);
      }
      else {
        url.searchParams.delete('term');
      }
      if (filterOrganisers != 'all') {
        url.searchParams.set('org', filterOrganisers);
      }
      else {
        url.searchParams.delete('org');
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
        let isDateMatching = $(this).data('start-date') >= filterStartDate;
        let eventSearchableText = $(this).data('title') + ' ' + $(this).data('fascilitators') + ' ' + $(this).data('labels');
        let areSearchTermsMatching = areSearchTermsEmpty || filterSearchTerms.every( 
          substring=>eventSearchableText.toLowerCase().includes( substring ) 
        );
        let isCategoryMatching = filterCategory == '0' || $(this).data('categories').includes(parseInt(filterCategory));
        let isOrganiserMatching = filterOrganisers == 'all' || $(this).children('a').hasClass(filterOrganisers + '-event');
        
        // Show events if filters are matching, otherwise hide them
        if (isDateMatching && areSearchTermsMatching && isCategoryMatching && isOrganiserMatching) {
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
    
    // Init filter form with values from url params
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
    if(url_params.has('org')) {
      $('#sd-filter-organisers').val(url_params.get('org'));
    }
    if(url_params.has('cat')) {
      $('#sd-filter-category').val(url_params.get('cat'));
    }
    
    // Filter on any filter field changed
    $('.sd-filter-form #sd-filter-date-from').on('change', filterEvents);
    $('.sd-filter-form #sd-filter-search-term').on('keyup blur change', filterEvents);
    $('.sd-filter-form #sd-filter-organisers').on('change', filterEvents);
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