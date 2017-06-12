(function () {
  'use strict';

  angular
    .module('app.data')
    .factory('UserServices', UserServices);

  UserServices.$inject = ['cachedResource'];

  /* @ngInject */
  function UserServices(cachedResource) {
    return cachedResource('rest/user/:Id/:Type', {Id: '@crsid'}, {});
  }

})();
