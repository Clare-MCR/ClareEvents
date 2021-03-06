(function () {
  'use strict';

  angular
    .module('app.admin')
    .run(appRun);

  appRun.$inject = ['routerHelper'];
  /* @ngInject */
  function appRun(routerHelper) {
    routerHelper.configureStates(getStates());
  }

  function getStates() {
    return [
      {
        state: 'adminBookings',
        config: {
          url: '/admin/bookings',
          templateUrl: 'app/admin/bookings/adminBookings.html',
          controller: 'AdminBookingsController',
          controllerAs: 'vm',
          title: 'Admin Bookings',
          resolve: {
            /* @ngInject */
            userPrepService: userPrepService,
            puntsPrepService: puntsPrepService,
            bookingsPrepService: bookingsPrepService
          },
          settings: {
            nav: 2,
            content: '<i class="fa fa-lock"></i> Admin Bookings',
            admin: true
          }
        }
      }
    ];
  }

  /* @ngInject */
  function userPrepService(UserServices) {
    return UserServices.get().$promise;
  }

  /* @ngInject */
  function puntsPrepService(PuntsServices) {
    return PuntsServices.query().$promise;
  }

  /* @ngInject */
  function bookingsPrepService(BookingServices) {
    return BookingServices.query().$promise;
  }
})();
