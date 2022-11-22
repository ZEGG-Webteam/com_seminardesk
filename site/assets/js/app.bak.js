/**
 * Librar for client side event rendering using Vue.js
 * This is experimantal, currently Vue.js is not loaded.
 */

Vue.component('event-date', {
  template: '\
    <li>\
      {{ eventId }}\
    </li>\
  ',
  props: ['eventId']
})

new Vue({
  el: "#seminardesk_events_app",
  data: {
    hello: 'Hello world',
    config: {},
    eventDates: [
      {eventId: '12345', title: 'test 1'}, 
      {eventId: '67890', title: 'test 2'}
    ],
  },
  mounted: function() {
    this.config = JSON.parse(this.$el.getAttribute('data-config'));
    axios.get(this.config.api + '/eventDates')
    .then(function(response) {
      console.log(response.data.dates);
      this.eventDates = response.data.dates;
      console.log(this.eventDates);
      this.hello = 'Response!';
      response.data.dates.forEach(function(item){
        this.eventDates.push(item);
      });
  //          this.eventDates = response.data.dates;
      console.log('... got data: ');
      console.log(this.eventDates);
      return this.eventDates;
    })
    .catch(function(err) {
      console.log(err);
    });
  },
  methods: {
    loadEvents(event, route) {
      console.log('Get data from ' + this.config.api + route + ' ...');
      this.hello = "Hallo du"
      axios.get(this.config.api + route)
        .then(function(response) {
//          console.log(response.data.dates);
          this.eventDates = response.data.dates;
//          console.log(this.eventDates);
          this.hello = 'Response! 2';
          response.data.dates.forEach(function(item){
            this.eventDates.push(item);
          });
//          this.eventDates = response.data.dates;
          console.log('... got data: ');
          console.log(this.eventDates);
        })
        .catch(function(err) {
          console.log(err);
        });
      this.eventDates.push({eventId: '24680', title: 'Neuer Event'})
    },
  },
})
