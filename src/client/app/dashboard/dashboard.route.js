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
              eventsPrepService: eventsPrepService,
              usersPrepService: usersPrepService
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
      return EventsServices.get().$promise;
   }

  /* @ngInject */
    function usersPrepService(UserServices) {
        return UserServices.get().$promise;
    }
   /* @ngInject */
  /*function bookingsPrepService(BookingServices) {
   return BookingServices.query().$promise;
   }*/

})();
