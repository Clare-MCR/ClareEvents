(function () {
  'use strict';

  angular
    .module('app.dashboard')
    .run(appRun);

  appRun.$inject = ['routerHelper'];
  /* @ngInject */
  function appRun(routerHelper) {
    routerHelper.configureStates(getStates());
  }

  function getStates() {
    return [
      {
        state: 'dashboard',
        config: {
          url: '/',
          templateUrl: 'app/dashboard/dashboard.html',
          controller: 'DashboardController',
          controllerAs: 'vm',
          title: 'Home',
          resolve: {
          /* @ngInject */
            eventsPrepService: eventsPrepService
            // bookingsPrepService: bookingsPrepService*/
          },
          settings: {
            nav: 1,
            content: '<i class="fa fa-dashboard"></i> Dashboard'
          }
        }
      }
    ];
  }

  /* @ngInject */
  function eventsPrepService(EventsServices) {
    return EventsServices.query().$promise;
   }

   /* @ngInject */
  /*function bookingsPrepService(BookingServices) {
   return BookingServices.query().$promise;
   }*/

})();
