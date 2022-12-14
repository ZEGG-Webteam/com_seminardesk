/**
 * JS for SeminarDesk Component
 */

(function ($) {
  
  $( document ).ready(function() {
    /***************************
     *  Event list and filter  *
     ***************************/
    if ($('.sd-filter-form').length > 0) {
      //-- Get current date
      let today = new Date()
      let todaysDate = today.toISOString().split('T')[0];

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

        // Update filter values in url (cond = if param should be set or deleted)
        let url = new URL(document.location);
        function updateUrlParam(name, value, cond) {
          if (cond) url.searchParams.set(name, value)
          else      url.searchParams.delete(name);
        }
        updateUrlParam('date', filterStartDate,   filterStartDate && todaysDate != filterStartDate);
        updateUrlParam('term', filterSearchTerms, !areSearchTermsEmpty);
        updateUrlParam('org',  filterOrganisers,  filterOrganisers != 'all');
        updateUrlParam('cat',  filterCategory,    filterCategory > 0);
        window.history.pushState({}, '', url);

        // Hide all events not matching ALL of the search terms
        $('.sd-eventlist .sd-event').each(function() {
          let isDateMatching = $(this).data('end-date') >= filterStartDate;
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
          $(this).removeClass('loading');
        });

        // Remove empty month headings
        $('.sd-eventlist .sd-month').each(function() {
          if ($(this).children('.sd-event:not(.hidden)').length > 0) {
            $(this).removeClass('hidden');
          } else {
            $(this).addClass('hidden');
          }
          $(this).removeClass('loading');
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
    }
    
    /****************************************
     *  Event detail - load async contents  *
     ****************************************/
    if ($('.event-details .async').length > 0) {
      function translate(field_values, lang_key) {
        field_values.forEach(function(field_value){
          if (field_value.language == lang_key) {
            return field_value.value;
          }
        });
        // Fallback
        return field_values[0].value;
      }
      
      let api_uri = $('.event-details').data('api-uri');
      let lang_key = $('.event-details').data('lang-key');
      $.ajax({
        url: api_uri,
      })
      .done(function( data ) {
        $('.event-details .async').each(function(){
          let field = $(this).attr('id');
          $(this).html(translate(data[field], lang_key));
          $(this).removeClass('loading');
        });
      });
    }
    
    //-- Read more links
    $('.readmore').on('click', function(){
      $(this).prev().toggleClass('show-all');
    });

  });
  
})(jQuery);