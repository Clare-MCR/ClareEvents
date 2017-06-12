(function () {
  'use strict';

  angular
    .module('app.data')
    .factory('EventsServices', EventsServices);

  EventsServices.$inject = ['cachedResource'];

  /* @ngInject */
  function EventsServices(cachedResource) {
      return cachedResource('rest/events/:Eid/:Sid', {Eid: '@eid'}, {});
  }

})();

